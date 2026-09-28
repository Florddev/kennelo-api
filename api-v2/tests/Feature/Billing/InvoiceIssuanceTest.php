<?php

declare(strict_types=1);

use App\Enums\InvoiceTypeEnum;
use App\Enums\PaymentKindEnum;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Invoice;
use App\Models\InvoiceSequence;
use App\Models\User;
use App\Services\Billing\InvoiceNumberGenerator;
use App\Services\Billing\InvoiceService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-03-10 09:00', 'Europe/Paris'));
});

/**
 * Factures (ou avoirs) de la réservation : celles de l'entreprise, puis celles de Kennelo, chacune par numéro.
 *
 * @return list<Invoice>
 */
function invoicesOf(Booking $booking, InvoiceTypeEnum $type = InvoiceTypeEnum::INVOICE): array
{
    return $booking->invoices()
        ->where('type', $type)
        ->with(['lines', 'creditedInvoice'])
        ->orderByRaw('issuer_organization_id is null')
        ->orderBy('number')
        ->get()
        ->all();
}

/**
 * Le pro ajoute un bain à 20 € au séjour ; la carte du client est débitée hors session (21,60 €).
 */
function addBath(Booking $booking): BookingPayment
{
    test()->stripe()
        ->fake('get', '/v1/payment_intents/*', ['object' => 'payment_intent', 'id' => 'pi_initial', 'payment_method' => 'pm_saved'])
        ->fake('post', '/v1/payment_intents', ['object' => 'payment_intent', 'id' => 'pi_supplement', 'status' => 'succeeded', 'latest_charge' => 'ch_supplement']);
    $unit = $booking->units()->with('unitType.animalTypes', 'unitType.activity')->sole();
    $dog = dogOf($booking->user, $unit->unitType);
    $booking->pets()->attach($dog->id, ['booking_unit_id' => $unit->id]);
    $bath = stayOption($unit->unitType, '20.00');

    test()->withHeaders(asUser($booking->organization->owner))
        ->postJson("/api/activities/{$booking->activity_id}/bookings/{$booking->id}/items", ['service_id' => $bath->id, 'pet_id' => $dog->id])
        ->assertOk();

    return $booking->payments()->where('kind', PaymentKindEnum::SUPPLEMENT)->sole();
}

