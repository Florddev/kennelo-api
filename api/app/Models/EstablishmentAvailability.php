<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AvailabilityStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property Carbon $date
 * @property AvailabilityStatusEnum $status
 * @property-read Establishment $establishment
 */
class EstablishmentAvailability extends Model
{
    use HasUuids;

    protected $fillable = [
        'establishment_id',
        'date',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'status' => AvailabilityStatusEnum::class,
        ];
    }

    public function establishment(): BelongsTo
    {
        return $this->belongsTo(Establishment::class);
    }
}
