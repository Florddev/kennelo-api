<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ServiceOfferEnum;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Conditions auxquelles une activité vend une prestation du catalogue (table activity_services).
 * organization_id est répété pour les clés étrangères composites : l'activité et la prestation
 * appartiennent forcément à la même entreprise.
 *
 * @property string $organization_id
 * @property string $activity_id
 * @property string $service_id
 * @property ServiceOfferEnum $offered_as
 * @property numeric-string $adjustment_percent
 * @property bool $is_included
 * @property bool $is_active
 */
class ActivityOffer extends Pivot
{
    protected $table = 'activity_services';

    protected function casts(): array
    {
        return [
            'offered_as' => ServiceOfferEnum::class,
            'adjustment_percent' => 'decimal:2',
            'is_included' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
