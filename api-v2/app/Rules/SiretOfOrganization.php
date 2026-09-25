<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Organization;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Un SIRET d'établissement commence par le SIREN de l'entreprise. Un particulier, sans SIREN, n'en a pas.
 */
final class SiretOfOrganization implements ValidationRule
{
    /**
     * L'entreprise peut manquer (fermée) : la validation s'exécute avant l'autorisation, qui répondra 404.
     */
    public function __construct(private readonly ?Organization $organization) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $siren = $this->organization?->siren;

        if (blank($siren) || ! str_starts_with((string) $value, (string) $siren)) {
            $fail(__('activity.siret_mismatch'));
        }
    }
}
