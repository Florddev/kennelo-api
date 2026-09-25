<?php

declare(strict_types=1);

namespace App\Services\Agenda;

use App\Enums\BookingItemStatusEnum;
use App\Enums\BookingStatusEnum;
use App\Enums\ResourceBookingKindEnum;
use App\Models\Activity;
use App\Models\ActivityAvailability;
use App\Models\ActivityOpeningHour;
use App\Models\AgendaResource;
use App\Models\BookingItem;
use App\Models\Organization;
use App\Models\ResourceBooking;
use App\Models\ResourceSchedule;
use App\Models\User;
use App\Services\Agenda\Exceptions\SlotUnavailableException;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Agenda des ressources d'une entreprise : ce qui les occupe, les créneaux qu'elles laissent libres, et la vue du pro.
 */
class AgendaService
{
    /**
     * Calculateur des créneaux de l'activité du $from au $to (dates locales) : ses horaires et ses exceptions, les
     * plannings des ressources actives qui y travaillent (ou de la seule $resourceId), et tout ce qui occupe ces
     * ressources, dans toutes les activités de l'entreprise.
     */
    public function slotFinder(Activity $activity, CarbonImmutable $from, CarbonImmutable $to, ?string $resourceId = null): SlotFinder
    {
        $schedules = [];

        ResourceSchedule::query()
            ->join('resources', 'resources.id', '=', 'resource_schedules.resource_id')
            ->where('resource_schedules.activity_id', $activity->id)
            ->where('resources.is_active', true)
            ->when($resourceId !== null, fn (Builder $query) => $query->where('resources.id', $resourceId))
            ->orderBy('resources.name')
            ->orderBy('resources.id')
            ->get(['resource_schedules.resource_id', 'resource_schedules.weekday', 'resource_schedules.start_time', 'resource_schedules.end_time'])
            ->each(function (ResourceSchedule $schedule) use (&$schedules): void {
                $schedules[$schedule->resource_id][$schedule->weekday->value][] = [self::time($schedule->start_time), self::time($schedule->end_time)];
            });

        $openingHours = [];

        $activity->openingHours()->get()->each(function (ActivityOpeningHour $hours) use (&$openingHours): void {
            $openingHours[$hours->weekday->value][] = [self::time($hours->opens_at), self::time($hours->closes_at)];
        });

        $exceptions = $activity->availabilities()
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString())
            ->get()
            ->mapWithKeys(fn (ActivityAvailability $exception): array => [$exception->date->toDateString() => $exception->status])
            ->all();

        $busy = [];

        ResourceBooking::query()
            ->whereIn('resource_id', array_keys($schedules))
            ->overlapping(
                CarbonImmutable::parse($from->toDateString(), $activity->timezone),
                CarbonImmutable::parse($to->toDateString(), $activity->timezone)->addDay(),
            )
            ->get(['resource_id', 'starts_at', 'ends_at'])
            ->each(function (ResourceBooking $entry) use (&$busy): void {
                $busy[$entry->resource_id][] = [$entry->starts_at->toImmutable(), $entry->ends_at->toImmutable()];
            });

