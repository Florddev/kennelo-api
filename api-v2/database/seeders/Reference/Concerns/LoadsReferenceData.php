<?php

declare(strict_types=1);

namespace Database\Seeders\Reference\Concerns;

trait LoadsReferenceData
{
    /**
     * Lit un fichier JSON de database/data.
     *
     * @return array<int|string, mixed>
     */
    protected function referenceData(string $file): array
    {
        return json_decode(
            (string) file_get_contents(database_path('data/'.$file)),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
    }

    protected function hasReferenceData(string $file): bool
    {
        return is_file(database_path('data/'.$file));
    }
}
