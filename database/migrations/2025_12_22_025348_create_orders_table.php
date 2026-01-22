<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('discount_code_id')
                ->nullable()
                ->constrained('discount_codes')
                ->nullOnDelete();

            $table->string('status')->default(OrderStatus::DRAFT->value);

            $table->decimal('subtotal', 10, 2);
            $table->decimal('discount_total', 10, 2)->default(0);
            $table->decimal('tax_total', 10, 2)->default(0);
            $table->decimal('total', 10, 2);

            $table->string('currency', 3)->default('USD');

            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index('discount_code_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
