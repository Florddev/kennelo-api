<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ReviewResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ReviewResponse */
class ReviewResponseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'review_id' => $this->review_id,
            'responder_id' => $this->responder_id,
            'response' => $this->response,
            'responder' => new UserResource($this->whenLoaded('responder')),
            'created_at' => human_date($this->created_at),
            'updated_at' => human_date($this->updated_at),
        ];
    }
}
