<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AnimalAttributeCategory;
use App\Models\AnimalType;
use App\Models\AttributeAnimalType;
use App\Models\AttributeDefinition;
use App\Models\AttributeOption;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    private array $animalTypes;

    public function run(): void
    {
        $this->loadAnimalTypes();

        $dog = $this->animalTypes['dog'];
        $cat = $this->animalTypes['cat'];
        $rabbit = $this->animalTypes['rabbit'];
        $rodent = $this->animalTypes['rodent'];
        $ferret = $this->animalTypes['ferret'];
        $bird = $this->animalTypes['bird'];
        $reptile = $this->animalTypes['reptile'];
        $amphibian = $this->animalTypes['amphibian'];

        $all = [$dog, $cat, $rabbit, $rodent, $ferret, $bird, $reptile, $amphibian];
        $commonPets = [$dog, $cat, $rabbit, $rodent, $ferret, $bird];
        $dogAndFerret = [$dog, $ferret];
        $dogCatFerret = [$dog, $cat, $ferret];
        $smallAnimals = [$rabbit, $rodent, $ferret, $bird];
        $exotics = [$reptile, $amphibian];

        $this->createAttribute('energy_level', 'Energy Level', AnimalAttributeCategory::BEHAVIOR, 'text', true, $commonPets, [
            ['value' => 'low', 'label' => 'Low'],
            ['value' => 'medium', 'label' => 'Medium'],
            ['value' => 'high', 'label' => 'High'],
            ['value' => 'very_high', 'label' => 'Very High'],
        ]);

        $this->createAttribute('friendly_with_children', 'Friendly with Children', AnimalAttributeCategory::SOCIAL, 'text', true, $commonPets, [
            ['value' => 'yes', 'label' => 'Yes'],
            ['value' => 'supervised_only', 'label' => 'Supervised Only'],
            ['value' => 'no', 'label' => 'No'],
            ['value' => 'unknown', 'label' => 'Unknown'],
        ]);

        $this->createAttribute('feeding_schedule', 'Feeding Schedule', AnimalAttributeCategory::DIET, 'text', true, $commonPets, [
            ['value' => 'morning_only', 'label' => 'Morning only'],
            ['value' => 'evening_only', 'label' => 'Evening only'],
            ['value' => 'morning_and_evening', 'label' => 'Morning & Evening'],
            ['value' => 'three_times_daily', 'label' => 'Three times a day'],
            ['value' => 'free_feeding', 'label' => 'Free feeding (always available)'],
        ]);

        $this->createAttribute('feeding_frequency', 'Feeding Frequency', AnimalAttributeCategory::DIET, 'text', true, $exotics, [
            ['value' => 'daily', 'label' => 'Once a day'],
            ['value' => 'every_2_days', 'label' => 'Every 2 days'],
            ['value' => 'twice_weekly', 'label' => 'Twice a week'],
            ['value' => 'weekly', 'label' => 'Once a week'],
        ]);

        $this->createAttribute('medications', 'Current Medications', AnimalAttributeCategory::HEALTH, 'text', false, $all);

        $this->createAttribute('special_diet', 'Special Diet Notes', AnimalAttributeCategory::DIET, 'text', false, $all);

        $this->createAttribute('friendly_with_dogs', 'Friendly with Dogs', AnimalAttributeCategory::SOCIAL, 'text', true, $dogCatFerret, [
            ['value' => 'yes', 'label' => 'Yes'],
            ['value' => 'no', 'label' => 'No'],
            ['value' => 'unknown', 'label' => 'Unknown'],
        ]);

        $this->createAttribute('friendly_with_cats', 'Friendly with Cats', AnimalAttributeCategory::SOCIAL, 'text', true, $dogAndFerret, [
            ['value' => 'yes', 'label' => 'Yes'],
            ['value' => 'no', 'label' => 'No'],
            ['value' => 'unknown', 'label' => 'Unknown'],
        ]);

        $this->createAttribute('can_be_left_alone', 'Max Time Alone', AnimalAttributeCategory::BEHAVIOR, 'text', true, $dogCatFerret, [
            ['value' => '1h', 'label' => '1 hour'],
            ['value' => '4h', 'label' => '4 hours'],
            ['value' => '8h', 'label' => '8 hours'],
            ['value' => 'never', 'label' => 'Never'],
        ]);

        $this->createAttribute('can_be_handled', 'Handleability', AnimalAttributeCategory::BEHAVIOR, 'text', true, array_merge($smallAnimals, $exotics), [
            ['value' => 'yes_easily', 'label' => 'Yes, easily'],
            ['value' => 'yes_with_care', 'label' => 'Yes, with care'],
            ['value' => 'requires_experience', 'label' => 'Requires experience'],
            ['value' => 'no', 'label' => 'Prefer not to be handled'],
        ]);

        $this->createAttribute('litter_trained', 'Litter Trained', AnimalAttributeCategory::HYGIENE, 'text', true, [$cat, $rabbit, $ferret], [
            ['value' => 'yes', 'label' => 'Yes'],
            ['value' => 'in_training', 'label' => 'In training'],
            ['value' => 'no', 'label' => 'No'],
        ]);

        $this->createAttribute('housing_size', 'Housing Size', AnimalAttributeCategory::HABITAT, 'text', true, $smallAnimals, [
            ['value' => 'small', 'label' => 'Small'],
            ['value' => 'medium', 'label' => 'Medium'],
            ['value' => 'large', 'label' => 'Large'],
            ['value' => 'extra_large', 'label' => 'Extra Large / Aviary'],
        ]);

        $this->createAttribute('is_nocturnal', 'Nocturnal', AnimalAttributeCategory::BEHAVIOR, 'boolean', false, [$rodent, $reptile, $amphibian]);

        $this->createAttribute('humidity_level', 'Humidity Level', AnimalAttributeCategory::HABITAT, 'text', true, $exotics, [
            ['value' => 'low', 'label' => 'Low (30–40%)'],
            ['value' => 'medium', 'label' => 'Medium (50–60%)'],
            ['value' => 'high', 'label' => 'High (70–80%)'],
            ['value' => 'very_high', 'label' => 'Very High (80%+)'],
        ]);

        $this->createAttribute('diet_type', 'Diet Type', AnimalAttributeCategory::DIET, 'text', true, $exotics, [
            ['value' => 'insects', 'label' => 'Insects'],
            ['value' => 'rodents', 'label' => 'Rodents / Pinky mice'],
            ['value' => 'worms', 'label' => 'Worms'],
            ['value' => 'vegetables', 'label' => 'Vegetables / Fruit'],
            ['value' => 'omnivore', 'label' => 'Omnivore'],
            ['value' => 'carnivore', 'label' => 'Carnivore'],
        ]);

        $this->createAttribute('requires_live_food', 'Requires Live Food', AnimalAttributeCategory::DIET, 'boolean', false, $exotics);

        $this->createAttribute('is_venomous', 'Venomous / Toxic', AnimalAttributeCategory::HEALTH, 'boolean', false, $exotics);

        $this->createAttribute('handling_frequency', 'Handling Frequency', AnimalAttributeCategory::CARE, 'text', true, $exotics, [
            ['value' => 'daily', 'label' => 'Daily'],
            ['value' => 'few_per_week', 'label' => 'A few times a week'],
            ['value' => 'weekly', 'label' => 'Weekly'],
            ['value' => 'rarely', 'label' => 'Rarely / Never'],
        ]);

        $this->createAttribute('potty_trained', 'Potty Trained', AnimalAttributeCategory::HYGIENE, 'text', true, [$dog], [
            ['value' => 'fully_trained', 'label' => 'Fully trained'],
            ['value' => 'in_training', 'label' => 'In training'],
            ['value' => 'not_trained', 'label' => 'Not trained'],
        ]);

        $this->createAttribute('potty_break_frequency', 'Potty Break Frequency', AnimalAttributeCategory::HYGIENE, 'text', true, [$dog], [
            ['value' => 'every_2h', 'label' => 'Every 2 hours'],
            ['value' => 'every_4h', 'label' => 'Every 4 hours'],
            ['value' => 'every_6h_plus', 'label' => 'Every 6+ hours'],
        ]);

        $this->createAttribute('leash_trained', 'Leash Trained', AnimalAttributeCategory::BEHAVIOR, 'text', true, [$dog], [
            ['value' => 'yes', 'label' => 'Yes'],
            ['value' => 'in_training', 'label' => 'In training'],
            ['value' => 'no', 'label' => 'No'],
        ]);

        $this->createAttribute('daily_walks', 'Daily Walks Needed', AnimalAttributeCategory::CARE, 'text', true, [$dog], [
            ['value' => 'one', 'label' => '1 walk'],
            ['value' => 'two', 'label' => '2 walks'],
            ['value' => 'three_plus', 'label' => '3+ walks'],
        ]);

        $this->createAttribute('separation_anxiety', 'Separation Anxiety', AnimalAttributeCategory::BEHAVIOR, 'text', true, [$dog], [
            ['value' => 'none', 'label' => 'None'],
            ['value' => 'mild', 'label' => 'Mild'],
            ['value' => 'moderate', 'label' => 'Moderate'],
            ['value' => 'severe', 'label' => 'Severe'],
        ]);

        $this->createAttribute('indoor_outdoor', 'Indoor / Outdoor', AnimalAttributeCategory::HABITAT, 'text', true, [$cat], [
            ['value' => 'indoor_only', 'label' => 'Indoor only'],
            ['value' => 'outdoor_access', 'label' => 'Outdoor access'],
            ['value' => 'outdoor', 'label' => 'Outdoor'],
        ]);

        $this->createAttribute('declawed', 'Declawed', AnimalAttributeCategory::HEALTH, 'boolean', false, [$cat]);

        $this->createAttribute('friendly_with_other_cats', 'Friendly with Other Cats', AnimalAttributeCategory::SOCIAL, 'text', true, [$cat], [
            ['value' => 'yes', 'label' => 'Yes'],
            ['value' => 'no', 'label' => 'No'],
            ['value' => 'unknown', 'label' => 'Unknown'],
        ]);

        $this->createAttribute('housing_type', 'Housing Type', AnimalAttributeCategory::HABITAT, 'text', true, [$rabbit], [
            ['value' => 'indoor_cage', 'label' => 'Indoor cage'],
            ['value' => 'outdoor_hutch', 'label' => 'Outdoor hutch'],
            ['value' => 'free_roam', 'label' => 'Free roam'],
            ['value' => 'cage_with_exercise', 'label' => 'Cage + exercise area'],
        ]);

        $this->createAttribute('hay_type', 'Primary Hay', AnimalAttributeCategory::DIET, 'text', true, [$rabbit], [
            ['value' => 'timothy', 'label' => 'Timothy'],
            ['value' => 'orchard', 'label' => 'Orchard grass'],
            ['value' => 'meadow', 'label' => 'Meadow hay'],
            ['value' => 'alfalfa', 'label' => 'Alfalfa (young/ill)'],
        ]);

        $this->createAttribute('free_roam_time', 'Daily Free Roam Time', AnimalAttributeCategory::CARE, 'text', true, [$rabbit], [
            ['value' => 'less_than_1h', 'label' => 'Less than 1 hour'],
            ['value' => '1_to_3h', 'label' => '1 to 3 hours'],
            ['value' => '3_to_6h', 'label' => '3 to 6 hours'],
            ['value' => 'all_day', 'label' => 'All day'],
        ]);

        $this->createAttribute('rodent_species', 'Species', AnimalAttributeCategory::INFO, 'text', false, [$rodent]);

        $this->createAttribute('social_living', 'Social Living', AnimalAttributeCategory::SOCIAL, 'text', true, [$rodent], [
            ['value' => 'alone', 'label' => 'Lives alone'],
            ['value' => 'pair', 'label' => 'Bonded pair'],
            ['value' => 'group', 'label' => 'Group'],
        ]);

        $this->createAttribute('biting_tendency', 'Biting Tendency', AnimalAttributeCategory::BEHAVIOR, 'text', true, [$ferret, $rodent], [
            ['value' => 'never', 'label' => 'Never'],
            ['value' => 'rarely', 'label' => 'Rarely'],
            ['value' => 'when_stressed', 'label' => 'When stressed'],
        ]);

        $this->createAttribute('social_with_ferrets', 'Social with Other Ferrets', AnimalAttributeCategory::SOCIAL, 'text', true, [$ferret], [
            ['value' => 'yes', 'label' => 'Yes'],
            ['value' => 'no', 'label' => 'No'],
            ['value' => 'unknown', 'label' => 'Unknown'],
        ]);

        $this->createAttribute('bird_species', 'Species', AnimalAttributeCategory::INFO, 'text', false, [$bird]);

        $this->createAttribute('flight_status', 'Flight Status', AnimalAttributeCategory::BEHAVIOR, 'text', true, [$bird], [
            ['value' => 'fully_flighted', 'label' => 'Fully flighted'],
            ['value' => 'wings_clipped', 'label' => 'Wings clipped'],
            ['value' => 'limited_flight', 'label' => 'Limited flight'],
        ]);

        $this->createAttribute('noise_level', 'Noise Level', AnimalAttributeCategory::BEHAVIOR, 'text', true, [$bird], [
            ['value' => 'quiet', 'label' => 'Quiet'],
            ['value' => 'moderate', 'label' => 'Moderate'],
            ['value' => 'loud', 'label' => 'Loud'],
            ['value' => 'very_loud', 'label' => 'Very loud'],
        ]);

        $this->createAttribute('can_talk', 'Can Talk', AnimalAttributeCategory::BEHAVIOR, 'boolean', false, [$bird]);

        $this->createAttribute('out_of_cage_time', 'Daily Out-of-Cage Time', AnimalAttributeCategory::CARE, 'text', true, [$bird], [
            ['value' => 'not_needed', 'label' => 'Not needed'],
            ['value' => 'less_than_1h', 'label' => 'Less than 1 hour'],
            ['value' => '1_to_3h', 'label' => '1 to 3 hours'],
            ['value' => 'more_than_3h', 'label' => 'More than 3 hours'],
        ]);

        $this->createAttribute('bath_method', 'Bathing Method', AnimalAttributeCategory::HYGIENE, 'text', true, [$bird], [
            ['value' => 'misting', 'label' => 'Misting'],
            ['value' => 'shallow_dish', 'label' => 'Shallow dish'],
            ['value' => 'both', 'label' => 'Both'],
            ['value' => 'none_needed', 'label' => 'None needed'],
        ]);

        $this->createAttribute('friendly_with_birds', 'Friendly with Other Birds', AnimalAttributeCategory::SOCIAL, 'text', true, [$bird], [
            ['value' => 'yes', 'label' => 'Yes'],
            ['value' => 'no', 'label' => 'No'],
            ['value' => 'unknown', 'label' => 'Unknown'],
        ]);

        $this->createAttribute('reptile_species', 'Species', AnimalAttributeCategory::INFO, 'text', false, [$reptile]);

        $this->createAttribute('terrarium_type', 'Terrarium Type', AnimalAttributeCategory::HABITAT, 'text', true, [$reptile], [
            ['value' => 'desert', 'label' => 'Desert'],
            ['value' => 'tropical', 'label' => 'Tropical'],
            ['value' => 'woodland', 'label' => 'Woodland'],
            ['value' => 'aqua_terrarium', 'label' => 'Aqua-terrarium'],
        ]);

        $this->createAttribute('temperature_day', 'Day Temperature (°C)', AnimalAttributeCategory::HABITAT, 'integer', false, [$reptile]);

        $this->createAttribute('temperature_night', 'Night Temperature (°C)', AnimalAttributeCategory::HABITAT, 'integer', false, [$reptile]);

        $this->createAttribute('uv_light_needed', 'UV Light Required', AnimalAttributeCategory::CARE, 'boolean', false, [$reptile]);

        $this->createAttribute('amphibian_species', 'Species', AnimalAttributeCategory::INFO, 'text', false, [$amphibian]);

        $this->createAttribute('habitat_type', 'Habitat Type', AnimalAttributeCategory::HABITAT, 'text', true, [$amphibian], [
            ['value' => 'aquatic', 'label' => 'Aquatic'],
            ['value' => 'semi_aquatic', 'label' => 'Semi-aquatic'],
            ['value' => 'terrestrial', 'label' => 'Terrestrial'],
        ]);

        $this->createAttribute('water_quality', 'Water Quality Requirements', AnimalAttributeCategory::HEALTH, 'text', false, [$amphibian]);

        $this->createAttribute('skin_care_notes', 'Skin Care Notes', AnimalAttributeCategory::HEALTH, 'text', false, [$amphibian]);
    }

    private function loadAnimalTypes(): void
    {
        $expectedCodes = ['dog', 'cat', 'rabbit', 'rodent', 'ferret', 'bird', 'reptile', 'amphibian'];

        $types = AnimalType::whereIn('code', $expectedCodes)->pluck('id', 'code')->toArray();

        if (count($types) !== count($expectedCodes)) {
            throw new \RuntimeException('Missing animal types. Run AnimalTypeSeeder first.');
        }

        $this->animalTypes = $types;
    }

    private function deriveIconName(string $code): ?string
    {
        return match ($code) {
            'energy_level' => 'BoltCircle',
            'friendly_with_children' => 'UsersGroupRounded',
            'feeding_schedule' => 'Calendar',
            'feeding_frequency' => 'Calendar',
            'medications' => 'Pill',
            'special_diet' => 'Notes',
            'friendly_with_dogs' => 'Paw',
            'friendly_with_cats' => 'Cat',
            'can_be_left_alone' => 'HomeSmile',
            'can_be_handled' => 'HandShake',
            'litter_trained' => 'Waterdrop',
            'housing_size' => 'Home2',
            'is_nocturnal' => 'Moon',
            'humidity_level' => 'Waterdrops',
            'diet_type' => 'Leaf',
            'requires_live_food' => 'DangerSquare',
            'is_venomous' => 'DangerTriangle',
            'handling_frequency' => 'ClockCircle',
            'potty_trained' => 'Waterdrop',
            'potty_break_frequency' => 'ClockCircle',
            'leash_trained' => 'WalkingRound',
            'daily_walks' => 'Running',
            'separation_anxiety' => 'HeartPulse',
            'indoor_outdoor' => 'Home2',
            'declawed' => 'Scissors',
            'friendly_with_other_cats' => 'Cat',
            'housing_type' => 'HomeSmile',
            'hay_type' => 'Leaf',
            'free_roam_time' => 'Running',
            'rodent_species' => 'Book',
            'social_living' => 'UsersGroupRounded',
            'biting_tendency' => 'DangerTriangle',
            'social_with_ferrets' => 'UsersGroupRounded',
            'bird_species' => 'Book',
            'flight_status' => 'Plain',
            'noise_level' => 'Speaker',
            'can_talk' => 'ChatRound',
            'out_of_cage_time' => 'Calendar',
            'bath_method' => 'Waterdrop',
            'friendly_with_birds' => 'UsersGroupRounded',
            'reptile_species' => 'Book',
            'terrarium_type' => 'Home2',
            'temperature_day' => 'Thermometer',
            'temperature_night' => 'Moon',
            'uv_light_needed' => 'Sun',
            'amphibian_species' => 'Book',
            'habitat_type' => 'Water',
            'water_quality' => 'Shield',
            'skin_care_notes' => 'Health',
            default => null,
        };
    }

    private function deriveInputType(string $code, string $valueType, bool $hasPredefinedOptions): string
    {
        if ($code === 'medications') {
            return 'multi-list';
        }
        if ($valueType === 'boolean') {
            return 'boolean';
        }
        if ($valueType === 'integer' || $valueType === 'decimal') {
            return 'number';
        }
        if ($valueType === 'date') {
            return 'date';
        }
        if ($hasPredefinedOptions) {
            return 'badge-list';
        }

        return 'textarea';
    }

    private function createAttribute(string $code, string $label, AnimalAttributeCategory $category, string $valueType, bool $hasPredefinedOptions, array $animalTypeIds, array $options = []): void
    {
        $attribute = AttributeDefinition::updateOrCreate(
            ['code' => $code],
            [
                'label' => $label,
                'category' => $category->value,
                'value_type' => $valueType,
                'input_type' => $this->deriveInputType($code, $valueType, $hasPredefinedOptions),
                'icon_name' => $this->deriveIconName($code),
                'has_predefined_options' => $hasPredefinedOptions,
                'is_required' => false,
                'validation_rules' => null,
            ]
        );

        foreach ($animalTypeIds as $animalTypeId) {
            AttributeAnimalType::firstOrCreate([
                'attribute_definition_id' => $attribute->id,
                'animal_type_id' => $animalTypeId,
            ]);
        }

        if ($hasPredefinedOptions && ! empty($options)) {
            foreach ($options as $index => $option) {
                AttributeOption::firstOrCreate(
                    [
                        'attribute_definition_id' => $attribute->id,
                        'value' => $option['value'],
                    ],
                    [
                        'label' => $option['label'],
                        'sort_order' => $index,
                    ]
                );
            }
        }
    }
}
