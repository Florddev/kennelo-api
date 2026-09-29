<?php

declare(strict_types=1);

use App\Http\Controllers\Activity\ActivityController;
use App\Http\Controllers\Activity\ActivityDocumentController;
use App\Http\Controllers\Activity\ActivityImageController;
use App\Http\Controllers\Activity\ActivityScheduleController;
use App\Http\Controllers\Activity\ActivityTravelFeeController;
use App\Http\Controllers\Agenda\AgendaController;
use App\Http\Controllers\Agenda\ResourceController;
use App\Http\Controllers\Agenda\ResourceScheduleController;
use App\Http\Controllers\Agenda\ResourceUnavailabilityController;
use App\Http\Controllers\Billing\BillingMandateController;
use App\Http\Controllers\Billing\OrganizationInvoiceController;
use App\Http\Controllers\Booking\ActivityBookingController;
use App\Http\Controllers\Booking\BookingItemController;
use App\Http\Controllers\Booking\BookingOperationController;
use App\Http\Controllers\Catalog\ActivityServiceController;
use App\Http\Controllers\Catalog\ServiceController;
use App\Http\Controllers\Catalog\ServicePackageItemController;
use App\Http\Controllers\Catalog\ServicePriceController;
use App\Http\Controllers\Conversation\ActivityConversationController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Hosting\InCarePetController;
use App\Http\Controllers\Organization\CompanyLookupController;
use App\Http\Controllers\Organization\InvitationController;
use App\Http\Controllers\Organization\OrganizationController;
use App\Http\Controllers\Organization\OrganizationMemberController;
use App\Http\Controllers\Organization\OrganizationMemberRoleController;
use App\Http\Controllers\Organization\OrganizationOwnerController;
use App\Http\Controllers\Pricing\ActivityPricingController;
use App\Http\Controllers\Pricing\PricingPeriodController;
use App\Http\Controllers\Stay\UnitTypeController;
use App\Http\Controllers\Stripe\StripeAccountController;
use App\Http\Controllers\Subscription\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Espace de gestion des pros. Chaque action est autorisée par les droits du membre dans l'entreprise
// (App\Services\Organization\OrganizationPermissions) ; une personne extérieure reçoit une 404.

// Organizations
Route::get('/organizations/company-lookup/{siren}', CompanyLookupController::class)
    ->where('siren', '[0-9]{9}')
    ->middleware('throttle:20,1')
    ->name('organizations.company-lookup.show');
Route::apiResource('organizations', OrganizationController::class);
Route::put('/organizations/{organization}/owner', [OrganizationOwnerController::class, 'update'])->name('organizations.owner.update');

// Team
Route::scopeBindings()->group(function () {
    Route::get('/organizations/{organization}/members', [OrganizationMemberController::class, 'index'])->name('organizations.members.index');
    Route::post('/organizations/{organization}/members', [OrganizationMemberController::class, 'store'])->middleware('throttle:20,1')->name('organizations.members.store');
    Route::delete('/organizations/{organization}/members/{member}', [OrganizationMemberController::class, 'destroy'])->name('organizations.members.destroy');
    Route::put('/organizations/{organization}/members/{member}/roles', [OrganizationMemberRoleController::class, 'update'])->name('organizations.members.roles.update');
});

// Invitations received
Route::get('/user/invitations', [InvitationController::class, 'index'])->name('user.invitations.index');
Route::post('/user/invitations/{member}/accept', [InvitationController::class, 'accept'])->name('user.invitations.accept');
Route::post('/user/invitations/{member}/decline', [InvitationController::class, 'decline'])->name('user.invitations.decline');

// Subscription
Route::get('/organizations/{organization}/subscription', [SubscriptionController::class, 'show'])->name('organizations.subscription.show');
Route::post('/organizations/{organization}/subscription/checkout', [SubscriptionController::class, 'checkout'])->name('organizations.subscription.checkout');
Route::delete('/organizations/{organization}/subscription', [SubscriptionController::class, 'destroy'])->name('organizations.subscription.destroy');
Route::get('/organizations/{organization}/subscription/invoices', [SubscriptionController::class, 'invoices'])->name('organizations.subscription.invoices.index');

// Billing: mandate given to Kennelo, invoices issued and received
Route::post('/organizations/{organization}/billing-mandate', [BillingMandateController::class, 'store'])->name('organizations.billing-mandate.store');
Route::get('/organizations/{organization}/invoices', [OrganizationInvoiceController::class, 'index'])->name('organizations.invoices.index');

