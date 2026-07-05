<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Prospect;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Prospect */
class ProspectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'postal_code' => $this->postal_code,
            'department' => $this->department,
            'region' => $this->region,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'phone' => $this->phone,
            'website' => $this->website,
            'google_rating' => $this->google_rating,
            'google_reviews_count' => $this->google_reviews_count,
            'google_place_id' => $this->google_place_id,
            'category' => $this->category,
            'animal_types' => $this->animal_types,
            'services' => $this->services,
            'siret' => $this->siret,
            'siren' => $this->siren,
            'ape_code' => $this->ape_code,
            'status' => $this->status->value,
            'source' => $this->source->value,
            'is_registered' => $this->kennelo_activity_id !== null,
            'assigned_to' => $this->assigned_to,
            'kennelo_activity_id' => $this->kennelo_activity_id,
            'assigned_user' => $this->whenLoaded('assignedTo', fn () => $this->assignedTo ? new UserResource($this->assignedTo) : null),
            'notes' => ProspectNoteResource::collection($this->whenLoaded('notes')),
            'contacts' => ProspectContactResource::collection($this->whenLoaded('contacts')),
            'reconciled_at' => human_date($this->reconciled_at),
            'created_at' => human_date($this->created_at),
            'updated_at' => human_date($this->updated_at),
        ];
    }
}
