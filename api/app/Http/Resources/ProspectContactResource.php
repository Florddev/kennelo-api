<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ProspectContact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProspectContact */
class ProspectContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'prospect_id' => $this->prospect_id,
            'author_id' => $this->author_id,
            'type' => $this->type->value,
            'contacted_at' => human_date($this->contacted_at),
            'outcome' => $this->outcome,
            'notes' => $this->notes,
            'author' => $this->whenLoaded('author', fn () => $this->author ? new UserResource($this->author) : null),
            'created_at' => human_date($this->created_at),
        ];
    }
}
