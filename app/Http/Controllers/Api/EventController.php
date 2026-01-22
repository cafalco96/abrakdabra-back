<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    protected function ensureCanManageEvents($user): void
    {
        if (! $user) {
            abort(401, 'No autenticado.');
        }

        if (! in_array($user->role, [UserRole::ADMIN, UserRole::GESTOR], true)) {
            abort(403, 'No autorizado.');
        }
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $this->ensureCanManageEvents($user);

        // Admin ve todos, gestor solo los suyos (puedes ajustar lógica)
        $query = Event::query()->with('creator');

        if ($user->role === UserRole::GESTOR) {
            $query->where('created_by', $user->id);
        }

        // filtros opcionales: status, search
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->query('search')) {
            $query->where('title', 'like', '%'.$search.'%');
        }

        $events = $query->orderByDesc('created_at')->paginate(15);

        return response()->json($events);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $this->ensureCanManageEvents($user);

        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location'    => ['required', 'string', 'max:255'],
            'status'      => ['nullable', 'string', 'in:upcoming,on_sale,sold_out,cancelled,finished'],
            'image_path'  => ['nullable', 'string', 'max:500'],
        ]);

        $event = Event::create([
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'location'    => $data['location'],
            'status'      => $data['status'] ?? EventStatus::UPCOMING->value,
            'created_by'  => $user->id,
            'image_path'  => $data['image_path'] ?? null,
        ]);

        return response()->json($event, 201);
    }

    public function show(Request $request, Event $event)
    {
        $user = $request->user();
        $this->ensureCanManageEvents($user);

        if ($user->role === UserRole::GESTOR && $event->created_by !== $user->id) {
            abort(403, 'No autorizado.');
        }

        $event->load('creator', 'dates');

        return response()->json($event);
    }

    public function update(Request $request, Event $event)
    {
        $user = $request->user();
        $this->ensureCanManageEvents($user);

        if ($user->role === UserRole::GESTOR && $event->created_by !== $user->id) {
            abort(403, 'No autorizado.');
        }

        $data = $request->validate([
            'title'       => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location'    => ['sometimes', 'required', 'string', 'max:255'],
            'status'      => ['sometimes', 'required', 'string', 'in:upcoming,on_sale,sold_out,cancelled,finished'],
            'image_path'  => ['nullable', 'string', 'max:500'],
        ]);

        $event->fill($data);
        $event->save();

        return response()->json($event);
    }

    public function destroy(Request $request, Event $event)
    {
        $user = $request->user();
        $this->ensureCanManageEvents($user);

        if ($user->role === UserRole::GESTOR && $event->created_by !== $user->id) {
            abort(403, 'No autorizado.');
        }

        $event->delete(); // soft delete

        return response()->json(['message' => 'Evento eliminado.']);
    }
}
