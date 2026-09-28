<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReviewReportReasonEnum;
use App\Enums\ReviewReportStatusEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Signalement d'un avis publié, examiné par un admin. Le statut n'est jamais rempli depuis une requête : le
 * service de modération l'écrit avec forceFill().
 *
 * @property string $id
 * @property string $review_id
 * @property string|null $reporter_id
 * @property ReviewReportReasonEnum $reason
 * @property string|null $description
 * @property ReviewReportStatusEnum $status
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property-read Review|null $review
 * @property-read User|null $reporter
 */
class ReviewReport extends Model
{
    use HasUuids;

    protected $fillable = [
        'review_id',
        'reporter_id',
        'reason',
        'description',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'reason' => ReviewReportReasonEnum::class,
            'status' => ReviewReportStatusEnum::class,
            'reviewed_at' => 'datetime',
        ];
    }

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
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id')->withTrashed();
    }
}
