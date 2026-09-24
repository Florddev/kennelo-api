<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AuditController;
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

// Audit
Route::get('/audit-actions', [AuditController::class, 'index']);

// Settings
Route::get('/settings', [SettingController::class, 'index']);
Route::put('/settings', [SettingController::class, 'update']);

// Subscription plans
Route::get('/subscription-plans', [SubscriptionPlanController::class, 'index']);
Route::match(['put', 'patch'], '/subscription-plans/{plan}', [SubscriptionPlanController::class, 'update']);