describe('payment captured', function () {
    it('issues the invoice of the company and the one of the Kennelo fee', function () {
        $client = User::factory()->create(['first_name' => 'Camille', 'last_name' => 'Martin']);
        $booking = Booking::factory()->occupying(dogBoarding())->for($client)->create();
        $this->stripe()->fake('post', '/v1/payment_intents/*/capture', ['object' => 'payment_intent', 'id' => 'pi_x', 'status' => 'succeeded', 'latest_charge' => 'ch_initial']);

        $this->withHeaders(asUser($booking->organization->owner))
            ->postJson("/api/activities/{$booking->activity_id}/bookings/{$booking->id}/confirm")
            ->assertOk();

        [$company, $fee] = invoicesOf($booking);

        expect($company->number)->toBe('KNL-2026-000001')
            ->and($company->issuer_organization_id)->toBe($booking->organization_id)
            ->and($company->recipient_user_id)->toBe($client->id)
            ->and($company->booking_payment_id)->toBe($booking->payments()->sole()->id)
            ->and([$company->total_ht, $company->total_vat, $company->total_ttc])->toBe(['50.00', '10.00', '60.00'])
            ->and($company->vat_mention)->toBeNull()
            ->and($company->issuer_details['name'])->toBe($booking->organization->legal_name)
            ->and($company->issuer_details['billing_mandate']['issued_by']['name'])->toBe('Kennelo')
            ->and($company->recipient_details['name'])->toBe('Camille Martin')
            ->and($company->lines)->toHaveCount(1)
            ->and($company->lines[0]->description)->toBe('Séjour (Box) du 20/03/2026 au 22/03/2026')
            ->and([$company->lines[0]->unit_price_ttc, $company->lines[0]->vat_rate])->toBe(['60.00', '20.00']);
        expect($fee->number)->toBe('KEN-2026-000001')
            ->and($fee->issuer_organization_id)->toBeNull()
            ->and($fee->recipient_user_id)->toBe($client->id)
            ->and([$fee->total_ht, $fee->total_vat, $fee->total_ttc])->toBe(['4.00', '0.80', '4.80'])
            ->and($fee->issuer_details['name'])->toBe('Kennelo');
    });

    it('applies the VAT exemption of the company, with its mention', function () {
        $booking = invoicedStay(attributes: ['vat_rate' => '0.00']);

        [$company, $fee] = invoicesOf($booking);

        expect([$company->total_ht, $company->total_vat, $company->total_ttc])->toBe(['60.00', '0.00', '60.00'])
            ->and($company->vat_mention)->toBe('TVA non applicable, art. 293 B du CGI')
            ->and($fee->total_vat)->toBe('0.80')
            ->and($fee->vat_mention)->toBeNull();
    });

    it('numbers the invoices of each issuer without gap, year by year', function () {
        $unitType = dogBoarding(units: 3);
        $first = invoicedStay($unitType);
        $second = invoicedStay($unitType);
        $elsewhere = invoicedStay();
        $this->travelTo(CarbonImmutable::parse('2027-01-01 00:30', 'Europe/Paris'));
        $nextYear = invoicedStay($unitType);

        expect(collect([$first, $second, $elsewhere, $nextYear])->map(fn (Booking $booking): array => array_map(
            fn (Invoice $invoice): string => $invoice->number,
            invoicesOf($booking),
        ))->all())->toBe([
            ['KNL-2026-000001', 'KEN-2026-000001'],
            ['KNL-2026-000002', 'KEN-2026-000002'],
            ['KNL-2026-000001', 'KEN-2026-000003'],
            ['KNL-2027-000001', 'KEN-2027-000001'],
        ]);
    });

    it('issues a pair of invoices for a supplement, with its option', function () {
        $booking = invoicedStay();

        $supplement = addBath($booking);

        $invoices = Invoice::query()->where('booking_payment_id', $supplement->id)->with('lines')->orderBy('number')->get();

        expect($invoices->pluck('number')->all())->toBe(['KEN-2026-000002', 'KNL-2026-000002'])
            ->and($invoices->pluck('total_ttc')->all())->toBe(['1.60', '20.00'])
            ->and($invoices[1]->lines->sole()->description)->toEndWith('pour '.$booking->pets()->sole()->name);
    });

    it('issues nothing more when the capture is announced again', function () {
        $booking = invoicedStay();

        app(InvoiceService::class)->invoicePayment($booking->payments()->sole());

        expect($booking->invoices()->count())->toBe(2)
            ->and(InvoiceSequence::query()->pluck('last_number')->all())->toBe([1, 1]);
    });

    it('invoices nothing before the payment is captured', function () {
        $booking = Booking::factory()->occupying(dogBoarding())->create();

        app(InvoiceService::class)->invoicePayment($booking->payments()->sole());

        expect($booking->invoices()->exists())->toBeFalse();
    });

    it('refuses to invoice a payment whose lines do not add up to it', function () {
        // Réservation de factory sans place ni prestation : 60 € payés pour rien.
        $booking = Booking::factory()->confirmed()->create();

        expect(fn () => app(InvoiceService::class)->invoicePayment($booking->payments()->sole()))
            ->toThrow(LogicException::class);
        expect(Invoice::query()->exists())->toBeFalse()
            ->and(InvoiceSequence::query()->value('last_number'))->toBeNull();
    });
});

