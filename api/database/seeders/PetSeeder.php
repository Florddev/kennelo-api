<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AnimalType;
use App\Models\AttributeDefinition;
use App\Models\AttributeOption;
use App\Models\Pet;
use App\Models\PetAttribute;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PetSeeder extends Seeder
{
    private array $animalTypes;

    private string $userId;

    private array $attributeCache = [];

    public function run(): void
    {
        $this->loadDependencies();

        $dog = $this->animalTypes['dog'];
        $cat = $this->animalTypes['cat'];
        $bird = $this->animalTypes['bird'];
        $rabbit = $this->animalTypes['rabbit'];
        $rodent = $this->animalTypes['rodent'];
        $reptile = $this->animalTypes['reptile'];

        // Rex — Labrador Retriever
        $rexId = (string) Str::uuid();
        DB::table('pets')->insert([
            'id' => $rexId,
            'user_id' => $this->userId,
            'animal_type_id' => $dog,
            'name' => 'Rex',
            'breed' => 'Labrador Retriever',
            'birth_date' => '2020-05-15',
            'sex' => 'male',
            'weight' => 32.5,
            'is_sterilized' => true,
            'has_microchip' => true,
            'microchip_number' => '250269801234567',
            'adoption_date' => '2020-08-20',
            'about' => 'Chien très joueur et affectueux, adore les enfants et les longues promenades.',
            'health_notes' => 'Vaccination à jour, traitement anti-puces mensuel.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->seedPetImages($rexId, 'labrador', 3, true);
        $this->addOption($rexId, 'energy_level', 'high');
        $this->addOption($rexId, 'potty_trained', 'fully_trained');
        $this->addOption($rexId, 'potty_break_frequency', 'every_4h');
        $this->addOption($rexId, 'leash_trained', 'yes');
        $this->addOption($rexId, 'daily_walks', 'two');
        $this->addOption($rexId, 'separation_anxiety', 'none');
        $this->addOption($rexId, 'friendly_with_children', 'yes');
        $this->addOption($rexId, 'friendly_with_dogs', 'yes');
        $this->addOption($rexId, 'friendly_with_cats', 'unknown');
        $this->addOption($rexId, 'can_be_left_alone', '4h');
        $this->addOption($rexId, 'feeding_frequency', 'twice_daily');

        // Minou — Chat Européen
        $minouId = (string) Str::uuid();
        DB::table('pets')->insert([
            'id' => $minouId,
            'user_id' => $this->userId,
            'animal_type_id' => $cat,
            'name' => 'Minou',
            'breed' => 'Européen',
            'birth_date' => '2019-03-10',
            'sex' => 'female',
            'weight' => 4.2,
            'is_sterilized' => true,
            'has_microchip' => true,
            'microchip_number' => '250269801234568',
            'adoption_date' => '2019-06-15',
            'about' => 'Chatte calme et indépendante, préfère les endroits tranquilles.',
            'health_notes' => 'Allergique aux crevettes, vaccination à jour.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->seedPetImages($minouId, 'cat', 2, true);
        $this->addOption($minouId, 'energy_level', 'low');
        $this->addOption($minouId, 'litter_trained', 'yes');
        $this->addOption($minouId, 'indoor_outdoor', 'indoor_only');
        $this->addOption($minouId, 'friendly_with_children', 'no');
        $this->addOption($minouId, 'friendly_with_dogs', 'no');
        $this->addOption($minouId, 'friendly_with_cats', 'yes');
        $this->addOption($minouId, 'friendly_with_other_cats', 'yes');
        $this->addOption($minouId, 'can_be_left_alone', '8h');
        $this->addOption($minouId, 'feeding_frequency', 'twice_daily');
        $this->addBoolean($minouId, 'declawed', false);
        $this->addText($minouId, 'special_diet', 'Croquettes hypoallergéniques, sans crustacés.');

        // Kiwi — Perruche ondulée
        $kiwiId = (string) Str::uuid();
        DB::table('pets')->insert([
            'id' => $kiwiId,
            'user_id' => $this->userId,
            'animal_type_id' => $bird,
            'name' => 'Kiwi',
            'breed' => 'Perruche ondulée',
            'birth_date' => '2022-01-20',
            'sex' => 'male',
            'weight' => 0.04,
            'is_sterilized' => null,
            'has_microchip' => false,
            'adoption_date' => '2022-03-05',
            'about' => 'Perruche très sociable qui aime chanter et siffler.',
            'health_notes' => 'En bonne santé, vétérinaire aviaire consulté tous les 6 mois.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->seedPetImages($kiwiId, 'parakeet', 2, false);
        $this->addText($kiwiId, 'bird_species', 'Melopsittacus undulatus');
        $this->addOption($kiwiId, 'flight_status', 'fully_flighted');
        $this->addOption($kiwiId, 'noise_level', 'moderate');
        $this->addBoolean($kiwiId, 'can_talk', true);
        $this->addOption($kiwiId, 'housing_size', 'medium');
        $this->addOption($kiwiId, 'out_of_cage_time', '1_to_3h');
        $this->addOption($kiwiId, 'bath_method', 'misting');
        $this->addOption($kiwiId, 'friendly_with_children', 'yes');
        $this->addOption($kiwiId, 'friendly_with_birds', 'yes');
        $this->addOption($kiwiId, 'can_be_handled', 'yes_with_care');
        $this->addOption($kiwiId, 'feeding_frequency', 'once_daily');
        $this->addText($kiwiId, 'special_diet', 'Mélange de graines, fruits frais (pomme, carotte), pas d\'avocat.');

        // Caramel — Lapin nain bélier
        $caramelId = (string) Str::uuid();
        DB::table('pets')->insert([
            'id' => $caramelId,
            'user_id' => $this->userId,
            'animal_type_id' => $rabbit,
            'name' => 'Caramel',
            'breed' => 'Lapin nain bélier',
            'birth_date' => '2021-09-15',
            'sex' => 'female',
            'weight' => 1.8,
            'is_sterilized' => true,
            'has_microchip' => false,
            'adoption_date' => '2021-11-20',
            'about' => 'Lapine très douce et câline, adore se faire caresser.',
            'health_notes' => 'Dents contrôlées régulièrement par vétérinaire.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->seedPetImages($caramelId, 'rabbit', 2, true);
        $this->addOption($caramelId, 'litter_trained', 'yes');
        $this->addOption($caramelId, 'housing_type', 'cage_with_exercise');
        $this->addOption($caramelId, 'housing_size', 'large');
        $this->addOption($caramelId, 'hay_type', 'timothy');
        $this->addOption($caramelId, 'free_roam_time', '1_to_3h');
        $this->addOption($caramelId, 'can_be_handled', 'yes_easily');
        $this->addOption($caramelId, 'friendly_with_children', 'yes');
        $this->addOption($caramelId, 'feeding_frequency', 'twice_daily');
        $this->addText($caramelId, 'special_diet', 'Foin à volonté, granulés, légumes frais (carottes, brocoli). Pas d\'oignons ni rhubarbe.');

        // Max — Berger Allemand (senior)
        $maxId = (string) Str::uuid();
        DB::table('pets')->insert([
            'id' => $maxId,
            'user_id' => $this->userId,
            'animal_type_id' => $dog,
            'name' => 'Max',
            'breed' => 'Berger Allemand',
            'birth_date' => '2015-02-10',
            'sex' => 'male',
            'weight' => 38.0,
            'is_sterilized' => true,
            'has_microchip' => true,
            'microchip_number' => '250269801234569',
            'adoption_date' => '2015-04-15',
            'about' => 'Chien calme et obéissant, excellent gardien. Arthrose aux hanches, nécessite des sorties courtes.',
            'health_notes' => 'Arthrose avancée aux hanches, traitement quotidien anti-inflammatoire et glucosamine.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->seedPetImages($maxId, 'german-shepherd', 2, true);
        $this->addOption($maxId, 'energy_level', 'low');
        $this->addOption($maxId, 'potty_trained', 'fully_trained');
        $this->addOption($maxId, 'potty_break_frequency', 'every_6h_plus');
        $this->addOption($maxId, 'leash_trained', 'yes');
        $this->addOption($maxId, 'daily_walks', 'one');
        $this->addOption($maxId, 'separation_anxiety', 'mild');
        $this->addOption($maxId, 'friendly_with_children', 'yes');
        $this->addOption($maxId, 'friendly_with_dogs', 'yes');
        $this->addOption($maxId, 'friendly_with_cats', 'yes');
        $this->addOption($maxId, 'can_be_left_alone', '8h');
        $this->addOption($maxId, 'feeding_frequency', 'twice_daily');
        $this->addText($maxId, 'medications', 'Anti-inflammatoires (1 comprimé matin), glucosamine (1 gélule soir).');

        // Luna — Maine Coon
        $lunaId = (string) Str::uuid();
        DB::table('pets')->insert([
            'id' => $lunaId,
            'user_id' => $this->userId,
            'animal_type_id' => $cat,
            'name' => 'Luna',
            'breed' => 'Maine Coon',
            'birth_date' => '2021-07-22',
            'sex' => 'female',
            'weight' => 6.5,
            'is_sterilized' => true,
            'has_microchip' => true,
            'microchip_number' => '250269801234570',
            'adoption_date' => '2021-09-30',
            'about' => 'Grande chatte très active qui aime explorer l\'extérieur.',
            'health_notes' => 'Vaccination complète incluant rage, vermifugée tous les 3 mois.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->seedPetImages($lunaId, 'maine-coon', 3, true);
        $this->addOption($lunaId, 'energy_level', 'high');
        $this->addOption($lunaId, 'litter_trained', 'yes');
        $this->addOption($lunaId, 'indoor_outdoor', 'outdoor_access');
        $this->addOption($lunaId, 'friendly_with_children', 'yes');
        $this->addOption($lunaId, 'friendly_with_dogs', 'unknown');
        $this->addOption($lunaId, 'friendly_with_cats', 'no');
        $this->addOption($lunaId, 'friendly_with_other_cats', 'no');
        $this->addOption($lunaId, 'can_be_left_alone', '8h');
        $this->addOption($lunaId, 'feeding_frequency', 'twice_daily');
        $this->addBoolean($lunaId, 'declawed', false);

        // Noisette — Hamster doré
        $noisetteId = (string) Str::uuid();
        DB::table('pets')->insert([
            'id' => $noisetteId,
            'user_id' => $this->userId,
            'animal_type_id' => $rodent,
            'name' => 'Noisette',
            'breed' => 'Hamster doré',
            'birth_date' => '2023-04-10',
            'sex' => 'female',
            'weight' => 0.12,
            'is_sterilized' => null,
            'has_microchip' => false,
            'adoption_date' => '2023-05-20',
            'about' => 'Hamster curieuse et active la nuit. Aime son roue et ses tunnels.',
            'health_notes' => 'Aucun problème de santé connu.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->seedPetImages($noisetteId, 'hamster', 2, true);
        $this->addText($noisetteId, 'rodent_species', 'Mesocricetus auratus');
        $this->addBoolean($noisetteId, 'is_nocturnal', true);
        $this->addOption($noisetteId, 'can_be_handled', 'yes_with_care');
        $this->addOption($noisetteId, 'social_living', 'alone');
        $this->addOption($noisetteId, 'housing_size', 'medium');
        $this->addOption($noisetteId, 'friendly_with_children', 'supervised_only');
        $this->addOption($noisetteId, 'feeding_frequency', 'once_daily');
        $this->addText($noisetteId, 'special_diet', 'Mélange de graines, légumes frais. Éviter agrumes et oignons.');

        // Zigzag — Gecko léopard
        $zigzagId = (string) Str::uuid();
        DB::table('pets')->insert([
            'id' => $zigzagId,
            'user_id' => $this->userId,
            'animal_type_id' => $reptile,
            'name' => 'Zigzag',
            'breed' => 'Gecko léopard',
            'birth_date' => '2022-08-01',
            'sex' => 'male',
            'weight' => 0.08,
            'is_sterilized' => null,
            'has_microchip' => false,
            'adoption_date' => '2022-10-15',
            'about' => 'Gecko docile et curieux. Actif en soirée, apprécie les sessions de manipulation courtes.',
            'health_notes' => 'Dernière mue sans complication. Pas de parasites.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->seedPetImages($zigzagId, 'gecko', 2, false);
        $this->addText($zigzagId, 'reptile_species', 'Eublepharis macularius');
        $this->addOption($zigzagId, 'terrarium_type', 'desert');
        $this->addInteger($zigzagId, 'temperature_day', 28);
        $this->addInteger($zigzagId, 'temperature_night', 22);
        $this->addOption($zigzagId, 'humidity_level', 'low');
        $this->addBoolean($zigzagId, 'uv_light_needed', false);
        $this->addOption($zigzagId, 'diet_type', 'insects');
        $this->addBoolean($zigzagId, 'requires_live_food', true);
        $this->addBoolean($zigzagId, 'is_venomous', false);
        $this->addBoolean($zigzagId, 'is_nocturnal', true);
        $this->addOption($zigzagId, 'can_be_handled', 'yes_with_care');
        $this->addOption($zigzagId, 'handling_frequency', 'few_per_week');
        $this->addOption($zigzagId, 'feeding_frequency', 'every_2_days');
        $this->addText($zigzagId, 'special_diet', 'Grillons et vers de farine vivants, calcium en poudre saupoudré sur les proies.');
    }

    private function seedPetImages(string $petId, string $category, int $count, bool $withAvatar): void
    {
        $pet = Pet::find($petId);

        if (! $pet) {
            return;
        }

        for ($i = 0; $i < $count; $i++) {
            try {
                $response = Http::withoutVerifying()
                    ->withOptions(['allow_redirects' => true])
                    ->timeout(15)
                    ->get("https://loremflickr.com/600/400/{$category}");

                if ($response->successful()) {
                    $tmpPath = tempnam(sys_get_temp_dir(), 'pet_image_').'.jpg';
                    file_put_contents($tmpPath, $response->body());
                    $pet->addMedia($tmpPath)->toMediaCollection(MediaService::COLLECTION_IMAGES);
                }
            } catch (\Throwable) {
                continue;
            }
        }

        if ($withAvatar) {
            try {
                $response = Http::withoutVerifying()
                    ->withOptions(['allow_redirects' => true])
                    ->timeout(15)
                    ->get("https://loremflickr.com/400/400/{$category}");

                if ($response->successful()) {
                    $tmpPath = tempnam(sys_get_temp_dir(), 'pet_avatar_').'.jpg';
                    file_put_contents($tmpPath, $response->body());
                    $pet->addMedia($tmpPath)->toMediaCollection(MediaService::COLLECTION_AVATAR);
                }
            } catch (\Throwable) {
            }
        }
    }

    private function loadDependencies(): void
    {
        $this->userId = User::where('email', 'user@orus.com')->value('id');
        if (! $this->userId) {
            throw new \RuntimeException('User user@orus.com not found. Run UsersSeeder first.');
        }

        $codes = ['dog', 'cat', 'bird', 'rabbit', 'rodent', 'reptile'];
        $types = AnimalType::whereIn('code', $codes)->pluck('id', 'code')->toArray();

        if (count($types) !== count($codes)) {
            throw new \RuntimeException('Missing animal types. Run AnimalTypeSeeder first.');
        }

        $this->animalTypes = $types;
    }

    private function getAttributeId(string $code): ?string
    {
        if (! isset($this->attributeCache[$code])) {
            $this->attributeCache[$code] = AttributeDefinition::where('code', $code)->value('id');
        }

        return $this->attributeCache[$code];
    }

    private function addOption(string $petId, string $attributeCode, string $optionValue): void
    {
        $attributeId = $this->getAttributeId($attributeCode);
        if (! $attributeId) {
            return;
        }

        $optionId = AttributeOption::where('attribute_definition_id', $attributeId)
            ->where('value', $optionValue)
            ->value('id');

        if ($optionId) {
            PetAttribute::firstOrCreate(
                ['pet_id' => $petId, 'attribute_definition_id' => $attributeId],
                ['attribute_option_id' => $optionId]
            );
        }
    }

    private function addText(string $petId, string $attributeCode, string $value): void
    {
        $attributeId = $this->getAttributeId($attributeCode);
        if (! $attributeId) {
            return;
        }

        PetAttribute::firstOrCreate(
            ['pet_id' => $petId, 'attribute_definition_id' => $attributeId],
            ['value_text' => $value]
        );
    }

    private function addInteger(string $petId, string $attributeCode, int $value): void
    {
        $attributeId = $this->getAttributeId($attributeCode);
        if (! $attributeId) {
            return;
        }

        PetAttribute::firstOrCreate(
            ['pet_id' => $petId, 'attribute_definition_id' => $attributeId],
            ['value_integer' => $value]
        );
    }

    private function addBoolean(string $petId, string $attributeCode, bool $value): void
    {
        $attributeId = $this->getAttributeId($attributeCode);
        if (! $attributeId) {
            return;
        }

        PetAttribute::firstOrCreate(
            ['pet_id' => $petId, 'attribute_definition_id' => $attributeId],
            ['value_boolean' => $value]
        );
    }
}
