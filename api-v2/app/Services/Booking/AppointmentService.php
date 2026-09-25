<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Enums\BookingModeEnum;
use App\Enums\ServiceOfferEnum;
use App\Models\Activity;
use App\Models\AgendaResource;
use App\Models\Pet;
use App\Models\Service;
use App\Services\Agenda\AgendaService;
use App\Services\Agenda\Exceptions\SlotUnavailableException;
use App\Services\Catalog\ServicePriceResolver;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Rendez-vous : une prestation de l'activité pour un ou plusieurs animaux du client, faits l'un après l'autre par la
 * même ressource. La recherche de créneaux et le devis suivent les mêmes règles : un créneau affiché se réserve.
 */
class AppointmentService
{
    public function __construct(
        private readonly AgendaService $agenda,
        private readonly ServicePriceResolver $prices,
    ) {}

    /**
     * Créneaux libres du $from au $to (dates locales). Avec des animaux, le créneau dure le temps de les faire tous ;
     * sans, le temps le plus long de la grille, pour qu'un créneau affiché convienne à n'importe quel animal.
     *
     * @param  list<Pet>  $pets
     * @return array{duration_minutes: int, slots: list<array{starts_at: CarbonImmutable, ends_at: CarbonImmutable, resource_ids: list<string>}>, resources: Collection<int, AgendaResource>}
     */
    public function slots(Activity $activity, string $serviceId, array $pets, CarbonImmutable $from, CarbonImmutable $to, ?string $resourceId = null): array
    {
        $service = $this->bookableService($activity, $serviceId);
        $duration = $pets === []
            ? $this->longestDuration($service)
            : array_sum(array_column($this->priceLines($activity, $service, $pets), 'duration_minutes'));
        $slots = $this->agenda->slotFinder($activity, $from, $to, $resourceId)->find($from, $to, $duration);
        $resourceIds = array_values(array_unique(array_merge([], ...array_column($slots, 'resource_ids'))));

        return [
            'duration_minutes' => $duration,
            'slots' => $slots,
            'resources' => AgendaResource::query()->whereKey($resourceIds)->orderBy('name')->orderBy('id')->get(),
        ];
    }

    /**
     * Rendez-vous demandé : la prestation pour chaque animal, dans l'ordre donné, placées l'une après l'autre à partir
     * de $startsAt sur la même ressource. C'est celle choisie par le client, ou la première libre.
     *
     * @param  list<Pet>  $pets
     * @return array{resource: AgendaResource, starts_at: CarbonImmutable, ends_at: CarbonImmutable, lines: list<array{service: Service, pet: Pet, unit_price: numeric-string, subtotal: numeric-string, duration_minutes: int, starts_at: CarbonImmutable, ends_at: CarbonImmutable}>}
     *
     * @throws ValidationException|SlotUnavailableException
     */
    public function plan(Activity $activity, string $serviceId, array $pets, CarbonImmutable $startsAt, ?string $resourceId = null): array
    {
        $service = $this->bookableService($activity, $serviceId);
        $lines = $this->priceLines($activity, $service, $pets);

        if ($resourceId !== null && ! AgendaResource::query()->whereKey($resourceId)->where('is_active', true)->presentIn($activity)->exists()) {
            throw ValidationException::withMessages(['resource_id' => __('agenda.resource_not_in_activity')]);
        }

        $startsAt = $startsAt->utc();
        $day = $startsAt->setTimezone($activity->timezone)->startOfDay();
        $free = $this->agenda->slotFinder($activity, $day, $day, $resourceId)
            ->freeResourcesAt($startsAt, array_sum(array_column($lines, 'duration_minutes')));

        if ($free === []) {
            throw SlotUnavailableException::taken();
        }

        $planned = [];
        $lineStart = $startsAt;

        foreach ($lines as $line) {
            $lineEnd = $lineStart->addMinutes($line['duration_minutes']);
            $planned[] = [...$line, 'starts_at' => $lineStart, 'ends_at' => $lineEnd];
            $lineStart = $lineEnd;
        }

        return [
            'resource' => AgendaResource::query()->findOrFail($free[0]),
            'starts_at' => $startsAt,
            'ends_at' => $lineStart,
            'lines' => $planned,
        ];
    }

    /**
     * Prestation que l'activité vend seule et sur rendez-vous.
     *
     * @throws ValidationException
     */
    private function bookableService(Activity $activity, string $serviceId): Service
    {
        if ($activity->loadMissing('profession')->profession?->booking_mode !== BookingModeEnum::APPOINTMENT) {
            throw ValidationException::withMessages(['activity_id' => __('agenda.appointment_only')]);
        }

        $service = $activity->services()
            ->wherePivot('is_active', true)
            ->wherePivotIn('offered_as', [ServiceOfferEnum::STANDALONE->value, ServiceOfferEnum::BOTH->value])
            ->where('services.is_active', true)
            ->where('services.requires_scheduling', true)
            ->with('prices')
            ->find($serviceId);

        if ($service === null) {
            throw ValidationException::withMessages(['service_id' => __('agenda.service_unavailable')]);
        }

        return $service;
    }

    /**
     * Prix et durée de la prestation pour chaque animal, selon la grille, ajustés par l'activité.
     *
     * @param  list<Pet>  $pets
     * @return list<array{service: Service, pet: Pet, unit_price: numeric-string, subtotal: numeric-string, duration_minutes: int}>
     *
     * @throws ValidationException une espèce que l'activité n'accueille pas, ou que la grille ne prévoit pas
     */
    private function priceLines(Activity $activity, Service $service, array $pets): array
    {
        $species = $activity->animalTypes()->pluck('animal_types.id')->all();
        $lines = [];

        foreach ($pets as $index => $pet) {
            $line = in_array($pet->animal_type_id, $species, true)
                ? $this->prices->forPet($service, $pet->loadMissing(['animalType', 'animalBreed']))
                : null;

            if ($line?->duration_minutes === null) {
                throw ValidationException::withMessages(["pet_ids.{$index}" => __('agenda.pet_not_served', ['pet' => $pet->name])]);
            }

            $price = Money::adjust($line->price, $service->offer->adjustment_percent);
            $lines[] = [
                'service' => $service,
                'pet' => $pet,
                'unit_price' => $price,
                'subtotal' => $price,
                'duration_minutes' => $line->duration_minutes,
            ];
        }

        return $lines;
    }

    private function longestDuration(Service $service): int
    {
        $duration = (int) $service->prices->max('duration_minutes');

        if ($duration === 0) {
            throw ValidationException::withMessages(['service_id' => __('agenda.service_unavailable')]);
        }

        return $duration;
    }
}