        return new SlotFinder(
            timezone: $activity->timezone,
            openingHours: $openingHours,
            exceptions: $exceptions,
            schedules: $schedules,
            busy: $busy,
            stepMinutes: (int) config('agenda.slot_step_minutes'),
            earliestStart: now()->toImmutable()->utc()->addMinutes((int) config('agenda.min_notice_minutes')),
        );
    }

    /**
     * Occupe la ressource sur [$start, $end). L'appelant la verrouille d'abord (lock()) : deux demandes pour la même
     * ressource passent alors l'une après l'autre, et la seconde voit la première.
     *
     * @param  array<string, mixed>  $attributes  booking_item_id, note, created_by
     *
     * @throws SlotUnavailableException
     */
    public function occupy(AgendaResource $resource, CarbonImmutable $start, CarbonImmutable $end, ResourceBookingKindEnum $kind, array $attributes = []): ResourceBooking
    {
        $unavailable = $kind === ResourceBookingKindEnum::BOOKING ? SlotUnavailableException::taken() : SlotUnavailableException::occupied();

        if ($resource->resourceBookings()->overlapping($start, $end)->exists()) {
            throw $unavailable;
        }

        try {
            return $resource->resourceBookings()->create([
                ...$attributes,
                'kind' => $kind,
                'starts_at' => $start->utc(),
                'ends_at' => $end->utc(),
            ]);
        } catch (QueryException $exception) {
            // PostgreSQL : la contrainte d'exclusion a vu passer une demande simultanée.
            if ($exception->getCode() === SlotUnavailableException::EXCLUSION_VIOLATION) {
                throw $unavailable;
            }

            throw $exception;
        }
    }

    public function lock(AgendaResource $resource): void
    {
        AgendaResource::query()->whereKey($resource->id)->lockForUpdate()->first();
    }

    /**
     * Absence d'une personne ou blocage d'un équipement ou d'un espace. Elle ne peut pas recouvrir un rendez-vous :
     * il faut d'abord l'annuler.
     *
     * @param  array{kind: string, starts_at: string, ends_at: string, note?: string|null}  $data
     */
    public function addUnavailability(AgendaResource $resource, array $data, User $author): ResourceBooking
    {
        return DB::transaction(function () use ($resource, $data, $author): ResourceBooking {
            $this->lock($resource);

            return $this->occupy(
                $resource,
                CarbonImmutable::parse($data['starts_at']),
                CarbonImmutable::parse($data['ends_at']),
                ResourceBookingKindEnum::from($data['kind']),
                ['note' => $data['note'] ?? null, 'created_by' => $author->id],
            );
        });
    }

    public function removeUnavailability(ResourceBooking $entry): void
    {
        $entry->delete();
    }

    /**
     * Vue agenda du $from au $to compris (dates dans le fuseau de l'activité, ou celui par défaut) : les ressources,
     * ce qui les occupe, et les options de séjour acceptées qui restent à placer.
     *
     * Vue d'une activité : ses ressources seulement. Une ressource qui travaille aussi ailleurs y montre ses autres
     * rendez-vous comme des plages occupées, sans leur détail.
     *
     * @param  array{from: string, to: string, resource_ids?: list<string>}  $filters
     * @return array{timezone: string, resources: Collection<int, AgendaResource>, entries: Collection<int, ResourceBooking>, to_schedule: Collection<int, BookingItem>}
     */
    public function agenda(Organization $organization, array $filters, ?Activity $activity = null): array
    {
        $timezone = $activity->timezone ?? (string) config('activities.default_timezone');
        $from = CarbonImmutable::parse($filters['from'], $timezone);
        $to = CarbonImmutable::parse($filters['to'], $timezone)->addDay();

        $resources = $organization->resources()
            ->when($activity !== null, fn (Builder $query) => $query->presentIn($activity))
            ->when(isset($filters['resource_ids']), fn (Builder $query) => $query->whereKey($filters['resource_ids']))
            ->with('member.user')
            ->get();

        $entries = ResourceBooking::query()
            ->whereIn('resource_id', $resources->modelKeys())
            ->overlapping($from, $to)
            ->with(['bookingItem.booking.user', 'bookingItem.service', 'bookingItem.pet'])
            ->orderBy('starts_at')
            ->orderBy('id')
            ->get();

        if ($activity !== null) {
            $entries
                ->reject(fn (ResourceBooking $entry): bool => $entry->bookingItem?->booking?->activity_id === $activity->id)
                ->each(fn (ResourceBooking $entry) => $entry->setRelation('bookingItem', null));
        }

        $toSchedule = BookingItem::query()
            ->where('status', BookingItemStatusEnum::TO_SCHEDULE)
            ->whereHas('service', fn (Builder $query) => $query->where('requires_scheduling', true))
            ->whereHas('booking', fn (Builder $query) => $query
                ->where('organization_id', $organization->id)
                ->when($activity !== null, fn (Builder $query) => $query->where('activity_id', $activity?->id))
                ->whereIn('status', [BookingStatusEnum::CONFIRMED, BookingStatusEnum::IN_PROGRESS])
                ->whereDate('start_date', '<=', $filters['to'])
                ->whereDate('end_date', '>=', $filters['from']))
            ->with(['booking.user', 'service', 'pet'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        return ['timezone' => $timezone, 'resources' => $resources, 'entries' => $entries, 'to_schedule' => $toSchedule];
    }

    /**
     * Heure « H:i », que la base la rende avec ou sans les secondes.
     */
    private static function time(string $time): string
    {
        return substr($time, 0, 5);
    }
}
