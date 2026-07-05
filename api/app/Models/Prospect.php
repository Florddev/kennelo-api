<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProspectSourceEnum;
use App\Enums\ProspectStatusEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property ProspectStatusEnum $status
 * @property ProspectSourceEnum $source
 * @property array<int, string>|null $animal_types
 * @property array<int, string>|null $services
 * @property-read User|null $assignedTo
 * @property-read Activity|null $kenneloActivity
 * @property-read Collection<int, ProspectNote> $notes
 * @property-read Collection<int, ProspectContact> $contacts
 */
class Prospect extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'address',
        'city',
        'postal_code',
        'department',
        'region',
        'country',
        'latitude',
        'longitude',
        'phone',
        'website',
        'google_rating',
        'google_reviews_count',
        'google_place_id',
        'category',
        'animal_types',
        'services',
        'siret',
        'siren',
        'ape_code',
        'status',
        'source',
        'assigned_to',
        'kennelo_activity_id',
        'reconciled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProspectStatusEnum::class,
            'source' => ProspectSourceEnum::class,
            'animal_types' => 'array',
            'services' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
            'google_rating' => 'float',
            'google_reviews_count' => 'integer',
            'reconciled_at' => 'datetime',
        ];
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function kenneloActivity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'kennelo_activity_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ProspectNote::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ProspectContact::class);
    }
}
