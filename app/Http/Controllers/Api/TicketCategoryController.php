<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventDate;
use App\Models\TicketCategory;
use Illuminate\Http\Request;
use App\Enums\UserRole;

class TicketCategoryController extends Controller
{
    protected function ensureCanManage(Event $event, $user): void
    {
        // misma lógica que en EventDateController
        if (! in_array($user->role, [UserRole::ADMIN, UserRole::GESTOR], true)) {
            abort(403, 'No autorizado.');
        }

        if ($user->role === UserRole::GESTOR && $event->created_by !== $user->id) {
            abort(403, 'No autorizado.');
        }
    }

    public function index(Request $request, Event $event, EventDate $date)
    {
        $this->ensureCanManage($event, $request->user());

        return response()->json(
            $date->ticketCategories()->orderBy('price')->get()
        );
    }

    public function show(Request $request, Event $event, EventDate $date, TicketCategory $category)
    {
        $this->ensureCanManage($event, $request->user());

        return response()->json($category);
    }

    public function store(Request $request, Event $event, EventDate $date)
    {
        $this->ensureCanManage($event, $request->user());

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'price'       => ['required', 'numeric', 'min:0'],
            'stock_total' => ['required', 'integer', 'min:1'],
            'status'      => ['nullable', 'string'],
        ]);

        $data['event_date_id'] = $date->id;
        $data['stock_sold'] = 0;
        $data['status'] = $data['status'] ?? 'available';

        $category = TicketCategory::create($data);

        return response()->json($category, 201);
    }

    public function update(Request $request, Event $event, EventDate $date, TicketCategory $category)
    {
        $this->ensureCanManage($event, $request->user());

        $data = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'price'       => ['sometimes', 'required', 'numeric', 'min:0'],
            'stock_total' => ['sometimes', 'required', 'integer', 'min:1'],
            'status'      => ['sometimes', 'required', 'string'],
        ]);

        $category->update($data);

        return response()->json($category);
    }

    public function destroy(Request $request, Event $event, EventDate $date, TicketCategory $category)
    {
        $this->ensureCanManage($event, $request->user());

        $category->delete();

        return response()->json(['message' => 'Categoría eliminada.']);
    }
}
