<?php

use App\Enums\TicketCategoryStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_categories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_date_id')
                ->constrained('event_dates')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('name'); // ej. VIP, General

            $table->decimal('price', 10, 2);

            $table->unsignedInteger('stock_total');
            $table->unsignedInteger('stock_sold')->default(0);

            $table->string('status')->default(TicketCategoryStatus::AVAILABLE->value);

            $table->timestamps();

            // índices
            $table->index('event_date_id');
            $table->index('status');
            $table->unique(['event_date_id', 'name']); // evitar duplicados por función
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_categories');
    }
};
