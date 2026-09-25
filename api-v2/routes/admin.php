<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Activity\ActivityController;
use App\Http\Controllers\Admin\Activity\ActivityDocumentController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\Organization\OrganizationController;
use App\Http\Controllers\Admin\Profession\ProfessionCategoryController;
use App\Http\Controllers\Admin\Profession\ProfessionController;
use App\Http\Controllers\Admin\Setting\SettingController;
use App\Http\Controllers\Admin\Subscription\SubscriptionPlanController;
use App\Http\Controllers\Admin\User\BulkController;
use App\Http\Controllers\Admin\User\UserController;
use Illuminate\Support\Facades\Route;

// Users bulk + export (declared before /users/{user} to avoid param capture)
Route::get('/users/export', [BulkController::class, 'export']);
Route::post('/users/bulk/status', [BulkController::class, 'status']);
Route::post('/users/bulk/roles', [BulkController::class, 'roles']);

// Users
Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{user}', [UserController::class, 'show']);
Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update']);
Route::delete('/users/{user}', [UserController::class, 'destroy']);
Route::put('/users/{user}/status', [UserController::class, 'updateStatus']);
Route::put('/users/{user}/roles', [UserController::class, 'assignRoles']);
Route::delete('/users/{user}/roles/{role}', [UserController::class, 'removeRole']);
Route::post('/users/{user}/ban', [UserController::class, 'ban']);
Route::delete('/users/{user}/ban', [UserController::class, 'unban']);
Route::post('/users/{user}/force-password-reset', [UserController::class, 'forcePasswordReset']);
Route::post('/users/{user}/verify-email', [UserController::class, 'verifyEmail']);
Route::post('/users/{user}/resend-verification', [UserController::class, 'resendVerification']);

// Organizations
Route::get('/organizations', [OrganizationController::class, 'index']);
Route::get('/organizations/{organization}', [OrganizationController::class, 'show']);
Route::post('/organizations/{organization}/approve', [OrganizationController::class, 'approve']);
Route::post('/organizations/{organization}/reject', [OrganizationController::class, 'reject']);
Route::post('/organizations/{organization}/suspend', [OrganizationController::class, 'suspend']);
Route::post('/organizations/{organization}/verify-company', [OrganizationController::class, 'verifyCompany'])->middleware('throttle:20,1');

// Professions
Route::get('/profession-categories', [ProfessionCategoryController::class, 'index']);
Route::post('/profession-categories', [ProfessionCategoryController::class, 'store']);
Route::patch('/profession-categories/{category}', [ProfessionCategoryController::class, 'update']);
Route::delete('/profession-categories/{category}', [ProfessionCategoryController::class, 'destroy']);
Route::get('/professions', [ProfessionController::class, 'index']);
Route::post('/professions', [ProfessionController::class, 'store']);
Route::patch('/professions/{profession}', [ProfessionController::class, 'update']);

// Activities
Route::get('/activities', [ActivityController::class, 'index']);
Route::get('/activities/{activity}', [ActivityController::class, 'show']);
Route::post('/activities/{activity}/approve', [ActivityController::class, 'approve']);
Route::post('/activities/{activity}/reject', [ActivityController::class, 'reject']);
Route::post('/activities/{activity}/suspend', [ActivityController::class, 'suspend']);

// Activity documents
Route::get('/activity-documents', [ActivityDocumentController::class, 'index']);
Route::get('/activity-documents/{document}/file', [ActivityDocumentController::class, 'file']);
Route::post('/activity-documents/{document}/approve', [ActivityDocumentController::class, 'approve']);
Route::post('/activity-documents/{document}/reject', [ActivityDocumentController::class, 'reject']);

// Audit
Route::get('/audit-actions', [AuditController::class, 'index']);

// Settings
Route::get('/settings', [SettingController::class, 'index']);
Route::put('/settings', [SettingController::class, 'update']);

// Subscription plans
Route::get('/subscription-plans', [SubscriptionPlanController::class, 'index']);
Route::match(['put', 'patch'], '/subscription-plans/{plan}', [SubscriptionPlanController::class, 'update']);
