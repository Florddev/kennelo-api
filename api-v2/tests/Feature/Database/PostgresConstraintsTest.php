<?php

declare(strict_types=1);

use App\Enums\OrganizationRoleEnum;
use App\Enums\RefundReasonEnum;
use App\Enums\ResourceBookingKindEnum;
use App\Models\Activity;
use App\Models\ActivityPeriodPrice;
use App\Models\AgendaResource;
use App\Models\Booking;
use App\Models\InvoiceSequence;
use App\Models\OrganizationMember;
use App\Models\PricingPeriod;
use App\Models\Profession;
use App\Models\ResourceBooking;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServicePrice;
use App\Services\Agenda\AgendaService;
use App\Services\Agenda\Exceptions\SlotUnavailableException;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

// Garde-fous que seule PostgreSQL applique : SQLite n'ajoute pas de CHECK après coup, ignore NULLS NOT DISTINCT
// et ne connaît pas les contraintes d'exclusion.

pest()->group('pgsql');

beforeEach(fn () => requiresPostgres());

/**
 * Écrit directement en base des valeurs que l'application n'écrirait jamais.
 *
 * @param  array<string, mixed>  $values
 */
function rewriteRow(Model $row, array $values): int
{
    return DB::table($row->getTable())->where($row->getKeyName(), $row->getKey())->update($values);
}

it('refuses what a check constraint forbids', function (string $constraint, Closure $write) {
    expectViolation($constraint, $write);
})->with([
    'professions_location_check' => ['professions_location_check', fn () => rewriteRow(Profession::factory()->create(), ['allows_at_pro' => false, 'allows_at_client' => false, 'allows_remote' => false])],
    'pricing_periods_dates_pair_check' => ['pricing_periods_dates_pair_check', fn () => rewriteRow(PricingPeriod::factory()->create(), ['end_date' => null])],
    'pricing_periods_dates_order_check' => ['pricing_periods_dates_order_check', fn () => rewriteRow(PricingPeriod::factory()->create(), ['start_date' => '2030-08-31', 'end_date' => '2030-07-01'])],
    'activities_location_check' => ['activities_location_check', fn () => rewriteRow(Activity::factory()->create(), ['serves_at_pro' => false, 'serves_at_client' => false, 'serves_remote' => false])],
    'activities_radius_check' => ['activities_radius_check', fn () => rewriteRow(Activity::factory()->create(), ['serves_at_client' => true, 'service_radius_km' => null])],
    'resources_staff_member_check' => ['resources_staff_member_check', fn () => rewriteRow(AgendaResource::factory()->staff(OrganizationMember::factory()->create())->create(), ['type' => 'equipment'])],
    'service_prices_values_check' => ['service_prices_values_check', fn () => rewriteRow(ServicePrice::factory()->create(), ['price' => -1])],
    'service_package_items_self_check' => ['service_package_items_self_check', function () {
        $package = Service::factory()->package()->create();
        DB::table('service_package_items')->insert(['package_id' => $package->id, 'service_id' => $package->id]);
    }],
    'organization_member_roles_scope_check' => ['organization_member_roles_scope_check', function () {
        $activity = Activity::factory()->create();
        $member = OrganizationMember::factory()->for($activity->organization)->withRole(OrganizationRoleEnum::EMPLOYEE, $activity->id)->create();
        rewriteRow($member->roles()->sole(), ['activity_id' => null]);
    }],
    'activity_services_adjustment_check' => ['activity_services_adjustment_check', function () {
        stayOption(dogBoarding(), '10.00');
        DB::table('activity_services')->update(['adjustment_percent' => 150]);
    }],
    'activity_opening_hours_range_check' => ['activity_opening_hours_range_check', function () {
        appointmentSalon();
        DB::table('activity_opening_hours')->update(['closes_at' => '09:00']);
    }],
    'activity_unit_types_quantity_check' => ['activity_unit_types_quantity_check', fn () => rewriteRow(dogBoarding(), ['quantity' => 0])],
    'resource_schedules_range_check' => ['resource_schedules_range_check', function () {
        appointmentSalon();
        DB::table('resource_schedules')->update(['end_time' => '09:00']);
    }],
    'activity_period_prices_amounts_check' => ['activity_period_prices_amounts_check', function () {
        dogBoarding();
        DB::table('activity_period_prices')->update(['price' => -1]);
    }],
    'bookings_dates_check' => ['bookings_dates_check', fn () => rewriteRow(Booking::factory()->create(), ['end_date' => today()->addDays(5)->toDateString()])],
    'bookings_amounts_check' => ['bookings_amounts_check', fn () => rewriteRow(Booking::factory()->create(), ['travel_fee' => -1])],
    'bookings_service_address_check' => ['bookings_service_address_check', fn () => rewriteRow(Booking::factory()->create(), ['location_mode' => 'at_client'])],
    'booking_payments_amount_check' => ['booking_payments_amount_check', fn () => rewriteRow(Booking::factory()->create()->payments()->sole(), ['service_fee' => 999])],
    'booking_units_values_check' => ['booking_units_values_check', fn () => rewriteRow(Booking::factory()->occupying(dogBoarding())->create()->units()->sole(), ['nights' => 0])],
    'reviews_overall_rating_check' => ['reviews_overall_rating_check', fn () => rewriteRow(Review::factory()->create(), ['overall_rating' => 5.5])],
    'booking_items_schedule_check' => ['booking_items_schedule_check', fn () => rewriteRow(Booking::factory()->appointment(appointmentSalon(), '2030-03-11T10:00:00+01:00')->create()->items()->sole(), ['ends_at' => null])],
    'booking_items_amounts_check' => ['booking_items_amounts_check', fn () => rewriteRow(Booking::factory()->appointment(appointmentSalon(), '2030-03-11T10:00:00+01:00')->create()->items()->sole(), ['quantity' => 0])],
    'booking_refunds_amounts_check' => ['booking_refunds_amounts_check', fn () => Booking::factory()->confirmed()->create()->payments()->sole()->refunds()->create([
        'amount' => '10.00',
        'service_fee_amount' => '20.00',
        'reason' => RefundReasonEnum::ADJUSTMENT,
    ])],
    'resource_bookings_range_check' => ['resource_bookings_range_check', function () {
        $entry = ResourceBooking::factory()->create();
        rewriteRow($entry, ['ends_at' => $entry->starts_at]);
    }],
    'resource_bookings_kind_check' => ['resource_bookings_kind_check', fn () => rewriteRow(ResourceBooking::factory()->create(), ['kind' => 'booking'])],
    'invoices_recipient_check' => ['invoices_recipient_check', fn () => rewriteRow(bookingInvoices(invoicedStay())[0], ['recipient_user_id' => null])],
    'invoices_credit_note_check' => ['invoices_credit_note_check', fn () => rewriteRow(bookingInvoices(invoicedStay())[0], ['type' => 'credit_note'])],
]);

