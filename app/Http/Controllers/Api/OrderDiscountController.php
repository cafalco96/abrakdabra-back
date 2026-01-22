<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderDiscountController extends Controller
{
    public function apply(Request $request, Order $order)
    {
        $user = $request->user();

        if ($order->user_id !== $user->id) {
            abort(403, 'No autorizado.');
        }

        if ($order->status !== OrderStatus::PENDING_PAYMENT) {
            abort(422, 'Solo se puede aplicar cupón a órdenes pendientes de pago.');
        }

        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $code = strtoupper(trim($data['code']));

        $discount = DiscountCode::where('code', $code)->first();

        if (! $discount) {
            abort(422, 'El código no existe.');
        }

        if (! $discount->is_active) {
            abort(422, 'El código no está activo.');
        }

        $now = now();

        if ($discount->starts_at && $discount->starts_at->isFuture()) {
            abort(422, 'El código aún no está disponible.');
        }

        if ($discount->ends_at && $discount->ends_at->isPast()) {
            abort(422, 'El código ha expirado.');
        }

        if (! is_null($discount->max_uses) && $discount->used_count >= $discount->max_uses) {
            abort(422, 'El código ya alcanzó el número máximo de usos.');
        }

        $subtotal = $order->subtotal;

        if ($subtotal <= 0) {
            abort(422, 'No se puede aplicar el código a una orden vacía.');
        }

        if ($discount->type === 'fixed') {
            $discountAmount = min($discount->value, $subtotal);
        } elseif ($discount->type === 'percentage') {
            $discountAmount = round($subtotal * ($discount->value / 100), 2);
        } else {
            abort(422, 'Tipo de código inválido.');
        }

        $taxRate = config('app.tax_rate', 0.0);

        $subAfterDiscount = max($subtotal - $discountAmount, 0);
        $tax = round($subAfterDiscount * $taxRate, 2);
        $total = $subAfterDiscount + $tax;

        DB::transaction(function () use ($order, $discount, $discountAmount, $tax, $total) {
            $order->discount_code_id = $discount->id;
            $order->discount_total = $discountAmount;
            $order->tax_total = $tax;
            $order->total = $total;
            $order->save();
        });

        return response()->json($order->fresh('items.ticketCategory.eventDate.event'));
    }
}
