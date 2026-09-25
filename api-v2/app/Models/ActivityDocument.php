<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DocumentStatusEnum;
use App\Enums\DocumentTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Justificatif métier déposé par une activité et vérifié par Kennelo. Le fichier est stocké sur un disque privé :
 * il ne se lit qu'au travers de l'API, par l'équipe de l'activité ou un admin.
 *
 * Le statut et la revue ne sont jamais remplis depuis une requête : les services les écrivent avec forceFill().
 *
 * @property string $id
 * @property string $activity_id
 * @property DocumentTypeEnum $document_type
 * @property DocumentStatusEnum $status
 * @property Carbon|null $expires_at
 * @property Carbon|null $reviewed_at
 * @property string|null $rejection_reason
 * @property-read Activity|null $activity
 */
class ActivityDocument extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia;

    public const string COLLECTION_FILE = 'file';

    protected $fillable = [
        'activity_id',
        'document_type',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'document_type' => DocumentTypeEnum::class,
            'status' => DocumentStatusEnum::class,
            'expires_at' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COLLECTION_FILE)
            ->singleFile()
            ->useDisk((string) config('activities.documents.disk'))
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * Justificatif valable aujourd'hui : approuvé, et sans date d'échéance dépassée.
     */
    public function scopeValid(Builder $query): Builder
    {
        $expiresAt = $query->qualifyColumn('expires_at');

        return $query
            ->where($query->qualifyColumn('status'), DocumentStatusEnum::APPROVED)
            ->where(fn (Builder $query) => $query->whereNull($expiresAt)->orWhereDate($expiresAt, '>=', today()));
    }

    /**
     * @return BelongsTo<Activity, $this>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
