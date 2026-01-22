<?php

namespace Database\Seeders;

use App\Enums\EventDateStatus;
use App\Enums\EventStatus;
use App\Enums\TicketCategoryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\User;
use App\Models\Event;
use App\Models\EventDate;
use App\Models\TicketCategory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Ticket;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoEventSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            // 1) Usar gestor existente o crearlo si no está
            $gestor = User::where('role', UserRole::GESTOR)
                ->first();

            if (! $gestor) {
                $gestor = User::factory()->create([
                    'name' => 'Gestor Demo',
                    'email' => 'gestor.demo@example.com',
                    'role' => UserRole::GESTOR,
                ]);
            }

            $buyer = User::where('role', UserRole::BUYER)->first()
                ?? User::factory()->create([
                    'name' => 'Comprador Demo',
                    'email' => 'buyer.demo@example.com',
                    'role' => UserRole::BUYER,
                ]);

            // 2) Evento
            $event = Event::create([
                'title'       => 'Concierto Demo Abrakdabra',
                'description' => 'Evento demo para pruebas de la plataforma.',
                'location'    => 'Quito, Teatro Demo',
                'status'      => EventStatus::ON_SALE,
                'created_by'  => $gestor->id,
            ]);

            // 3) Fecha / función
            $eventDate = EventDate::create([
                'event_id' => $event->id,
                'starts_at' => now()->addDays(10)->setTime(20, 0),
                'ends_at'   => now()->addDays(10)->setTime(22, 0),
                'status'    => EventDateStatus::SCHEDULED,
            ]);

            // 4) Categorías de boletos
            $vip = TicketCategory::create([
                'event_date_id' => $eventDate->id,
                'name'          => 'VIP',
                'price'         => 50.00,
                'stock_total'   => 100,
                'stock_sold'    => 0,
                'status'        => TicketCategoryStatus::AVAILABLE,
            ]);

            $general = TicketCategory::create([
                'event_date_id' => $eventDate->id,
                'name'          => 'General',
                'price'         => 25.00,
                'stock_total'   => 200,
                'stock_sold'    => 0,
                'status'        => TicketCategoryStatus::AVAILABLE,
            ]);

            // 5) Orden demo (comprador compra 2 VIP y 3 General)
            $quantityVip = 2;
            $quantityGeneral = 3;

            $subtotal = ($vip->price * $quantityVip) + ($general->price * $quantityGeneral);
            $discount = 0;
            $tax      = 0;
            $total    = $subtotal - $discount + $tax;

            $order = Order::create([
                'user_id'          => $buyer->id,
                'discount_code_id' => null,
                'status'           => OrderStatus::PAID,
                'subtotal'         => $subtotal,
                'discount_total'   => $discount,
                'tax_total'        => $tax,
                'total'            => $total,
                'currency'         => 'USD',
            ]);

            // 6) Items de la orden
            $itemVip = OrderItem::create([
                'order_id'                     => $order->id,
                'ticket_category_id'           => $vip->id,
                'quantity'                     => $quantityVip,
                'unit_price'                   => $vip->price,
                'line_total'                   => $vip->price * $quantityVip,
                'event_date_id'                => $eventDate->id,
                'ticket_category_name_snapshot'=> $vip->name,
            ]);

            $itemGeneral = OrderItem::create([
                'order_id'                     => $order->id,
                'ticket_category_id'           => $general->id,
                'quantity'                     => $quantityGeneral,
                'unit_price'                   => $general->price,
                'line_total'                   => $general->price * $quantityGeneral,
                'event_date_id'                => $eventDate->id,
                'ticket_category_name_snapshot'=> $general->name,
            ]);

            // Actualizar stock_sold
            $vip->increment('stock_sold', $quantityVip);
            $general->increment('stock_sold', $quantityGeneral);

            // 7) Payment demo (Stripe sandbox simulado)
            Payment::create([
                'order_id'                 => $order->id,
                'provider'                 => 'stripe',
                'environment'              => 'sandbox',
                'stripe_payment_intent_id' => 'pi_demo_' . Str::random(10),
                'status'                   => PaymentStatus::SUCCEEDED,
                'amount'                   => $total,
                'currency'                 => 'USD',
                'paid_at'                  => now(),
            ]);

            // 8) Tickets (1 por boleto)
            $createTickets = function (OrderItem $item) {
                for ($i = 0; $i < $item->quantity; $i++) {
                    Ticket::create([
                        'order_item_id'      => $item->id,
                        'ticket_category_id' => $item->ticket_category_id,
                        'code'               => (string) Str::uuid(),
                        'qr_payload'         => null, // se puede rellenar luego
                        'status'             => TicketStatus::ISSUED,
                        'issued_at'          => now(),
                    ]);
                }
            };

            $createTickets($itemVip);
            $createTickets($itemGeneral);
        });
    }
}
