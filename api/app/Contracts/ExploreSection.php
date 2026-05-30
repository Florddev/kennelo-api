<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface ExploreSection
{
    public function id(): string;

    public function apply(Builder $query, ?float $lat, ?float $lng): Builder;
}
