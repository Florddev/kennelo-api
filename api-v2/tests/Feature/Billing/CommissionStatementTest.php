<?php

declare(strict_types=1);

use App\Enums\BookingStatusEnum;
use App\Enums\PayoutStatusEnum;
use App\Models\ActivityUnitType;
use App\Models\Booking;
use App\Models\Invoice;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

beforeEach(function () {
    // Le 1er septembre au matin : la commande facture le mois d'août.
    $this->travelTo(CarbonImmutable::parse('2026-09-01 04:00', 'Europe/Paris'));
});

/**
 * Séjour terminé de l'activité (4,80 € de commission), versé à l'entreprise à $at (heure de Paris).
 *
 * @param  array<string, mixed>  $attributes
 */
function paidOutStay(ActivityUnitType $unitType, string $at, PayoutStatusEnum $status = PayoutStatusEnum::PAID, array $attributes = []): Booking
{
    $booking = Booking::factory()->confirmed()->status(BookingStatusEnum::COMPLETED)->occupying($unitType)->create($attributes);
    $booking->payout()->create([
        'stripe_transfer_id' => 'tr_'.fake()->unique()->bothify('????????????'),
        'stripe_account_id' => 'acct_test',
        'amount' => $booking->activity_amount,
        'currency' => 'EUR',
        'status' => $status,
        'transferred_at' => CarbonImmutable::parse($at, 'Europe/Paris')->utc(),
    ]);

    return $booking;
}

it('invoices each company the commissions of the bookings paid out last month', function () {
    $kennel = dogBoarding();
    $first = paidOutStay($kennel, '2026-08-05 06:00');
    $second = paidOutStay($kennel, '2026-08-20 06:00');
    $cattery = dogBoarding();
    paidOutStay($cattery, '2026-08-31 23:30');

    $this->artisan('billing:issue-commission-statements')
        ->expectsOutputToContain('2 issued, 0 failed')
        ->assertSuccessful();

    $statement = Invoice::query()->where('recipient_organization_id', $kennel->activity->organization_id)->with('lines')->sole();

    expect($statement->issuer_organization_id)->toBeNull()
        ->and($statement->recipient_user_id)->toBeNull()
        ->and($statement->booking_id)->toBeNull()
        ->and($statement->period_start?->toDateString())->toBe('2026-08-01')
        ->and($statement->period_end?->toDateString())->toBe('2026-08-31')
        ->and($statement->recipient_details['name'])->toBe($kennel->activity->organization->legal_name)
        ->and([$statement->total_ht, $statement->total_vat, $statement->total_ttc])->toBe(['8.00', '1.60', '9.60'])
        ->and($statement->lines->pluck('description')->all())->toBe([
            'Commission Kennelo, réservation '.mb_strtoupper(mb_substr($first->id, 0, 8)).' chez '.$kennel->activity->name.' du '.$first->start_date->format('d/m/Y').' au '.$first->end_date->format('d/m/Y'),
            'Commission Kennelo, réservation '.mb_strtoupper(mb_substr($second->id, 0, 8)).' chez '.$kennel->activity->name.' du '.$second->start_date->format('d/m/Y').' au '.$second->end_date->format('d/m/Y'),
        ]);
    expect(Invoice::query()->where('recipient_organization_id', $cattery->activity->organization_id)->value('total_ttc'))->toBe('4.80')
        ->and(Invoice::query()->pluck('number')->sort()->values()->all())->toBe(['KEN-2026-000001', 'KEN-2026-000002']);
});

it('leaves out what was not paid out in the month, or given back', function () {
    $kennel = dogBoarding();
    paidOutStay($kennel, '2026-07-31 23:30');
    paidOutStay($kennel, '2026-09-01 00:30');
    paidOutStay($kennel, '2026-08-10 06:00', PayoutStatusEnum::CANCELED);
    paidOutStay($kennel, '2026-08-10 06:00', attributes: ['platform_fee' => '0.00', 'activity_amount' => '60.00']);

    $this->artisan('billing:issue-commission-statements')->assertSuccessful();

    expect(Invoice::query()->exists())->toBeFalse();
});

it('issues nothing more when run again', function () {
    $kennel = dogBoarding();
    paidOutStay($kennel, '2026-08-05 06:00');

    $this->artisan('billing:issue-commission-statements')->assertSuccessful();
    paidOutStay($kennel, '2026-08-06 06:00');
    $this->artisan('billing:issue-commission-statements')->expectsOutputToContain('0 issued')->assertSuccessful();

    expect(Invoice::query()->sole()->total_ttc)->toBe('4.80');
});

it('invoices the month asked for, and a company closed since', function () {
    $kennel = dogBoarding();
    paidOutStay($kennel, '2026-06-15 06:00');
    $kennel->activity->organization->delete();

    $this->artisan('billing:issue-commission-statements', ['--month' => '2026-06'])->assertSuccessful();

    expect(Invoice::query()->sole()->period_start?->toDateString())->toBe('2026-06-01');
});

it('rejects a malformed month', function () {
    $this->artisan('billing:issue-commission-statements', ['--month' => 'June'])->assertExitCode(Command::INVALID);
});
