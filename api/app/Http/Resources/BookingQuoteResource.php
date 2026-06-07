<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingQuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $quote */
        $quote = $this->resource;

        return $quote;
    }
}
