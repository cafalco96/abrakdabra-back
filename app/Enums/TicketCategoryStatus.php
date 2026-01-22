<?php

namespace App\Enums;

enum TicketCategoryStatus: string
{
    case AVAILABLE = 'available';
    case SOLD_OUT  = 'sold_out';
    case DISABLED  = 'disabled';
}
