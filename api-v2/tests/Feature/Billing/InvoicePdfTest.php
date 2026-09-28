<?php

declare(strict_types=1);

use App\Enums\OrganizationRoleEnum;
use App\Enums\RefundReasonEnum;
use App\Enums\VatRegimeEnum;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Billing\InvoicePdfRenderer;
use App\Services\Billing\InvoiceService;
use Carbon\CarbonImmutable;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-03-10 09:00', 'Europe/Paris'));
});

function pdfDisk(): FilesystemAdapter
{
    /** @var FilesystemAdapter */
    return Storage::disk((string) config('billing.pdf_disk'));
}

it('produces the PDF of each invoice once it is issued', function () {
    [$company, $fee] = bookingInvoices(invoicedStay());
    $pdfs = app(InvoicePdfRenderer::class);

    expect($pdfs->path($company))->toBe("invoices/{$company->issuer_organization_id}/KNL-2026-000001.pdf")
        ->and($pdfs->path($fee))->toBe('invoices/kennelo/KEN-2026-000001.pdf')
        ->and(pdfDisk()->get($pdfs->path($company)))->toStartWith('%PDF')
        ->and(pdfDisk()->exists($pdfs->path($fee)))->toBeTrue();
});

it('produces no PDF when the issue is rolled back', function () {
    $booking = Booking::factory()->confirmed()->occupying(dogBoarding())->create();

    try {
        DB::transaction(function () use ($booking): never {
            app(InvoiceService::class)->invoicePayment($booking->payments()->sole());

            throw new RuntimeException('Rolled back.');
        });
    } catch (RuntimeException) {
    }

    expect(Invoice::query()->exists())->toBeFalse()
        ->and(pdfDisk()->allFiles())->toBe([]);
});

it('downloads the PDF for whoever reads the invoice', function () {
    $booking = invoicedStay();
    [$company] = bookingInvoices($booking);

    $response = $this->withHeaders(asUser($booking->user))
        ->get("/api/invoices/{$company->id}/pdf")
        ->assertOk()
        ->assertDownload('KNL-2026-000001.pdf')
        ->assertHeader('Content-Type', 'application/pdf');

    expect($response->streamedContent())->toStartWith('%PDF');

    $this->withHeaders(asUser(memberOf($booking->organization, OrganizationRoleEnum::ACCOUNTANT)))
        ->get("/api/invoices/{$company->id}/pdf")
        ->assertOk();
});

it('keeps the PDF produced at issue, and produces a missing one on download', function () {
    $booking = invoicedStay();
    [$company, $fee] = bookingInvoices($booking);
    $pdfs = app(InvoicePdfRenderer::class);
    pdfDisk()->put($pdfs->path($company), '%PDF issued');
    pdfDisk()->delete($pdfs->path($fee));

    $issued = $this->withHeaders(asUser($booking->user))->get("/api/invoices/{$company->id}/pdf")->assertOk();
    $this->withHeaders(asUser($booking->user))->get("/api/invoices/{$fee->id}/pdf")->assertOk();

    expect($issued->streamedContent())->toBe('%PDF issued')
        ->and(pdfDisk()->exists($pdfs->path($fee)))->toBeTrue();
});

it('keeps the PDF from the other members and from strangers', function () {
    $booking = invoicedStay();
    [$company, $fee] = bookingInvoices($booking);

    $this->withHeaders(asUser(memberOf($booking->organization, OrganizationRoleEnum::EMPLOYEE, $booking->activity_id)))
        ->get("/api/invoices/{$company->id}/pdf")
        ->assertForbidden();
    $this->withHeaders(asUser(memberOf($booking->organization, OrganizationRoleEnum::ACCOUNTANT)))
        ->get("/api/invoices/{$fee->id}/pdf")
        ->assertNotFound();
    $this->withHeaders(asUser(User::factory()->create()))
        ->get("/api/invoices/{$company->id}/pdf")
        ->assertNotFound();
});

describe('page', function () {
    it('shows the invoice issued in the name of the company', function () {
        $booking = invoicedStay();
        [$company] = bookingInvoices($booking);

        $html = app(InvoicePdfRenderer::class)->html($company);

        expect($html)
            ->toContain('>Facture<', 'N° KNL-2026-000001', 'Date : 10/03/2026')
            ->toContain('Facture émise par Kennelo au nom et pour le compte de '.e($booking->organization->legal_name))
            ->toContain('mandat de facturation accepté le 10/03/2026')
            ->toContain('60,00 €', 'Facture acquittée');
        expect($html)->not->toContain('art. 293 B');
    });

    it('carries the VAT exemption of a company under the franchise', function () {
        $unitType = dogBoarding();
        $unitType->activity->organization->update(['vat_regime' => VatRegimeEnum::FRANCHISE]);
        [$company] = bookingInvoices(invoicedStay($unitType, ['vat_rate' => '0.00']));

        expect(app(InvoicePdfRenderer::class)->html($company))
            ->toContain('TVA non applicable, art. 293 B du CGI', '0,00 %');
    });

    it('refers a credit note to the invoice it cancels', function () {
        $booking = invoicedStay();
        [$company] = bookingInvoices($booking);
        $booking->payments()->sole()->refunds()->create([
            'stripe_refund_id' => 're_test',
            'amount' => '60.00',
            'service_fee_amount' => '0.00',
            'reason' => RefundReasonEnum::PRO_CANCELLATION,
        ]);
        $creditNote = app(InvoiceService::class)->creditRefunds($booking)->sole();

        $html = app(InvoicePdfRenderer::class)->html($creditNote);

        expect($html)
            ->toContain('>Avoir<', 'Avoir sur la facture n° '.$company->number.' du 10/03/2026', 'moyen de paiement d')
            ->toContain('Avoir émis par Kennelo au nom et pour le compte de '.e($booking->organization->legal_name));
        expect($html)->not->toContain('Facture acquittée');
    });

    it('shows the period of a commission statement', function () {
        $statement = withCommissionStatement(invoicedStay());

        $html = app(InvoicePdfRenderer::class)->html($statement);

        expect($html)->toContain('N° KEN-2026-000002', 'Période : du 01/03/2026 au 31/03/2026.', 'Montant retenu sur les versements de la période.');
        expect($html)->not->toContain('au nom et pour le compte de');
    });
});
