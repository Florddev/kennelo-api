<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ProspectImport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProspectImport */
class ProspectImportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'location' => $this->location,
            'max_results' => $this->max_results,
            'status' => $this->status->value,
            'imported_count' => $this->imported_count,
            'skipped_count' => $this->skipped_count,
            'error' => $this->error,
            'finished_at' => human_date($this->finished_at),
            'created_at' => human_date($this->created_at),
        ];
    }
}
