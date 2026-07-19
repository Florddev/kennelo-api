<?php

declare(strict_types=1);

namespace App\Filament\Support;

class StatFormatter
{
    public static function number(int|float $value, int $decimals = 0): string
    {
        return number_format((float) $value, $decimals, ',', "\u{202F}");
    }

    public static function euro(int|float $value, int $decimals = 0): string
    {
        return self::number($value, $decimals).' €';
    }

    public static function percent(int|float $value, int $decimals = 1): string
    {
        return self::number($value, $decimals).' %';
    }

    /**
     * @return array{label: string, color: string, icon: string}
     */
    public static function trend(?float $variation): array
    {
        if ($variation === null) {
            return ['label' => 'n/d', 'color' => 'gray', 'icon' => 'heroicon-m-minus'];
        }

        if ($variation >= 0) {
            return [
                'label' => '+'.self::number($variation, 1).' %',
                'color' => 'success',
                'icon' => 'heroicon-m-arrow-trending-up',
            ];
        }

        return [
            'label' => self::number($variation, 1).' %',
            'color' => 'danger',
            'icon' => 'heroicon-m-arrow-trending-down',
        ];
    }

    public static function month(string $ym): string
    {
        $parts = explode('-', $ym);

        if (count($parts) !== 2) {
            return $ym;
        }

        return $parts[1].'/'.substr($parts[0], 2);
    }
}