describe('unique even where a column is empty (NULLS NOT DISTINCT)', function () {
    it('refuses a second price for the same line of the grid', function () {
        $price = ServicePrice::factory()->create(['size_class' => null, 'coat_type' => null, 'animal_breed_id' => null]);

        expectViolation('service_prices_unique', fn () => $price->replicate()->save());
    });

    it('refuses a second organization-wide role for the same member', function () {
        $member = OrganizationMember::factory()->withRole(OrganizationRoleEnum::MANAGER)->create();

        expectViolation('organization_member_roles_unique', fn () => $member->roles()->sole()->replicate()->fill(['role' => OrganizationRoleEnum::ACCOUNTANT])->save());
    });

    it('refuses a second every-day price for the same unit and period', function () {
        dogBoarding();

        expectViolation('activity_period_prices_unique', fn () => ActivityPeriodPrice::query()->whereNull('weekday')->sole()->replicate()->save());
    });

    it('keeps one Kennelo counter per year', function () {
        InvoiceSequence::query()->create(['issuer_organization_id' => null, 'year' => 2030, 'last_number' => 0]);

        expectViolation('invoice_sequences_issuer_year_unique', fn () => InvoiceSequence::query()->create(['issuer_organization_id' => null, 'year' => 2030, 'last_number' => 0]));
    });

    it('gives a Kennelo invoice number to one invoice, and invoices a payment once', function () {
        [, $kennelo] = bookingInvoices(invoicedStay());

        expect($kennelo->issuer_organization_id)->toBeNull();
        expectViolation('invoices_issuer_number_unique', fn () => $kennelo->replicate()->forceFill(['booking_payment_id' => Booking::factory()->create()->payments()->value('id')])->save());
        expectViolation('invoices_payment_issuer_unique', fn () => $kennelo->replicate()->forceFill(['number' => 'KEN-2099-999999'])->save());
    });
});

describe('a resource is never booked twice at once', function () {
    it('refuses two entries of the same resource that overlap', function () {
        $absence = ResourceBooking::factory()->between('2030-03-11T10:00:00+01:00', '2030-03-11T12:00:00+01:00')->create();

        // Bout à bout, ou sur une autre ressource : accepté.
        ResourceBooking::factory()->for($absence->resource, 'resource')->between('2030-03-11T12:00:00+01:00', '2030-03-11T13:00:00+01:00')->create();
        ResourceBooking::factory()->between('2030-03-11T10:00:00+01:00', '2030-03-11T12:00:00+01:00')->create();

        expectViolation('resource_bookings_no_overlap', fn () => ResourceBooking::factory()->for($absence->resource, 'resource')->between('2030-03-11T11:30:00+01:00', '2030-03-11T12:30:00+01:00')->create());
    });

    it('refuses an appointment during an absence', function () {
        $salon = appointmentSalon();
        ResourceBooking::factory()->for($salon['resource'], 'resource')->between('2030-03-11T09:00:00+01:00', '2030-03-11T12:00:00+01:00')->create();

        expectViolation('resource_bookings_no_overlap', fn () => Booking::factory()->appointment($salon, '2030-03-11T10:00:00+01:00')->create());
    });

    it('answers that the slot is taken when a simultaneous request took it first', function () {
        $resource = AgendaResource::factory()->create();
        $start = CarbonImmutable::parse('2030-03-11T10:00:00+01:00')->utc();

        // La demande concurrente s'intercale entre la vérification du créneau et l'écriture.
        Event::listen('eloquent.creating: '.ResourceBooking::class, fn () => DB::table('resource_bookings')->insert([
            'id' => (string) Str::uuid7(),
            'resource_id' => $resource->id,
            'kind' => ResourceBookingKindEnum::ABSENCE->value,
            'starts_at' => $start,
            'ends_at' => $start->addHour(),
        ]));

        expect(fn () => DB::transaction(fn () => app(AgendaService::class)->occupy($resource, $start, $start->addHour(), ResourceBookingKindEnum::ABSENCE)))
            ->toThrow(SlotUnavailableException::class);
    });
});
