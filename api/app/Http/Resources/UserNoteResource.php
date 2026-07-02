<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\UserNote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UserNote */
class UserNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'author_id' => $this->author_id,
            'body' => $this->body,
            'author' => $this->whenLoaded('author', fn () => $this->author ? new UserResource($this->author) : null),
            'created_at' => human_date($this->created_at),
            'updated_at' => human_date($this->updated_at),
        ];
    }
}
