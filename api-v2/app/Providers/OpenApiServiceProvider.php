<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Stripe\StripeWebhookController;
use App\Support\OpenApi\LoadedRelationSchemas;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Dedoc\Scramble\Support\Generator\Server;
use Dedoc\Scramble\Support\Generator\Tag;
use Dedoc\Scramble\Support\RouteInfo;
use Illuminate\Routing\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use LogicException;

class OpenApiServiceProvider extends ServiceProvider
{
    private const string CONTROLLERS_NAMESPACE = 'App\\Http\\Controllers\\';

    private const array TAGS = [
        'Auth', 'Users', 'Addresses', 'Pets', 'Professions', 'Explore', 'Activities', 'Services', 'Pricing', 'Unit types', 'Agenda',
        'Bookings', 'Conversations', 'Reviews', 'Favorites', 'Notifications', 'Organizations', 'Subscriptions', 'Invoices',
        'Payment methods', 'Dashboards', 'Stats', 'Settings', 'Audit',
    ];

    public function boot(): void
    {
        Scramble::configure()
            ->routes(fn (Route $route): bool => Str::startsWith($route->uri, 'api/')
                && Str::startsWith((string) $route->getControllerClass(), self::CONTROLLERS_NAMESPACE)
                && $route->getControllerClass() !== StripeWebhookController::class)
            ->withOperationTransformers(function (Operation $operation, RouteInfo $routeInfo): void {
                $operation->setTags([self::tag($operation, $routeInfo->route)]);
                $operation->setOperationId(self::operationId($routeInfo->route));

                if (! in_array('auth:sanctum', $routeInfo->route->gatherMiddleware(), true)) {
                    $operation->security = [];
                }
            })
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->servers = [Server::make('/api')];
                $openApi->tags = array_map(fn (string $tag): Tag => new Tag($tag), self::TAGS);
                $openApi->secure(
                    SecurityScheme::apiKey('cookie', 'kennelo-session')
                        ->as('session')
                        ->setDescription('Session Sanctum : GET /sanctum/csrf-cookie, puis POST /api/login. Les requêtes qui modifient renvoient le cookie XSRF-TOKEN dans l\'en-tête X-XSRF-TOKEN.'),
                );
            })
            ->withDocumentTransformers(LoadedRelationSchemas::class);
    }

    private static function tag(Operation $operation, Route $route): string
    {
        $tag = $operation->tags[0] ?? null;

        if (! in_array($tag, self::TAGS, true)) {
            throw new LogicException("The route [{$route->uri}] has no domain tag: give its controller @tags with one of ".implode(', ', self::TAGS).'.');
        }

        return $tag;
    }

    private static function operationId(Route $route): string
    {
        $name = $route->getName();

        if ($name === null || Str::startsWith($name, 'generated::')) {
            throw new LogicException("The route [{$route->uri}] has no name: name it, the name is its operationId.");
        }

        return $name;
    }
}
