<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Address extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'line1',
        'line2',
        'postal_code',
        'city',
        'region',
        'country',
        'latitude',
        'longitude',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }
}
