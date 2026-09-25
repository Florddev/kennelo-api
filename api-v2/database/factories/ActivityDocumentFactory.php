<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DocumentStatusEnum;
use App\Enums\DocumentTypeEnum;
use App\Models\Activity;
use App\Models\ActivityDocument;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * Par défaut, une attestation d'assurance en attente de vérification, valable un an.
 *
 * @extends Factory<ActivityDocument>
 */
class ActivityDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'document_type' => DocumentTypeEnum::RC_PRO_INSURANCE,
            'expires_at' => now()->addYear()->toDateString(),
        ];
    }

    public function approved(): static
    {
        return $this->afterMaking(function (ActivityDocument $document): void {
            $document->forceFill(['status' => DocumentStatusEnum::APPROVED, 'reviewed_at' => now()]);
        });
    }

    public function expiringOn(Carbon $date): static
    {
        return $this->state(fn (): array => ['expires_at' => $date->toDateString()]);
    }
}
