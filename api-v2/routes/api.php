<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\PaymentMethod\PaymentMethodController;
use App\Http\Controllers\Pet\AnimalBreedController;
use App\Http\Controllers\Pet\AnimalTypeController;
use App\Http\Controllers\Pet\PetAttributeController;
use App\Http\Controllers\Pet\PetController;
use App\Http\Controllers\Pet\PetImageController;
use App\Http\Controllers\Stripe\StripeWebhookController;
use App\Http\Controllers\Subscription\SubscriptionController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/animal-types', [AnimalTypeController::class, 'index']);

Route::post('/webhooks/stripe', StripeWebhookController::class)->middleware('throttle:120,1');

Route::middleware(['auth:sanctum', 'role:admin'])
    ->prefix('admin')
    ->group(base_path('routes/admin.php'));

Route::middleware('auth:sanctum')
    ->group(base_path('routes/management.php'));

Route::middleware('auth:sanctum')->group(function () {
    // Subscriptions
    Route::get('/plans', [SubscriptionController::class, 'plans']);

    // Pets
    Route::get('/animal-breeds', [AnimalBreedController::class, 'index']);
    Route::apiResource('pets', PetController::class);
    Route::put('/pets/{pet}/attributes', [PetAttributeController::class, 'upsert']);
    Route::post('/pets/{pet}/avatar', [PetImageController::class, 'uploadAvatar']);
    Route::get('/pets/{pet}/images', [PetImageController::class, 'index']);
    Route::post('/pets/{pet}/images', [PetImageController::class, 'store']);
    Route::post('/pets/{pet}/images/bulk', [PetImageController::class, 'storeBulk']);
    Route::delete('/pets/{pet}/images/{media:uuid}', [PetImageController::class, 'destroy'])->scopeBindings();

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);

    // Users (public profile)
    Route::get('/users/{user}', [UserController::class, 'show']);

    // Current user
    Route::get('/user', [UserController::class, 'getCurrentUser']);
    Route::put('/user/locale', [UserController::class, 'updateLocale']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/user/avatar', [UserController::class, 'uploadAvatar']);
    Route::put('/user/password', [UserController::class, 'changePassword']);
    Route::put('/user/email', [UserController::class, 'changeEmail']);
    Route::delete('/user', [UserController::class, 'destroy']);

    Route::post('/user/two-factor', [TwoFactorAuthenticationController::class, 'store'])->middleware('throttle:6,1');
    Route::post('/user/two-factor/confirm', [TwoFactorAuthenticationController::class, 'confirm'])->middleware('throttle:6,1');
    Route::delete('/user/two-factor', [TwoFactorAuthenticationController::class, 'destroy'])->middleware('throttle:6,1');
    Route::post('/user/two-factor/recovery-codes', [TwoFactorAuthenticationController::class, 'recoveryCodes'])->middleware('throttle:6,1');

    // Payment methods (client)
    Route::get('/me/payment-methods', [PaymentMethodController::class, 'index']);
    Route::post('/me/payment-methods/setup-intent', [PaymentMethodController::class, 'setupIntent']);
    Route::post('/me/payment-methods/setup-checkout-session', [PaymentMethodController::class, 'setupCheckoutSession']);
    Route::put('/me/payment-methods/{id}/default', [PaymentMethodController::class, 'setDefault']);
    Route::delete('/me/payment-methods/{id}', [PaymentMethodController::class, 'destroy']);
});

require __DIR__.'/auth.php';
