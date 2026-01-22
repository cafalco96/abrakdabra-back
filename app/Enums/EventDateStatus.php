<?php

namespace App\Enums;

enum EventDateStatus: string
{
    case SCHEDULED = 'scheduled';
    case CANCELLED = 'cancelled';
    case FINISHED  = 'finished';
}
