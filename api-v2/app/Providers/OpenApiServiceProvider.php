<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Stripe\StripeWebhookController;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\Server;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class OpenApiServiceProvider extends ServiceProvider
{
    private const string CONTROLLERS_NAMESPACE = 'App\\Http\\Controllers\\';

    public function boot(): void
    {
        Scramble::configure()
            ->routes(fn (Route $route): bool => Str::startsWith($route->uri, 'api/')
                && Str::startsWith((string) $route->getControllerClass(), self::CONTROLLERS_NAMESPACE)
                && $route->getControllerClass() !== StripeWebhookController::class)
            ->withOperationTransformers(function (Operation $operation, RouteInfo $routeInfo): void {
                $operation->setTags(array_slice($operation->tags, 0, 1));
                $operation->setOperationId(self::operationId($routeInfo));

                if (! in_array('auth:sanctum', $routeInfo->route->gatherMiddleware(), true)) {
                    $operation->security = [];
                }
            })
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->servers = [Server::make('/api')];
                $openApi->secure(
                    SecurityScheme::apiKey('cookie', 'kennelo-session')
                        ->as('session')
                        ->setDescription('Session Sanctum : GET /sanctum/csrf-cookie, puis POST /api/login. Les requêtes qui modifient renvoient le cookie XSRF-TOKEN dans l\'en-tête X-XSRF-TOKEN.'),
                );
            });
    }

    private static function operationId(RouteInfo $routeInfo): string
    {
        $name = $routeInfo->route->getName();

        if ($name !== null && ! Str::contains($name, 'generated::')) {
            return $name;
        }

        $segments = explode('\\', Str::after((string) $routeInfo->className(), self::CONTROLLERS_NAMESPACE));
        $segments[] = Str::beforeLast((string) array_pop($segments), 'Controller');

        $segments = array_filter(
            $segments,
            fn (string $segment, int $index): bool => ! Str::startsWith($segments[$index + 1] ?? '', $segment),
            ARRAY_FILTER_USE_BOTH,
        );

        return collect([...$segments, $routeInfo->methodName()])
            ->reject(fn (?string $segment): bool => $segment === null || $segment === '__invoke')
            ->map(Str::camel(...))
            ->implode('.');
    }
}
