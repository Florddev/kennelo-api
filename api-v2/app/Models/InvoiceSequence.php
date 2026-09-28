<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Compteur des numéros d'un émetteur pour une année, partagé par ses factures et ses avoirs. Kennelo est
 * l'émetteur NULL. Seul InvoiceNumberGenerator le lit et l'écrit, sous verrou.
 *
 * @property string $id
 * @property string|null $issuer_organization_id
 * @property int $year
 * @property int $last_number
 */
class InvoiceSequence extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'issuer_organization_id',
        'year',
        'last_number',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'last_number' => 'integer',
        ];
    }
}
