<?php

namespace App\Http\Controllers\order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Open a new order for a table.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function open(Request $request)
    {
        // Validate request
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
}
