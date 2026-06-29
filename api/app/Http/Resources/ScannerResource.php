<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Scanner;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Scanner */
class ScannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'created_at' => human_date($this->created_at),
            'updated_at' => human_date($this->updated_at),
        ];
    }
}
