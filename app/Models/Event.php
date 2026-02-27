<?php

namespace App\Models;

use App\Enums\EventStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'location',
        'status',
        'created_by',
        'image_path',
    ];

    protected $casts = [
        'status' => EventStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (Event $event) {
            if (empty($event->slug)) {
                $event->slug = static::generateUniqueSlug($event->title);
            }
        });

        static::updating(function (Event $event) {
            if ($event->isDirty('title') && empty($event->slug)) {
                $event->slug = static::generateUniqueSlug($event->title, $event->id);
            }
        });
    }

    protected static function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $count = 1;

        while (
            static::withTrashed()
                ->where('slug', $slug)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->exists()
        ) {
            $slug = $base . '-' . $count++;
        }

        return $slug;
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function dates()
    {
        return $this->hasMany(EventDate::class);
    }
}
