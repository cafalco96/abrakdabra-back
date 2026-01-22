<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class PublicEventController extends Controller
{
    // Lista de eventos visibles públicamente
    public function index(Request $request)
    {
        $query = Event::query()
            ->whereIn('status', [
                EventStatus::UPCOMING->value,
                EventStatus::ON_SALE->value,
            ])
            ->with(['creator', 'dates.ticketCategories']);

        if ($search = $request->query('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $events = $query->orderByDesc('created_at')->paginate(15);

        return response()->json($events);
    }

    // Detalle de un evento
    public function show(Event $event)
    {
        if (! in_array($event->status->value, [
            EventStatus::UPCOMING->value,
            EventStatus::ON_SALE->value,
        ], true)) {
            abort(404);
        }

        $event->load(['creator', 'dates.ticketCategories']);

        return response()->json($event);
    }
}
