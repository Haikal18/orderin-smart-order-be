<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_number',
        'table_id',
        'user_id',
        'status',
        'total_amount',
        'cash_received',
        'change_given',
        'opened_at',
        'closed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'cash_received' => 'decimal:2',
            'change_given' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * Bootstrap the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
            if (empty($order->opened_at)) {
                $order->opened_at = now();
            }
        });
    }

    /**
     * Generate unique order number.
     */
    public static function generateOrderNumber(): string
    {
        $date = now()->format('Ymd');
        $lastOrder = static::whereDate('created_at', now())->latest('id')->first();
        $sequence = $lastOrder ? (int) substr($lastOrder->order_number, -4) + 1 : 1;
        
        return 'ORD-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the table for this order.
     */
    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    /**
     * Get the user (pelayan) who handles this order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all items in this order.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Calculate and update total amount.
     */
    public function calculateTotal(): void
    {
        // Only consider items that have been sent to kitchen when computing total
        $this->total_amount = $this->orderItems()->where('status', 'sent')->sum('subtotal');
        $this->save();
    }

    /**
     * Force recalculate total (including all items regardless of status) - for debugging
     */
    public function forceCalculateTotal(): void
    {
        $this->total_amount = $this->orderItems()->sum('subtotal');
        $this->save();
    }

    /**
     * Close the order.
     */
    public function close(): void
    {
        $this->status = 'closed';
        $this->closed_at = now();
        $this->save();

        // Update table status to available
        Table::where('id', $this->table_id)->update(['status' => 'available']);
    }

    /**
     * Scope a query to only include open orders.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope a query to only include closed orders.
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }
}
