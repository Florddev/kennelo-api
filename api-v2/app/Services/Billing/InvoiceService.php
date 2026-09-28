<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\InvoiceTypeEnum;
use App\Enums\PaymentKindEnum;
use App\Enums\PaymentStatusEnum;
use App\Jobs\GenerateInvoicePdf;
use App\Models\Address;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BookingPayment;
use App\Models\BookingRefund;
use App\Models\BookingUnit;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\User;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Factures et avoirs des réservations.
 *
 * - Chaque paiement encaissé produit deux factures : celle de l'entreprise au client pour les prestations qu'il
 *   couvre, émise par Kennelo au nom et pour le compte de l'entreprise (mandat de facturation), et celle des frais
 *   de service de Kennelo, s'il y en a.
 * - Chaque remboursement produit ses avoirs sur les factures de son paiement : chez l'entreprise pour les
 *   prestations, chez Kennelo pour sa part des frais.
 *
 * L'émission est idempotente et se fait sous le verrou de la réservation : une nouvelle livraison d'un événement,
 * ou deux traitements simultanés, n'émettent rien de plus. Le numéro est pris dans la même transaction.
 */
class InvoiceService
{
    public function __construct(private readonly InvoiceNumberGenerator $numbers) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function forUser(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->filtered($user->invoices()->getQuery(), $filters)->paginate($filters['per_page'] ?? null);
    }

