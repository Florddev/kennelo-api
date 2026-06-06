<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\IdentityVerificationStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property IdentityVerificationStatusEnum $status
 */
class IdentityVerification extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'document_url',
        'status',
        'reviewer_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => IdentityVerificationStatusEnum::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }
}
