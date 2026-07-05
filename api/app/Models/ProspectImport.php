<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProspectImportStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ProspectImportStatusEnum $status
 * @property array<int, string>|null $search_terms
 */
class ProspectImport extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'location',
        'search_terms',
        'max_results',
        'status',
        'imported_count',
        'skipped_count',
        'error',
        'requested_by',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProspectImportStatusEnum::class,
            'search_terms' => 'array',
            'max_results' => 'integer',
            'imported_count' => 'integer',
            'skipped_count' => 'integer',
            'finished_at' => 'datetime',
        ];
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
