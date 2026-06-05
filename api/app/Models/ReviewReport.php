<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReviewReportReasonEnum;
use App\Enums\ReviewReportStatusEnum;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $review_id
 * @property string $reporter_id
 * @property ReviewReportReasonEnum $reason
 * @property string|null $description
 * @property ReviewReportStatusEnum $status
 * @property Carbon|null $reviewed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
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
        'status',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'reason' => ReviewReportReasonEnum::class,
            'status' => ReviewReportStatusEnum::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
