<?php

declare(strict_types=1);

namespace App\Http\Controllers\Conversation;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\MessageFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageAttachmentController extends Controller
{
    public function show(Request $request, Conversation $conversation, MessageFile $messageFile): StreamedResponse
    {
        $this->authorize('view', $conversation);

        abort_if((string) $messageFile->message->conversation_id !== (string) $conversation->id, 404);

        $disk = Storage::disk('local');

        abort_unless($disk->exists($messageFile->file_path), 404);

        return $disk->download($messageFile->file_path, $messageFile->file_name);
    }
}
