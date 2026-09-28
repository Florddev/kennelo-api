<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\ReviewerTypeEnum;
use App\Models\Review;
use App\Services\MediaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;

/**
 * Un avis. L'auteur d'un avis de client n'est présenté que par son prénom ; celui d'un avis d'équipe, par son
 * activité. Le retour privé n'apparaît que pour la partie notée et pour Kennelo.
 *
 * @mixin Review
 */
class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'activity' => $this->whenLoaded('activity', fn (): array => ['id' => $this->activity_id, 'name' => $this->activity?->name]),
            'reviewer_type' => $this->reviewer_type->value,
            'reviewer' => $this->whenLoaded('reviewer', fn (): ?array => $this->reviewer_type !== ReviewerTypeEnum::USER || $this->reviewer === null ? null : [
                'id' => $this->reviewer->id,
                'first_name' => $this->reviewer->first_name,
                'avatar_url' => $this->reviewer->relationLoaded('media')
                    ? $this->reviewer->getFirstMediaUrl(MediaService::COLLECTION_AVATAR, MediaService::CONVERSION_AVATAR_WEBP) ?: null
                    : null,
            ]),
            'overall_rating' => $this->overall_rating,
            'comment' => $this->comment,
            'would_recommend' => $this->would_recommend,
            'private_feedback' => $this->when(
                $user !== null && Gate::forUser($user)->allows('viewPrivateFeedback', $this->resource),
                fn (): ?string => $this->private_feedback,
            ),
            'is_published' => $this->is_published,
            'published_at' => $this->published_at?->toISOString(),
            'response' => $this->whenLoaded('response', fn (): ?array => $this->response === null ? null : [
                'id' => $this->response->id,
                'response' => $this->response->response,
                'created_at' => $this->response->created_at?->toISOString(),
            ]),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
