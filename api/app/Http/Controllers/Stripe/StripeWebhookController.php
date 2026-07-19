<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stripe;

use App\Http\Controllers\Controller;
use App\Models\StripeEvent;
use App\Services\Stripe\StripeWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __construct(
        private StripeWebhookService $webhookService
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature', '');
        $secret = (string) config('services.stripe.webhook_secret');

        if ($secret === '') {
            Log::error('Stripe webhook called but STRIPE_WEBHOOK_SECRET is not configured.');

            return response()->json(['message' => 'Webhook secret not configured.'], 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (UnexpectedValueException $e) {
            return response()->json(['message' => 'Invalid payload.'], 400);
        } catch (SignatureVerificationException $e) {
            return response()->json(['message' => 'Invalid signature.'], 400);
        }

        StripeEvent::firstOrCreate(
            ['id' => $event->id],
            ['type' => $event->type],
        );

        $claimed = StripeEvent::where('id', $event->id)
            ->whereNull('processed_at')
            ->update(['processed_at' => now()]);

        if ($claimed === 0) {
            return response()->json(['received' => true]);
        }

        try {
            $this->webhookService->handleEvent($event);
        } catch (\Throwable $e) {
            StripeEvent::where('id', $event->id)->update(['processed_at' => null]);

            throw $e;
        }

        return response()->json(['received' => true]);
    }
}
