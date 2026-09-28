<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Ligne d'une facture. Le prix est saisi TTC ; HT et TVA en sont déduits ligne par ligne (InvoiceCalculator).
 *
 * @property string $id
 * @property string $invoice_id
 * @property int $position
 * @property string $description
 * @property numeric-string $quantity
 * @property numeric-string $unit_price_ttc
 * @property numeric-string $vat_rate
 * @property numeric-string $total_ht
 * @property numeric-string $total_vat
 * @property numeric-string $total_ttc
 */
class InvoiceLine extends Model
{
    use HasUuids;

    // Comme la facture, une ligne ne change pas.
    public $timestamps = false;

    protected $fillable = [
        'position',
        'description',
        'quantity',
        'unit_price_ttc',
        'vat_rate',
        'total_ht',
        'total_vat',
        'total_ttc',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'quantity' => 'decimal:2',
            'unit_price_ttc' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'total_ht' => 'decimal:2',
            'total_vat' => 'decimal:2',
            'total_ttc' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('An issued invoice line never changes.'));
        static::deleting(fn (): never => throw new LogicException('An issued invoice line is never deleted.'));
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
