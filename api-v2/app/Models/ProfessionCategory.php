<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * Famille de métiers : hébergement, soin, éducation…
 *
 * @property string $id
 * @property string $code
 * @property string $name
 * @property int $sort_order
 */
class ProfessionCategory extends Model
{
    use HasFactory, HasTranslations, HasUuids;

    protected $fillable = [
        'code',
        'name',
        'sort_order',
    ];

    public array $translatable = [
        'name',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<Profession, $this>
     */
    public function professions(): HasMany
    {
        return $this->hasMany(Profession::class);
    }
}
