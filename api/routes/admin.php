<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\UserController;
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

// Review moderation
Route::get('/review-reports', [ReviewReportController::class, 'index']);
Route::put('/review-reports/{report}', [ReviewReportController::class, 'update']);
