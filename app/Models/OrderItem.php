<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'ticket_category_id',
        'quantity',
        'unit_price',
        'line_total',
        'event_date_id',
        'ticket_category_name_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function ticketCategory()
    {
        return $this->belongsTo(TicketCategory::class);
    }

    public function eventDate()
    {
        return $this->belongsTo(EventDate::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
