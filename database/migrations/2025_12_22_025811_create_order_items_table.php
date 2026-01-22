<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('ticket_category_id')
                ->constrained('ticket_categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedInteger('quantity');

            $table->decimal('unit_price', 10, 2);
            $table->decimal('line_total', 10, 2);

            // Opcionales útiles para reportes / snapshots
            $table->foreignId('event_date_id')
                ->nullable()
                ->constrained('event_dates')
                ->nullOnDelete();

            $table->string('ticket_category_name_snapshot')->nullable();

            $table->timestamps();

            $table->index('order_id');
            $table->index('ticket_category_id');
            $table->index('event_date_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
