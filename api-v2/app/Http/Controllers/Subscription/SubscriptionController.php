<?php

declare(strict_types=1);

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionPlanResource;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Subscriptions
 *
 * L'abonnement d'une entreprise (souscription, factures, résiliation) arrive avec le lot « Entreprise et équipe ».
 */
class SubscriptionController extends Controller
{
    public function plans(): AnonymousResourceCollection
    {
        return SubscriptionPlanResource::collection(
            SubscriptionPlan::where('is_active', true)->orderByDesc('commission_rate')->get()
        );
    }
}
