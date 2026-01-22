<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TicketCategory;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $orders = Order::with('items.ticketCategory.eventDate.event')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($orders);
    }

    public function show(Request $request, Order $order)
    {
        $user = $request->user();

        if ($order->user_id !== $user->id) {
            abort(403, 'No autorizado.');
        }

        $relations = ['items.ticketCategory.eventDate.event', 'payment'];

        if ($order->status === OrderStatus::PAID) {
            $relations[] = 'items.tickets';
        }

        $order->load($relations);

        return response()->json($order);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'items'                       => ['required', 'array', 'min:1'],
            'items.*.ticket_category_id'  => ['required', 'integer', 'exists:ticket_categories,id'],
            'items.*.quantity'            => ['required', 'integer', 'min:1'],
            // opcional: 'discount_code'  => ['nullable', 'string'],
        ]);

        $itemsInput = $data['items'];

        $order = DB::transaction(function () use ($user, $itemsInput) {
            $subtotal = 0;
            $orderItemsData = [];

            // Bloquear filas para evitar overselling en alta concurrencia
            $categoryIds = collect($itemsInput)->pluck('ticket_category_id');
            $categories = TicketCategory::whereIn('id', $categoryIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($itemsInput as $item) {
                $category = $categories[$item['ticket_category_id']] ?? null;

                if (! $category) {
                    abort(422, 'Categoría de ticket inválida.');
                }

                $qty = $item['quantity'];

                if ($qty > $category->stock_available) {
                    abort(422, 'No hay stock suficiente para la categoría: '.$category->name);
                }

                $unitPrice = $category->price;
                $lineTotal = $unitPrice * $qty;

                $subtotal += $lineTotal;

                $orderItemsData[] = [
                    'ticket_category_id'           => $category->id,
                    'event_date_id'                => $category->event_date_id,
                    'quantity'                     => $qty,
                    'unit_price'                   => $unitPrice,
                    'line_total'                   => $lineTotal,
                    'ticket_category_name_snapshot'=> $category->name,
                ];

                // actualizar stock_sold
                $category->increment('stock_sold', $qty);
            }

            $taxRate = config('app.tax_rate', 0.0);

            $discountTotal = 0; // sin cupón al crear
            $subAfterDiscount = max($subtotal - $discountTotal, 0);
            $taxTotal = round($subAfterDiscount * $taxRate, 2);
            $total = $subAfterDiscount + $taxTotal;

            $order = Order::create([
                'user_id'          => $user->id,
                'discount_code_id' => null,
                'status'           => OrderStatus::PENDING_PAYMENT->value,
                'subtotal'         => $subtotal,
                'discount_total'   => $discountTotal,
                'tax_total'        => $taxTotal,
                'total'            => $total,
                'currency'         => 'USD',
            ]);

            foreach ($orderItemsData as $itemData) {
                $itemData['order_id'] = $order->id;
                OrderItem::create($itemData);
            }

            return $order;
        });

        $order->load('items.ticketCategory.eventDate.event');

        return response()->json($order, 201);
    }

    public function checkout(Request $request, Order $order)
    {
        $user = $request->user();

        if ($order->user_id !== $user->id) {
            abort(403, 'No autorizado.');
        }

        if ($order->status !== OrderStatus::PENDING_PAYMENT) {
            abort(422, 'La orden no está pendiente de pago.');
        }

        // Revalidar cupón si existe
        if ($order->discount_code_id) {
            $order->loadMissing('discountCode');
            $discount = $order->discountCode;

            if (! $discount) {
                // Por seguridad: limpiar referencia colgada
                $this->removeDiscountFromOrder($order);
                abort(422, 'El código de descuento ya no es válido.');
            }

            $now = now();

            $invalid =
                ! $discount->is_active ||
                ($discount->starts_at && $discount->starts_at->isFuture()) ||
                ($discount->ends_at && $discount->ends_at->isPast()) ||
                (! is_null($discount->max_uses) && $discount->used_count >= $discount->max_uses);

            if ($invalid) {
                // Quitar descuento y recalcular totales sin cupón
                $this->removeDiscountFromOrder($order);
                abort(422, 'El código de descuento que tenías aplicado ya no es válido. Se ha actualizado el total de la orden.');
            }
        }

        // A partir de aquí, $order->total es el monto correcto (con o sin cupón válido)
        Stripe::setApiKey(config('services.stripe.secret'));

        $frontendUrl = config('app.frontend_url');

        $session = StripeSession::create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'customer_email' => $user->email,
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => strtolower($order->currency),
                        'product_data' => [
                            'name' => 'Orden #'.$order->id.' - Abrakdabra Tickets',
                        ],
                        'unit_amount' => (int) round($order->total * 100),
                    ],
                    'quantity' => 1,
                ],
            ],
            'success_url' => $frontendUrl.'/checkout/success?order_id='.$order->id.'&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => $frontendUrl.'/checkout/cancel?order_id='.$order->id,
            'metadata'    => [
                'order_id' => $order->id,
                'user_id'  => $user->id,
            ],
        ]);

        $order->stripe_session_id = $session->id;
        $order->save();

        return response()->json([
            'checkout_url' => $session->url,
            'session_id'   => $session->id,
            'order_id'     => $order->id,
        ]);
    }

    public function markPaid(Request $request, Order $order)
    {
        $user = $request->user();

        // Buyer solo puede marcar sus órdenes, o aquí podrías permitir solo admin
        if ($order->user_id !== $user->id) {
            abort(403, 'No autorizado.');
        }

        if ($order->status !== OrderStatus::PENDING_PAYMENT) {
            abort(422, 'La orden no está pendiente de pago.');
        }

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

            if ($order->discount_code_id) {
                $order->loadMissing('discountCode');
                if ($order->discountCode) {
                    $order->discountCode->increment('used_count');
                }
            }
        });

        $order->load('items.ticketCategory.eventDate.event', 'items.tickets', 'payment');

        return response()->json($order);
    }

    public function cancel(Request $request, Order $order)
    {
        $user = $request->user();

        if ($order->user_id !== $user->id) {
            abort(403, 'No autorizado.');
        }

        if ($order->status !== OrderStatus::PENDING_PAYMENT) {
            abort(422, 'Solo se pueden cancelar órdenes pendientes de pago.');
        }

        DB::transaction(function () use ($order) {
            // Devolver stock reservado
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

        $order->load('items.ticketCategory.eventDate.event');

        return response()->json($order);
    }

    private function removeDiscountFromOrder(Order $order): void
    {
        $subtotal = $order->subtotal;
        $taxRate = config('app.tax_rate', 0.0);

        $discountTotal = 0;
        $subAfterDiscount = max($subtotal - $discountTotal, 0);
        $taxTotal = round($subAfterDiscount * $taxRate, 2);
        $total = $subAfterDiscount + $taxTotal;

        $order->discount_code_id = null;
        $order->discount_total   = $discountTotal;
        $order->tax_total        = $taxTotal;
        $order->total            = $total;
        $order->save();
    }
}
