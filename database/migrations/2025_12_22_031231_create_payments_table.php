<?php

use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('provider')->default('stripe');
            $table->string('environment')->default('sandbox'); // sandbox, production

            $table->string('stripe_payment_intent_id')->nullable();

            $table->string('status')->default(PaymentStatus::INITIATED->value);

            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');

            $table->dateTime('paid_at')->nullable();

            $table->timestamps();

            $table->unique('order_id');
            $table->index('status');
            $table->index('stripe_payment_intent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
