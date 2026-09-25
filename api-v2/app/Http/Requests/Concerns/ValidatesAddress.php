<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rules\RequiredIf;

/**
 * Règles d'une adresse imbriquée, par exemple le siège d'une entreprise. Le département est
 * déduit du code postal par le modèle Address.
 */
trait ValidatesAddress
{
    /**
     * @param  bool|RequiredIf  $required  l'adresse doit-elle être fournie
     * @param  bool  $withCoordinates  latitude et longitude obligatoires, pour la recherche par distance
     * @return array<string, array<int, mixed>>
     */
    protected function addressRules(string $field = 'address', bool|RequiredIf $required = false, bool $withCoordinates = false): array
    {
        $coordinates = $withCoordinates ? "required_with:{$field}" : 'nullable';

        return [
            $field => [$required === false ? 'sometimes' : ($required === true ? 'required' : $required), 'array'],
            "{$field}.line1" => ["required_with:{$field}", 'string', 'max:255'],
            "{$field}.line2" => ['nullable', 'string', 'max:255'],
            "{$field}.postal_code" => ["required_with:{$field}", 'string', 'max:10'],
            "{$field}.city" => ["required_with:{$field}", 'string', 'max:100'],
            "{$field}.region" => ['nullable', 'string', 'max:100'],
            "{$field}.country" => ["required_with:{$field}", 'string', 'size:2'],
            "{$field}.latitude" => [$coordinates, 'numeric', 'between:-90,90'],
            "{$field}.longitude" => [$coordinates, 'numeric', 'between:-180,180'],
        ];
    }
}
