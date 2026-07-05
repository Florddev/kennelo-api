<?php

declare(strict_types=1);

namespace App\Services\Prospect\Contracts;

interface PlaceDiscoveryService
{
    public function isConfigured(): bool;

    /**
     * @param  array<int, string>  $searchTerms
     * @return array<int, array<string, mixed>>
     */
    public function discover(array $searchTerms, string $location, int $maxResults): array;
}
