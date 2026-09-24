<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stripe;

use App\Http\Controllers\Controller;
use App\Services\Stripe\StripeWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

/**
 * @tags Stripe
 */
class StripeWebhookController extends Controller
{
    /**
     * Receive a Stripe event
     *
     * La signature de Stripe remplace l'authentification : un appel non signé est refusé.
     *
     * @unauthenticated
     */
    public function __invoke(Request $request, StripeWebhookService $webhooks): Response
    {
        $secret = (string) config('services.stripe.webhook_secret');

        if ($secret === '') {
            throw new RuntimeException('STRIPE_WEBHOOK_SECRET is not configured.');
        }

        try {
            $event = Webhook::constructEvent($request->getContent(), (string) $request->header('Stripe-Signature'), $secret);
        } catch (UnexpectedValueException|SignatureVerificationException) {
            abort(400, 'Invalid Stripe webhook.');
        }

        $webhooks->handle($event);

        return response()->noContent();
    }
}
