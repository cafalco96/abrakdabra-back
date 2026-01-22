<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'role'      => UserRole::BUYER,
            'is_active' => true,
        ]);

        // dispara evento para verificación de email
        event(new Registered($user));

        // opcional: si quieres que pueda navegar algo antes de verificar,
        // puedes darle token, pero el backend debe exigir email verificado en zonas críticas
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas.'], 422);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Usuario inactivo.'], 403);
        }

        // Opcional: exigir email verificado antes de emitir token
        if (! $user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Debe verificar su correo.'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout correcto.']);
    }

    public function deactivateMe(Request $request)
    {
        $user = $request->user();

        $user->is_active = false;
        $user->save();

        // Opcional: invalidar todos los tokens
        $user->tokens()->delete();

        return response()->json(['message' => 'Cuenta desactivada']);
    }

    public function updateMe(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $emailChanged = $data['email'] !== $user->email;

        $user->name = $data['name'];
        $user->email = $data['email'];

        if ($emailChanged) {
            $user->email_verified_at = null;
            $user->sendEmailVerificationNotification();
        }

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return response()->json($user);
    }
}
