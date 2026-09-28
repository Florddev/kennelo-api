<?php

declare(strict_types=1);

use App\Http\Controllers\Activity\ActivityController;
use App\Http\Controllers\Activity\ActivityTravelFeeController;
use App\Http\Controllers\Agenda\SlotController;
use App\Http\Controllers\Auth\TwoFactorAuthenticationController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Booking\BookingPaymentController;
use App\Http\Controllers\Catalog\ActivityServiceController;
use App\Http\Controllers\Conversation\ActivityConversationController;
use App\Http\Controllers\Conversation\BookingConversationController;
use App\Http\Controllers\Conversation\ConversationController;
use App\Http\Controllers\Conversation\MessageController;
use App\Http\Controllers\Explore\ExploreController;
use App\Http\Controllers\Favorite\FavoriteController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\PaymentMethod\PaymentMethodController;
use App\Http\Controllers\Pet\AnimalBreedController;
use App\Http\Controllers\Pet\AnimalTypeController;
use App\Http\Controllers\Pet\PetAttributeController;
use App\Http\Controllers\Pet\PetController;
use App\Http\Controllers\Pet\PetImageController;
use App\Http\Controllers\Pricing\ActivityPricingController;
use App\Http\Controllers\Profession\ProfessionController;
use App\Http\Controllers\Review\ReviewController;
use App\Http\Controllers\Stay\UnitTypeController;
use App\Http\Controllers\Stripe\StripeWebhookController;
use App\Http\Controllers\Subscription\SubscriptionController;
use App\Http\Controllers\User\UserAddressController;
use App\Http\Controllers\User\UserController;
use App\Services\Explore\ExploreService;
use Illuminate\Support\Facades\Route;

Route::get('/animal-types', [AnimalTypeController::class, 'index']);
Route::get('/professions', [ProfessionController::class, 'index']);

// Public, avec ou sans compte : l'utilisateur connecté voit en plus ses favoris et les prix pour ses animaux.
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/explore/activities', [ExploreController::class, 'activities']);
    Route::get('/explore/activities/sections/{section}', [ExploreController::class, 'section'])->whereIn('section', ExploreService::SECTIONS);
    Route::get('/explore/search', [ExploreController::class, 'search']);
});
Route::get('/activities/{activity}', [ActivityController::class, 'show']);
Route::get('/activities/{activity}/services', [ActivityServiceController::class, 'index']);
Route::get('/activities/{activity}/unit-types', [UnitTypeController::class, 'index']);
Route::get('/activities/{activity}/travel-fees', [ActivityTravelFeeController::class, 'index']);
Route::get('/activities/{activity}/price-calendar', [ActivityPricingController::class, 'calendar'])->middleware('throttle:60,1');
Route::get('/activities/{activity}/slots', [SlotController::class, 'index'])->middleware('throttle:60,1');
Route::get('/activities/{activity}/reviews', [ReviewController::class, 'forActivity'])->middleware('throttle:60,1');

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

    // Bookings (client)
    Route::post('/bookings/quote', [BookingController::class, 'quote'])->middleware('throttle:60,1');
    Route::post('/bookings', [BookingController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    Route::post('/bookings/{booking}/payments/{payment}/confirm', [BookingPaymentController::class, 'confirm'])->scopeBindings();

    // Invoices (received as a client; one invoice for anyone allowed to read it)
    Route::get('/user/invoices', [InvoiceController::class, 'index']);
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf']);

    // Conversations (client side; the team reads its inbox by /activities/{activity}/conversations)
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/unread-count', [ConversationController::class, 'unreadCount']);
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show']);
    Route::put('/conversations/{conversation}/read', [ConversationController::class, 'read']);
    Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index']);
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])->middleware('throttle:30,1');
    Route::get('/conversations/{conversation}/files/{file}', [MessageController::class, 'file'])
        ->scopeBindings()
        ->name('conversations.files.show');
    Route::post('/activities/{activity}/conversations', [ActivityConversationController::class, 'store'])->middleware('throttle:20,1');
    Route::post('/bookings/{booking}/conversation', [BookingConversationController::class, 'store']);

    // Reviews (both ways: the client reviews the activity, the team reviews the client)
    Route::post('/bookings/{booking}/reviews', [ReviewController::class, 'store']);
    Route::get('/user/reviews/given', [ReviewController::class, 'given']);
    Route::get('/user/reviews/received', [ReviewController::class, 'received']);
    Route::get('/users/{user}/reviews', [ReviewController::class, 'aboutClient']);
    Route::get('/reviews/{review}', [ReviewController::class, 'show']);
    Route::post('/reviews/{review}/response', [ReviewController::class, 'respond']);
    Route::post('/reviews/{review}/reports', [ReviewController::class, 'report'])->middleware('throttle:20,1');

    // Favorites
    Route::get('/favorites', [FavoriteController::class, 'index']);
    Route::post('/favorites/{activity}', [FavoriteController::class, 'store']);
    Route::delete('/favorites/{activity}', [FavoriteController::class, 'destroy']);

    // Address book
    Route::get('/user/addresses', [UserAddressController::class, 'index']);
    Route::post('/user/addresses', [UserAddressController::class, 'store']);
    Route::patch('/user/addresses/{address}', [UserAddressController::class, 'update']);
    Route::delete('/user/addresses/{address}', [UserAddressController::class, 'destroy']);

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
