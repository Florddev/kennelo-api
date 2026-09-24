<?php

declare(strict_types=1);

namespace App\Enums;

enum PlanEnum: string
{
    case FREE = 'free';
    case STARTER = 'starter';
    case PRO = 'pro';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function commissionRate(): string
    {
        return (string) config('plans.'.$this->value.'.commission_rate', setting('host_commission_rate'));
    }

    /**
     * @return array<string, int|null>
     */
    public function limits(): array
    {
        $limits = config('plans.'.$this->value.'.limits', []);

        return is_array($limits) ? $limits : [];
    }

    public function limit(string $key): ?int
    {
        $value = $this->limits()[$key] ?? null;

        return $value === null ? null : (int) $value;
    }

    public function isUnlimited(string $key): bool
    {
        $value = $this->limit($key);

        return $value === null || $value < 0;
    }
}
