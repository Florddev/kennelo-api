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

Route::get('/animal-types', [AnimalTypeController::class, 'index'])->name('animal-types.index');
Route::get('/professions', [ProfessionController::class, 'index'])->name('professions.index');

// Public, avec ou sans compte : l'utilisateur connecté voit en plus ses favoris et les prix pour ses animaux.
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/explore/activities', [ExploreController::class, 'activities'])->name('explore.activities.index');
    Route::get('/explore/activities/sections/{section}', [ExploreController::class, 'section'])->whereIn('section', ExploreService::SECTIONS)->name('explore.activities.sections.show');
    Route::get('/explore/search', [ExploreController::class, 'search'])->name('explore.search');
});
Route::get('/activities/{activity}', [ActivityController::class, 'show'])->name('activities.show');
Route::get('/activities/{activity}/services', [ActivityServiceController::class, 'index'])->name('activities.services.index');
Route::get('/activities/{activity}/unit-types', [UnitTypeController::class, 'index'])->name('activities.unit-types.index');
Route::get('/activities/{activity}/travel-fees', [ActivityTravelFeeController::class, 'index'])->name('activities.travel-fees.index');
Route::get('/activities/{activity}/price-calendar', [ActivityPricingController::class, 'calendar'])->middleware('throttle:60,1')->name('activities.price-calendar.show');
Route::get('/activities/{activity}/slots', [SlotController::class, 'index'])->middleware('throttle:60,1')->name('activities.slots.index');
Route::get('/activities/{activity}/reviews', [ReviewController::class, 'forActivity'])->middleware('throttle:60,1')->name('activities.reviews.index');

Route::post('/webhooks/stripe', StripeWebhookController::class)->middleware('throttle:120,1')->name('webhooks.stripe');

