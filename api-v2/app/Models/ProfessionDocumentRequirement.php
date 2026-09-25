<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentTypeEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Justificatif qu'un métier exige. validity_months vide : le document n'expire pas.
 *
 * @property string $id
 * @property string $profession_id
 * @property DocumentTypeEnum $document_type
 * @property bool $is_required
 * @property int|null $validity_months
 */
class ProfessionDocumentRequirement extends Model
{
    use HasUuids;

    protected $fillable = [
        'profession_id',
        'document_type',
        'is_required',
        'validity_months',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentTypeEnum::class,
            'is_required' => 'boolean',
            'validity_months' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Profession, $this>
     */
    public function profession(): BelongsTo
    {
        return $this->belongsTo(Profession::class);
    }
}
