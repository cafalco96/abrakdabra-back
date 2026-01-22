<?php

namespace App\Models;

use App\Enums\EventDateStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at'   => 'datetime',
            'status'    => EventDateStatus::class,
        ];
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function ticketCategories()
    {
        return $this->hasMany(TicketCategory::class);
    }
}
