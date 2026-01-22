<?php
namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Enums\EventDateStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventDate;
use Illuminate\Http\Request;

class EventDateController extends Controller
{
    protected function ensureCanManage(Event $event, $user): void
    {
        // Reutiliza tu lógica de roles según EventController
        if (! in_array($user->role, [UserRole::ADMIN, UserRole::GESTOR], true)) {
            abort(403, 'No autorizado.');
        }

        if ($user->role === UserRole::GESTOR && $event->created_by !== $user->id) {
            abort(403, 'No autorizado.');
        }
    }

    public function index(Request $request, Event $event)
    {
        $this->ensureCanManage($event, $request->user());

        return response()->json(
            $event->dates()->orderBy('starts_at')->get()
        );
    }

    public function show(Request $request, Event $event, EventDate $date)
    {
        $this->ensureCanManage($event, $request->user());

        return response()->json($date);
    }

    public function store(Request $request, Event $event)
    {
        $this->ensureCanManage($event, $request->user());

        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at'   => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status'    => ['nullable', 'string', 'in:scheduled,on_sale,finished,cancelled'],
        ]);

        $data['status'] = $data['status'] ?? EventDateStatus::SCHEDULED->value;

        $date = $event->dates()->create($data);

        return response()->json($date, 201);
    }

    public function update(Request $request, Event $event, EventDate $date)
    {
        $this->ensureCanManage($event, $request->user());

        $data = $request->validate([
            'starts_at' => ['sometimes', 'required', 'date'],
            'ends_at'   => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status'    => ['sometimes', 'required', 'string', 'in:scheduled,on_sale,finished,cancelled'],
        ]);

        $date->update($data);

        return response()->json($date);
    }

    public function destroy(Request $request, Event $event, EventDate $date)
    {
        $this->ensureCanManage($event, $request->user());

        $date->delete();

        return response()->json(['message' => 'Fecha eliminada.']);
    }
}
