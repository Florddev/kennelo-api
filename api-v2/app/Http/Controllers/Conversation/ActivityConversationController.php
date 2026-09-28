<?php

declare(strict_types=1);

namespace App\Http\Controllers\Conversation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Conversation\ListConversationsRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Activity;
use App\Models\Conversation;
use App\Services\Conversation\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Conversations
 */
class ActivityConversationController extends Controller
{
    public function __construct(private readonly ConversationService $conversations) {}

    /**
     * List the conversations of an activity
     *
     * Boîte de réception de l'équipe (messages.reply), les plus récentes d'abord.
     */
    public function index(ListConversationsRequest $request, Activity $activity): AnonymousResourceCollection
    {
        $this->authorize('viewForActivity', [Conversation::class, $activity]);

        return ConversationResource::collection($this->conversations->forActivity($activity, $request->user(), $request->validated()));
    }

    /**
     * Contact an activity
     *
     * Ouvre la conversation du client avec une activité réservable, ou renvoie celle qui existe. Elle n'apparaît
     * dans les listes qu'à son premier message.
     */
    public function store(Request $request, Activity $activity): ConversationResource
    {
        $this->authorize('contact', [Conversation::class, $activity]);

        return new ConversationResource($this->conversations->show($this->conversations->open($request->user(), $activity), $request->user()));
    }
}
