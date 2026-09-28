<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Réponse publique de la partie notée à un avis publié. Une seule par avis.
 *
 * @property string $id
 * @property string $review_id
 * @property string|null $responder_id
 * @property string $response
 * @property Carbon|null $created_at
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

    /**
     * @return BelongsTo<Review, $this>
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responder_id')->withTrashed();
    }
}
