<?php

declare(strict_types=1);

namespace App\Http\Controllers\Conversation;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Models\Booking;
use App\Models\Conversation;
use App\Services\Conversation\ConversationService;
use Illuminate\Http\Request;

/**
 * @tags Conversations
 */
class BookingConversationController extends Controller
{
    public function __construct(private readonly ConversationService $conversations) {}

    /**
     * Open the conversation of a booking
     *
     * La conversation du client avec l'activité, où la réservation a son fil. Pour le client, et pour l'équipe
     * (messages.reply) qui veut lui écrire. Elle est déjà ouverte pour toute réservation passée par l'API.
     */
    public function store(Request $request, Booking $booking): ConversationResource
    {
        $this->authorize('openForBooking', [Conversation::class, $booking]);

        return new ConversationResource($this->conversations->show($this->conversations->forBooking($booking), $request->user()));
    }
}
