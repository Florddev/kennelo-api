<?php

declare(strict_types=1);

namespace App\Services\Admin\Prospect;

use App\Enums\PaginationEnum;
use App\Enums\ProspectImportStatusEnum;
use App\Enums\ProspectSourceEnum;
use App\Jobs\ImportProspectsFromApifyJob;
use App\Models\Activity;
use App\Models\Prospect;
use App\Models\ProspectImport;
use App\Models\User;
use App\Services\Prospect\CompanyLookupService;
use App\Services\Prospect\Contracts\PlaceDiscoveryService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProspectService
{
    public function __construct(
        private PlaceDiscoveryService $discovery,
        private CompanyLookupService $companyLookup,
    ) {}

    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? PaginationEnum::DEFAULT_PAGINATION->value();

        return Prospect::query()
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['department'] ?? null, fn ($q, $dept) => $q->where('department', $dept))
            ->when($filters['region'] ?? null, fn ($q, $region) => $q->where('region', $region))
            ->when($filters['assigned_to'] ?? null, fn ($q, $userId) => $q->where('assigned_to', $userId))
            ->when(
                isset($filters['registered']),
                fn ($q) => $filters['registered']
                    ? $q->whereNotNull('kennelo_activity_id')
                    : $q->whereNull('kennelo_activity_id'),
            )
            ->when(
                $filters['min_rating'] ?? null,
                fn ($q, $rating) => $q->where('google_rating', '>=', $rating),
            )
            ->when(
                $filters['search'] ?? null,
                fn ($q, $search) => $q->where(
                    fn ($sub) => $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%"),
                ),
            )
            ->when(
                $filters['sort_by'] ?? null,
                fn ($q, $sort) => $q->orderBy($sort, $filters['sort_direction'] ?? 'asc'),
                fn ($q) => $q->latest(),
            )
            ->with(['assignedTo', 'kenneloActivity'])
            ->paginate($perPage);
    }

    public function mapGeoJson(array $filters = []): array
    {
        $features = $this->mapData($filters)
            ->map(fn (Prospect $prospect): array => [
                'type' => 'Feature',
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [(float) $prospect->longitude, (float) $prospect->latitude],
                ],
                'properties' => [
                    'id' => $prospect->id,
                    'name' => $prospect->name,
                    'address' => $prospect->address,
                    'city' => $prospect->city,
                    'phone' => $prospect->phone,
                    'website' => $prospect->website,
                    'google_rating' => $prospect->google_rating,
                    'status' => $prospect->status->value,
                    'is_registered' => $prospect->kennelo_activity_id !== null,
                ],
            ])
            ->all();

        return [
            'type' => 'FeatureCollection',
            'features' => $features,
        ];
    }

    /**
     * @return Collection<int, Prospect>
     */
    private function mapData(array $filters = []): Collection
    {
        return Prospect::query()
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['department'] ?? null, fn ($q, $dept) => $q->where('department', $dept))
            ->when(
                isset($filters['registered']),
                fn ($q) => $filters['registered']
                    ? $q->whereNotNull('kennelo_activity_id')
                    : $q->whereNull('kennelo_activity_id'),
            )
            ->when(
                isset($filters['bbox']),
                fn ($q) => $q->whereBetween('longitude', [$filters['bbox'][0], $filters['bbox'][2]])
                    ->whereBetween('latitude', [$filters['bbox'][1], $filters['bbox'][3]]),
            )
            ->get(['id', 'name', 'address', 'city', 'phone', 'website', 'google_rating', 'status', 'latitude', 'longitude', 'kennelo_activity_id']);
    }

    public function find(string $id): Prospect
    {
        return Prospect::with(['assignedTo', 'kenneloActivity', 'notes.author', 'contacts.author'])
            ->findOrFail($id);
    }

    public function updateStatus(Prospect $prospect, array $data): Prospect
    {
        $prospect->update(['status' => $data['status']]);

        return $prospect->fresh(['assignedTo', 'kenneloActivity']);
    }

    public function assign(Prospect $prospect, array $data): Prospect
    {
        $prospect->update(['assigned_to' => $data['assigned_to'] ?? null]);

        return $prospect->fresh(['assignedTo', 'kenneloActivity']);
    }

    public function update(Prospect $prospect, array $data): Prospect
    {
        $prospect->update($data);

        return $prospect->fresh(['assignedTo', 'kenneloActivity']);
    }

    public function delete(Prospect $prospect): void
    {
        DB::transaction(fn () => $prospect->delete());
    }

    /**
     * @return array{imported: int, skipped: int}
     */
    public function importFromApify(array $data): array
    {
        $terms = $data['search_terms'] ?? [
            'pension canine',
            'pension féline',
            'chenil',
            "garde d'animaux",
            'pet sitter',
            'pension pour animaux',
        ];

        $items = $this->discovery->discover(
            $terms,
            $data['location'],
            (int) ($data['max_results'] ?? 20),
        );

        $imported = 0;
        $skipped = 0;

        foreach ($items as $item) {
            if (empty($item['google_place_id'])) {
                $skipped++;

                continue;
            }

            if (Prospect::where('google_place_id', $item['google_place_id'])->exists()) {
                $skipped++;

                continue;
            }

            Prospect::create(array_merge($item, [
                'source' => ProspectSourceEnum::APIFY->value,
            ]));
            $imported++;
        }

        return ['imported' => $imported, 'skipped' => $skipped];
    }

    public function startImport(array $data, ?User $requestedBy = null): ProspectImport
    {
        $import = ProspectImport::create([
            'location' => $data['location'],
            'search_terms' => $data['search_terms'] ?? null,
            'max_results' => (int) ($data['max_results'] ?? 20),
            'status' => ProspectImportStatusEnum::PENDING->value,
            'requested_by' => $requestedBy?->id,
        ]);

        ImportProspectsFromApifyJob::dispatch($import->id);

        return $import;
    }

    public function processImport(string $importId): void
    {
        $import = ProspectImport::find($importId);

        if ($import === null) {
            return;
        }

        $import->update(['status' => ProspectImportStatusEnum::PROCESSING->value]);

        try {
            $result = $this->importFromApify([
                'location' => $import->location,
                'search_terms' => $import->search_terms,
                'max_results' => $import->max_results,
            ]);

            $import->update([
                'status' => ProspectImportStatusEnum::COMPLETED->value,
                'imported_count' => $result['imported'],
                'skipped_count' => $result['skipped'],
                'finished_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $import->update([
                'status' => ProspectImportStatusEnum::FAILED->value,
                'error' => $exception->getMessage(),
                'finished_at' => now(),
            ]);
        }
    }

    public function reconcile(Prospect $prospect): Prospect
    {
        $activity = $this->matchActivity($prospect);

        if ($activity === null && $prospect->google_place_id !== null) {
            $company = $this->companyLookup->searchByText(
                trim($prospect->name.' '.($prospect->city ?? '')),
            );

            if ($company !== null) {
                $prospect->fill([
                    'siret' => $company['siret'] ?? $prospect->siret,
                    'siren' => $company['siren'] ?? $prospect->siren,
                    'ape_code' => $company['ape_code'] ?? $prospect->ape_code,
                ]);

                if (! empty($company['siret'])) {
                    $activity = Activity::where('siret', $company['siret'])->first();
                }
            }
        }

        $prospect->fill([
            'kennelo_activity_id' => $activity?->id,
            'reconciled_at' => now(),
        ]);
        $prospect->save();

        return $prospect->fresh(['assignedTo', 'kenneloActivity']);
    }

    private function matchActivity(Prospect $prospect): ?Activity
    {
        if ($prospect->siret !== null) {
            $bySiret = Activity::where('siret', $prospect->siret)->first();

            if ($bySiret !== null) {
                return $bySiret;
            }
        }

        return Activity::query()
            ->where('name', $prospect->name)
            ->whereHas('address', function ($q) use ($prospect): void {
                $q->where('city', $prospect->city);
            })
            ->first();
    }
}
