<?php

declare(strict_types=1);

use App\Http\Controllers\Organization\CompanyLookupController;
use App\Http\Controllers\Organization\InvitationController;
use App\Http\Controllers\Organization\OrganizationController;
use App\Http\Controllers\Organization\OrganizationMemberController;
use App\Http\Controllers\Organization\OrganizationMemberRoleController;
use App\Http\Controllers\Organization\OrganizationOwnerController;
use App\Http\Controllers\Stripe\StripeAccountController;
use App\Http\Controllers\Subscription\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Espace de gestion des pros. Chaque action est autorisée par les droits du membre dans l'entreprise
// (App\Services\Organization\OrganizationPermissions) ; une personne extérieure reçoit une 404.

// Organizations
Route::get('/organizations/company-lookup/{siren}', CompanyLookupController::class)
    ->where('siren', '[0-9]{9}')
    ->middleware('throttle:20,1');
Route::apiResource('organizations', OrganizationController::class);
Route::put('/organizations/{organization}/owner', [OrganizationOwnerController::class, 'update']);

// Team
Route::scopeBindings()->group(function () {
    Route::get('/organizations/{organization}/members', [OrganizationMemberController::class, 'index']);
    Route::post('/organizations/{organization}/members', [OrganizationMemberController::class, 'store'])->middleware('throttle:20,1');
    Route::delete('/organizations/{organization}/members/{member}', [OrganizationMemberController::class, 'destroy']);
    Route::put('/organizations/{organization}/members/{member}/roles', [OrganizationMemberRoleController::class, 'update']);
});

// Invitations received
Route::get('/user/invitations', [InvitationController::class, 'index']);
Route::post('/user/invitations/{member}/accept', [InvitationController::class, 'accept']);
Route::post('/user/invitations/{member}/decline', [InvitationController::class, 'decline']);

// Subscription
Route::get('/organizations/{organization}/subscription', [SubscriptionController::class, 'show']);
Route::post('/organizations/{organization}/subscription/checkout', [SubscriptionController::class, 'checkout']);
Route::delete('/organizations/{organization}/subscription', [SubscriptionController::class, 'destroy']);
Route::get('/organizations/{organization}/subscription/invoices', [SubscriptionController::class, 'invoices']);

// Stripe Connect
Route::post('/organizations/{organization}/stripe/account-session', [StripeAccountController::class, 'store']);
Route::get('/organizations/{organization}/stripe/status', [StripeAccountController::class, 'show']);
