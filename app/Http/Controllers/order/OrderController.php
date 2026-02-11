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

        if (!$table->isAvailable()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Table is not available. Current status: ' . $table->status
            ], 400);
        }

        if ($table->hasOpenOrder()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Table already has an open order'
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
                        'food_name' => $item->food->name,
                        'category' => $item->food->category,
                        'image_url' => $item->food->image_url,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'subtotal' => $item->subtotal,
                        'notes' => $item->notes,
                    ];
                }),
                'summary' => [
                    'total_items' => $order->orderItems->sum('quantity'),
                    'total_amount' => $order->total_amount,
                ],
            ]
        ], 200);
    }

    public function addItem(Request $request, $orderId)
    {
        $validator = Validator::make($request->all(), [
            'food_id' => 'required|integer|exists:foods,id',
            'qty' => 'required|integer|min:1',
        ]);

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

        $food = Food::find($request->food_id);

        if (!$food) {
            return response()->json([
                'status' => 'error',
                'message' => 'Food not found'
            ], 404);
        }

        if (!$food->is_available) {
            return response()->json([
                'status' => 'error',
                'message' => 'Food is not available'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'food_id' => $food->id,
                'quantity' => $request->qty,
                'price' => $food->price,
            ]);

            $orderItem->load('food:id,name,category,price,image_url');

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Item added to order successfully',
                'data' => [
                    'id' => $orderItem->id,
                    'order_id' => $orderItem->order_id,
                    'food_id' => $orderItem->food_id,
                    'food_name' => $orderItem->food->name,
                    'category' => $orderItem->food->category,
                    'qty' => $orderItem->quantity,
                    'price' => $orderItem->price,
                    'subtotal' => $orderItem->subtotal,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add item to order: ' . $e->getMessage()
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

        try {
            DB::beginTransaction();

            $order->calculateTotal();
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

        // Generate PDF using dompdf
        $pdf = \PDF::loadView('receipts.receipt', [
            'order' => $order
        ]);

        // Stream PDF (tidak disimpan ke disk)
        return $pdf->stream('receipt-' . $order->order_number . '.pdf');
    }
}
