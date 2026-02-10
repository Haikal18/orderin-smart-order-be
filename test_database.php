<?php

/**
 * TEST SCRIPT - Database Initialization Verification
 * 
 * Jalankan dengan: php test_database.php
 * 
 * Script ini untuk verifikasi:
 * - Data seeder berhasil
 * - Relasi model berfungsi
 * - Auto-calculation berjalan
 * - Order flow lengkap
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Table;
use App\Models\Food;
use App\Models\Order;
use App\Models\OrderItem;

echo "=================================================\n";
echo "📊 DATABASE INITIALIZATION TEST\n";
echo "=================================================\n\n";

// 1. Verify Seeded Data
echo "✅ SEEDED DATA:\n";
echo "   Users: " . User::count() . "\n";
echo "   Tables: " . Table::count() . "\n";
echo "   Foods: " . Food::count() . "\n";
echo "   Orders: " . Order::count() . "\n\n";

// 2. Display Users
echo "✅ USERS:\n";
User::all()->each(function($user) {
    echo "   - {$user->name} ({$user->email}) - Role: {$user->role}\n";
});
echo "\n";

// 3. Display Tables
echo "✅ TABLES (First 5):\n";
Table::limit(5)->get()->each(function($table) {
    echo "   - {$table->table_number} - Capacity: {$table->capacity} - Status: {$table->status}\n";
});
echo "\n";

// 4. Display Foods by Category
echo "✅ FOODS BY CATEGORY:\n";
foreach(['food', 'beverage', 'dessert'] as $category) {
    $count = Food::where('category', $category)->count();
    echo "   - {$category}: {$count} items\n";
}
echo "\n";

// 5. Test Order Creation Flow
echo "=================================================\n";
echo "🧪 TEST ORDER FLOW\n";
echo "=================================================\n\n";

try {
    // Get pelayan and available table
    $pelayan = User::where('role', 'pelayan')->first();
    $table = Table::where('status', 'available')->first();
    
    echo "1️⃣ Create Order\n";
    $order = Order::create([
        'table_id' => $table->id,
        'user_id' => $pelayan->id,
    ]);
    
    // Update table status
    $table->update(['status' => 'occupied']);
    
    echo "   ✓ Order Number: {$order->order_number}\n";
    echo "   ✓ Table: {$order->table->table_number}\n";
    echo "   ✓ Pelayan: {$order->user->name}\n";
    echo "   ✓ Status: {$order->status}\n\n";
    
    // Add items to order
    echo "2️⃣ Add Order Items\n";
    $nasiGoreng = Food::where('name', 'Nasi Goreng')->first();
    $esTeh = Food::where('name', 'Es Teh Manis')->first();
    $esKrim = Food::where('name', 'Es Krim Vanilla')->first();
    
    // Add item 1
    OrderItem::create([
        'order_id' => $order->id,
        'food_id' => $nasiGoreng->id,
        'quantity' => 2,
        'price' => $nasiGoreng->price,
        'notes' => 'Pedas level 3'
    ]);
    echo "   ✓ Added: 2x {$nasiGoreng->name} @ Rp " . number_format($nasiGoreng->price, 0, ',', '.') . "\n";
    
    // Add item 2
    OrderItem::create([
        'order_id' => $order->id,
        'food_id' => $esTeh->id,
        'quantity' => 2,
        'price' => $esTeh->price,
    ]);
    echo "   ✓ Added: 2x {$esTeh->name} @ Rp " . number_format($esTeh->price, 0, ',', '.') . "\n";
    
    // Add item 3
    OrderItem::create([
        'order_id' => $order->id,
        'food_id' => $esKrim->id,
        'quantity' => 1,
        'price' => $esKrim->price,
    ]);
    echo "   ✓ Added: 1x {$esKrim->name} @ Rp " . number_format($esKrim->price, 0, ',', '.') . "\n\n";
    
    // Refresh order to get updated total
    $order->refresh();
    
    echo "3️⃣ Order Summary\n";
    echo "   Order Number: {$order->order_number}\n";
    echo "   Table: {$order->table->table_number}\n";
    echo "   Items: {$order->orderItems->count()}\n";
    echo "   Total: Rp " . number_format($order->total_amount, 0, ',', '.') . "\n";
    echo "   Status: {$order->status}\n\n";
    
    // Test eager loading (avoid N+1)
    echo "4️⃣ Order Details (with eager loading)\n";
    $orderWithItems = Order::with(['table', 'user', 'orderItems.food'])
        ->find($order->id);
    
    foreach($orderWithItems->orderItems as $item) {
        echo "   - {$item->quantity}x {$item->food->name} "
           . "@ Rp " . number_format($item->price, 0, ',', '.') 
           . " = Rp " . number_format($item->subtotal, 0, ',', '.')
           . ($item->notes ? " ({$item->notes})" : "") . "\n";
    }
    echo "\n";
    
    // Close order
    echo "5️⃣ Close Order\n";
    $order->close();
    $order->refresh(); // Refresh order data
    $order->load('table'); // Reload table relationship
    echo "   ✓ Order closed at: {$order->closed_at}\n";
    echo "   ✓ Table status: {$order->table->status}\n\n";
    
    echo "=================================================\n";
    echo "✅ ALL TESTS PASSED!\n";
    echo "=================================================\n\n";
    
    echo "📋 Receipt Data Structure:\n";
    $receipt = [
        'order_number' => $order->order_number,
        'table' => $order->table->table_number,
        'pelayan' => $order->user->name,
        'opened_at' => $order->opened_at->format('d/m/Y H:i'),
        'closed_at' => $order->closed_at->format('d/m/Y H:i'),
        'items' => $order->orderItems->map(function($item) {
            return [
                'name' => $item->food->name,
                'qty' => $item->quantity,
                'price' => $item->price,
                'subtotal' => $item->subtotal,
                'notes' => $item->notes,
            ];
        }),
        'total' => $order->total_amount,
    ];
    
    echo json_encode($receipt, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    echo "🎉 Database initialization COMPLETE!\n";
    echo "🎉 Ready untuk development API Controllers.\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