Route::middleware(['auth:sanctum', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(base_path('routes/admin.php'));

Route::middleware('auth:sanctum')
    ->group(base_path('routes/management.php'));

Route::middleware('auth:sanctum')->group(function () {
    // Subscriptions
    Route::get('/plans', [SubscriptionController::class, 'plans'])->name('plans.index');

    // Pets
    Route::get('/animal-breeds', [AnimalBreedController::class, 'index'])->name('animal-breeds.index');
    Route::apiResource('pets', PetController::class);
    Route::put('/pets/{pet}/attributes', [PetAttributeController::class, 'upsert'])->name('pets.attributes.update');
    Route::post('/pets/{pet}/avatar', [PetImageController::class, 'uploadAvatar'])->name('pets.avatar.store');
    Route::get('/pets/{pet}/images', [PetImageController::class, 'index'])->name('pets.images.index');
    Route::post('/pets/{pet}/images', [PetImageController::class, 'store'])->name('pets.images.store');
    Route::post('/pets/{pet}/images/bulk', [PetImageController::class, 'storeBulk'])->name('pets.images.bulk.store');
    Route::delete('/pets/{pet}/images/{media:uuid}', [PetImageController::class, 'destroy'])->scopeBindings()->name('pets.images.destroy');

    // Bookings (client)
    Route::post('/bookings/quote', [BookingController::class, 'quote'])->middleware('throttle:60,1')->name('bookings.quote');
    Route::post('/bookings', [BookingController::class, 'store'])->middleware('throttle:10,1')->name('bookings.store');
    Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::post('/bookings/{booking}/payments/{payment}/confirm', [BookingPaymentController::class, 'confirm'])->scopeBindings()->name('bookings.payments.confirm');

    // Invoices (received as a client; one invoice for anyone allowed to read it)
    Route::get('/user/invoices', [InvoiceController::class, 'index'])->name('user.invoices.index');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf.show');

    // Conversations (client side; the team reads its inbox by /activities/{activity}/conversations)
    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::get('/conversations/unread-count', [ConversationController::class, 'unreadCount'])->name('conversations.unread-count.show');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::put('/conversations/{conversation}/read', [ConversationController::class, 'read'])->name('conversations.read');
    Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index'])->name('conversations.messages.index');
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])->middleware('throttle:30,1')->name('conversations.messages.store');
    Route::get('/conversations/{conversation}/files/{file}', [MessageController::class, 'file'])
        ->scopeBindings()
        ->name('conversations.files.show');
    Route::post('/activities/{activity}/conversations', [ActivityConversationController::class, 'store'])->middleware('throttle:20,1')->name('activities.conversations.store');
    Route::post('/bookings/{booking}/conversation', [BookingConversationController::class, 'store'])->name('bookings.conversation.store');

    // Reviews (both ways: the client reviews the activity, the team reviews the client)
    Route::post('/bookings/{booking}/reviews', [ReviewController::class, 'store'])->name('bookings.reviews.store');
    Route::get('/user/reviews/given', [ReviewController::class, 'given'])->name('user.reviews.given.index');
    Route::get('/user/reviews/received', [ReviewController::class, 'received'])->name('user.reviews.received.index');
    Route::get('/users/{user}/reviews', [ReviewController::class, 'aboutClient'])->name('users.reviews.index');
    Route::get('/reviews/{review}', [ReviewController::class, 'show'])->name('reviews.show');
    Route::post('/reviews/{review}/response', [ReviewController::class, 'respond'])->name('reviews.response.store');
    Route::post('/reviews/{review}/reports', [ReviewController::class, 'report'])->middleware('throttle:20,1')->name('reviews.reports.store');

    // Favorites
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites/{activity}', [FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('/favorites/{activity}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');

    // Address book
    Route::get('/user/addresses', [UserAddressController::class, 'index'])->name('user.addresses.index');
    Route::post('/user/addresses', [UserAddressController::class, 'store'])->name('user.addresses.store');
    Route::patch('/user/addresses/{address}', [UserAddressController::class, 'update'])->name('user.addresses.update');
    Route::delete('/user/addresses/{address}', [UserAddressController::class, 'destroy'])->name('user.addresses.destroy');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count.show');
    Route::put('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::put('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Users (public profile)
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');

    // Current user
    Route::get('/user', [UserController::class, 'getCurrentUser'])->name('user.show');
    Route::put('/user/locale', [UserController::class, 'updateLocale'])->name('user.locale.update');
    Route::put('/user/profile', [UserController::class, 'updateProfile'])->name('user.profile.update');
    Route::post('/user/avatar', [UserController::class, 'uploadAvatar'])->name('user.avatar.store');
    Route::put('/user/password', [UserController::class, 'changePassword'])->name('user.password.update');
    Route::put('/user/email', [UserController::class, 'changeEmail'])->name('user.email.update');
    Route::delete('/user', [UserController::class, 'destroy'])->name('user.destroy');

    Route::post('/user/two-factor', [TwoFactorAuthenticationController::class, 'store'])->middleware('throttle:6,1')->name('user.two-factor.store');
    Route::post('/user/two-factor/confirm', [TwoFactorAuthenticationController::class, 'confirm'])->middleware('throttle:6,1')->name('user.two-factor.confirm');
    Route::delete('/user/two-factor', [TwoFactorAuthenticationController::class, 'destroy'])->middleware('throttle:6,1')->name('user.two-factor.destroy');
    Route::post('/user/two-factor/recovery-codes', [TwoFactorAuthenticationController::class, 'recoveryCodes'])->middleware('throttle:6,1')->name('user.two-factor.recovery-codes.store');

    // Payment methods (client)
    Route::get('/me/payment-methods', [PaymentMethodController::class, 'index'])->name('me.payment-methods.index');
    Route::post('/me/payment-methods/setup-intent', [PaymentMethodController::class, 'setupIntent'])->name('me.payment-methods.setup-intent.store');
    Route::post('/me/payment-methods/setup-checkout-session', [PaymentMethodController::class, 'setupCheckoutSession'])->name('me.payment-methods.setup-checkout-session.store');
    Route::put('/me/payment-methods/{id}/default', [PaymentMethodController::class, 'setDefault'])->name('me.payment-methods.default.update');
    Route::delete('/me/payment-methods/{id}', [PaymentMethodController::class, 'destroy'])->name('me.payment-methods.destroy');
});

require __DIR__.'/auth.php';
