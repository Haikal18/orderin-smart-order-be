<?php

namespace App\Http\Controllers\table;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Http\JsonResponse;

class TableController extends Controller
{
    /**
     * Display a listing of tables.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        $tables = Table::select('id', 'table_number', 'status')
            ->orderBy('table_number')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Tables retrieved successfully',
            'data' => $tables
        ], 200);
    }
}
