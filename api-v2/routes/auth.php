<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\MagicLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordRenewalController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

// Authentification par cookie de session (Sanctum, mode SPA) : le front appelle d'abord
// GET /sanctum/csrf-cookie, puis envoie l'en-tête X-XSRF-TOKEN sur chaque requête.
//
// Pas de middleware « guest » : il répond par une redirection HTML à un utilisateur déjà connecté,
// ce qui n'a pas de sens pour une API. Se reconnecter ouvre simplement une nouvelle session.

Route::post('/register', [RegisteredUserController::class, 'store'])
    ->name('register');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->name('login');

Route::post('/login/google', [GoogleAuthController::class, 'store'])
    ->name('login.google');

Route::post('/login/two-factor-challenge', [AuthenticatedSessionController::class, 'twoFactorChallenge'])
    ->middleware('throttle:6,1')
    ->name('login.two-factor');

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('password.email');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('password.store');

Route::post('/password/renew', [PasswordRenewalController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('password.renew');

Route::post('/magic-link', [MagicLinkController::class, 'store'])
    ->middleware('throttle:3,1')
    ->name('magic-link.send');

Route::get('/magic-link/verify/{id}', [MagicLinkController::class, 'verify'])
    ->whereUuid('id')
    ->middleware(['signed', 'throttle:6,1'])
    ->name('magic-link.verify');

Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
    ->whereUuid('id')
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['auth:sanctum', 'throttle:6,1'])
    ->name('verification.send');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth:sanctum')
    ->name('logout');
