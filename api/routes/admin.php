<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminActionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserImpersonationController;
use App\Http\Controllers\Review\Admin\ReviewReportController;
use Illuminate\Support\Facades\Route;

// Users
Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::match(['put', 'patch'], '/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);
Route::put('/users/{id}/status', [UserController::class, 'updateStatus']);
Route::put('/users/{id}/roles', [UserController::class, 'assignRoles']);
Route::delete('/users/{id}/roles/{role}', [UserController::class, 'removeRole']);
Route::put('/users/{id}/identity-verification', [UserController::class, 'reviewIdentityVerification']);
Route::post('/users/{id}/ban', [UserController::class, 'ban']);
Route::delete('/users/{id}/ban', [UserController::class, 'unban']);
Route::post('/users/{id}/force-password-reset', [UserController::class, 'forcePasswordReset']);
Route::post('/users/{id}/verify-email', [UserController::class, 'verifyEmail']);
Route::post('/users/{id}/resend-verification', [UserController::class, 'resendVerification']);
Route::post('/users/{id}/impersonate', [UserImpersonationController::class, 'start']);

// Audit
Route::get('/audit-actions', [AdminActionController::class, 'index']);

// Review moderation
Route::get('/review-reports', [ReviewReportController::class, 'index']);
Route::put('/review-reports/{report}', [ReviewReportController::class, 'update']);
