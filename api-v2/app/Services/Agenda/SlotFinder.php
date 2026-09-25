<?php

declare(strict_types=1);

namespace App\Services\Agenda;

use App\Enums\AvailabilityStatusEnum;
use App\Enums\WeekDayEnum;
use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

/**
 * Créneaux libres des ressources d'une activité. Classe pure : AgendaService lui passe les horaires, les
 * plannings et les occupations, elle ne lit rien d'autre.
 *
 * Un jour, une ressource est disponible sur ses plages de planning qui tombent dans les horaires d'ouverture.
 * Une fermeture exceptionnelle supprime la journée ; une ouverture exceptionnelle lève les horaires d'ouverture,
 * seuls les plannings comptent alors. Les créneaux commencent sur la grille de l'écart configuré, comptée depuis
 * minuit dans le fuseau de l'activité (9 h, 9 h 15…), au plus tôt au délai de prévenance, et ne chevauchent
 * aucune occupation de la ressource. Les horaires sont des heures locales, les créneaux des instants UTC : un jour
 * de changement d'heure garde ses heures locales.
 */
final class SlotFinder
{
    /**
     * @param  array<int, list<array{0: string, 1: string}>>  $openingHours  [jour (WeekDayEnum)] => plages « H:i »
     * @param  array<string, AvailabilityStatusEnum>  $exceptions  [Y-m-d] => fermeture ou ouverture exceptionnelle
     * @param  array<string, array<int, list<array{0: string, 1: string}>>>  $schedules  [ressource][jour] => plages « H:i »,
     *                                                                                   dans l'ordre où les ressources sont proposées
     * @param  array<string, list<array{0: CarbonImmutable, 1: CarbonImmutable}>>  $busy  [ressource] => occupations
     */
    public function __construct(
        private readonly string $timezone,
        private readonly array $openingHours,
        private readonly array $exceptions,
        private readonly array $schedules,
        private readonly array $busy,
        private readonly int $stepMinutes,
        private readonly CarbonImmutable $earliestStart,
    ) {}

    /**
     * Créneaux de $durationMinutes du $from au $to compris (dates locales), avec les ressources libres pour chacun.
     *
     * @return list<array{starts_at: CarbonImmutable, ends_at: CarbonImmutable, resource_ids: list<string>}>
     */
    public function find(CarbonImmutable $from, CarbonImmutable $to, int $durationMinutes): array
    {
        $slots = [];

        foreach (CarbonPeriod::create($from->toDateString(), $to->toDateString()) as $date) {
            $day = $date->toDateString();

            foreach ($this->schedules as $resourceId => $week) {
                foreach ($this->windows($day, $week) as [$opens, $closes]) {
                    foreach ($this->starts($day, $opens, $closes, $durationMinutes) as $start) {
                        $end = $start->addMinutes($durationMinutes);

                        if ($this->isFree($resourceId, $start, $end)) {
                            $slots[$start->getTimestamp()] ??= ['starts_at' => $start, 'ends_at' => $end, 'resource_ids' => []];
                            $slots[$start->getTimestamp()]['resource_ids'][] = $resourceId;
                        }
                    }
                }
            }
        }

        ksort($slots);

        return array_values($slots);
    }

    /**
     * Ressources libres pour un créneau qui commence à $startsAt, dans l'ordre où elles sont proposées.
     *
     * @return list<string>
     */
    public function freeResourcesAt(CarbonImmutable $startsAt, int $durationMinutes): array
    {
        $day = $startsAt->setTimezone($this->timezone)->startOfDay();

        foreach ($this->find($day, $day, $durationMinutes) as $slot) {
            if ($slot['starts_at']->equalTo($startsAt)) {
                return $slot['resource_ids'];
            }
        }

        return [];
    }

    /**
     * Plages de la ressource ce jour-là, limitées aux horaires d'ouverture.
     *
     * @param  array<int, list<array{0: string, 1: string}>>  $week
     * @return list<array{0: string, 1: string}>
     */
    private function windows(string $day, array $week): array
    {
        $exception = $this->exceptions[$day] ?? null;

        if ($exception === AvailabilityStatusEnum::CLOSED) {
            return [];
        }

        $weekday = WeekDayEnum::fromDate(CarbonImmutable::parse($day))->value;
        $schedule = self::merge($week[$weekday] ?? []);

        if ($exception === AvailabilityStatusEnum::OPEN) {
            return $schedule;
        }

        $windows = [];

        foreach ($schedule as [$start, $end]) {
            foreach (self::merge($this->openingHours[$weekday] ?? []) as [$opens, $closes]) {
                $from = max($start, $opens);
                $to = min($end, $closes);

                if ($from < $to) {
                    $windows[] = [$from, $to];
                }
            }
        }

        return $windows;
    }

    /**
     * Débuts possibles dans une plage : sur la grille de l'écart, au plus tôt au délai de prévenance, et assez tôt
     * pour que le créneau finisse dans la plage.
     *
     * @return list<CarbonImmutable>
     */
    private function starts(string $day, string $opens, string $closes, int $durationMinutes): array
    {
        $opensAt = CarbonImmutable::parse("{$day} {$opens}", $this->timezone);
        $closesAt = CarbonImmutable::parse("{$day} {$closes}", $this->timezone)->utc();
        $minutes = $opensAt->hour * 60 + $opensAt->minute;
        $start = $opensAt->utc()->addMinutes(($this->stepMinutes - $minutes % $this->stepMinutes) % $this->stepMinutes);
        $starts = [];

        while ($start->addMinutes($durationMinutes)->lessThanOrEqualTo($closesAt)) {
            if ($start->greaterThanOrEqualTo($this->earliestStart)) {
                $starts[] = $start;
            }

            $start = $start->addMinutes($this->stepMinutes);
        }

        return $starts;
    }

    private function isFree(string $resourceId, CarbonImmutable $start, CarbonImmutable $end): bool
    {
        foreach ($this->busy[$resourceId] ?? [] as [$busyStart, $busyEnd]) {
            if ($start->lessThan($busyEnd) && $busyStart->lessThan($end)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Trie les plages et fusionne celles qui se touchent : 9 h - 12 h et 12 h - 14 h forment 9 h - 14 h.
     *
     * @param  list<array{0: string, 1: string}>  $ranges
     * @return list<array{0: string, 1: string}>
     */
    private static function merge(array $ranges): array
    {
        usort($ranges, fn (array $a, array $b): int => strcmp($a[0], $b[0]));
        $merged = [];

        foreach ($ranges as [$start, $end]) {
            $last = array_key_last($merged);

            if ($last !== null && $start <= $merged[$last][1]) {
                $merged[$last][1] = max($merged[$last][1], $end);
            } else {
                $merged[] = [$start, $end];
            }
        }

        return $merged;
    }
}
