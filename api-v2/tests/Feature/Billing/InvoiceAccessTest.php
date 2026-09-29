<?php

declare(strict_types=1);

use App\Enums\OrganizationRoleEnum;
use App\Models\User;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-03-10 09:00', 'Europe/Paris'));
});

describe('client', function () {
    it('lists the invoices received, not the ones of others', function () {
        $booking = invoicedStay();
        invoicedStay();

        $this->withHeaders(asUser($booking->user))
            ->getJson('/api/user/invoices')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.*.booking_id', [$booking->id, $booking->id])
            ->assertJsonPath('data.*.issuer', fn (array $issuers): bool => collect($issuers)->sort()->values()->all() === ['kennelo', 'organization'])
            ->assertJsonPath('data.0.lines.0.quantity', '1.00');

        $this->withHeaders(asUser($booking->user))
            ->getJson('/api/user/invoices?type=credit_note')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('reads both invoices of a booking', function () {
        $booking = invoicedStay();
        [$company, $fee] = bookingInvoices($booking);

        $this->withHeaders(asUser($booking->user))
            ->getJson("/api/invoices/{$company->id}")
            ->assertOk()
            ->assertJsonPath('number', $company->number)
            ->assertJsonPath('total_ttc', '60.00')
            ->assertJsonPath('lines.0.total_ht', '50.00');
        $this->withHeaders(asUser($booking->user))->getJson("/api/invoices/{$fee->id}")->assertOk();
    });
});

describe('company', function () {
    it('lists the invoices issued and received', function () {
        $booking = invoicedStay();
        [$company] = bookingInvoices($booking);
        $statement = withCommissionStatement($booking);
        $organization = $booking->organization;
        $headers = asUser(memberOf($organization, OrganizationRoleEnum::ACCOUNTANT));

        $this->withHeaders($headers)
            ->getJson("/api/organizations/{$organization->id}/invoices")
            ->assertOk()
            ->assertJsonCount(2, 'data');
        $this->withHeaders($headers)
            ->getJson("/api/organizations/{$organization->id}/invoices?direction=issued")
            ->assertOk()
            ->assertJsonPath('data.*.id', [$company->id]);
        $this->withHeaders($headers)
            ->getJson("/api/organizations/{$organization->id}/invoices?direction=received")
            ->assertOk()
            ->assertJsonPath('data.*.id', [$statement->id])
            ->assertJsonPath('data.0.period_start', '2026-03-01');
    });

    it('keeps the invoices to the members who see the finances', function () {
        $booking = invoicedStay();
        $organization = $booking->organization;

        $this->withHeaders(asUser(memberOf($organization, OrganizationRoleEnum::EMPLOYEE, $booking->activity_id)))
            ->getJson("/api/organizations/{$organization->id}/invoices")
            ->assertForbidden();
        $this->withHeaders(asUser(User::factory()->create()))
            ->getJson("/api/organizations/{$organization->id}/invoices")
            ->assertNotFound();
    });

    it('reads its own invoices, not the Kennelo fee charged to its client', function () {
        $booking = invoicedStay();
        [$company, $fee] = bookingInvoices($booking);
        $headers = asUser(memberOf($booking->organization, OrganizationRoleEnum::ACCOUNTANT));

        $this->withHeaders($headers)->getJson("/api/invoices/{$company->id}")->assertOk();
        $this->withHeaders($headers)->getJson("/api/invoices/{$fee->id}")->assertNotFound();
    });
});

it('hides an invoice from anyone else', function () {
    [$company] = bookingInvoices(invoicedStay());

    $this->withHeaders(asUser(User::factory()->create()))
        ->getJson("/api/invoices/{$company->id}")
        ->assertNotFound();
});

describe('admin', function () {
    it('lists every invoice, with filters', function () {
        $booking = invoicedStay();
        invoicedStay();
        $headers = asUser(adminUser());

        $this->withHeaders($headers)->getJson('/api/admin/invoices')->assertOk()->assertJsonCount(4, 'data');
        $this->withHeaders($headers)->getJson('/api/admin/invoices?issuer=kennelo')->assertOk()->assertJsonPath('data.*.number', ['KEN-2026-000002', 'KEN-2026-000001']);
        $this->withHeaders($headers)->getJson("/api/admin/invoices?organization_id={$booking->organization_id}")->assertOk()->assertJsonCount(1, 'data');
        $this->withHeaders($headers)->getJson('/api/admin/invoices?search=KNL-2026')->assertOk()->assertJsonCount(2, 'data');
    });

    it('is not open to other users', function () {
        $this->withHeaders(asUser(User::factory()->create()))->getJson('/api/admin/invoices')->assertForbidden();
    });
});
