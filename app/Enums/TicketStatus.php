<?php

namespace App\Enums;

enum TicketStatus: string
{
    case ISSUED   = 'issued';
    case CANCELLED = 'cancelled';
    case USED      = 'used';
}
