<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('title');
            }
        });

        // Generar slugs para eventos existentes
        \App\Models\Event::withTrashed()->each(function ($event) {
            if (!$event->slug) {
                $base = Str::slug($event->title);
                $slug = $base;
                $count = 1;
                while (\App\Models\Event::withTrashed()->where('slug', $slug)->where('id', '!=', $event->id)->exists()) {
                    $slug = $base . '-' . $count++;
                }
                $event->slug = $slug;
                $event->saveQuietly();
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
