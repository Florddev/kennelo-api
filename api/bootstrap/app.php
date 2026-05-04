<?php

declare(strict_types=1);

use App\Enums\ApiStatus;
use App\Http\Middleware\AuthenticateJWT;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\SetLocale;
use App\Services\Review\ReviewPublicationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Sentry\Laravel\Integration;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->call(fn () => app(ReviewPublicationService::class)->publishMatured())
            ->dailyAt('03:00')
            ->name('reviews:publish-matured');
    })
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->api([
            HandleCors::class,
            SetLocale::class,
        ]);

        $middleware->alias([
            'verified' => EnsureEmailIsVerified::class,
            'role' => RoleMiddleware::class,
            'auth.jwt' => AuthenticateJWT::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);

        $exceptions->render(function (Throwable $e, $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $timestamp = human_date(Carbon::now());

            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                    'status' => ApiStatus::ERROR,
                    'timestamp' => $timestamp,
                ], 422);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                    'status' => ApiStatus::ERROR,
                    'timestamp' => $timestamp,
                ], 401);
            }

            if ($e instanceof AuthorizationException || ($e instanceof HttpException && $e->getStatusCode() === 403)) {
                return response()->json([
                    'message' => 'This action is unauthorized.',
                    'status' => ApiStatus::ERROR,
                    'timestamp' => $timestamp,
                ], 403);
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return response()->json([
                    'message' => 'Resource not found.',
                    'status' => ApiStatus::ERROR,
                    'timestamp' => $timestamp,
                ], 404);
            }

            if ($e instanceof MethodNotAllowedHttpException) {
                return response()->json([
                    'message' => 'Method not allowed.',
                    'status' => ApiStatus::ERROR,
                    'timestamp' => $timestamp,
                ], 405);
            }

            if ($e instanceof HttpException && $e->getStatusCode() < 500) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Request failed.',
                    'status' => ApiStatus::ERROR,
                    'timestamp' => $timestamp,
                ], $e->getStatusCode());
            }

            return response()->json([
                'message' => 'Server error.',
                'status' => ApiStatus::ERROR,
                'timestamp' => $timestamp,
            ], 500);
        });
    })->create();
