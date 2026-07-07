<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SettingGroupEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property SettingGroupEnum $group
 * @property string $key
 * @property mixed $value
 */
class Setting extends Model
{
    use HasUuids;

    protected $fillable = [
        'group',
        'key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'group' => SettingGroupEnum::class,
            'value' => 'json',
        ];
    }
}
