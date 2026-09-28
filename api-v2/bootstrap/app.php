<?php

declare(strict_types=1);

use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrackLastSeen;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Temps réel : POST /api/broadcasting/auth autorise l'accès aux canaux privés, avec la session du front.
    ->withBroadcasting(__DIR__.'/../routes/channels.php', ['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum']])
    ->withMiddleware(function (Middleware $middleware): void {
        // Authentification des fronts Kennelo par cookie de session (Sanctum, mode SPA).
        $middleware->statefulApi();

        $middleware->api(append: [
            SetLocale::class,
            TrackLastSeen::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Réponses d'erreur au format JSON standard de Laravel : { message, errors? }.
        $exceptions->shouldRenderJsonWhen(fn (Request $request): bool => $request->is('api/*') || $request->expectsJson());

        // Les messages par défaut d'un modèle introuvable (nom de la classe) ou d'une route inconnue (chemin)
        // exposent des détails internes : on les remplace. Un abort(404, $message) garde son message.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            $isFrameworkMessage = $e->getPrevious() instanceof ModelNotFoundException || $request->route() === null;

            if ($request->is('api/*') && $isFrameworkMessage) {
                return response()->json(['message' => __('errors.not_found')], 404);
            }

            return null;
        });
    })->create();
