<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\EventController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use App\Http\Controllers\Api\PublicEventController;
use App\Http\Controllers\Api\EventDateController;
use App\Http\Controllers\Api\TicketCategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\TicketValidationController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\EventStatsController;
use App\Http\Controllers\Api\AdminOrderController;
use App\Http\Controllers\Api\OrderDiscountController;
use App\Http\Controllers\Api\AdminDiscountCodeController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Middleware\IsAdmin;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// email verification con middleware signed
Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::findOrFail($id);

    // Verificar que el hash coincida
    if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        return redirect(config('app.frontend_url') . '/auth/login?error=invalid_verification');
    }

    // Verificar si ya está verificado
    if ($user->hasVerifiedEmail()) {
        return redirect(config('app.frontend_url') . '/auth/login?already_verified=1');
    }

    // Marcar como verificado
    if ($user->markEmailAsVerified()) {
        event(new Verified($user));
    }

    return redirect(config('app.frontend_url') . '/auth/login?verified=1');
})->middleware('signed')->name('verification.verify');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me', [AuthController::class, 'updateMe']);
    Route::post('/me/deactivate', [AuthController::class, 'deactivateMe']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Events CRUD protegido
    Route::apiResource('events', EventController::class);

    Route::get('/events/{event}/dates', [EventDateController::class, 'index']);
    Route::post('/events/{event}/dates', [EventDateController::class, 'store']);
    Route::get('/events/{event}/dates/{date}', [EventDateController::class, 'show']);
    Route::put('/events/{event}/dates/{date}', [EventDateController::class, 'update']);
    Route::delete('/events/{event}/dates/{date}', [EventDateController::class, 'destroy']);

    //Tickets
    Route::get('/events/{event}/dates/{date}/ticket-categories', [TicketCategoryController::class, 'index']);
    Route::post('/events/{event}/dates/{date}/ticket-categories', [TicketCategoryController::class, 'store']);
    Route::get('/events/{event}/dates/{date}/ticket-categories/{category}', [TicketCategoryController::class, 'show']);
    Route::put('/events/{event}/dates/{date}/ticket-categories/{category}', [TicketCategoryController::class, 'update']);
    Route::delete('/events/{event}/dates/{date}/ticket-categories/{category}', [TicketCategoryController::class, 'destroy']);
    
    //orders
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders', [OrderController::class, 'index']);      // historial buyer
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/checkout', [OrderController::class, 'checkout']);
    Route::post('/orders/{order}/mark-paid', [OrderController::class, 'markPaid']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
    Route::post('/tickets/validate', [TicketValidationController::class, 'validateTicket']);
    Route::post('/orders/{order}/apply-discount', [OrderDiscountController::class, 'apply']);
    // Gestión de usuarios solo para admin
    Route::middleware(IsAdmin::class)->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::get('/users/{user}', [AdminUserController::class, 'show']);
        Route::put('/users/{user}', [AdminUserController::class, 'update']);
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);
        Route::get('/admin/dashboard', [AdminDashboardController::class, 'index']);
        Route::get('/events/{event}/stats', [EventStatsController::class, 'show']);
        Route::get('/admin/orders', [AdminOrderController::class, 'index']);
        Route::get('/admin/orders/{order}', [AdminOrderController::class, 'show']);
        Route::put('/admin/orders/{order}', [AdminOrderController::class, 'update']);
        Route::apiResource('admin/discount-codes', AdminDiscountCodeController::class);
    });
});
// Rutas públicas de catálogo
Route::get('/public/events', [PublicEventController::class, 'index']);
Route::get('/public/events/{event}', [PublicEventController::class, 'show']);

// Stripe webhook endpoint (public, handled by Stripe signing secret)
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle']);

// Ruta de reseteo password
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])
    ->name('password.email');

Route::post('/reset-password', [PasswordResetController::class, 'reset'])
    ->name('password.update');