// Stripe Connect
Route::post('/organizations/{organization}/stripe/account-session', [StripeAccountController::class, 'store'])->name('organizations.stripe.account-session.store');
Route::get('/organizations/{organization}/stripe/status', [StripeAccountController::class, 'show'])->name('organizations.stripe.status.show');

// Activities
Route::get('/organizations/{organization}/activities', [ActivityController::class, 'index'])->name('organizations.activities.index');
Route::post('/organizations/{organization}/activities', [ActivityController::class, 'store'])->name('organizations.activities.store');
Route::patch('/activities/{activity}', [ActivityController::class, 'update'])->name('activities.update');
Route::delete('/activities/{activity}', [ActivityController::class, 'destroy'])->name('activities.destroy');
Route::post('/activities/{activity}/images', [ActivityImageController::class, 'store'])->name('activities.images.store');

// Opening hours and exceptions
Route::put('/activities/{activity}/opening-hours', [ActivityScheduleController::class, 'updateOpeningHours'])->name('activities.opening-hours.update');
Route::get('/activities/{activity}/availabilities', [ActivityScheduleController::class, 'indexAvailabilities'])->name('activities.availabilities.index');
Route::post('/activities/{activity}/availabilities', [ActivityScheduleController::class, 'storeAvailabilities'])->name('activities.availabilities.store');
Route::put('/activities/{activity}/travel-fees', [ActivityTravelFeeController::class, 'update'])->name('activities.travel-fees.update');

// Documents
Route::get('/activities/{activity}/documents', [ActivityDocumentController::class, 'index'])->name('activities.documents.index');
Route::post('/activities/{activity}/documents', [ActivityDocumentController::class, 'store'])->middleware('throttle:20,1')->name('activities.documents.store');

Route::scopeBindings()->group(function () {
    Route::delete('/activities/{activity}/images/{media:uuid}', [ActivityImageController::class, 'destroy'])->name('activities.images.destroy');
    Route::delete('/activities/{activity}/availabilities/{availability}', [ActivityScheduleController::class, 'destroyAvailability'])->name('activities.availabilities.destroy');
    Route::get('/activities/{activity}/documents/{document}/file', [ActivityDocumentController::class, 'file'])->name('activities.documents.file.show');
});

// Catalog
Route::get('/organizations/{organization}/services', [ServiceController::class, 'index'])->name('organizations.services.index');
Route::post('/organizations/{organization}/services', [ServiceController::class, 'store'])->name('organizations.services.store');
Route::scopeBindings()->group(function () {
    Route::patch('/organizations/{organization}/services/{service}', [ServiceController::class, 'update'])->name('organizations.services.update');
    Route::delete('/organizations/{organization}/services/{service}', [ServiceController::class, 'destroy'])->name('organizations.services.destroy');
    Route::put('/organizations/{organization}/services/{service}/prices', [ServicePriceController::class, 'update'])->name('organizations.services.prices.update');
    Route::put('/organizations/{organization}/services/{service}/package-items', [ServicePackageItemController::class, 'update'])->name('organizations.services.package-items.update');
});

// Offer of an activity (the service must belong to the same organization, checked by ActivityPolicy::offer)
Route::put('/activities/{activity}/services/{service}', [ActivityServiceController::class, 'update'])->name('activities.services.update');
Route::delete('/activities/{activity}/services/{service}', [ActivityServiceController::class, 'destroy'])->name('activities.services.destroy');

// Pricing periods of the organization
Route::get('/organizations/{organization}/pricing-periods', [PricingPeriodController::class, 'index'])->name('organizations.pricing-periods.index');
Route::post('/organizations/{organization}/pricing-periods', [PricingPeriodController::class, 'store'])->name('organizations.pricing-periods.store');
Route::scopeBindings()->group(function () {
    Route::patch('/organizations/{organization}/pricing-periods/{pricingPeriod}', [PricingPeriodController::class, 'update'])->name('organizations.pricing-periods.update');
    Route::delete('/organizations/{organization}/pricing-periods/{pricingPeriod}', [PricingPeriodController::class, 'destroy'])->name('organizations.pricing-periods.destroy');
});

