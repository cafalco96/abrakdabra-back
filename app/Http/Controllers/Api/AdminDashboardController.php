<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        // Total eventos
        $totalEvents = Event::count();

        // Eventos en venta
        $eventsOnSale = Event::where('status', 'on_sale')->count();

        // Órdenes pagadas (base)
        $paidOrdersQuery = Order::where('orders.status', 'paid');

        $today = now()->toDateString();

        // Entradas vendidas hoy (solo órdenes pagadas hoy)
        $ticketsSoldToday = (clone $paidOrdersQuery)
            ->whereDate('orders.created_at', $today)
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->sum('order_items.quantity');

        // Ingresos de hoy
        $revenueToday = (clone $paidOrdersQuery)
            ->whereDate('orders.created_at', $today)
            ->sum('orders.total');

        // Entradas totales (todas las órdenes pagadas)
        $ticketsSoldTotal = (clone $paidOrdersQuery)
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->sum('order_items.quantity');

        // Ingresos totales
        $revenueTotal = (clone $paidOrdersQuery)->sum('orders.total');

        return response()->json([
            'total_events'       => $totalEvents,
            'events_on_sale'     => $eventsOnSale,
            'tickets_sold_today' => (int) $ticketsSoldToday,
            'revenue_today'      => (float) $revenueToday,
            'tickets_sold_total' => (int) $ticketsSoldTotal,
            'revenue_total'      => (float) $revenueTotal,
        ]);
    }
}
