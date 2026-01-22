<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case INITIATED = 'initiated';
    case SUCCEEDED = 'succeeded';
    case FAILED    = 'failed';
    case CANCELLED = 'cancelled';
}
