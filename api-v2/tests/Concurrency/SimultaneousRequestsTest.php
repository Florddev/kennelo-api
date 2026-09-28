<?php

declare(strict_types=1);

use App\Models\Booking;
use App\Models\User;
use App\Services\Billing\InvoiceNumberGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

function concurrently(Closure $query): mixed
{
    config(['database.connections.concurrent' => config('database.connections.'.config('database.default'))]);
    $connection = DB::connection('concurrent');

    try {
        $connection->statement("set lock_timeout = '200ms'");

        return $connection->transaction(fn () => $query($connection));
    } finally {
        DB::purge('concurrent');
    }
}

function expectToWait(Closure $query): void
{
    $code = null;

    try {
        concurrently($query);
    } catch (QueryException $exception) {
        $code = $exception->getCode();
    }

    expect($code)->toBe('55P03');
}

it('lets only one of two simultaneous requests book the last place', function () {
    $unitType = dogBoarding(units: 1);
    stripeAuthorizes($this->stripe());
    $first = User::factory()->create();
    $second = User::factory()->create();
    $secondRequest = stayRequest($unitType, [dogOf($second, $unitType)]);

    DB::beginTransaction();
    $this->withHeaders(asUser($first))
        ->postJson('/api/bookings', stayRequest($unitType, [dogOf($first, $unitType)]))
        ->assertCreated();

    expectToWait(fn (ConnectionInterface $db) => $db->table('activity_unit_types')->where('id', $unitType->id)->lockForUpdate()->get());

    DB::commit();

    $this->withHeaders(asUser($second))
        ->postJson('/api/bookings', $secondRequest)
        ->assertUnprocessable()
        ->assertJson(['message' => __('booking.full_on', ['date' => $secondRequest['start_date']])]);

    expect(Booking::query()->count())->toBe(1);
});

it('numbers the invoices of one issuer one after the other, and gives back the number of a failed issue', function () {
    $numbers = app(InvoiceNumberGenerator::class);
    $year = 2030;

    expect(DB::transaction(fn () => $numbers->next(null, $year)))->toEndWith('-2030-000001');

    DB::beginTransaction();
    expect($numbers->next(null, $year))->toEndWith('-2030-000002');

    expectToWait(fn (ConnectionInterface $db) => $db->table('invoice_sequences')->whereNull('issuer_organization_id')->where('year', $year)->lockForUpdate()->first());

    DB::rollBack();

    expect(DB::transaction(fn () => $numbers->next(null, $year)))->toEndWith('-2030-000002');
});

it('creates the counter of a new year once when two issues start it together', function () {
    $numbers = app(InvoiceNumberGenerator::class);
    $counter = fn (): array => ['id' => (string) Str::uuid7(), 'issuer_organization_id' => null, 'year' => 2031, 'last_number' => 0];

    DB::beginTransaction();
    expect($numbers->next(null, 2031))->toEndWith('-2031-000001');

    expectToWait(fn (ConnectionInterface $db) => $db->table('invoice_sequences')->insertOrIgnore($counter()));

    DB::commit();

    expect(concurrently(fn (ConnectionInterface $db) => $db->table('invoice_sequences')->insertOrIgnore($counter())))->toBe(0)
        ->and(DB::transaction(fn () => $numbers->next(null, 2031)))->toEndWith('-2031-000002');
});

it('lets only one of two simultaneous requests book the same slot of a resource', function () {
    $this->travelTo(CarbonImmutable::parse('2026-10-05 08:00', 'Europe/Paris'));
    $salon = appointmentSalon();
    stripeAuthorizes($this->stripe());
    $first = User::factory()->create();
    $second = User::factory()->create();
    $secondRequest = appointmentRequest($salon, [petFor($second, $salon['activity'])], '2026-10-06T10:00:00+02:00');

    DB::beginTransaction();
    $this->withHeaders(asUser($first))
        ->postJson('/api/bookings', appointmentRequest($salon, [petFor($first, $salon['activity'])], '2026-10-06T10:00:00+02:00'))
        ->assertCreated();

    expectToWait(fn (ConnectionInterface $db) => $db->table('resources')->where('id', $salon['resource']->id)->lockForUpdate()->first());

    DB::commit();

    $this->withHeaders(asUser($second))
        ->postJson('/api/bookings', $secondRequest)
        ->assertConflict()
        ->assertJson(['message' => __('agenda.slot_unavailable')]);

    expect(Booking::query()->count())->toBe(1);
});
