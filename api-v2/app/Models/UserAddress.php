<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Adresse du carnet d'un client : « Domicile », « Chez mes parents ». Une seule est l'adresse par défaut.
 *
 * @property string $id
 * @property string $user_id
 * @property string $address_id
 * @property string $label
 * @property bool $is_default
 * @property-read Address|null $address
 */
class UserAddress extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'address_id',
        'label',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Address, $this>
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }
}
