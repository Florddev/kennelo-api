<?php

declare(strict_types=1);

namespace App\Http\Controllers\Conversation;

use App\Enums\ApiStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Conversation\ListConversationsRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Models\Establishment;
use App\Services\Conversation\ConversationService;
use Illuminate\Http\JsonResponse;

class EstablishmentConversationController extends Controller
{
    public function __construct(
        private ConversationService $conversationService
    ) {}

    public function index(ListConversationsRequest $request, Establishment $establishment): JsonResponse
    {
        $this->authorize('manageForEstablishment', [Conversation::class, $establishment]);

        $conversations = $this->conversationService->getEstablishmentConversations(
            $establishment,
            $request->user(),
            $request->validated()
        );

        return ConversationResource::collection($conversations)
            ->additional([
                'status' => ApiStatusEnum::SUCCESS,
                'timestamp' => human_date(now()),
            ])
            ->response();
    }
}
