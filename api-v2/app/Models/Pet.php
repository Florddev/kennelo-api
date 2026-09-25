<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PetCoatTypeEnum;
use App\Enums\PetSizeClassEnum;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property Carbon|null $birth_date
 * @property Carbon|null $adoption_date
 * @property PetSizeClassEnum|null $size_class
 * @property PetCoatTypeEnum|null $coat_type
 * @property numeric-string|null $weight
 * @property-read AnimalType|null $animalType
 * @property-read AnimalBreed|null $animalBreed
 * @property-read User|null $user
 */
class Pet extends Model implements HasMedia
{
    use HasFactory, HasUuids, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'animal_type_id',
        'animal_breed_id',
        'name',
        'size_class',
        'coat_type',
        'birth_date',
        'sex',
        'weight',
        'is_sterilized',
        'has_microchip',
        'microchip_number',
        'adoption_date',
        'about',
        'health_notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'adoption_date' => 'date',
            'weight' => 'decimal:2',
            'is_sterilized' => 'boolean',
            'has_microchip' => 'boolean',
            'size_class' => PetSizeClassEnum::class,
            'coat_type' => PetCoatTypeEnum::class,
        ];
    }

    /**
     * Taille retenue par la grille de prix : celle de la fiche, sinon déduite du poids selon les seuils
     * de l'espèce (config/pets.php), sinon celle de la race. L'espèce et la race doivent être chargées.
     */
    public function effectiveSizeClass(): ?PetSizeClassEnum
    {
        return $this->size_class
            ?? $this->sizeClassFromWeight()
            ?? $this->animalBreed?->default_size_class;
    }

    /**
     * Poil retenu par la grille de prix : celui de la fiche, sinon celui de la race.
     */
    public function effectiveCoatType(): ?PetCoatTypeEnum
    {
        return $this->coat_type ?? $this->animalBreed?->default_coat_type;
    }

    private function sizeClassFromWeight(): ?PetSizeClassEnum
    {
        $thresholds = config('pets.size_thresholds.'.$this->animalType?->code);

        if ($this->weight === null || ! is_array($thresholds)) {
            return null;
        }

        // Seuils rangés de la plus petite taille à la plus grande ; null = sans limite supérieure.
        foreach ($thresholds as $size => $maxWeight) {
            if ($maxWeight === null || bccomp((string) $this->weight, (string) $maxWeight, 2) <= 0) {
                return PetSizeClassEnum::from($size);
            }
        }

        return null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(MediaService::COLLECTION_AVATAR)
            ->singleFile();

        $this->addMediaCollection(MediaService::COLLECTION_IMAGES)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        MediaService::registerAvatarConversion($this);
        MediaService::registerImagesConversion($this);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function animalType(): BelongsTo
    {
        return $this->belongsTo(AnimalType::class);
    }

    public function animalBreed(): BelongsTo
    {
        return $this->belongsTo(AnimalBreed::class);
    }

    public function petAttributes(): HasMany
    {
        return $this->hasMany(PetAttribute::class);
    }

    public function scopeForUser(Builder $query, string $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
