<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventStatsController extends Controller
{
    public function show(Request $request, int $eventId)
    {
        // Ventas por categoría (localidad) para este evento
        $perCategory = Order::query()
            ->where('orders.status', OrderStatus::PAID->value)
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('ticket_categories', 'order_items.ticket_category_id', '=', 'ticket_categories.id')
            ->join('event_dates', 'ticket_categories.event_date_id', '=', 'event_dates.id')
            ->where('event_dates.event_id', $eventId)
            ->groupBy('ticket_categories.id', 'ticket_categories.name')
            ->select([
                'ticket_categories.id as category_id',
                'ticket_categories.name',
                DB::raw('SUM(order_items.quantity) as tickets_sold'),
                DB::raw('SUM(order_items.line_total) as revenue'),
            ])
            ->get();

        // Evolución de ventas por día para este evento
        $perDay = Order::query()
            ->where('orders.status', OrderStatus::PAID->value)
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('ticket_categories', 'order_items.ticket_category_id', '=', 'ticket_categories.id')
            ->join('event_dates', 'ticket_categories.event_date_id', '=', 'event_dates.id')
            ->where('event_dates.event_id', $eventId)
            ->groupBy(DB::raw('DATE(orders.created_at)'))
            ->orderBy(DB::raw('DATE(orders.created_at)'))
            ->select([
                DB::raw('DATE(orders.created_at) as date'),
                DB::raw('SUM(order_items.quantity) as tickets_sold'),
                DB::raw('SUM(order_items.line_total) as revenue'),
            ])
            ->get();

        return response()->json([
            'per_category' => $perCategory,
            'per_day'      => $perDay,
        ]);
    }
}
