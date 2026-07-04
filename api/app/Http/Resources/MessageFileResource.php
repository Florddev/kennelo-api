<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\MessageFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

/** @mixin MessageFile */
class MessageFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'file_url' => URL::temporarySignedRoute(
                'conversations.attachments.show',
                now()->addMinutes(30),
                [
                    'conversation' => $this->message->conversation_id,
                    'messageFile' => $this->id,
                ],
            ),
            'file_type' => $this->file_type,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
        ];
    }
}