// Units of a stay
Route::post('/activities/{activity}/unit-types', [UnitTypeController::class, 'store'])->name('activities.unit-types.store');
Route::scopeBindings()->group(function () {
    Route::patch('/activities/{activity}/unit-types/{unitType}', [UnitTypeController::class, 'update'])->name('activities.unit-types.update');
    Route::delete('/activities/{activity}/unit-types/{unitType}', [UnitTypeController::class, 'destroy'])->name('activities.unit-types.destroy');
});

// Pricing of an activity (the period must belong to the same organization, checked by ActivityPolicy::price)
Route::get('/activities/{activity}/pricing-periods', [ActivityPricingController::class, 'index'])->name('activities.pricing-periods.index');
Route::put('/activities/{activity}/pricing-periods/{pricingPeriod}', [ActivityPricingController::class, 'updateSetting'])->name('activities.pricing-periods.update');
Route::put('/activities/{activity}/pricing-periods/{pricingPeriod}/prices', [ActivityPricingController::class, 'updatePrices'])->name('activities.pricing-periods.prices.update');

// Bookings of an activity
Route::get('/activities/{activity}/bookings', [ActivityBookingController::class, 'index'])->name('activities.bookings.index');
Route::scopeBindings()->group(function () {
    Route::get('/activities/{activity}/bookings/{booking}', [ActivityBookingController::class, 'show'])->name('activities.bookings.show');
    Route::post('/activities/{activity}/bookings/{booking}/confirm', [ActivityBookingController::class, 'confirm'])->name('activities.bookings.confirm');
    Route::post('/activities/{activity}/bookings/{booking}/reject', [ActivityBookingController::class, 'reject'])->name('activities.bookings.reject');
    Route::post('/activities/{activity}/bookings/{booking}/cancel', [ActivityBookingController::class, 'cancel'])->name('activities.bookings.cancel');
    Route::post('/activities/{activity}/bookings/{booking}/items', [BookingItemController::class, 'store'])->name('activities.bookings.items.store');
    Route::delete('/activities/{activity}/bookings/{booking}/items/{item}', [BookingItemController::class, 'destroy'])->name('activities.bookings.items.destroy');
    Route::post('/activities/{activity}/bookings/{booking}/items/{item}/schedule', [BookingItemController::class, 'schedule'])->name('activities.bookings.items.schedule');
    Route::get('/activities/{activity}/bookings/{booking}/operations', [BookingOperationController::class, 'index'])->name('activities.bookings.operations.index');
});

// Conversations of an activity (messages.reply)
Route::get('/activities/{activity}/conversations', [ActivityConversationController::class, 'index'])->name('activities.conversations.index');

// Dashboards and pets in care
Route::get('/activities/{activity}/dashboard', [DashboardController::class, 'activity'])->name('activities.dashboard.show');
Route::get('/organizations/{organization}/dashboard', [DashboardController::class, 'organization'])->name('organizations.dashboard.show');
Route::get('/organizations/{organization}/in-care-pets', [InCarePetController::class, 'index'])->name('organizations.in-care-pets.index');

// Agenda: resources of the organization, their absences and blocks
Route::get('/organizations/{organization}/resources', [ResourceController::class, 'index'])->name('organizations.resources.index');
Route::post('/organizations/{organization}/resources', [ResourceController::class, 'store'])->name('organizations.resources.store');
Route::scopeBindings()->group(function () {
    Route::patch('/organizations/{organization}/resources/{resource}', [ResourceController::class, 'update'])->name('organizations.resources.update');
    Route::delete('/organizations/{organization}/resources/{resource}', [ResourceController::class, 'destroy'])->name('organizations.resources.destroy');
    Route::post('/organizations/{organization}/resources/{resource}/absences', [ResourceUnavailabilityController::class, 'store'])->name('organizations.resources.absences.store');
    Route::delete('/organizations/{organization}/resources/{resource}/absences/{resourceBooking}', [ResourceUnavailabilityController::class, 'destroy'])->name('organizations.resources.absences.destroy');
});
Route::get('/organizations/{organization}/agenda', [AgendaController::class, 'show'])->name('organizations.agenda.show');

// Schedule of a resource in an activity (the resource must belong to the same organization, checked by ActivityPolicy::schedule)
Route::put('/activities/{activity}/resources/{resource}/schedules', [ResourceScheduleController::class, 'update'])->name('activities.resources.schedules.update');
