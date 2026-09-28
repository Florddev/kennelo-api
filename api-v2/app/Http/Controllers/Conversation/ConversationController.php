<?php

declare(strict_types=1);

namespace App\Http\Controllers\Conversation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Conversation\ListConversationsRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Services\Conversation\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Conversations
 */
class ConversationController extends Controller
{
    public function __construct(private readonly ConversationService $conversations) {}

    /**
     * List my conversations
     *
     * Les conversations en tant que client qui ont au moins un message, les plus récentes d'abord. L'équipe d'une
     * activité lit les siennes par /activities/{activity}/conversations.
     */
    public function index(ListConversationsRequest $request): AnonymousResourceCollection
    {
        return ConversationResource::collection($this->conversations->forClient($request->user(), $request->validated()));
    }

    /**
     * Count my unread messages
     *
     * En tant que client et dans les activités où j'ai messages.reply. Les messages système ne comptent pas.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['data' => ['unread_count' => $this->conversations->unreadCount($request->user())]]);
    }

    /**
     * Show a conversation
     */
    public function show(Request $request, Conversation $conversation): ConversationResource
    {
        $this->authorize('view', $conversation);

        return new ConversationResource($this->conversations->show($conversation, $request->user()));
    }

    /**
     * Mark a conversation as read
     *
     * Marque lus les messages de l'autre côté et prévient l'expéditeur en direct (messages.read).
     */
    public function read(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        return response()->json(['data' => ['marked_count' => $this->conversations->markAsRead($conversation, $request->user())]]);
    }
}
