<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property array<string, mixed>|null $filters
 * @property-read User|null $user
 */
class SearchLog extends Model
{
    use HasFactory, HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'location',
        'latitude',
        'longitude',
        'department',
        'region',
        'filters',
        'results_count',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
            'results_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
