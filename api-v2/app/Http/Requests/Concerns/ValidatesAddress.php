<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

/**
 * Règles d'une adresse imbriquée, par exemple le siège d'une entreprise. Le département est
 * déduit du code postal par le modèle Address.
 */
trait ValidatesAddress
{
    /**
     * @return array<string, array<int, string>>
     */
    protected function addressRules(string $field = 'address'): array
    {
        return [
            $field => ['sometimes', 'array'],
            "{$field}.line1" => ["required_with:{$field}", 'string', 'max:255'],
            "{$field}.line2" => ['nullable', 'string', 'max:255'],
            "{$field}.postal_code" => ["required_with:{$field}", 'string', 'max:10'],
            "{$field}.city" => ["required_with:{$field}", 'string', 'max:100'],
            "{$field}.region" => ['nullable', 'string', 'max:100'],
            "{$field}.country" => ["required_with:{$field}", 'string', 'size:2'],
            "{$field}.latitude" => ['nullable', 'numeric', 'between:-90,90'],
            "{$field}.longitude" => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
