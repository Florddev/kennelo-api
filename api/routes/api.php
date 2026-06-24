<?php

declare(strict_types=1);

use App\Http\Controllers\Activity\ActivityAvailabilityController;
use App\Http\Controllers\Activity\ActivityController;
use App\Http\Controllers\Activity\ActivityCycleController;
use App\Http\Controllers\Activity\ActivityDashboardController;
use App\Http\Controllers\Activity\ActivityImageController;
use App\Http\Controllers\Activity\StripeConnectController;
use App\Http\Controllers\Booking\ActivityBookingController;
use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Conversation\ActivityConversationController;
use App\Http\Controllers\Conversation\ConversationController;
use App\Http\Controllers\Conversation\MessageController;
use App\Http\Controllers\Explore\ExploreController;
use App\Http\Controllers\Hosting\HostScanController;
use App\Http\Controllers\PaymentMethod\PaymentMethodController;
use App\Http\Controllers\Pet\AnimalTypeController;
use App\Http\Controllers\Pet\PetAttributeController;
use App\Http\Controllers\Pet\PetBroadcastController;
use App\Http\Controllers\Pet\PetByMicrochipController;
use App\Http\Controllers\Pet\PetController;
use App\Http\Controllers\Pet\PetImageController;
use App\Http\Controllers\Pet\PetReviewController;
use App\Http\Controllers\Review\ActivityReviewController;
use App\Http\Controllers\Review\Admin\ReviewReportController as AdminReviewReportController;
use App\Http\Controllers\Review\BookingReviewController;
use App\Http\Controllers\Review\MyReviewController;
use App\Http\Controllers\Review\ReviewController;
use App\Http\Controllers\Review\ReviewCriteriaController;
use App\Http\Controllers\Review\ReviewReportController;
use App\Http\Controllers\Review\ReviewResponseController;
use App\Http\Controllers\Review\UserReviewController;
use App\Http\Controllers\Scanner\ScannerController;
use App\Http\Controllers\Scanner\ScannerScanController;
use App\Http\Controllers\Stripe\StripeWebhookController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\UserStripeController;
use Illuminate\Support\Facades\Route;

Route::get('/test', [TestController::class, 'index']);

Route::get('/animal-types', [AnimalTypeController::class, 'index']);
Route::post('/pets/broadcast', [PetBroadcastController::class, 'broadcast']);

Route::get('/explore/activities', [ExploreController::class, 'activities']);
Route::get('/explore/activities/sections/{sectionId}', [ExploreController::class, 'sectionPage']);
Route::get('/explore/search', [ExploreController::class, 'search']);
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle']);

