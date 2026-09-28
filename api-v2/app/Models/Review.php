<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReviewerTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Avis sur une réservation terminée, dans un sens : le client note l'activité, ou l'équipe note le client.
 *
 * Un avis reste caché jusqu'à sa publication : quand l'autre partie a donné le sien, ou à la fin du délai pour
 * en donner un. Personne n'écrit donc le sien en ayant lu celui de l'autre. Le retour privé n'est lu que par la
 * partie notée. La publication n'est jamais remplie depuis une requête : les services l'écrivent avec forceFill().
 *
 * @property string $id
 * @property string $booking_id
 * @property string $activity_id
 * @property string $reviewer_id
 * @property ReviewerTypeEnum $reviewer_type
 * @property numeric-string $overall_rating
 * @property string|null $comment
 * @property string|null $private_feedback
 * @property bool $would_recommend
 * @property bool $is_published
 * @property Carbon|null $published_at
 * @property Carbon|null $created_at
 * @property-read Booking|null $booking
 * @property-read Activity|null $activity
 * @property-read User|null $reviewer
 * @property-read ReviewResponse|null $response
 */
class Review extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'booking_id',
        'activity_id',
        'reviewer_id',
        'reviewer_type',
        'overall_rating',
        'comment',
        'private_feedback',
        'would_recommend',
    ];

    protected function casts(): array
    {
        return [
            'reviewer_type' => ReviewerTypeEnum::class,
            'overall_rating' => 'decimal:1',
            'would_recommend' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('reviews.is_published', true);
    }

    /**
     * Avis donnés par les clients : ceux qui notent les activités.
     */
    public function scopeByClients(Builder $query): Builder
    {
        return $query->where('reviews.reviewer_type', ReviewerTypeEnum::USER);
    }

    /**
     * Avis donnés par les équipes : ceux qui notent les clients.
     */
    public function scopeAboutClients(Builder $query): Builder
    {
        return $query->where('reviews.reviewer_type', ReviewerTypeEnum::ACTIVITY);
    }

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id')->withTrashed();
    }

    /**
     * @return HasOne<ReviewResponse, $this>
     */
    public function response(): HasOne
    {
        return $this->hasOne(ReviewResponse::class);
    }

    /**
     * @return HasMany<ReviewReport, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(ReviewReport::class);
    }
}