    /**
     * Factures émises par l'entreprise (ses réservations) et reçues de Kennelo (récapitulatifs de commission).
     *
     * @param  array<string, mixed>  $filters
     */
    public function forOrganization(Organization $organization, array $filters = []): LengthAwarePaginator
    {
        $query = Invoice::query()
            ->when(
                $filters['direction'] ?? null,
                fn (Builder $query, string $direction) => $query->where($direction === 'issued' ? 'issuer_organization_id' : 'recipient_organization_id', $organization->id),
                fn (Builder $query) => $query->involvingOrganization($organization->id),
            );

        return $this->filtered($query, $filters)->paginate($filters['per_page'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function forAdmin(array $filters = []): LengthAwarePaginator
    {
        $query = Invoice::query()
            ->when($filters['organization_id'] ?? null, fn (Builder $query, string $id) => $query->involvingOrganization($id))
            ->when($filters['issuer'] ?? null, fn (Builder $query, string $issuer) => $issuer === 'kennelo'
                ? $query->whereNull('issuer_organization_id')
                : $query->whereNotNull('issuer_organization_id'))
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->where('number', 'like', '%'.$search.'%'));

        return $this->filtered($query, $filters)->paginate($filters['per_page'] ?? null);
    }

    /**
     * Émet les factures d'un paiement encaissé qui ne les a pas encore.
     *
     * @return array{organization: Invoice|null, kennelo: Invoice|null}
     */
    public function invoicePayment(BookingPayment $payment): array
    {
        return DB::transaction(fn (): array => $this->issuePaymentInvoices($this->lock($payment->booking_id), $payment->refresh()));
    }

    /**
     * Émet les avoirs des remboursements de la réservation qui n'en ont pas encore, après les factures qu'ils
     * annulent si elles manquent.
     *
     * @return Collection<int, Invoice>
     */
    public function creditRefunds(Booking $booking): Collection
    {
        return DB::transaction(function () use ($booking): Collection {
            $booking = $this->lock($booking->id);
            $creditNotes = collect();

            $refunds = BookingRefund::query()
                ->whereHas('payment', fn (Builder $query) => $query->where('booking_id', $booking->id))
                ->whereDoesntHave('creditNotes')
                ->with('payment')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            foreach ($refunds as $refund) {
                $invoices = $this->issuePaymentInvoices($booking, $refund->payment ?? throw new LogicException('A refund always has its payment.'));

                if (bccomp($refund->itemsAmount(), '0', 2) > 0) {
                    $creditNotes->push($this->issueCreditNote($booking, $refund, $invoices['organization'], $refund->itemsAmount()));
                }

                if (bccomp($refund->service_fee_amount, '0', 2) > 0) {
                    $creditNotes->push($this->issueCreditNote($booking, $refund, $invoices['kennelo'], $refund->service_fee_amount));
                }
            }

            return $creditNotes;
        });
    }

    /**
     * Émet une facture ou un avoir : totaux calculés ligne par ligne, émetteur et destinataire copiés, numéro
     * suivant de l'émetteur. Appelé dans la transaction de l'émission ; le PDF est produit après son commit.
     *
     * @param  list<array{description: string, quantity: numeric-string, unit_price_ttc: numeric-string, vat_rate: numeric-string}>  $lines
     * @param  array<string, mixed>  $attributes  liens (réservation, paiement, facture annulée…), devise, période
     */
    public function issue(?Organization $issuer, User|Organization $recipient, array $lines, array $attributes = [], InvoiceTypeEnum $type = InvoiceTypeEnum::INVOICE): Invoice
    {
        $issuedAt = now();
        $lines = array_map(fn (array $line): array => [
            ...$line,
            ...InvoiceCalculator::line($line['unit_price_ttc'], $line['quantity'], $line['vat_rate']),
        ], $lines);
        $franchise = $issuer !== null && collect($lines)->every(fn (array $line): bool => bccomp($line['vat_rate'], '0', 2) === 0);

        $invoice = Invoice::query()->create([
            'currency' => 'EUR',
            ...$attributes,
            'issuer_organization_id' => $issuer?->id,
            // L'année de numérotation est celle de la date de la facture, en France.
            'number' => $this->numbers->next($issuer?->id, $issuedAt->copy()->setTimezone((string) config('billing.timezone'))->year),
            'type' => $type,
            'recipient_user_id' => $recipient instanceof User ? $recipient->id : null,
            'recipient_organization_id' => $recipient instanceof Organization ? $recipient->id : null,
            'issuer_details' => $issuer === null ? $this->kenneloDetails() : [
                ...$this->organizationDetails($issuer),
                // Kennelo émet la facture au nom et pour le compte de l'entreprise.
                'billing_mandate' => [
                    'issued_by' => $this->kenneloDetails(),
                    'accepted_at' => $issuer->billing_mandate_accepted_at?->toISOString(),
                ],
            ],
            'recipient_details' => $recipient instanceof User ? $this->clientDetails($recipient) : $this->organizationDetails($recipient),
            ...InvoiceCalculator::totals($lines),
            'vat_mention' => $franchise ? __('billing.mentions.vat_franchise', locale: $this->locale()) : null,
            'issued_at' => $issuedAt,
        ]);

        $invoice->lines()->createMany(array_map(
            fn (array $line, int $index): array => [...$line, 'position' => $index + 1],
            $lines,
            array_keys($lines),
        ));

        GenerateInvoicePdf::dispatch($invoice);

        return $invoice;
    }

    /**
     * @return array{organization: Invoice|null, kennelo: Invoice|null}
     */
    private function issuePaymentInvoices(Booking $booking, BookingPayment $payment): array
    {
        if ($payment->status !== PaymentStatusEnum::SUCCEEDED) {
            return ['organization' => null, 'kennelo' => null];
        }

        $issued = $payment->invoices()->where('type', InvoiceTypeEnum::INVOICE)->get();

        return [
            'organization' => $issued->first(fn (Invoice $invoice): bool => ! $invoice->isIssuedByKennelo())
                ?? $this->issueBookingInvoice($booking, $payment),
            'kennelo' => $issued->first(fn (Invoice $invoice): bool => $invoice->isIssuedByKennelo())
                ?? $this->issueServiceFeeInvoice($booking, $payment),
        ];
    }

    /**
     * Facture de l'entreprise au client : les places, les frais de déplacement et les prestations que le paiement
     * couvre, au taux de TVA figé sur la réservation. Leur total est exactement la part du paiement hors frais Kennelo.
     */
    private function issueBookingInvoice(Booking $booking, BookingPayment $payment): ?Invoice
    {
        if (bccomp($payment->itemsAmount(), '0', 2) <= 0) {
            return null;
        }

        $lines = $this->bookingLines($booking, $payment);
        $total = InvoiceCalculator::totals(array_map(
            fn (array $line): array => InvoiceCalculator::line($line['unit_price_ttc'], $line['quantity'], $line['vat_rate']),
            $lines,
        ))['total_ttc'];

        if (bccomp($total, $payment->itemsAmount(), 2) !== 0) {
            throw new LogicException("Payment {$payment->id} covers {$payment->itemsAmount()} of services but its lines total {$total}.");
        }

        return $this->issue($this->organizationOf($booking), $this->clientOf($booking), $lines, [
            'booking_id' => $booking->id,
            'booking_payment_id' => $payment->id,
            'currency' => $payment->currency,
        ]);
    }

    /**
     * Facture de Kennelo au client pour les frais de service que porte le paiement.
     */
    private function issueServiceFeeInvoice(Booking $booking, BookingPayment $payment): ?Invoice
    {
        if (bccomp($payment->service_fee, '0', 2) <= 0) {
            return null;
        }

        $booking->loadMissing('activity');

        return $this->issue(null, $this->clientOf($booking), [[
            'description' => __('billing.lines.service_fee', [
                'activity' => $booking->activity->name ?? '',
                'start' => $this->date($booking->startsAt()),
            ], $this->locale()),
            'quantity' => '1',
            'unit_price_ttc' => $payment->service_fee,
            'vat_rate' => Money::round((string) config('billing.kennelo.vat_rate')),
        ]], [
            'booking_id' => $booking->id,
            'booking_payment_id' => $payment->id,
            'currency' => $payment->currency,
        ]);
    }

    /**
     * Avoir d'un remboursement sur la facture de son paiement, au taux de TVA de cette facture.
     *
     * @param  numeric-string  $amount
     */
    private function issueCreditNote(Booking $booking, BookingRefund $refund, ?Invoice $credited, string $amount): Invoice
    {
        $rate = $credited?->lines()->value('vat_rate');

        if ($credited === null || $rate === null) {
            throw new LogicException("Refund {$refund->id} has no invoice to credit.");
        }

        return $this->issue(
            $credited->isIssuedByKennelo() ? null : $this->organizationOf($booking),
            $this->clientOf($booking),
            [[
                'description' => __('billing.lines.refund.'.$refund->reason->value, ['number' => $credited->number], $this->locale()),
                'quantity' => '1',
                'unit_price_ttc' => $amount,
                'vat_rate' => Money::round((string) $rate),
            ]],
            [
                'credited_invoice_id' => $credited->id,
                'booking_id' => $booking->id,
                'booking_payment_id' => $refund->booking_payment_id,
                'booking_refund_id' => $refund->id,
                'currency' => $credited->currency,
            ],
            InvoiceTypeEnum::CREDIT_NOTE,
        );
    }

    /**
     * Ce que couvre le paiement : le paiement initial couvre les places et les frais de déplacement ; chacun couvre
     * ses prestations, même retirées depuis (leur remboursement a son avoir). Une option incluse, gratuite, n'y
     * figure pas.
     *
     * @return list<array{description: string, quantity: numeric-string, unit_price_ttc: numeric-string, vat_rate: numeric-string}>
     */
    private function bookingLines(Booking $booking, BookingPayment $payment): array
    {
        $booking->loadMissing('activity');
        $rate = $booking->vat_rate;
        $lines = [];

        if ($payment->kind === PaymentKindEnum::INITIAL) {
            foreach ($booking->units()->with('unitType')->get() as $unit) {
                /** @var BookingUnit $unit */
                $lines[] = $this->line(__('billing.lines.stay', [
                    'unit' => $unit->unitType->name ?? '',
                    'start' => $this->date($booking->start_date),
                    'end' => $this->date($booking->end_date),
                ], $this->locale()), '1', $unit->subtotal, $rate);
            }

            if (bccomp($booking->travel_fee, '0', 2) > 0) {
                $lines[] = $this->line(__('billing.lines.travel_fee', locale: $this->locale()), '1', $booking->travel_fee, $rate);
            }
        }

        $items = $payment->items()->with(['service', 'pet'])->orderBy('created_at')->orderBy('id')->get();

        foreach ($items as $item) {
            if (bccomp($item->subtotal, '0', 2) > 0) {
                $lines[] = $this->line($this->itemDescription($booking, $item), (string) $item->quantity, $item->unit_price, $rate);
            }
        }

        return $lines;
    }

    private function itemDescription(Booking $booking, BookingItem $item): string
    {
        $startsAt = $item->starts_at === null
            ? null
            : CarbonImmutable::instance($item->starts_at)->setTimezone($booking->activity->timezone ?? (string) config('activities.default_timezone'));
        $key = 'billing.lines.item'.($item->pet !== null ? '_pet' : '').($startsAt !== null ? '_scheduled' : '');

        return __($key, [
            'service' => $item->service->name ?? '',
            'pet' => $item->pet->name ?? '',
            'date' => $startsAt === null ? '' : $this->date($startsAt),
            'time' => $startsAt?->locale($this->locale())->isoFormat('LT') ?? '',
        ], $this->locale());
    }

    /**
     * @param  numeric-string  $quantity
     * @param  numeric-string  $unitPrice
     * @param  numeric-string  $vatRate
     * @return array{description: string, quantity: numeric-string, unit_price_ttc: numeric-string, vat_rate: numeric-string}
     */
    private function line(string $description, string $quantity, string $unitPrice, string $vatRate): array
    {
        return ['description' => $description, 'quantity' => $quantity, 'unit_price_ttc' => $unitPrice, 'vat_rate' => $vatRate];
    }

    /**
     * @param  Builder<Invoice>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Invoice>
     */
    private function filtered(Builder $query, array $filters): Builder
    {
        return $query
            ->with(['lines', 'creditedInvoice'])
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->when($filters['from'] ?? null, fn ($query, string $from) => $query->whereDate('issued_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, string $to) => $query->whereDate('issued_at', '<=', $to))
            ->orderByDesc('issued_at')
            ->orderByDesc('number');
    }

    private function lock(string $bookingId): Booking
    {
        return Booking::query()->whereKey($bookingId)->lockForUpdate()->firstOrFail();
    }

    private function organizationOf(Booking $booking): Organization
    {
        return $booking->organization()->with('address')->firstOrFail();
    }

    /**
     * Le client reste destinataire de ses factures même si son compte a été fermé depuis.
     */
    private function clientOf(Booking $booking): User
    {
        return User::withInactive()->withTrashed()->findOrFail($booking->user_id);
    }

    /**
     * @return array<string, mixed>
     */
    private function kenneloDetails(): array
    {
        return [
            'name' => config('billing.kennelo.legal_name'),
            'legal_form' => config('billing.kennelo.legal_form'),
            'siren' => config('billing.kennelo.siren'),
            'siret' => config('billing.kennelo.siret'),
            'vat_number' => config('billing.kennelo.vat_number'),
            'address' => config('billing.kennelo.address'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function organizationDetails(Organization $organization): array
    {
        return [
            'name' => $organization->legal_name,
            'legal_form' => $organization->legal_form->value,
            'siren' => $organization->siren,
            'siret' => $organization->siret,
            'vat_number' => $organization->vat_number,
            'vat_regime' => $organization->vat_regime->value,
            'address' => $this->addressDetails($organization->loadMissing('address')->address),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function clientDetails(User $client): array
    {
        $address = $client->addresses()->where('is_default', true)->with('address')->first()?->address;

        return [
            'name' => trim($client->first_name.' '.$client->last_name),
            'email' => $client->email,
            'address' => $this->addressDetails($address),
        ];
    }

    /**
     * @return array<string, string|null>|null
     */
    private function addressDetails(?Address $address): ?array
    {
        return $address?->only(['line1', 'line2', 'postal_code', 'city', 'country']);
    }

    private function date(CarbonInterface $date): string
    {
        return CarbonImmutable::instance($date)->locale($this->locale())->isoFormat('L');
    }

    private function locale(): string
    {
        return (string) config('billing.locale');
    }
}
