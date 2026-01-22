<?php

namespace App\Enums;

enum OrderStatus: string
{
    case DRAFT           = 'draft';
    case PENDING_PAYMENT = 'pending_payment';
    case PAID            = 'paid';
    case CANCELLED       = 'cancelled';
}
