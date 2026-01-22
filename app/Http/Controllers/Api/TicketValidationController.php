<?php

namespace App\Http\Controllers\Api;

use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketValidationController extends Controller
{
    protected function ensureCanValidate($user): void
    {
        if (! in_array($user->role, [UserRole::ADMIN, UserRole::GESTOR], true)) {
            abort(403, 'No autorizado.');
        }
    }

    // POST /api/tickets/validate
    public function validateTicket(Request $request)
    {
        $user = $request->user();
        $this->ensureCanValidate($user);

        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $code = $data['code'];

        $result = DB::transaction(function () use ($code) {
            $ticket = Ticket::with('ticketCategory.eventDate.event', 'orderItem.order.user')
                ->lockForUpdate()
                ->where('code', $code)
                ->first();

            if (! $ticket) {
                return [
                    'valid'  => false,
                    'reason' => 'Ticket no encontrado.',
                    'ticket' => null,
                ];
            }

            if ($ticket->status !== TicketStatus::ISSUED) {
                return [
                    'valid'  => false,
                    'reason' => 'Ticket no está disponible (ya usado o cancelado).',
                    'ticket' => $ticket,
                ];
            }

            $ticket->status = TicketStatus::USED;
            $ticket->used_at = now();
            $ticket->save();

            return [
                'valid'  => true,
                'reason' => null,
                'ticket' => $ticket,
            ];
        });

        return response()->json($result);
    }
}
