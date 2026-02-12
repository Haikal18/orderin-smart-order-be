<?php

namespace App\Http\Controllers\table;

use App\Http\Controllers\Controller;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TableController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Table::select('id', 'table_number', 'status')
            ->orderBy('table_number');

        if ($request->has('search')) {
            $query->where('table_number', 'like', '%' . $request->search . '%');
        }

        $tables = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Tables retrieved successfully',
            'data' => $tables
        ], 200);
    }
}
