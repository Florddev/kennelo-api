<?php

declare(strict_types=1);

use App\Enums\OrganizationRoleEnum;
use App\Enums\ServiceOfferEnum;
use App\Models\Activity;
use App\Models\ActivityUnitType;
use App\Models\AnimalType;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Pet;
use App\Models\Profession;
use App\Models\Service;
use App\Models\ServicePrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Support\FakeStripe;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * Authentifie les requêtes suivantes du test en tant que $user (session Sanctum).
 * Retourne un tableau d'en-têtes vide pour s'utiliser en dernier argument des appels HTTP.
 *
 * @return array<string, string>
 */
function asUser(User $user): array
{
    // Rechargé depuis la base comme lors d'une vraie requête (valeurs par défaut incluses : locale, status…).
    test()->actingAs($user->refresh());

    return [];
}

function adminUser(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    return $admin;
}

/**
 * Crée une personne membre active de l'entreprise, avec un rôle facultatif.
 */
function memberOf(Organization $organization, ?OrganizationRoleEnum $role = null, ?string $activityId = null): User
{
    $factory = OrganizationMember::factory()->for($organization);

    if ($role !== null) {
        $factory = $factory->withRole($role, $activityId);
    }

    return $factory->create()->user;
}

/**
 * Pension canine réservable, qui compte en nuits : un type de place « Box » au tarif de base.
 * L'activité est $unitType->activity, l'espèce $unitType->animalTypes->first().
 */
function dogBoarding(int $units = 2, string $price = '30.00', ?string $extraAnimalPrice = null, int $animalsPerUnit = 1): ActivityUnitType
{
    $dog = AnimalType::query()->where('code', 'dog')->first() ?? AnimalType::factory()->dog()->create();

    $activity = Activity::factory()
        ->bookable()
        ->for(Profession::factory()->stay()->forSpecies($dog))
        ->forSpecies($dog)
        ->create();

    return ActivityUnitType::factory()
        ->for($activity)
        ->forSpecies($dog)
        ->priced($price, $extraAnimalPrice)
        ->create(['quantity' => $units, 'max_animals_per_unit' => $animalsPerUnit])
        ->load(['activity', 'animalTypes']);
}

/**
 * Demande de deux nuits dans dix jours, chaque animal dans sa propre place du type donné.
 *
 * @param  list<Pet>  $pets
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function stayRequest(ActivityUnitType $unitType, array $pets, array $overrides = []): array
{
    return [
        'activity_id' => $unitType->activity_id,
        'start_date' => today()->addDays(10)->toDateString(),
        'end_date' => today()->addDays(12)->toDateString(),
        'units' => array_map(fn (Pet $pet): array => ['unit_type_id' => $unitType->id, 'pet_ids' => [$pet->id]], $pets),
        'payment_method_id' => 'pm_card_visa',
        ...$overrides,
    ];
}

/**
 * Un chien du client, d'une espèce que la place accueille.
 */
function dogOf(User $client, ActivityUnitType $unitType): Pet
{
    return Pet::factory()->for($client)->create(['animal_type_id' => $unitType->animalTypes->firstOrFail()->id]);
}

/**
 * La banque du client autorise le paiement initial, ou demande 3-D Secure (requires_action).
 */
function stripeAuthorizes(FakeStripe $stripe, string $status = 'requires_capture'): FakeStripe
{
    return $stripe
        ->fake('post', '/v1/customers', ['object' => 'customer', 'id' => 'cus_client'])
        ->fake('post', '/v1/payment_intents', fn (array $params): array => [
            'object' => 'payment_intent',
            'id' => 'pi_initial',
            'status' => $status,
            'amount' => $params['amount'],
            'client_secret' => 'pi_initial_secret',
            'latest_charge' => null,
        ]);
}

/**
 * Option de séjour du catalogue de l'entreprise, vendue par l'activité avec son ajustement.
 */
function stayOption(ActivityUnitType $unitType, string $price, string $adjustment = '0', bool $included = false): Service
{
    $activity = $unitType->activity;
    $service = Service::factory()->for($activity->organization)->create();
    ServicePrice::factory()->for($service)->create(['animal_type_id' => $unitType->animalTypes->firstOrFail()->id, 'price' => $price]);
    $activity->services()->attach($service->id, [
        'organization_id' => $activity->organization_id,
        'offered_as' => ServiceOfferEnum::STAY_OPTION,
        'adjustment_percent' => $adjustment,
        'is_included' => $included,
        'is_active' => true,
    ]);

    return $service;
}

const WEBHOOK_SECRET = 'whsec_test';

/**
 * Envoie un événement signé comme le fait Stripe.
 *
 * @param  array<string, mixed>  $object
 */
function postStripeEvent(string $type, array $object, ?string $id = null): TestResponse
{
    config(['services.stripe.webhook_secret' => WEBHOOK_SECRET]);

    $payload = (string) json_encode([
        'id' => $id ?? 'evt_'.Str::random(12),
        'object' => 'event',
        'type' => $type,
        'data' => ['object' => $object],
    ]);
    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", WEBHOOK_SECRET);

    return test()->call('POST', '/api/webhooks/stripe', server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
    ], content: $payload);
}
