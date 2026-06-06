<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReviewerTypeEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property string $id
 * @property string $booking_id
 * @property string $reviewer_id
 * @property ReviewerTypeEnum $reviewer_type
 * @property string $overall_rating
 * @property string|null $comment
 * @property string|null $private_feedback
 * @property bool $would_recommend
 * @property bool $is_published
 * @property Carbon|null $published_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Booking|null $booking
 * @property-read User|null $reviewer
 * @property-read Collection<int, ReviewCriteriaScore> $criteriaScores
 * @property-read ReviewResponse|null $response
 * @property-read Collection<int, ReviewReport> $reports
 */
class Review extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'booking_id',
        'reviewer_id',
        'reviewer_type',
        'overall_rating',
        'comment',
        'private_feedback',
        'would_recommend',
        'is_published',
        'published_at',
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

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function criteriaScores(): HasMany
    {
        return $this->hasMany(ReviewCriteriaScore::class);
    }

    public function response(): HasOne
    {
        return $this->hasOne(ReviewResponse::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(ReviewReport::class);
    }
}
