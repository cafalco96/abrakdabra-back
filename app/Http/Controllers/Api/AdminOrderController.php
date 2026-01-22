<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with([
            'user',
            'items.ticketCategory.eventDate.event',
            'payment',
        ])->orderByDesc('created_at');

        // ?status=pending_payment|paid|cancelled
        if ($status = $request->query('status')) {
            // Si quieres, puedes validar contra OrderStatus::cases()
            $query->where('status', $status);
        }

        // ?event_id=1
        if ($eventId = $request->query('event_id')) {
            $query->whereHas('items.ticketCategory.eventDate', function ($q) use ($eventId) {
                $q->where('event_id', $eventId);
            });
        }

        // ?buyer_email=foo@bar.com (búsqueda parcial)
        if ($buyerEmail = $request->query('buyer_email')) {
            $query->whereHas('user', function ($q) use ($buyerEmail) {
                $q->where('email', 'like', '%'.$buyerEmail.'%');
            });
        }

        $perPage = (int) $request->query('per_page', 15);

        $orders = $query->paginate($perPage);

        return response()->json($orders);
    }

    public function show(Request $request, Order $order)
    {
        $order->load(
            'user',
            'items.ticketCategory.eventDate.event',
            'items.tickets',
            'payment'
        );

        return response()->json($order);
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending_payment,paid,cancelled'],
        ]);

        $newStatus = $validated['status'];

        // Solo permitimos cambiar órdenes pending_payment
        if ($order->status !== OrderStatus::PENDING_PAYMENT) {
            return response()->json([
                'message' => 'Solo se pueden actualizar órdenes pendientes de pago.',
            ], 422);
        }

        // Si la marcamos como pagada, generamos tickets igual que markPaid
        if ($newStatus === OrderStatus::PAID->value) {
            DB::transaction(function () use ($order) {
                $order->load('items');

                foreach ($order->items as $item) {
                    for ($i = 0; $i < $item->quantity; $i++) {
                        Ticket::create([
                            'order_item_id'      => $item->id,
                            'ticket_category_id' => $item->ticket_category_id,
                            'code'               => (string) Str::uuid(),
                            'qr_payload'         => null,
                            'status'             => TicketStatus::ISSUED->value,
                            'issued_at'          => now(),
                            'used_at'            => null,
                        ]);
                    }
                }

                $order->status = OrderStatus::PAID;
                $order->save();
            });
        } elseif ($newStatus === OrderStatus::CANCELLED->value) {
            // Reusar lógica de cancel para devolver stock
            DB::transaction(function () use ($order) {
                $order->load('items.ticketCategory');

                foreach ($order->items as $item) {
                    $category = $item->ticketCategory;
                    if ($category) {
                        $category->decrement('stock_sold', $item->quantity);
                    }
                }

                $order->status = OrderStatus::CANCELLED;
                $order->save();
            });
        } else {
            // Dejarla en pending_payment explícitamente (no cambia mucho, pero por si acaso)
            $order->status = OrderStatus::PENDING_PAYMENT;
            $order->save();
        }

        // Recargar con relaciones completas para el front
        $order->load(
            'user',
            'items.ticketCategory.eventDate.event',
            'items.tickets',
            'payment'
        );

        return response()->json($order);
    }
}
