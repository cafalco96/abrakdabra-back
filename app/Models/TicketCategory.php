<?php

namespace App\Models;

use App\Enums\TicketCategoryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_date_id',
        'name',
        'price',
        'stock_total',
        'stock_sold',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price'  => 'decimal:2',
            'status' => TicketCategoryStatus::class,
        ];
    }

    public function eventDate()
    {
        return $this->belongsTo(EventDate::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    // helper para stock disponible
    public function getStockAvailableAttribute(): int
    {
        return $this->stock_total - $this->stock_sold;
    }
}
