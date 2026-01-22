<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request): ?string
    {
        // Si es una petición de API o espera JSON, devolver null para que responda 401
        if ($request->expectsJson() || $request->is('api/*')) {
            return null;
        }

        // Si es para verificación de email, redirigir al frontend login
        if ($request->is('email/verify/*')) {
            return config('app.frontend_url') . '/login?error=unauthorized';
        }

        // Para cualquier otra ruta no autenticada, redirigir al frontend
        return config('app.frontend_url') . '/login';
    }
}
