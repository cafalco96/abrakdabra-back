<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isGestor = $user->role === UserRole::GESTOR;

        // Total eventos (gestor: solo los suyos)
        $totalEvents = $isGestor
            ? Event::where('created_by', $user->id)->count()
            : Event::count();

        // Eventos en venta
        $eventsOnSale = $isGestor
            ? Event::where('status', 'on_sale')->where('created_by', $user->id)->count()
            : Event::where('status', 'on_sale')->count();

        // Ordenes pagadas filtradas por gestor si aplica
        $paidOrdersQuery = Order::where('orders.status', 'paid');
        if ($isGestor) {
            $paidOrdersQuery->whereHas('items.ticketCategory.eventDate.event', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $today = now()->toDateString();

        // Entradas vendidas hoy
        $ticketsSoldToday = (clone $paidOrdersQuery)
            ->whereDate('orders.created_at', $today)
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->sum('order_items.quantity');

        // Ingresos de hoy
        $revenueToday = (clone $paidOrdersQuery)
            ->whereDate('orders.created_at', $today)
            ->sum('orders.total');

        // Entradas totales
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
