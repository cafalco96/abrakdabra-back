<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')
            ->stateless() // si usas SPA sin sesión
            ->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')
            ->stateless()
            ->user();

        // Buscar usuario por email
        $user = User::where('email', $googleUser->getEmail())->first();

        if (! $user) {
            // Crear nuevo buyer con email ya verificado
            $user = User::create([
                'name'              => $googleUser->getName() ?? $googleUser->getNickname() ?? 'Usuario Google',
                'email'             => $googleUser->getEmail(),
                'email_verified_at' => now(), // Google ya verificó el correo
                'password'          => Hash::make(Str::random(32)), // password aleatoria
                'role'              => UserRole::BUYER,
                'is_active'         => true,
            ]);
        }

        if (! $user->is_active) {
            // Aquí puedes redirigir a una URL de error en tu frontend
            return redirect(config('app.frontend_url') . '/login?error=inactive');
        }

        // Crear token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // Redirigir al frontend con el token (ej. como query o fragmento)
        $frontendUrl = config('app.frontend_url') . '/auth/callback?token=' . urlencode($token);

        return redirect($frontendUrl);
    }
}
