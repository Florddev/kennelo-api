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
use Illuminate\Support\Facades\Http;

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

        $rex = Pet::create([
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
        ]);
        $this->seedPetImages($rex->id, 'labrador', 3, true);
        $this->addOption($rex->id, 'energy_level', 'high');
        $this->addOption($rex->id, 'potty_trained', 'fully_trained');
        $this->addOption($rex->id, 'potty_break_frequency', 'every_4h');
        $this->addOption($rex->id, 'leash_trained', 'yes');
        $this->addOption($rex->id, 'daily_walks', 'two');
        $this->addOption($rex->id, 'separation_anxiety', 'none');
        $this->addOption($rex->id, 'friendly_with_children', 'yes');
        $this->addOption($rex->id, 'friendly_with_dogs', 'yes');
        $this->addOption($rex->id, 'friendly_with_cats', 'unknown');
        $this->addOption($rex->id, 'can_be_left_alone', '4h');
        $this->addOption($rex->id, 'feeding_schedule', 'morning_and_evening');

        $minou = Pet::create([
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
        ]);
        $this->seedPetImages($minou->id, 'cat', 2, true);
        $this->addOption($minou->id, 'energy_level', 'low');
        $this->addOption($minou->id, 'litter_trained', 'yes');
        $this->addOption($minou->id, 'indoor_outdoor', 'indoor_only');
        $this->addOption($minou->id, 'friendly_with_children', 'no');
        $this->addOption($minou->id, 'friendly_with_dogs', 'no');
        $this->addOption($minou->id, 'friendly_with_cats', 'yes');
        $this->addOption($minou->id, 'friendly_with_other_cats', 'yes');
        $this->addOption($minou->id, 'can_be_left_alone', '8h');
        $this->addOption($minou->id, 'feeding_schedule', 'morning_and_evening');
        $this->addBoolean($minou->id, 'declawed', false);
        $this->addText($minou->id, 'special_diet', 'Croquettes hypoallergéniques, sans crustacés.');

        $kiwi = Pet::create([
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
        ]);
        $this->seedPetImages($kiwi->id, 'parakeet', 2, false);
        $this->addText($kiwi->id, 'bird_species', 'Melopsittacus undulatus');
        $this->addOption($kiwi->id, 'flight_status', 'fully_flighted');
        $this->addOption($kiwi->id, 'noise_level', 'moderate');
        $this->addBoolean($kiwi->id, 'can_talk', true);
        $this->addOption($kiwi->id, 'housing_size', 'medium');
        $this->addOption($kiwi->id, 'out_of_cage_time', '1_to_3h');
        $this->addOption($kiwi->id, 'bath_method', 'misting');
        $this->addOption($kiwi->id, 'friendly_with_children', 'yes');
        $this->addOption($kiwi->id, 'friendly_with_birds', 'yes');
        $this->addOption($kiwi->id, 'can_be_handled', 'yes_with_care');
        $this->addOption($kiwi->id, 'feeding_schedule', 'morning_only');
        $this->addText($kiwi->id, 'special_diet', 'Mélange de graines, fruits frais (pomme, carotte), pas d\'avocat.');

        $caramel = Pet::create([
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
        ]);
        $this->seedPetImages($caramel->id, 'rabbit', 2, true);
        $this->addOption($caramel->id, 'litter_trained', 'yes');
        $this->addOption($caramel->id, 'housing_type', 'cage_with_exercise');
        $this->addOption($caramel->id, 'housing_size', 'large');
        $this->addOption($caramel->id, 'hay_type', 'timothy');
        $this->addOption($caramel->id, 'free_roam_time', '1_to_3h');
        $this->addOption($caramel->id, 'can_be_handled', 'yes_easily');
        $this->addOption($caramel->id, 'friendly_with_children', 'yes');
        $this->addOption($caramel->id, 'feeding_schedule', 'morning_and_evening');
        $this->addText($caramel->id, 'special_diet', 'Foin à volonté, granulés, légumes frais (carottes, brocoli). Pas d\'oignons ni rhubarbe.');

        $max = Pet::create([
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
        ]);
        $this->seedPetImages($max->id, 'german-shepherd', 2, true);
        $this->addOption($max->id, 'energy_level', 'low');
        $this->addOption($max->id, 'potty_trained', 'fully_trained');
        $this->addOption($max->id, 'potty_break_frequency', 'every_6h_plus');
        $this->addOption($max->id, 'leash_trained', 'yes');
        $this->addOption($max->id, 'daily_walks', 'one');
        $this->addOption($max->id, 'separation_anxiety', 'mild');
        $this->addOption($max->id, 'friendly_with_children', 'yes');
        $this->addOption($max->id, 'friendly_with_dogs', 'yes');
        $this->addOption($max->id, 'friendly_with_cats', 'yes');
        $this->addOption($max->id, 'can_be_left_alone', '8h');
        $this->addOption($max->id, 'feeding_schedule', 'morning_and_evening');
        $this->addText($max->id, 'medications', 'Anti-inflammatoires (1 comprimé matin), glucosamine (1 gélule soir).');

        $luna = Pet::create([
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
        ]);
        $this->seedPetImages($luna->id, 'maine-coon', 3, true);
        $this->addOption($luna->id, 'energy_level', 'high');
        $this->addOption($luna->id, 'litter_trained', 'yes');
        $this->addOption($luna->id, 'indoor_outdoor', 'outdoor_access');
        $this->addOption($luna->id, 'friendly_with_children', 'yes');
        $this->addOption($luna->id, 'friendly_with_dogs', 'unknown');
        $this->addOption($luna->id, 'friendly_with_cats', 'no');
        $this->addOption($luna->id, 'friendly_with_other_cats', 'no');
        $this->addOption($luna->id, 'can_be_left_alone', '8h');
        $this->addOption($luna->id, 'feeding_schedule', 'morning_and_evening');
        $this->addBoolean($luna->id, 'declawed', false);

        $noisette = Pet::create([
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
        ]);
        $this->seedPetImages($noisette->id, 'hamster', 2, true);
        $this->addText($noisette->id, 'rodent_species', 'Mesocricetus auratus');
        $this->addBoolean($noisette->id, 'is_nocturnal', true);
        $this->addOption($noisette->id, 'can_be_handled', 'yes_with_care');
        $this->addOption($noisette->id, 'social_living', 'alone');
        $this->addOption($noisette->id, 'housing_size', 'medium');
        $this->addOption($noisette->id, 'friendly_with_children', 'supervised_only');
        $this->addOption($noisette->id, 'feeding_schedule', 'morning_only');
        $this->addText($noisette->id, 'special_diet', 'Mélange de graines, légumes frais. Éviter agrumes et oignons.');

        $zigzag = Pet::create([
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
        ]);
        $this->seedPetImages($zigzag->id, 'gecko', 2, false);
        $this->addText($zigzag->id, 'reptile_species', 'Eublepharis macularius');
        $this->addOption($zigzag->id, 'terrarium_type', 'desert');
        $this->addInteger($zigzag->id, 'temperature_day', 28);
        $this->addInteger($zigzag->id, 'temperature_night', 22);
        $this->addOption($zigzag->id, 'humidity_level', 'low');
        $this->addBoolean($zigzag->id, 'uv_light_needed', false);
        $this->addOption($zigzag->id, 'diet_type', 'insects');
        $this->addBoolean($zigzag->id, 'requires_live_food', true);
        $this->addBoolean($zigzag->id, 'is_venomous', false);
        $this->addBoolean($zigzag->id, 'is_nocturnal', true);
        $this->addOption($zigzag->id, 'can_be_handled', 'yes_with_care');
        $this->addOption($zigzag->id, 'handling_frequency', 'few_per_week');
        $this->addOption($zigzag->id, 'feeding_frequency', 'every_2_days');
        $this->addText($zigzag->id, 'special_diet', 'Grillons et vers de farine vivants, calcium en poudre saupoudré sur les proies.');
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