describe('refund', function () {
    it('credits both invoices when everything is refunded', function () {
        $booking = invoicedStay();
        stripeRefunds($this->stripe());

        $this->withHeaders(asUser($booking->user))->postJson("/api/bookings/{$booking->id}/cancel")->assertOk();

        [$company, $fee] = invoicesOf($booking);
        [$companyCredit, $feeCredit] = invoicesOf($booking, InvoiceTypeEnum::CREDIT_NOTE);

        expect($companyCredit->number)->toBe('KNL-2026-000002')
            ->and($companyCredit->credited_invoice_id)->toBe($company->id)
            ->and($companyCredit->booking_refund_id)->toBe($booking->refunds()->sole()->id)
            ->and([$companyCredit->total_ht, $companyCredit->total_vat, $companyCredit->total_ttc])->toBe(['50.00', '10.00', '60.00'])
            ->and($companyCredit->lines->sole()->description)->toBe('Remboursement après annulation par le client (facture KNL-2026-000001)');
        expect($feeCredit->number)->toBe('KEN-2026-000002')
            ->and($feeCredit->credited_invoice_id)->toBe($fee->id)
            ->and($feeCredit->total_ttc)->toBe('4.80');
    });

    it('credits only the company when the Kennelo fee is kept', function () {
        $booking = invoicedStay(attributes: ['cancellation_policy' => 'moderate', 'start_date' => today()->addDays(3)->toDateString(), 'end_date' => today()->addDays(5)->toDateString()]);
        stripeRefunds($this->stripe());

        $this->withHeaders(asUser($booking->user))->postJson("/api/bookings/{$booking->id}/cancel")->assertOk();

        $credit = invoicesOf($booking, InvoiceTypeEnum::CREDIT_NOTE);

        expect($credit)->toHaveCount(1)
            ->and($credit[0]->issuer_organization_id)->toBe($booking->organization_id)
            ->and($credit[0]->total_ttc)->toBe('30.00');
    });

    it('credits each payment for what it covered', function () {
        $booking = invoicedStay();
        $supplement = addBath($booking);
        stripeRefunds($this->stripe());

        $this->withHeaders(asUser($booking->user))->postJson("/api/bookings/{$booking->id}/cancel")->assertOk();

        $refunds = $booking->refunds()->get()->keyBy('booking_payment_id');
        $initial = $booking->payments()->where('kind', PaymentKindEnum::INITIAL)->sole();

        // Les frais Kennelo rendus restent ceux de chaque paiement : 1,60 € sur le complément, 4,80 € sur l'initial.
        expect([$refunds[$supplement->id]->amount, $refunds[$supplement->id]->service_fee_amount])->toBe(['21.60', '1.60'])
            ->and([$refunds[$initial->id]->amount, $refunds[$initial->id]->service_fee_amount])->toBe(['64.80', '4.80']);

        foreach (invoicesOf($booking, InvoiceTypeEnum::CREDIT_NOTE) as $credit) {
            expect($credit->total_ttc)->toBe($credit->creditedInvoice?->total_ttc)
                ->and($credit->booking_payment_id)->toBe($credit->creditedInvoice?->booking_payment_id);
        }
        expect(invoicesOf($booking, InvoiceTypeEnum::CREDIT_NOTE))->toHaveCount(4);
    });

    it('credits a removed option on the invoices of its supplement', function () {
        $booking = invoicedStay();
        $supplement = addBath($booking);
        stripeRefunds($this->stripe());
        $item = $supplement->items()->sole();

        $this->withHeaders(asUser($booking->organization->owner))
            ->deleteJson("/api/activities/{$booking->activity_id}/bookings/{$booking->id}/items/{$item->id}")
            ->assertOk();

        $credits = collect(invoicesOf($booking, InvoiceTypeEnum::CREDIT_NOTE));

        expect($credits->map(fn (Invoice $credit): array => [$credit->creditedInvoice?->booking_payment_id, $credit->total_ttc])->all())
            ->toBe([[$supplement->id, '20.00'], [$supplement->id, '1.60']])
            ->and($credits[0]->lines->sole()->description)->toStartWith('Remboursement d\'une prestation retirée');
    });

    it('issues the missing invoices before their credit notes', function () {
        $booking = Booking::factory()->confirmed()->occupying(dogBoarding())->create();
        stripeRefunds($this->stripe());

        $this->withHeaders(asUser($booking->user))->postJson("/api/bookings/{$booking->id}/cancel")->assertOk();

        expect(array_map(fn (Invoice $invoice): string => $invoice->number, invoicesOf($booking)))->toBe(['KNL-2026-000001', 'KEN-2026-000001'])
            ->and(array_map(fn (Invoice $invoice): string => $invoice->number, invoicesOf($booking, InvoiceTypeEnum::CREDIT_NOTE)))->toBe(['KNL-2026-000002', 'KEN-2026-000002']);
    });

    it('issues nothing more when the refund is announced again', function () {
        $booking = invoicedStay();
        stripeRefunds($this->stripe());
        $this->withHeaders(asUser($booking->user))->postJson("/api/bookings/{$booking->id}/cancel")->assertOk();

        app(InvoiceService::class)->creditRefunds($booking);

        expect($booking->invoices()->count())->toBe(4);
    });
});

it('never changes an issued invoice', function () {
    $invoice = invoicesOf(invoicedStay())[0];

    expect(fn () => $invoice->update(['total_ttc' => '1.00']))->toThrow(LogicException::class)
        ->and(fn () => $invoice->delete())->toThrow(LogicException::class);
});

it('gives its number back when the issue fails', function () {
    $numbers = app(InvoiceNumberGenerator::class);

    try {
        DB::transaction(function () use ($numbers): void {
            $numbers->next(null, 2026);

            throw new RuntimeException('Issue failed');
        });
    } catch (RuntimeException) {
        // L'émission a échoué : sa transaction est annulée.
    }

    expect(DB::transaction(fn (): string => $numbers->next(null, 2026)))->toBe('KEN-2026-000001');
});
