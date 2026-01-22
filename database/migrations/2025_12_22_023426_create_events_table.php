<?php

use App\Enums\EventStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location');

            $table->string('status')->default(EventStatus::UPCOMING->value);

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // índices para filtros / auditoría
            $table->index('status');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
