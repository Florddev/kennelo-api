<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\User\BulkController;
use App\Http\Controllers\Admin\User\ImpersonationController;
use App\Http\Controllers\Admin\User\NoteController;
use App\Http\Controllers\Admin\User\UserController;
use App\Http\Controllers\Review\Admin\ReviewReportController;
use Illuminate\Support\Facades\Route;

// Users bulk + export (declared before /users/{id} to avoid param capture)
Route::get('/users/export', [BulkController::class, 'export']);
Route::post('/users/bulk/status', [BulkController::class, 'status']);
Route::post('/users/bulk/roles', [BulkController::class, 'roles']);

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
Route::post('/users/{id}/impersonate', [ImpersonationController::class, 'start']);

// User notes
Route::get('/users/{id}/notes', [NoteController::class, 'index']);
Route::post('/users/{id}/notes', [NoteController::class, 'store']);
Route::put('/users/{id}/notes/{note}', [NoteController::class, 'update']);
Route::delete('/users/{id}/notes/{note}', [NoteController::class, 'destroy']);

// Audit
Route::get('/audit-actions', [AuditController::class, 'index']);

// Review moderation
Route::get('/review-reports', [ReviewReportController::class, 'index']);
Route::put('/review-reports/{report}', [ReviewReportController::class, 'update']);
