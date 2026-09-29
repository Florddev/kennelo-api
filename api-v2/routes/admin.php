<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Activity\ActivityController;
use App\Http\Controllers\Admin\Activity\ActivityDocumentController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\Billing\InvoiceController;
use App\Http\Controllers\Admin\Booking\BookingController;
use App\Http\Controllers\Admin\Organization\OrganizationController;
use App\Http\Controllers\Admin\Profession\ProfessionCategoryController;
use App\Http\Controllers\Admin\Profession\ProfessionController;
use App\Http\Controllers\Admin\Review\ReviewReportController;
use App\Http\Controllers\Admin\Setting\SettingController;
use App\Http\Controllers\Admin\StatsController;
use App\Http\Controllers\Admin\Subscription\SubscriptionPlanController;
use App\Http\Controllers\Admin\User\BulkController;
use App\Http\Controllers\Admin\User\UserController;
use Illuminate\Support\Facades\Route;

// Users bulk + export (declared before /users/{user} to avoid param capture)
Route::get('/users/export', [BulkController::class, 'export'])->name('users.export');
Route::post('/users/bulk/status', [BulkController::class, 'status'])->name('users.bulk.status.update');
Route::post('/users/bulk/roles', [BulkController::class, 'roles'])->name('users.bulk.roles.update');

// Users
Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update'])->name('users.update');
Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
Route::put('/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.status.update');
Route::put('/users/{user}/roles', [UserController::class, 'assignRoles'])->name('users.roles.update');
Route::delete('/users/{user}/roles/{role}', [UserController::class, 'removeRole'])->name('users.roles.destroy');
Route::post('/users/{user}/ban', [UserController::class, 'ban'])->name('users.ban.store');
Route::delete('/users/{user}/ban', [UserController::class, 'unban'])->name('users.ban.destroy');
Route::post('/users/{user}/force-password-reset', [UserController::class, 'forcePasswordReset'])->name('users.force-password-reset');
Route::post('/users/{user}/verify-email', [UserController::class, 'verifyEmail'])->name('users.verify-email');
Route::post('/users/{user}/resend-verification', [UserController::class, 'resendVerification'])->name('users.resend-verification');

// Organizations
Route::get('/organizations', [OrganizationController::class, 'index'])->name('organizations.index');
Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])->name('organizations.show');
Route::post('/organizations/{organization}/approve', [OrganizationController::class, 'approve'])->name('organizations.approve');
Route::post('/organizations/{organization}/reject', [OrganizationController::class, 'reject'])->name('organizations.reject');
Route::post('/organizations/{organization}/suspend', [OrganizationController::class, 'suspend'])->name('organizations.suspend');
Route::post('/organizations/{organization}/verify-company', [OrganizationController::class, 'verifyCompany'])->middleware('throttle:20,1')->name('organizations.verify-company');

// Professions
Route::get('/profession-categories', [ProfessionCategoryController::class, 'index'])->name('profession-categories.index');
Route::post('/profession-categories', [ProfessionCategoryController::class, 'store'])->name('profession-categories.store');
Route::patch('/profession-categories/{category}', [ProfessionCategoryController::class, 'update'])->name('profession-categories.update');
Route::delete('/profession-categories/{category}', [ProfessionCategoryController::class, 'destroy'])->name('profession-categories.destroy');
Route::get('/professions', [ProfessionController::class, 'index'])->name('professions.index');
Route::post('/professions', [ProfessionController::class, 'store'])->name('professions.store');
Route::patch('/professions/{profession}', [ProfessionController::class, 'update'])->name('professions.update');

// Activities
Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
Route::get('/activities/{activity}', [ActivityController::class, 'show'])->name('activities.show');
Route::post('/activities/{activity}/approve', [ActivityController::class, 'approve'])->name('activities.approve');
Route::post('/activities/{activity}/reject', [ActivityController::class, 'reject'])->name('activities.reject');
Route::post('/activities/{activity}/suspend', [ActivityController::class, 'suspend'])->name('activities.suspend');

// Activity documents
Route::get('/activity-documents', [ActivityDocumentController::class, 'index'])->name('activity-documents.index');
Route::get('/activity-documents/{document}/file', [ActivityDocumentController::class, 'file'])->name('activity-documents.file.show');
Route::post('/activity-documents/{document}/approve', [ActivityDocumentController::class, 'approve'])->name('activity-documents.approve');
Route::post('/activity-documents/{document}/reject', [ActivityDocumentController::class, 'reject'])->name('activity-documents.reject');

// Invoices
Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');

Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
Route::post('/bookings/{booking}/refunds', [BookingController::class, 'refund'])->name('bookings.refunds.store');

// Review moderation
Route::get('/review-reports', [ReviewReportController::class, 'index'])->name('review-reports.index');
Route::put('/review-reports/{report}', [ReviewReportController::class, 'update'])->name('review-reports.update');

// Stats
Route::get('/stats/overview', [StatsController::class, 'overview'])->name('stats.overview.show');
Route::get('/stats/finance', [StatsController::class, 'finance'])->name('stats.finance.show');
Route::get('/stats/bookings', [StatsController::class, 'bookings'])->name('stats.bookings.show');
Route::get('/stats/community', [StatsController::class, 'community'])->name('stats.community.show');

// Audit
Route::get('/audit-actions', [AuditController::class, 'index'])->name('audit-actions.index');

// Settings
Route::get('/settings', [SettingController::class, 'index'])->name('settings.show');
Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');

// Subscription plans
Route::get('/subscription-plans', [SubscriptionPlanController::class, 'index'])->name('subscription-plans.index');
Route::match(['put', 'patch'], '/subscription-plans/{plan}', [SubscriptionPlanController::class, 'update'])->name('subscription-plans.update');
