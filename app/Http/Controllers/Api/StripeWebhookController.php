<?php 

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\StripeEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $secret
            );
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook firma inválida', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $eventId = $event->id;

        // Idempotencia: si ya procesaste este event.id, terminas aquí
        if (StripeEvent::where('event_id', $eventId)->exists()) {
            Log::info('Stripe webhook duplicado ignorado', [
                'event_id' => $eventId,
                'type' => $event->type,
            ]);

            return response()->json(['status' => 'duplicate'], 200);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $orderId = $session->metadata->order_id ?? null;

            if (! $orderId) {
                Log::warning('Stripe webhook: session sin order_id en metadata', [
                    'session_id' => $session->id ?? null,
                ]);

                return response()->json(['status' => 'no_order_id'], 200);
            }

            /** @var \App\Models\Order|null $order */
            $order = Order::with('items')
                ->where('id', $orderId)
                ->first();

            if (! $order) {
                Log::warning('Stripe webhook: orden no encontrada', [
                    'order_id' => $orderId,
                ]);

                // aun así marcamos el evento como procesado para no reintentar eternamente
                StripeEvent::create([
                    'event_id'     => $eventId,
                    'type'         => $event->type,
                    'processed_at' => now(),
                ]);

                return response()->json(['status' => 'order_not_found'], 200);
            }

            // Si ya está pagada, no volvemos a emitir tickets
            if ($order->status === OrderStatus::PAID) {
                Log::info('Stripe webhook: orden ya pagada, se ignora emisión', [
                    'order_id' => $order->id,
                ]);

                StripeEvent::create([
                    'event_id'     => $eventId,
                    'type'         => $event->type,
                    'processed_at' => now(),
                ]);

                return response()->json(['status' => 'already_paid'], 200);
            }

            DB::transaction(function () use ($order, $session, $eventId, $event) {
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
                $order->stripe_payment_intent = $session->payment_intent ?? null;
                $order->save();

                // Contar uso del cupón solo al pagar
                if ($order->discount_code_id) {
                    $order->loadMissing('discountCode');
                    if ($order->discountCode) {
                        $order->discountCode->increment('used_count');
                    }
                }

                StripeEvent::create([
                    'event_id'     => $eventId,
                    'type'         => $event->type,
                    'processed_at' => now(),
                ]);

                Log::info('Stripe webhook: orden marcada como pagada y tickets emitidos', [
                    'order_id' => $order->id,
                ]);
            });

            return response()->json(['status' => 'success'], 200);
        }

        return response()->json(['status' => 'ignored'], 200);
    }
}
