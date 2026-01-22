<?php

namespace App\Enums;

enum EventStatus: string
{
    case UPCOMING  = 'upcoming';
    case ON_SALE   = 'on_sale';
    case SOLD_OUT  = 'sold_out';
    case CANCELLED = 'cancelled';
    case FINISHED  = 'finished';
}
