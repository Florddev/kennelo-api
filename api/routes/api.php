<?php

declare(strict_types=1);

use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Booking\EstablishmentBookingController;
use App\Http\Controllers\Conversation\ConversationController;
use App\Http\Controllers\Conversation\EstablishmentConversationController;
use App\Http\Controllers\Conversation\MessageController;
use App\Http\Controllers\Establishment\EstablishmentAvailabilityController;
use App\Http\Controllers\Establishment\EstablishmentCapacityController;
use App\Http\Controllers\Establishment\EstablishmentController;
use App\Http\Controllers\Establishment\EstablishmentDashboardController;
use App\Http\Controllers\Pet\AnimalTypeController;
use App\Http\Controllers\Pet\PetAttributeController;
use App\Http\Controllers\Pet\PetController;
use App\Http\Controllers\Pet\PetImageController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/test', [TestController::class, 'index']);
Route::get('/animal-types', [AnimalTypeController::class, 'index']);

Route::middleware(['auth.jwt'])->group(function () {
    // Establishments
    Route::apiResource('establishments', EstablishmentController::class);
    Route::put('/establishments/{establishment}/collaborators/{user}/permissions', [EstablishmentController::class, 'syncCollaboratorPermissions']);
    Route::get('/establishments/{establishment}/dashboard', [EstablishmentDashboardController::class, 'show']);
    Route::get('/establishments/{establishment}/availabilities', [EstablishmentAvailabilityController::class, 'index']);
    Route::get('/establishments/{establishment}/availabilities/range', [EstablishmentAvailabilityController::class, 'range']);
    Route::post('/establishments/{establishment}/availabilities', [EstablishmentAvailabilityController::class, 'store']);
    Route::post('/establishments/{establishment}/availabilities/bulk', [EstablishmentAvailabilityController::class, 'bulk']);
    Route::put('/establishments/{establishment}/availabilities/{availability}', [EstablishmentAvailabilityController::class, 'update']);
    Route::delete('/establishments/{establishment}/availabilities/{availability}', [EstablishmentAvailabilityController::class, 'destroy']);
    Route::get('/establishments/{establishment}/capacities', [EstablishmentCapacityController::class, 'index']);
    Route::post('/establishments/{establishment}/capacities', [EstablishmentCapacityController::class, 'store']);
    Route::put('/establishments/{establishment}/capacities/{capacity}', [EstablishmentCapacityController::class, 'update']);
    Route::delete('/establishments/{establishment}/capacities/{capacity}', [EstablishmentCapacityController::class, 'destroy']);

    // Bookings (user)
    Route::apiResource('bookings', BookingController::class)->only(['index', 'show', 'store']);
    Route::put('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);

    // Bookings (establishment)
    Route::get('/establishments/{establishment}/bookings', [EstablishmentBookingController::class, 'index']);
    Route::put('/establishments/{establishment}/bookings/{booking}/confirm', [EstablishmentBookingController::class, 'confirm']);
    Route::put('/establishments/{establishment}/bookings/{booking}/cancel', [EstablishmentBookingController::class, 'cancel']);
    Route::put('/establishments/{establishment}/bookings/{booking}/complete', [EstablishmentBookingController::class, 'complete']);

    // Pets
    Route::apiResource('pets', PetController::class);
    Route::put('/pets/{pet}/attributes', [PetAttributeController::class, 'upsert']);
    Route::post('/pets/{pet}/avatar', [PetImageController::class, 'uploadAvatar']);
    Route::get('/pets/{pet}/images', [PetImageController::class, 'index']);
    Route::post('/pets/{pet}/images', [PetImageController::class, 'store']);
    Route::delete('/pets/{pet}/images/{media}', [PetImageController::class, 'destroy']);

    // Users (admin)
    Route::apiResource('users', UserController::class)->only(['index', 'show', 'update']);
    Route::put('/users/{id}/status', [UserController::class, 'updateStatus']);
    Route::put('/users/{id}/roles', [UserController::class, 'assignRoles']);
    Route::delete('/users/{id}/roles/{role}', [UserController::class, 'removeRole']);
    Route::put('/users/{id}/identity-verification', [UserController::class, 'reviewIdentityVerification']);
    Route::delete('/users/{id}', [UserController::class, 'adminDestroy']);

    // Conversations (user)
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/unread-count', [ConversationController::class, 'unreadCount']);
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
    Route::post('/bookings/{booking}/conversation', [ConversationController::class, 'storeForBooking']);

    // Conversations (establishment)
    Route::get('/establishments/{establishment}/conversations', [EstablishmentConversationController::class, 'index']);

    // Messages
    Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index']);
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store']);
    Route::put('/conversations/{conversation}/messages/read', [MessageController::class, 'markAsRead']);

    // Current user
    Route::get('/user', [UserController::class, 'getCurrentUser']);
    Route::put('/user/locale', [UserController::class, 'updateLocale']);
    Route::put('/user/profile', [UserController::class, 'updateProfile']);
    Route::post('/user/avatar', [UserController::class, 'uploadAvatar']);
    Route::put('/user/password', [UserController::class, 'changePassword']);
    Route::put('/user/email', [UserController::class, 'changeEmail']);
    Route::put('/user/address', [UserController::class, 'upsertAddress']);
    Route::delete('/user/address', [UserController::class, 'destroyAddress']);
    Route::get('/user/identity-verification', [UserController::class, 'getIdentityVerification']);
    Route::post('/user/identity-verification', [UserController::class, 'submitIdentityVerification']);
    Route::delete('/user', [UserController::class, 'destroy']);
});

require __DIR__.'/auth.php';
