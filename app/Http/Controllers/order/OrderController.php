<?php

namespace App\Http\Controllers\order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Food;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:open,closed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Order::with('table:id,table_number');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $query->latest();

        $orders = $query->paginate(15);

        $transformedData = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'table_number' => $order->table->table_number,
                'total_price' => $order->total_amount,
                'status' => $order->status,
                'opened_at' => $order->opened_at,
                'closed_at' => $order->closed_at,
            ];
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Orders retrieved successfully',
            'data' => $transformedData,
            'meta' => [
                'current_page' => $orders->currentPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'last_page' => $orders->lastPage(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem(),
            ],
            'links' => [
                'first' => $orders->url(1),
                'last' => $orders->url($orders->lastPage()),
                'prev' => $orders->previousPageUrl(),
                'next' => $orders->nextPageUrl(),
            ]
        ], 200);
    }

    public function getOrderByTable($tableId)
    {
        $table = Table::find($tableId);
        
        if (!$table) {
            return response()->json([
                'status' => 'error',
                'message' => 'Table not found'
            ], 404);
        }

        $order = Order::with([
            'table:id,table_number,capacity,status',
            'user:id,name,email,role',
            'orderItems.food:id,name,category,price,image_url'
        ])->where('table_id', $tableId)
          ->where('status', 'open')
          ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'No open order found for this table'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Order retrieved successfully',
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'total_amount' => $order->total_amount,
                'opened_at' => $order->opened_at,
                'table' => [
                    'id' => $order->table->id,
                    'table_number' => $order->table->table_number,
                ],
                'pelayan' => [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                ],
                'summary' => [
                    'total_items' => $order->orderItems->sum('quantity'),
                    'draft_items' => $order->orderItems->where('status', 'draft')->sum('quantity'),
                    'sent_items' => $order->orderItems->where('status', 'sent')->sum('quantity'),
                    'total_amount' => $order->total_amount,
                ],
            ]
        ], 200);
    }

    public function open(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'table_id' => 'required|integer|exists:tables,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $table = Table::find($request->table_id);

        if (!$table) {
            return response()->json([
                'status' => 'error',
                'message' => 'Table not found'
            ], 404);
        }

        // ✅ FIX: Check for existing open order FIRST before checking availability
        if ($table->hasOpenOrder()) {
            // If an open order exists, return it so frontend can open the existing page
            $existingOrder = $table->currentOrder()->with(['orderItems.food:id,name,category,price,image_url','user:id,name'])->first();

            return response()->json([
                'status' => 'success',
                'message' => 'Order already open',
                'data' => $existingOrder
            ], 200);
        }

        // Only check availability if no open order exists
        if (!$table->isAvailable()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Table is not available. Current status: ' . $table->status
            ], 400);
        }

        try {
            DB::beginTransaction();

            $order = Order::create([
                'table_id' => $table->id,
                'user_id' => $request->user()->id,
                'status' => 'open',
                'total_amount' => 0,
            ]);

            $table->update(['status' => 'occupied']);

            DB::commit();
            $order->load('table');

            return response()->json([
                'status' => 'success',
                'message' => 'Order opened successfully',
                'data' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to open order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $order = Order::with([
            'table:id,table_number,capacity,status',
            'user:id,name,email,role',
            'orderItems.food:id,name,category,price,image_url'
        ])->find($id);

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Order retrieved successfully',
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'total_amount' => $order->total_amount,
                'opened_at' => $order->opened_at,
                'closed_at' => $order->closed_at,
                'table' => [
                    'id' => $order->table->id,
                    'table_number' => $order->table->table_number,
                    'capacity' => $order->table->capacity,
                    'status' => $order->table->status,
                ],
                'pelayan' => [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                ],
                'items' => $order->orderItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'food_id' => $item->food_id,
                        'food_name' => $item->food ? $item->food->name : 'Unknown Food',
                        'category' => $item->food ? $item->food->category : '',
                        'image_url' => $item->food ? $item->food->image_url : null,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'subtotal' => $item->subtotal,
                        'notes' => $item->notes,
                        'status' => $item->status ?? 'draft',
                        'sent_at' => optional($item->sent_at)->toDateTimeString(),
                    ];
                }),
                'drafts' => $order->orderItems->where('status', 'draft')->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'food_id' => $item->food_id,
                        'food_name' => $item->food ? $item->food->name : 'Unknown Food',
                        'qty' => $item->quantity,
                        'notes' => $item->notes,
                    ];
                })->values(),
                'summary' => [
                    'total_items' => $order->orderItems->sum('quantity'),
                    'draft_items' => $order->orderItems->where('status', 'draft')->sum('quantity'),
                    'draft_total' => $order->orderItems->where('status', 'draft')->sum('subtotal'),
                    'total_amount' => $order->total_amount,
                ],
            ]
        ], 200);
    }

    public function addItem(Request $request, $orderId)
    {
        if ($request->has('items')) {
            $validator = Validator::make($request->all(), [
                'items' => 'required|array|min:1',
                'items.*.food_id' => 'required|integer|exists:foods,id',
                'items.*.qty' => 'required|integer|min:1',
                'items.*.notes' => 'nullable|string',
                'send_now' => 'nullable|boolean',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'food_id' => 'required|integer|exists:foods,id',
                'qty' => 'required|integer|min:1',
                'notes' => 'nullable|string',
                'send_now' => 'nullable|boolean',
            ]);
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::find($orderId);

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        if ($order->status !== 'open') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot add items to a closed order'
            ], 400);
        }

        $created = [];

        try {
            DB::beginTransaction();

            $processItem = function ($foodId, $qty, $notes = null, $sendNow = false) use ($order, &$created) {
                $food = Food::find($foodId);
                if (!$food) {
                    throw new \Exception('Food not found');
                }
                if (!$food->is_available) {
                    throw new \Exception('Food not available');
                }

                $status = $sendNow ? 'sent' : 'draft';
                $sentAt = $sendNow ? now() : null;

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'food_id' => $food->id,
                    'quantity' => $qty,
                    'price' => $food->price,
                    'notes' => $notes,
                    'status' => $status,
                    'sent_at' => $sentAt,
                ]);

                $orderItem->load('food:id,name,category,price,image_url');

                $created[] = $orderItem;
            };

            if ($request->has('items')) {
                $sendNow = (bool) $request->get('send_now', false);
                foreach ($request->get('items') as $it) {
                    $processItem($it['food_id'], $it['qty'], $it['notes'] ?? null, $sendNow);
                }
            } else {
                $processItem($request->food_id, $request->qty, $request->notes ?? null, (bool) $request->get('send_now', false));
            }

            // If any items were sent immediately, recalc total
            if (collect($created)->contains(fn($i) => $i->status === 'sent')) {
                $order->calculateTotal();
            }

            DB::commit();

            $responseData = collect($created)->map(function ($orderItem) {
                return [
                    'id' => $orderItem->id,
                    'order_id' => $orderItem->order_id,
                    'food_id' => $orderItem->food_id,
                    'food_name' => $orderItem->food->name,
                    'category' => $orderItem->food->category,
                    'qty' => $orderItem->quantity,
                    'price' => $orderItem->price,
                    'subtotal' => $orderItem->subtotal,
                    'status' => $orderItem->status,
                    'sent_at' => optional($orderItem->sent_at)->toDateTimeString(),
                ];
            })->values();

            return response()->json([
                'status' => 'success',
                'message' => 'Items added to order successfully',
                'data' => $responseData,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add items to order: ' . $e->getMessage()
            ], 500);
        }
    }


    public function sendDrafts($orderId)
    {
        $order = Order::with('orderItems')->find($orderId);

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        if ($order->status !== 'open') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot send items for a closed order'
            ], 400);
        }

        $drafts = $order->orderItems->where('status', 'draft');

        if ($drafts->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'No draft items to send'
            ], 400);
        }

        try {
            DB::beginTransaction();

            foreach ($drafts as $item) {
                $item->status = 'sent';
                $item->sent_at = now();
                $item->save();
            }

            $order->calculateTotal();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Draft items sent to kitchen',
                'data' => $drafts->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'food_id' => $item->food_id,
                        'qty' => $item->quantity,
                        'price' => $item->price,
                        'subtotal' => $item->subtotal,
                        'sent_at' => optional($item->sent_at)->toDateTimeString(),
                    ];
                })->values(),
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send draft items: ' . $e->getMessage()
            ], 500);
        }
    }

    public function closeOrder($orderId)
    {
        $order = Order::with(['orderItems', 'table'])->find($orderId);

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        if ($order->status !== 'open') {
            return response()->json([
                'status' => 'error',
                'message' => 'Order is already closed'
            ], 400);
        }

        if ($order->orderItems->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot close order with no items'
            ], 400);
        }

        // ✅ FIX: Check for draft items and block closing
        $draftItems = $order->orderItems->where('status', 'draft');
        if ($draftItems->isNotEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot close order with draft items. Please send all items to kitchen first.',
                'draft_count' => $draftItems->count(),
                'draft_items' => $draftItems->map(function($item) {
                    return [
                        'food_name' => $item->food ? $item->food->name : 'Unknown',
                        'qty' => $item->quantity
                    ];
                })
            ], 400);
        }

        // Validate cash input (uang pelanggan) - required when closing an order
        $validator = Validator::make(request()->all(), [
            'cash' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $cash = (float) request()->get('cash');


        try {
            DB::beginTransaction();

            // Force calculate total one more time before closing
            $order->calculateTotal();
            
            // If total is still 0, force calculate with all items (debugging)
            if ($order->total_amount == 0) {
                $order->forceCalculateTotal();
            }

            // Ensure customer provided enough cash
            if ($cash < $order->total_amount) {
                DB::rollBack();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Insufficient cash provided',
                    'total_amount' => $order->total_amount,
                    'cash_received' => $cash,
                ], 400);
            }

            // Save cash and change into the order record then close
            $order->cash_received = $cash;
            $order->change_given = $cash - $order->total_amount;

            $order->close();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Order closed successfully',
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                    'cash_received' => $order->cash_received,
                    'change_given' => $order->change_given,
                    'opened_at' => $order->opened_at,
                    'closed_at' => $order->closed_at,
                    'table' => [
                        'id' => $order->table->id,
                        'table_number' => $order->table->table_number,
                        'status' => 'available',
                    ],
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to close order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateReceipt($id)
    {
        $order = Order::with([
            'table:id,table_number',
            'user:id,name',
            'orderItems.food:id,name,price'
        ])->find($id);

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        if ($order->status !== 'closed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Order must be closed to generate receipt'
            ], 400);
        }

        // ✅ FIX: Consolidate items with same food_id (fixed array reference bug)
        $consolidatedItems = [];
        
        foreach ($order->orderItems->where('status', 'sent') as $item) {
            $foodId = $item->food_id;
            $foodName = $item->food ? $item->food->name : 'Unknown Food';
            
            if (isset($consolidatedItems[$foodId])) {
                // Combine quantities and recalculate subtotal
                $consolidatedItems[$foodId]['quantity'] += $item->quantity;
                $consolidatedItems[$foodId]['subtotal'] = $consolidatedItems[$foodId]['quantity'] * $consolidatedItems[$foodId]['price'];
            } else {
                // Add new item
                $consolidatedItems[$foodId] = [
                    'food_id' => $item->food_id,
                    'food_name' => $foodName,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'subtotal' => $item->subtotal,
                ];
            }
        }

        // Convert to indexed array for easier iteration in view
        $consolidatedItems = array_values($consolidatedItems);

        // Debug: Check if we have any items
        if (empty($consolidatedItems)) {
            // Fallback: use all items regardless of status for debugging
            $consolidatedItems = [];
            foreach ($order->orderItems as $item) {
                $foodId = $item->food_id;
                $foodName = $item->food ? $item->food->name : 'Unknown Food';
                
                if (isset($consolidatedItems[$foodId])) {
                    $consolidatedItems[$foodId]['quantity'] += $item->quantity;
                    $consolidatedItems[$foodId]['subtotal'] = $consolidatedItems[$foodId]['quantity'] * $consolidatedItems[$foodId]['price'];
                } else {
                    $consolidatedItems[$foodId] = [
                        'food_id' => $item->food_id,
                        'food_name' => $foodName,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'subtotal' => $item->subtotal,
                    ];
                }
            }
            $consolidatedItems = array_values($consolidatedItems);
            
            // If total is still 0, recalculate it for display
            if ($order->total_amount == 0) {
                $calculatedTotal = array_sum(array_column($consolidatedItems, 'subtotal'));
                $order->total_amount = $calculatedTotal; // Just for display, don't save
            }
        }

        // Generate PDF using dompdf
        $pdf = \PDF::loadView('receipts.receipt', [
            'order' => $order,
            'consolidatedItems' => $consolidatedItems
        ]);

        // Stream PDF (tidak disimpan ke disk)
        return $pdf->stream('receipt-' . $order->order_number . '.pdf');
    }

}
