<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'order_id',
        'food_id',
        'quantity',
        'price',
        'subtotal',
        'notes',
        'status',
        'sent_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Bootstrap the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto calculate subtotal before saving
        static::saving(function ($orderItem) {
            $orderItem->subtotal = $orderItem->quantity * $orderItem->price;
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    
    public function food()
    {
        return $this->belongsTo(Food::class);
    }
}
