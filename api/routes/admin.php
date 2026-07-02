<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminActionController;
use App\Http\Controllers\Admin\UserBulkController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\UserImpersonationController;
use App\Http\Controllers\Admin\UserNoteController;
use App\Http\Controllers\Review\Admin\ReviewReportController;
use Illuminate\Support\Facades\Route;

// Users bulk + export (declared before /users/{id} to avoid param capture)
Route::get('/users/export', [UserBulkController::class, 'export']);
Route::post('/users/bulk/status', [UserBulkController::class, 'status']);
Route::post('/users/bulk/roles', [UserBulkController::class, 'roles']);

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

// User notes
Route::get('/users/{id}/notes', [UserNoteController::class, 'index']);
Route::post('/users/{id}/notes', [UserNoteController::class, 'store']);
Route::put('/users/{id}/notes/{note}', [UserNoteController::class, 'update']);
Route::delete('/users/{id}/notes/{note}', [UserNoteController::class, 'destroy']);

// Audit
Route::get('/audit-actions', [AdminActionController::class, 'index']);

// Review moderation
Route::get('/review-reports', [ReviewReportController::class, 'index']);
Route::put('/review-reports/{report}', [ReviewReportController::class, 'update']);
