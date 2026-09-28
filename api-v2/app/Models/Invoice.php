<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Facture ou avoir. Trois sortes, selon l'émetteur et le destinataire :
 *
 * - la facture d'une réservation, de l'entreprise au client, émise par Kennelo au nom et pour le compte de
 *   l'entreprise (mandat de facturation) ;
 * - la facture des frais de service, de Kennelo (émetteur NULL) au client ;
 * - le récapitulatif mensuel de commission, de Kennelo à l'entreprise, sur une période.
 *
 * L'émetteur et le destinataire sont copiés tels quels à l'émission (issuer_details, recipient_details). Une
 * facture ne se modifie ni ne se supprime jamais : un remboursement produit un avoir.
 *
 * @property string $id
 * @property string|null $issuer_organization_id
 * @property string $number
 * @property InvoiceTypeEnum $type
 * @property string|null $credited_invoice_id
 * @property string|null $booking_id
 * @property string|null $booking_payment_id
 * @property string|null $booking_refund_id
 * @property string|null $recipient_user_id
 * @property string|null $recipient_organization_id
 * @property array<string, mixed> $issuer_details
 * @property array<string, mixed> $recipient_details
 * @property string $currency
 * @property numeric-string $total_ht
 * @property numeric-string $total_vat
 * @property numeric-string $total_ttc
 * @property string|null $vat_mention
 * @property Carbon|null $period_start
 * @property Carbon|null $period_end
 * @property Carbon $issued_at
 * @property Carbon|null $created_at
 * @property-read Invoice|null $creditedInvoice
 */
class Invoice extends Model
{
    use HasUuids;

    // Pas de updated_at : une facture ne change pas.
    public const UPDATED_AT = null;

    protected $fillable = [
        'issuer_organization_id',
        'number',
        'type',
        'credited_invoice_id',
        'booking_id',
        'booking_payment_id',
        'booking_refund_id',
        'recipient_user_id',
        'recipient_organization_id',
        'issuer_details',
        'recipient_details',
        'currency',
        'total_ht',
        'total_vat',
        'total_ttc',
        'vat_mention',
        'period_start',
        'period_end',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => InvoiceTypeEnum::class,
            'issuer_details' => 'array',
            'recipient_details' => 'array',
            'total_ht' => 'decimal:2',
            'total_vat' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'period_start' => 'date',
            'period_end' => 'date',
            'issued_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('An issued invoice never changes: issue a credit note instead.'));
        static::deleting(fn (): never => throw new LogicException('An issued invoice is never deleted.'));
    }

    /**
     * Émise par Kennelo : frais de service ou récapitulatif de commission.
     */
    public function isIssuedByKennelo(): bool
    {
        return $this->issuer_organization_id === null;
    }

    /**
     * Factures et avoirs que l'entreprise a émis (ses réservations) ou reçus (ses récapitulatifs de commission).
     */
    public function scopeInvolvingOrganization(Builder $query, string $organizationId): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->where('issuer_organization_id', $organizationId)
            ->orWhere('recipient_organization_id', $organizationId));
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function issuerOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'issuer_organization_id')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function recipientUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id')->withTrashed();
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function recipientOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'recipient_organization_id')->withTrashed();
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<BookingPayment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(BookingPayment::class, 'booking_payment_id');
    }

    /**
     * @return BelongsTo<BookingRefund, $this>
     */
    public function refund(): BelongsTo
    {
        return $this->belongsTo(BookingRefund::class, 'booking_refund_id');
    }

    /**
     * Facture qu'annule cet avoir.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function creditedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'credited_invoice_id');
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function creditNotes(): HasMany
    {
        return $this->hasMany(Invoice::class, 'credited_invoice_id');
    }

    /**
     * @return HasMany<InvoiceLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('position');
    }
}
