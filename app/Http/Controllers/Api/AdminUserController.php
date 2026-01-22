<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminUserController extends Controller
{
    protected function ensureIsAdmin(?User $user): void
    {
        if (! $user || $user->role !== UserRole::ADMIN) {
            abort(403, 'No autorizado.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureIsAdmin($request->user());

        $users = User::orderByDesc('created_at')->paginate(15);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $this->ensureIsAdmin($request->user());

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'confirmed', Password::min(8)],
            'role'      => ['required', 'string', 'in:admin,gestor,buyer'],
            'is_active' => ['boolean'],
        ]);

        $user = User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'password'          => Hash::make($data['password']),
            'role'              => $data['role'],
            'is_active'         => $data['is_active'] ?? true,
            'email_verified_at' => now(), // marcado como verificado
        ]);

        return response()->json($user, 201);
    }

    public function show(Request $request, User $user)
    {
        $this->ensureIsAdmin($request->user());

        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $this->ensureIsAdmin($request->user());

        $data = $request->validate([
            'name'      => ['sometimes', 'required', 'string', 'max:255'],
            'email'     => ['sometimes', 'required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password'  => ['nullable', 'confirmed', Password::min(8)],
            'role'      => ['sometimes', 'required', 'string', 'in:admin,gestor,buyer'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        // manejar password opcional: solo si viene y no está vacío
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json($user);
    }

    public function destroy(Request $request, User $user)
    {
        $this->ensureIsAdmin($request->user());

        $user->delete(); // si User usa SoftDeletes, será soft delete

        return response()->json(['message' => 'Usuario eliminado.']);
    }
}
