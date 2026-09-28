<?php

declare(strict_types=1);

use App\Enums\OrganizationRoleEnum;
use App\Enums\PayoutStatusEnum;
use App\Enums\ServiceOfferEnum;
use App\Enums\WeekDayEnum;
use App\Models\Activity;
use App\Models\ActivityUnitType;
use App\Models\AgendaResource;
use App\Models\AnimalType;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Pet;
use App\Models\Profession;
use App\Models\Service;
use App\Models\ServicePrice;
use App\Models\User;
use App\Services\Billing\CommissionStatementService;
use App\Services\Billing\InvoiceService;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\ConcurrencyTestCase;
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

pest()->extend(ConcurrencyTestCase::class)
    ->group('pgsql')
    ->in('Concurrency');

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

function requiresPostgres(): void
{
    if (DB::connection()->getDriverName() !== 'pgsql') {
        test()->markTestSkipped('PostgreSQL only.');
    }
}

function expectViolation(string $constraint, Closure $write): void
{
    $message = DB::connection()->getDriverName() === 'pgsql' ? "\"{$constraint}\"" : null;

    expect(fn () => DB::transaction($write))->toThrow(QueryException::class, $message);
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
 * Stripe accepte chaque remboursement demandé.
 */
function stripeRefunds(FakeStripe $stripe): FakeStripe
{
    return $stripe->fake('post', '/v1/refunds', fn (array $params): array => [
        'object' => 'refund',
        'id' => 're_'.$params['amount'],
        'amount' => $params['amount'],
        'status' => 'succeeded',
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

/**
 * Séjour confirmé d'une place à 60 € (64,80 € payés dont 4,80 € de frais Kennelo), dont le paiement initial est
 * facturé : la facture de l'entreprise et celle des frais Kennelo.
 *
 * @param  array<string, mixed>  $attributes
 */
function invoicedStay(?ActivityUnitType $unitType = null, array $attributes = []): Booking
{
    $booking = Booking::factory()->confirmed()->occupying($unitType ?? dogBoarding())->create($attributes);
    app(InvoiceService::class)->invoicePayment($booking->payments()->sole());

    return $booking;
}

/**
 * La réservation est versée à l'entreprise ce mois-ci, puis son récapitulatif de commission est émis.
 */
function withCommissionStatement(Booking $booking): Invoice
{
    $booking->payout()->create([
        'stripe_transfer_id' => 'tr_'.fake()->unique()->bothify('????????????'),
        'stripe_account_id' => 'acct_test',
        'amount' => $booking->activity_amount,
        'currency' => 'EUR',
        'status' => PayoutStatusEnum::PAID,
        'transferred_at' => now(),
    ]);

    return app(CommissionStatementService::class)->issue($booking->organization()->firstOrFail(), CarbonImmutable::now('Europe/Paris'))
        ?? throw new LogicException('No statement issued.');
}

/**
 * La facture de l'entreprise, puis celle des frais Kennelo, du paiement initial.
 *
 * @return array{Invoice, Invoice}
 */
function bookingInvoices(Booking $booking): array
{
    $invoices = $booking->invoices()->orderByRaw('issuer_organization_id is null')->get();

    return [$invoices[0], $invoices[1]];
}

/**
 * Salon de toilettage réservable sur rendez-vous, ouvert tous les jours de 9 h à 18 h (Europe/Paris) : un
 * toilettage pour chien vendu seul, à $price pour $duration minutes, et Léa, toiletteuse de l'équipe, planifiée
 * aux mêmes heures.
 *
 * @return array{activity: Activity, service: Service, resource: AgendaResource}
 */
function appointmentSalon(string $price = '40.00', int $duration = 60): array
{
    $dog = AnimalType::query()->where('code', 'dog')->first() ?? AnimalType::factory()->dog()->create();

    $activity = Activity::factory()
        ->bookable()
        ->for(Profession::factory()->forSpecies($dog))
        ->forSpecies($dog)
        ->create();
    $activity->openingHours()->createMany(array_map(
        fn (WeekDayEnum $day): array => ['weekday' => $day, 'opens_at' => '09:00', 'closes_at' => '18:00'],
        WeekDayEnum::cases(),
    ));

    $service = Service::factory()->for($activity->organization)->create(['name' => 'Toilettage', 'requires_scheduling' => true]);
    ServicePrice::factory()->for($service)->create(['animal_type_id' => $dog->id, 'price' => $price, 'duration_minutes' => $duration]);
    $activity->services()->attach($service->id, [
        'organization_id' => $activity->organization_id,
        'offered_as' => ServiceOfferEnum::STANDALONE,
        'adjustment_percent' => '0',
        'is_included' => false,
        'is_active' => true,
    ]);

    $lea = AgendaResource::factory()
        ->staff(OrganizationMember::factory()->for($activity->organization)->create())
        ->scheduledIn($activity)
        ->create(['name' => 'Léa']);

    return ['activity' => $activity, 'service' => $service, 'resource' => $lea];
}

/**
 * Un chien du client, d'une espèce que l'activité accueille.
 */
function petFor(User $client, Activity $activity): Pet
{
    return Pet::factory()->for($client)->create(['animal_type_id' => $activity->animalTypes()->firstOrFail()->id]);
}

/**
 * Demande de rendez-vous au salon, les animaux passant dans l'ordre donné.
 *
 * @param  array{activity: Activity, service: Service, resource: AgendaResource}  $salon
 * @param  list<Pet>  $pets
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function appointmentRequest(array $salon, array $pets, string $startsAt, array $overrides = []): array
{
    return [
        'activity_id' => $salon['activity']->id,
        'service_id' => $salon['service']->id,
        'pet_ids' => array_map(fn (Pet $pet): string => $pet->id, $pets),
        'starts_at' => $startsAt,
        'payment_method_id' => 'pm_card_visa',
        ...$overrides,
    ];
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
