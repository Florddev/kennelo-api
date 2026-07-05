<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProspectImportStatusEnum;
use App\Models\ProspectImport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProspectImport>
 */
class ProspectImportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location' => 'Lyon, France',
            'search_terms' => null,
            'max_results' => 20,
            'status' => ProspectImportStatusEnum::PENDING->value,
            'imported_count' => 0,
            'skipped_count' => 0,
            'error' => null,
            'requested_by' => null,
            'finished_at' => null,
        ];
    }

    public function completed(int $imported = 5, int $skipped = 1): static
    {
        return $this->state(fn (): array => [
            'status' => ProspectImportStatusEnum::COMPLETED->value,
            'imported_count' => $imported,
            'skipped_count' => $skipped,
            'finished_at' => now(),
        ]);
    }
}
