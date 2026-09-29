<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ActivityDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Le fichier est privé : la resource le décrit, il se télécharge par une route dédiée.
 *
 * @mixin ActivityDocument
 */
class ActivityDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_type' => $this->document_type,
            'status' => $this->status,
            'expires_at' => $this->expires_at?->toDateString(),
            'rejection_reason' => $this->rejection_reason,
            'reviewed_at' => $this->reviewed_at?->toISOString(),
            'file' => $this->whenLoaded('media', function (): ?array {
                $media = $this->getFirstMedia(ActivityDocument::COLLECTION_FILE);

                return $media === null ? null : [
                    'name' => $media->file_name,
                    'mime_type' => $media->mime_type,
                    'size' => $media->size,
                ];
            }),
            'activity' => $this->whenLoaded('activity', fn (): array => [
                'id' => $this->activity?->id,
                'name' => $this->activity?->name,
                'organization_id' => $this->activity?->organization_id,
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
