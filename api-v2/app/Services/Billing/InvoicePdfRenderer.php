<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\InvoiceTypeEnum;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Traits\Localizable;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * PDF d'une facture ou d'un avoir, dans la langue de la facturation. Il est produit une fois, juste après
 * l'émission (job GenerateInvoicePdf), puis conservé sur un disque privé : la pièce téléchargée reste celle de
 * l'émission, même si la mise en page change ensuite. Un PDF manquant (job en retard ou en échec) est produit au
 * premier téléchargement.
 */
class InvoicePdfRenderer
{
    use Localizable;

    /**
     * Produit et conserve le PDF s'il n'existe pas encore.
     */
    public function store(Invoice $invoice): string
    {
        $path = $this->path($invoice);

        if (! $this->disk()->exists($path)) {
            $this->disk()->put($path, $this->render($invoice));
        }

        return $path;
    }

    public function download(Invoice $invoice): StreamedResponse
    {
        return $this->disk()->download($this->store($invoice), $invoice->number.'.pdf', ['Content-Type' => 'application/pdf']);
    }

    public function render(Invoice $invoice): string
    {
        // Seuls les caractères utilisés de la police sont embarqués : quelques dizaines de Ko au lieu de 900.
        return Pdf::loadHTML($this->html($invoice))
            ->setPaper('a4')
            ->setOption('isFontSubsettingEnabled', true)
            ->output();
    }

    /**
     * La page que dompdf met en PDF : une vue par type de pièce.
     */
    public function html(Invoice $invoice): string
    {
        $invoice->loadMissing(['lines', 'creditedInvoice']);
        $mandateAcceptedAt = $invoice->issuer_details['billing_mandate']['accepted_at'] ?? null;

        return $this->withLocale((string) config('billing.locale'), fn (): string => view(
            $invoice->type === InvoiceTypeEnum::CREDIT_NOTE ? 'billing.pdf.credit-note' : 'billing.pdf.invoice',
            [
                'invoice' => $invoice,
                'issuedOn' => $this->date($invoice->issued_at),
                'creditedOn' => $invoice->creditedInvoice === null ? null : $this->date($invoice->creditedInvoice->issued_at),
                'mandateAcceptedOn' => is_string($mandateAcceptedAt) ? $this->date(CarbonImmutable::parse($mandateAcceptedAt)) : null,
                'periodStart' => $invoice->period_start?->locale(app()->getLocale())->isoFormat('L'),
                'periodEnd' => $invoice->period_end?->locale(app()->getLocale())->isoFormat('L'),
                'settlement' => match (true) {
                    $invoice->type === InvoiceTypeEnum::CREDIT_NOTE => 'refunded',
                    $invoice->recipient_organization_id !== null => 'withheld',
                    default => 'paid',
                },
            ],
        )->render());
    }

    /**
     * Un numéro n'est unique que chez son émetteur : chaque entreprise a sa série KNL.
     */
    public function path(Invoice $invoice): string
    {
        return 'invoices/'.($invoice->issuer_organization_id ?? 'kennelo').'/'.$invoice->number.'.pdf';
    }

    /**
     * Date d'un instant, dans le fuseau de la facturation.
     */
    private function date(CarbonInterface $at): string
    {
        return CarbonImmutable::instance($at)->setTimezone((string) config('billing.timezone'))->locale(app()->getLocale())->isoFormat('L');
    }

    private function disk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter */
        return Storage::disk((string) config('billing.pdf_disk'));
    }
}
