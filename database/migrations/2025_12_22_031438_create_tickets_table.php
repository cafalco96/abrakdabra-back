<?php

use App\Enums\TicketStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_item_id')
                ->constrained('order_items')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('ticket_category_id')
                ->constrained('ticket_categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('code')->unique(); // valor que irá en el QR
            $table->text('qr_payload')->nullable();

            $table->string('status')->default(TicketStatus::ISSUED->value);

            $table->dateTime('issued_at');
            $table->dateTime('used_at')->nullable();

            $table->timestamps();

            $table->index('ticket_category_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
