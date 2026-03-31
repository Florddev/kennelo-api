<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EstablishmentPermission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Establishment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'siret',
        'description',
        'phone',
        'email',
        'website',
        'address_id',
        'timezone',
        'is_active',
        'manager_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'establishment_collaborators', 'establishment_id', 'user_id');
    }

    public function collaboratorPermissions(): HasMany
    {
        return $this->hasMany(EstablishmentCollaboratorPermission::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(EstablishmentAvailability::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function collaboratorHasPermission(User $user, EstablishmentPermission $permission): bool
    {
        if (! $this->collaborators()->where('users.id', $user->id)->exists()) {
            return false;
        }

        return $this->collaboratorPermissions()
            ->where('user_id', $user->id)
            ->where('permission', $permission->value)
            ->exists();
    }
}