Route::middleware(['auth.jwt'])->group(function () {
    // Activities
    Route::apiResource('activities', ActivityController::class);
    Route::put('/activities/{activity}/collaborators/{user}/permissions', [ActivityController::class, 'syncCollaboratorPermissions']);
    Route::get('/activities/{activity}/dashboard', [ActivityDashboardController::class, 'show']);
    Route::get('/activities/{activity}/availabilities', [ActivityAvailabilityController::class, 'index']);
    Route::get('/activities/{activity}/availabilities/range', [ActivityAvailabilityController::class, 'range']);
    Route::post('/activities/{activity}/availabilities', [ActivityAvailabilityController::class, 'store']);
    Route::post('/activities/{activity}/availabilities/bulk', [ActivityAvailabilityController::class, 'bulk']);
    Route::put('/activities/{activity}/availabilities/{availability}', [ActivityAvailabilityController::class, 'update']);
    Route::delete('/activities/{activity}/availabilities/{availability}', [ActivityAvailabilityController::class, 'destroy']);
    Route::get('/activities/{activity}/cycle-settings', [ActivityCycleController::class, 'settingsIndex']);
    Route::get('/activities/{activity}/cycles', [ActivityCycleController::class, 'index']);
    Route::post('/activities/{activity}/cycles', [ActivityCycleController::class, 'store']);
    Route::put('/activities/{activity}/cycles/{cycle}', [ActivityCycleController::class, 'update']);
    Route::delete('/activities/{activity}/cycles/{cycle}', [ActivityCycleController::class, 'destroy']);
    Route::put('/activities/{activity}/cycles/{cycle}/settings', [ActivityCycleController::class, 'settings']);
    Route::put('/activities/{activity}/cycles/{cycle}/closed-week-days', [ActivityCycleController::class, 'closedWeekDays']);
    Route::post('/activities/{activity}/avatar', [ActivityImageController::class, 'uploadAvatar']);
    Route::get('/activities/{activity}/images', [ActivityImageController::class, 'index']);
    Route::post('/activities/{activity}/images', [ActivityImageController::class, 'store']);
    Route::post('/activities/{activity}/images/bulk', [ActivityImageController::class, 'storeBulk']);
    Route::delete('/activities/{activity}/images/{media}', [ActivityImageController::class, 'destroy']);

    Route::post('/activities/{activity}/stripe/onboarding-link', [StripeConnectController::class, 'onboardingLink']);
    Route::get('/activities/{activity}/stripe/status', [StripeConnectController::class, 'status']);

    // Bookings (user)
    Route::post('/bookings/quote', [BookingController::class, 'quote']);
    Route::apiResource('bookings', BookingController::class)->only(['index', 'show', 'store']);
    Route::put('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);

    // Bookings (activity)
    Route::get('/activities/{activity}/bookings', [ActivityBookingController::class, 'index']);
    Route::put('/activities/{activity}/bookings/{booking}/confirm', [ActivityBookingController::class, 'confirm']);
    Route::put('/activities/{activity}/bookings/{booking}/cancel', [ActivityBookingController::class, 'cancel']);
    Route::put('/activities/{activity}/bookings/{booking}/complete', [ActivityBookingController::class, 'complete']);

    // Pets
    Route::get('/pets/by-microchip/{microchipNumber}', [PetByMicrochipController::class, 'show']);
    Route::apiResource('pets', PetController::class);
    Route::put('/pets/{pet}/attributes', [PetAttributeController::class, 'upsert']);
    Route::post('/pets/{pet}/avatar', [PetImageController::class, 'uploadAvatar']);
    Route::get('/pets/{pet}/images', [PetImageController::class, 'index']);
    Route::post('/pets/{pet}/images', [PetImageController::class, 'store']);
    Route::post('/pets/{pet}/images/bulk', [PetImageController::class, 'storeBulk']);
    Route::delete('/pets/{pet}/images/{media}', [PetImageController::class, 'destroy']);
    Route::get('/pets/{pet}/reviews', [PetReviewController::class, 'index']);

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

    // Conversations (activity)
    Route::get('/activities/{activity}/conversations', [ActivityConversationController::class, 'index']);

    // Messages
    Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index']);
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store']);
    Route::put('/conversations/{conversation}/messages/read', [MessageController::class, 'markAsRead']);

    // Reviews
    Route::get('/review-criteria', [ReviewCriteriaController::class, 'index']);
    Route::get('/activities/{activity}/reviews', [ActivityReviewController::class, 'index']);
    Route::get('/users/{user}/reviews', [UserReviewController::class, 'index']);
    Route::get('/reviews/{review}', [ReviewController::class, 'show']);
    Route::post('/bookings/{booking}/reviews', [BookingReviewController::class, 'store']);
    Route::post('/reviews/{review}/response', [ReviewResponseController::class, 'store']);
    Route::post('/reviews/{review}/reports', [ReviewReportController::class, 'store']);
    Route::get('/user/reviews/given', [MyReviewController::class, 'given']);
    Route::get('/user/reviews/received', [MyReviewController::class, 'received']);

    // Admin — review moderation
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/review-reports', [AdminReviewReportController::class, 'index']);
        Route::put('/admin/review-reports/{report}', [AdminReviewReportController::class, 'update']);
    });

    // Scanners
    Route::get('/user/scanners', [ScannerController::class, 'index']);
    Route::post('/user/scanners', [ScannerController::class, 'store']);
    Route::put('/user/scanners/{scanner}', [ScannerController::class, 'update']);
    Route::delete('/user/scanners/{scanner}', [ScannerController::class, 'destroy']);
    Route::get('/user/scanner-scans', [ScannerScanController::class, 'index']);

    // Hosting — scan
    Route::get('/hosting/scan-lookup/{microchipNumber}', [HostScanController::class, 'show']);
    Route::get('/hosting/in-care-pets', [HostScanController::class, 'inCare']);
    Route::put('/hosting/pets/{pet}/microchip', [HostScanController::class, 'assignMicrochip']);
    Route::post('/hosting/bookings/{booking}/conversation', [HostScanController::class, 'conversation']);

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

    Route::get('/me/payment-methods', [PaymentMethodController::class, 'index']);
    Route::post('/me/payment-methods/setup-intent', [PaymentMethodController::class, 'setupIntent']);
    Route::post('/me/payment-methods/setup-checkout-session', [PaymentMethodController::class, 'setupCheckoutSession']);
    Route::put('/me/payment-methods/{id}/default', [PaymentMethodController::class, 'setDefault']);
    Route::delete('/me/payment-methods/{id}', [PaymentMethodController::class, 'destroy']);

    Route::post('/users/me/stripe/account-session', [UserStripeController::class, 'accountSession']);
    Route::get('/users/me/stripe/status', [UserStripeController::class, 'status']);
});

require __DIR__.'/auth.php';
