<?php

declare(strict_types=1);

namespace App\Services\Setting;

use App\Enums\SettingGroupEnum;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Réglages de la plateforme. La valeur par défaut vient de la config (donc du .env) ;
 * la base ne contient que les valeurs modifiées dans le back-office.
 */
class SettingService
{
    private const CACHE_KEY = 'settings:all';

    /**
     * @return array<string, array{group: SettingGroupEnum, default: mixed, rules: list<string>}>
     */
    public function definitions(): array
    {
        return [
            'user_service_fee_rate' => ['group' => SettingGroupEnum::FEES, 'default' => (string) config('booking.user_service_fee_rate'), 'rules' => ['numeric', 'min:0', 'max:1']],
            'host_commission_rate' => ['group' => SettingGroupEnum::FEES, 'default' => (string) config('booking.host_commission_rate'), 'rules' => ['numeric', 'min:0', 'max:1']],
            'acceptance_window_hours' => ['group' => SettingGroupEnum::BOOKING, 'default' => (int) config('booking.acceptance_window_hours'), 'rules' => ['integer', 'min:0']],
            'payout_delay_hours' => ['group' => SettingGroupEnum::BOOKING, 'default' => (int) config('booking.payout_delay_hours'), 'rules' => ['integer', 'min:0']],
            'reminder_after_hours' => ['group' => SettingGroupEnum::BOOKING, 'default' => (int) config('booking.reminder_after_hours'), 'rules' => ['integer', 'min:0']],
            'currency' => ['group' => SettingGroupEnum::STRIPE, 'default' => (string) config('services.stripe.currency'), 'rules' => ['string', 'size:3']],
            'tier3_enabled' => ['group' => SettingGroupEnum::NOTIFICATIONS, 'default' => (bool) config('notifications.tier3_enabled'), 'rules' => ['boolean']],
            'soft_disable_activities' => ['group' => SettingGroupEnum::DOWNGRADE, 'default' => (bool) config('plans.downgrade.soft_disable.activities'), 'rules' => ['boolean']],
            'soft_disable_periods' => ['group' => SettingGroupEnum::DOWNGRADE, 'default' => (bool) config('plans.downgrade.soft_disable.periods'), 'rules' => ['boolean']],
            'soft_disable_photos' => ['group' => SettingGroupEnum::DOWNGRADE, 'default' => (bool) config('plans.downgrade.soft_disable.photos'), 'rules' => ['boolean']],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function stored(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): array => Setting::query()->pluck('value', 'key')->all());
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function grouped(): array
    {
        $stored = $this->stored();
        $grouped = [];

        foreach ($this->definitions() as $key => $definition) {
            $grouped[$definition['group']->value][$key] = $stored[$key] ?? $definition['default'];
        }

        return $grouped;
    }

    public function get(string $key): mixed
    {
        return $this->stored()[$key] ?? $this->definitions()[$key]['default'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function update(array $values): void
    {
        $definitions = $this->definitions();

        foreach ($values as $key => $value) {
            if (! isset($definitions[$key])) {
                continue;
            }

            Setting::updateOrCreate(['key' => $key], ['group' => $definitions[$key]['group'], 'value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }
}
