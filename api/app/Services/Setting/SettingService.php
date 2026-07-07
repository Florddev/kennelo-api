<?php

declare(strict_types=1);

namespace App\Services\Setting;

use App\Enums\SettingGroupEnum;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    private const CACHE_KEY = 'settings:all';

    /**
     * @var array<string, array{group: SettingGroupEnum, default: mixed, rules: array<int, string>}>
     */
    public const DEFINITIONS = [
        'user_service_fee_rate' => [
            'group' => SettingGroupEnum::FEES,
            'default' => '0.08',
            'rules' => ['numeric', 'min:0', 'max:1'],
        ],
        'host_commission_rate' => [
            'group' => SettingGroupEnum::FEES,
            'default' => '0.06',
            'rules' => ['numeric', 'min:0', 'max:1'],
        ],
        'acceptance_window_hours' => [
            'group' => SettingGroupEnum::BOOKING,
            'default' => 72,
            'rules' => ['integer', 'min:0'],
        ],
        'payout_delay_hours' => [
            'group' => SettingGroupEnum::BOOKING,
            'default' => 24,
            'rules' => ['integer', 'min:0'],
        ],
        'reminder_after_hours' => [
            'group' => SettingGroupEnum::BOOKING,
            'default' => 36,
            'rules' => ['integer', 'min:0'],
        ],
        'currency' => [
            'group' => SettingGroupEnum::STRIPE,
            'default' => 'eur',
            'rules' => ['string', 'size:3'],
        ],
        'tier3_enabled' => [
            'group' => SettingGroupEnum::NOTIFICATIONS,
            'default' => false,
            'rules' => ['boolean'],
        ],
        'soft_disable_activities' => [
            'group' => SettingGroupEnum::DOWNGRADE,
            'default' => false,
            'rules' => ['boolean'],
        ],
        'soft_disable_cycles' => [
            'group' => SettingGroupEnum::DOWNGRADE,
            'default' => false,
            'rules' => ['boolean'],
        ],
        'soft_disable_photos' => [
            'group' => SettingGroupEnum::DOWNGRADE,
            'default' => false,
            'rules' => ['boolean'],
        ],
    ];

    /**
     * @return array<string, mixed>
     */
    public function stored(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): array => Setting::query()
            ->pluck('value', 'key')
            ->all());
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $stored = $this->stored();

        $resolved = [];

        foreach (self::DEFINITIONS as $key => $definition) {
            $resolved[$key] = $stored[$key] ?? $definition['default'];
        }

        return $resolved;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function grouped(): array
    {
        $all = $this->all();
        $grouped = [];

        foreach (self::DEFINITIONS as $key => $definition) {
            $grouped[$definition['group']->value][$key] = $all[$key];
        }

        return $grouped;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $stored = $this->stored();

        if (array_key_exists($key, $stored)) {
            return $stored[$key];
        }

        if ($default !== null) {
            return $default;
        }

        return self::DEFINITIONS[$key]['default'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function update(array $values): void
    {
        foreach ($values as $key => $value) {
            $definition = self::DEFINITIONS[$key] ?? null;

            if ($definition === null) {
                continue;
            }

            Setting::updateOrCreate(
                ['key' => $key],
                ['group' => $definition['group'], 'value' => $value]
            );
        }

        Cache::forget(self::CACHE_KEY);
    }

    public function seedDefaults(): void
    {
        foreach (self::DEFINITIONS as $key => $definition) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['group' => $definition['group'], 'value' => $definition['default']]
            );
        }

        Cache::forget(self::CACHE_KEY);
    }
}
