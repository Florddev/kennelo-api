<?php

declare(strict_types=1);

namespace App\Services\Explore\Sections;

use App\Contracts\ExploreSection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class NewHostsSection implements ExploreSection
{
    public function id(): string
    {
        return 'new_hosts';
    }

    public function apply(Builder $query, ?float $lat, ?float $lng): Builder
    {
        return $query
            ->where('establishments.created_at', '>=', Carbon::now()->subDays(60))
            ->orderByDesc('establishments.created_at');
    }
}
