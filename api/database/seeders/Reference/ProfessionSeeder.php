<?php

declare(strict_types=1);

namespace Database\Seeders\Reference;

use Database\Seeders\Reference\Concerns\LoadsReferenceData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Catégories et métiers de départ. Données : database/data/professions.json.
 *
 * Les métiers se gèrent ensuite dans le back-office : le seeder crée uniquement ce qui manque
 * et ne modifie jamais un métier existant, ni ses espèces, ni ses justificatifs.
 */
class ProfessionSeeder extends Seeder
{
    use LoadsReferenceData;

    public function run(): void
    {
        $data = $this->referenceData('professions.json');

        DB::transaction(function () use ($data): void {
            $now = now();
            $categoryIds = $this->seedCategories($data['categories'], $now);
            $animalTypeIds = DB::table('animal_types')->pluck('id', 'code')->all();
            $existing = DB::table('professions')->pluck('code')->all();

            foreach ($data['professions'] as $profession) {
                if (in_array($profession['code'], $existing, true)) {
                    continue;
                }

                $this->createProfession($profession, $categoryIds[$profession['category']], $animalTypeIds, $now);
            }
        });
    }

    /**
     * @param  list<array<string, mixed>>  $categories
     * @return array<string, string> Identifiants des catégories, indexés par code.
     */
    private function seedCategories(array $categories, Carbon $now): array
    {
        DB::table('profession_categories')->insertOrIgnore(array_map(fn (array $category): array => [
            'id' => (string) Str::uuid7(),
            'code' => $category['code'],
            'name' => $this->json($category['name']),
            'sort_order' => $category['sort_order'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $categories));

        return DB::table('profession_categories')->pluck('id', 'code')->all();
    }

    /**
     * @param  array<string, mixed>  $profession
     * @param  array<string, string>  $animalTypeIds
     */
    private function createProfession(array $profession, string $categoryId, array $animalTypeIds, Carbon $now): void
    {
        $id = (string) Str::uuid7();

        DB::table('professions')->insert([
            'id' => $id,
            'profession_category_id' => $categoryId,
            'code' => $profession['code'],
            'name' => $this->json($profession['name']),
            'booking_mode' => $profession['booking_mode'],
            'billing_unit' => $profession['billing_unit'],
            'allows_at_pro' => in_array('at_pro', $profession['locations'], true),
            'allows_at_client' => in_array('at_client', $profession['locations'], true),
            'allows_remote' => in_array('remote', $profession['locations'], true),
            'pricing_dimensions' => $this->json($profession['pricing_dimensions']),
            'is_active' => true,
            'sort_order' => $profession['sort_order'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('profession_animal_types')->insert(array_map(fn (string $code): array => [
            'profession_id' => $id,
            'animal_type_id' => $animalTypeIds[$code],
        ], $profession['animal_types']));

        DB::table('profession_document_requirements')->insert(array_map(fn (array $document): array => [
            'id' => (string) Str::uuid7(),
            'profession_id' => $id,
            'document_type' => $document['type'],
            'is_required' => $document['required'],
            'validity_months' => $document['validity_months'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $profession['documents']));
    }

    private function json(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
