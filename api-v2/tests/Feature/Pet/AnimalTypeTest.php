<?php

declare(strict_types=1);

use App\Models\AnimalType;
use App\Models\AttributeDefinition;
use App\Models\AttributeOption;

// ─── index ────────────────────────────────────────────────────────────────────

it('returns all animal types without authentication', function () {
    AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    AnimalType::create(['code' => 'cat', 'name' => 'Chat', 'category' => 'mammals']);

    $this->getJson('/api/animal-types')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('includes attribute definitions with each animal type', function () {
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $definition = AttributeDefinition::create([
        'code' => 'energy_level',
        'label' => 'Niveau d\'énergie',
        'value_type' => 'text',
        'has_predefined_options' => true,
        'is_required' => false,
    ]);
    $animalType->attributeDefinitions()->attach($definition->id);

    $this->getJson('/api/animal-types')
        ->assertOk()
        ->assertJsonPath('data.0.code', 'dog')
        ->assertJsonStructure(['data' => [['id', 'code', 'name', 'category', 'attribute_definitions']]]);
});

it('includes options for attributes with has_predefined_options', function () {
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $definition = AttributeDefinition::create([
        'code' => 'energy_level',
        'label' => 'Niveau d\'énergie',
        'value_type' => 'text',
        'has_predefined_options' => true,
        'is_required' => false,
    ]);
    $animalType->attributeDefinitions()->attach($definition->id);

    AttributeOption::create(['attribute_definition_id' => $definition->id, 'value' => 'low', 'label' => 'Faible', 'sort_order' => 0]);
    AttributeOption::create(['attribute_definition_id' => $definition->id, 'value' => 'high', 'label' => 'Élevé', 'sort_order' => 1]);

    $response = $this->getJson('/api/animal-types')->assertOk();

    expect($response->json('data.0.attribute_definitions.0.options'))->toHaveCount(2);
});

it('returns empty list when no animal types exist', function () {
    $this->getJson('/api/animal-types')
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
