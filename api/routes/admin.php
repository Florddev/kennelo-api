<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Activity\ActivityController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\Prospect\MapController;
use App\Http\Controllers\Admin\Prospect\ProspectContactController;
use App\Http\Controllers\Admin\Prospect\ProspectController;
use App\Http\Controllers\Admin\Prospect\ProspectNoteController;
use App\Http\Controllers\Admin\SearchLogController;
use App\Http\Controllers\Admin\StatsController;
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

// Prospects import + map (declared before /prospects/{prospect} to avoid param capture)
Route::post('/prospects/import', [ProspectController::class, 'import']);
Route::get('/prospects/imports/{import}', [ProspectController::class, 'importStatus']);
Route::get('/prospects/map', [MapController::class, 'index']);

// Prospects
Route::get('/prospects', [ProspectController::class, 'index']);
Route::get('/prospects/{prospect}', [ProspectController::class, 'show']);
Route::delete('/prospects/{prospect}', [ProspectController::class, 'destroy']);
Route::put('/prospects/{prospect}/status', [ProspectController::class, 'updateStatus']);
Route::put('/prospects/{prospect}/assign', [ProspectController::class, 'assign']);
Route::post('/prospects/{prospect}/reconcile', [ProspectController::class, 'reconcile']);

// Prospect notes
Route::get('/prospects/{prospect}/notes', [ProspectNoteController::class, 'index']);
Route::post('/prospects/{prospect}/notes', [ProspectNoteController::class, 'store']);
Route::put('/prospects/{prospect}/notes/{note}', [ProspectNoteController::class, 'update']);
Route::delete('/prospects/{prospect}/notes/{note}', [ProspectNoteController::class, 'destroy']);

// Prospect contacts
Route::get('/prospects/{prospect}/contacts', [ProspectContactController::class, 'index']);
Route::post('/prospects/{prospect}/contacts', [ProspectContactController::class, 'store']);
Route::delete('/prospects/{prospect}/contacts/{contact}', [ProspectContactController::class, 'destroy']);

// Activities (professional validation)
Route::get('/activities', [ActivityController::class, 'index']);
Route::get('/activities/{activity}', [ActivityController::class, 'show']);
Route::match(['put', 'patch'], '/activities/{activity}', [ActivityController::class, 'update']);
Route::post('/activities/{activity}/approve', [ActivityController::class, 'approve']);
Route::post('/activities/{activity}/reject', [ActivityController::class, 'reject']);
Route::post('/activities/{activity}/verify-company', [ActivityController::class, 'verifyCompany']);

// Search logs
Route::get('/search-logs', [SearchLogController::class, 'index']);

// Stats / KPI
Route::get('/stats/overview', [StatsController::class, 'overview']);
Route::get('/stats/searches', [StatsController::class, 'searches']);
Route::get('/stats/business', [StatsController::class, 'business']);
Route::get('/stats/finance', [StatsController::class, 'finance']);
Route::get('/stats/bookings', [StatsController::class, 'bookings']);
Route::get('/stats/community', [StatsController::class, 'community']);

// Audit
Route::get('/audit-actions', [AuditController::class, 'index']);

// Review moderation
Route::get('/review-reports', [ReviewReportController::class, 'index']);
Route::put('/review-reports/{report}', [ReviewReportController::class, 'update']);
