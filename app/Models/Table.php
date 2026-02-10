<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'table_number',
        'capacity',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
        ];
    }

    /**
     * Get all orders for this table.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the current open order for this table.
     */
    public function currentOrder()
    {
        return $this->hasOne(Order::class)->where('status', 'open');
    }

    /**
     * Check if table is available.
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /**
     * Check if table has open order.
     */
    public function hasOpenOrder(): bool
    {
        return $this->currentOrder()->exists();
    }
}
