<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_item_id',
        'ticket_category_id',
        'code',
        'qr_payload',
        'status',
        'issued_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'status'    => TicketStatus::class,
            'issued_at' => 'datetime',
            'used_at'   => 'datetime',
        ];
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function ticketCategory()
    {
        return $this->belongsTo(TicketCategory::class);
    }
}
