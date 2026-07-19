<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProspectContactTypeEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ProspectContactTypeEnum $type
 * @property-read User|null $author
 */
class ProspectContact extends Model
{
    use HasUuids;

    protected $fillable = [
        'prospect_id',
        'author_id',
        'type',
        'contacted_at',
        'outcome',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProspectContactTypeEnum::class,
            'contacted_at' => 'datetime',
        ];
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class, 'prospect_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
