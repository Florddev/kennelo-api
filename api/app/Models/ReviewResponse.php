<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $review_id
 * @property string $responder_id
 * @property string $response
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Review|null $review
 * @property-read User|null $responder
 */
class ReviewResponse extends Model
{
    use HasUuids;

    protected $fillable = [
        'review_id',
        'responder_id',
        'response',
    ];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responder_id');
    }
}
