<?php

declare(strict_types=1);

namespace App\Http\Controllers\Conversation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Conversation\ListMessagesRequest;
use App\Http\Requests\Conversation\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\MessageFile;
use App\Services\Conversation\ConversationService;
use App\Services\Conversation\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @tags Conversations
 */
class MessageController extends Controller
{
    public function __construct(
        private readonly ConversationService $conversations,
        private readonly MessageService $messages,
    ) {}

    /**
     * List the messages of a conversation
     *
     * Les plus récents d'abord.
     */
    public function index(ListMessagesRequest $request, Conversation $conversation): AnonymousResourceCollection
    {
        $this->authorize('view', $conversation);

        return MessageResource::collection($this->conversations->messages($conversation, $request->validated()));
    }

    /**
     * Send a message
     *
     * En multipart pour joindre des fichiers (files[]). Diffusé en direct sur le canal de la conversation
     * (message.sent) ; l'autre côté reçoit aussi une notification new_message.
     */
    public function store(StoreMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        return MessageResource::make($this->messages->send($request->user(), $conversation, $request->validated()))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Download an attachment
     */
    public function file(Conversation $conversation, MessageFile $file): StreamedResponse
    {
        $this->authorize('view', $conversation);

        $disk = Storage::disk(MessageFile::DISK);
        abort_unless($disk->exists($file->file_path), 404, __('errors.not_found'));

        return $disk->download($file->file_path, $file->file_name);
    }
}
