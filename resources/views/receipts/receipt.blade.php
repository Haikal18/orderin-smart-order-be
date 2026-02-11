<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $order->order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            color: #333;
            padding: 20px;
        }
        
        .receipt {
            max-width: 300px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        
        .header h1 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .header p {
            font-size: 10px;
            color: #666;
        }
        
        .order-info {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #999;
        }
        
        .order-info p {
            margin-bottom: 5px;
        }
        
        .order-info strong {
            display: inline-block;
            width: 100px;
        }
        
        .items-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }
        
        .items-table thead {
            border-bottom: 1px solid #333;
        }
        
        .items-table th {
            text-align: left;
            padding: 8px 5px;
            font-weight: bold;
        }
        
        .items-table td {
            padding: 8px 5px;
            border-bottom: 1px dashed #ddd;
        }
        
        .items-table td.text-right {
            text-align: right;
        }
        
        .items-table td.text-center {
            text-align: center;
        }
        
        .items-table tbody tr:last-child td {
            border-bottom: 1px solid #333;
        }
        
        .total-section {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px solid #333;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .total-row.grand-total {
            font-weight: bold;
            font-size: 16px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #333;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px dashed #999;
            font-size: 10px;
            color: #666;
        }
        
        .footer p {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <h1>OrderIn</h1>
            <p>Smart Order System</p>
        </div>
        
        <!-- Order Information -->
        <div class="order-info">
            <p><strong>No. Order:</strong> {{ $order->order_number }}</p>
            <p><strong>No. Meja:</strong> {{ $order->table->table_number }}</p>
            <p><strong>Tanggal:</strong> {{ $order->closed_at->format('d/m/Y H:i') }}</p>
            <p><strong>Pelayan:</strong> {{ $order->user->name }}</p>
        </div>
        
        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Harga</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->orderItems as $item)
                <tr>
                    <td>{{ $item->food->name }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Total Section -->
        <div class="total-section">
            <div class="total-row grand-total">
                <span>TOTAL:</span>
                <span>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>Terima kasih atas kunjungan Anda!</p>
            <p>{{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
