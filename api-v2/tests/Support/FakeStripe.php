<?php

declare(strict_types=1);

namespace Tests\Support;

use Closure;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;
use RuntimeException;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;

/**
 * Faux client HTTP du SDK Stripe, l'équivalent de Http::fake() pour Stripe : le vrai code du SDK
 * s'exécute, seules les réponses sont simulées. Une requête sans réponse prévue échoue, aucune
 * n'atteint le réseau.
 *
 * Les réponses doivent contenir la clé « object » (account, checkout.session…) pour que le SDK
 * construise la bonne classe.
 */
final class FakeStripe implements ClientInterface
{
    /** @var list<array{method: string, path: string, response: array<string, mixed>|Closure, status: int}> */
    private array $responses = [];

    /** @var list<array{method: string, path: string, params: array<string, mixed>}> */
    private array $requests = [];

    public static function install(): self
    {
        $fake = new self;
        ApiRequestor::setHttpClient($fake);

        return $fake;
    }

    /**
     * @param  string  $path  chemin de l'API, avec * comme joker : /v1/accounts/*
     * @param  array<string, mixed>|Closure(array<string, mixed>): array<string, mixed>  $response
     */
    public function fake(string $method, string $path, array|Closure $response, int $status = 200): self
    {
        $this->responses[] = ['method' => mb_strtolower($method), 'path' => $path, 'response' => $response, 'status' => $status];

        return $this;
    }

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $method = mb_strtolower((string) $method);
        $path = (string) parse_url((string) $absUrl, PHP_URL_PATH);

        $this->requests[] = ['method' => $method, 'path' => $path, 'params' => $params];

        foreach (array_reverse($this->responses) as $response) {
            if ($response['method'] === $method && Str::is($response['path'], $path)) {
                $body = $response['response'] instanceof Closure ? ($response['response'])($params) : $response['response'];

                return [(string) json_encode($body), $response['status'], []];
            }
        }

        throw new RuntimeException("Unexpected Stripe request: {$method} {$path}");
    }

    /**
     * Les paramètres sont ceux du SDK, déjà encodés : un booléen y devient 'true' ou 'false'.
     *
     * @param  (Closure(array<string, mixed>): bool)|null  $callback  vérifie les paramètres envoyés
     */
    public function assertSent(string $method, string $path, ?Closure $callback = null): void
    {
        $sent = collect($this->requests)->contains(fn (array $request): bool => $request['method'] === mb_strtolower($method)
            && Str::is($path, $request['path'])
            && ($callback === null || $callback($request['params'])));

        Assert::assertTrue($sent, "The Stripe request [{$method} {$path}] was not sent.");
    }

    public function assertNotSent(string $method, string $path): void
    {
        $sent = collect($this->requests)->contains(fn (array $request): bool => $request['method'] === mb_strtolower($method)
            && Str::is($path, $request['path']));

        Assert::assertFalse($sent, "The Stripe request [{$method} {$path}] was sent unexpectedly.");
    }
}
