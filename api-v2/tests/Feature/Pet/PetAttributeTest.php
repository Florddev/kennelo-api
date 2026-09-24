<?php

declare(strict_types=1);

use App\Models\AnimalType;
use App\Models\AttributeDefinition;
use App\Models\AttributeOption;
use App\Models\Pet;
use App\Models\PetAttribute;
use App\Models\User;

function makeDogWithDefinition(string $valueType = 'text', bool $hasOptions = false): array
{
    $user = User::factory()->create();
    $animalType = AnimalType::create(['code' => 'dog', 'name' => 'Chien', 'category' => 'mammals']);
    $pet = Pet::create(['user_id' => $user->id, 'animal_type_id' => $animalType->id, 'name' => 'Rex']);
    $definition = AttributeDefinition::create([
        'code' => 'test_attr_'.$valueType,
        'label' => 'Test attribute',
        'value_type' => $valueType,
        'has_predefined_options' => $hasOptions,
        'is_required' => false,
    ]);
    $animalType->attributeDefinitions()->attach($definition->id);

    return compact('user', 'animalType', 'pet', 'definition');
}

// ─── upsert nominal ───────────────────────────────────────────────────────────

it('owner can sync attributes on their pet', function () {
    ['user' => $user, 'pet' => $pet, 'definition' => $definition] = makeDogWithDefinition('text');

    $this->withHeaders(asUser($user))
        ->putJson("/api/pets/{$pet->id}/attributes", [
            'attributes' => [
                ['attribute_definition_id' => $definition->id, 'value_text' => 'some medication'],
            ],
        ])
        ->assertOk()
        ->assertJsonStructure(['data']);

    expect(PetAttribute::where('pet_id', $pet->id)->count())->toBe(1);
});

it('upsert is idempotent: same payload twice yields same result', function () {
    ['user' => $user, 'pet' => $pet, 'definition' => $definition] = makeDogWithDefinition('text');

    $payload = [
        'attributes' => [
            ['attribute_definition_id' => $definition->id, 'value_text' => 'paracetamol'],
        ],
    ];

    $this->withHeaders(asUser($user))->putJson("/api/pets/{$pet->id}/attributes", $payload)->assertOk();
    $this->withHeaders(asUser($user))->putJson("/api/pets/{$pet->id}/attributes", $payload)->assertOk();

    expect(PetAttribute::where('pet_id', $pet->id)->count())->toBe(1)
        ->and(PetAttribute::where('pet_id', $pet->id)->first()->value_text)->toBe('paracetamol');
});

it('upsert updates existing attribute value', function () {
    ['user' => $user, 'pet' => $pet, 'definition' => $definition] = makeDogWithDefinition('text');

    $this->withHeaders(asUser($user))->putJson("/api/pets/{$pet->id}/attributes", [
        'attributes' => [['attribute_definition_id' => $definition->id, 'value_text' => 'first']],
    ])->assertOk();

    $this->withHeaders(asUser($user))->putJson("/api/pets/{$pet->id}/attributes", [
        'attributes' => [['attribute_definition_id' => $definition->id, 'value_text' => 'updated']],
    ])->assertOk();

    expect(PetAttribute::where('pet_id', $pet->id)->first()->value_text)->toBe('updated');
});

// ─── authorization ────────────────────────────────────────────────────────────

it('other user cannot sync attributes on someone else pet', function () {
    ['pet' => $pet, 'definition' => $definition] = makeDogWithDefinition('text');
    $other = User::factory()->create();

    $this->withHeaders(asUser($other))
        ->putJson("/api/pets/{$pet->id}/attributes", [
            'attributes' => [['attribute_definition_id' => $definition->id, 'value_text' => 'hack']],
        ])
        ->assertForbidden();
});

it('unauthenticated user cannot sync attributes', function () {
    ['pet' => $pet, 'definition' => $definition] = makeDogWithDefinition('text');

    $this->putJson("/api/pets/{$pet->id}/attributes", [
        'attributes' => [['attribute_definition_id' => $definition->id, 'value_text' => 'hack']],
    ])->assertUnauthorized();
});

// ─── EAV validation ───────────────────────────────────────────────────────────

it('attribute not belonging to animal type is rejected with 422', function () {
    ['user' => $user, 'pet' => $pet] = makeDogWithDefinition('text');

    $wrongDefinition = AttributeDefinition::create([
        'code' => 'water_type',
        'label' => 'Type d\'eau',
        'value_type' => 'text',
        'has_predefined_options' => false,
        'is_required' => false,
    ]);

    $this->withHeaders(asUser($user))
        ->putJson("/api/pets/{$pet->id}/attributes", [
            'attributes' => [['attribute_definition_id' => $wrongDefinition->id, 'value_text' => 'freshwater']],
        ])
        ->assertUnprocessable();
});

it('providing more than one value field is rejected with 422', function () {
    ['user' => $user, 'pet' => $pet, 'definition' => $definition] = makeDogWithDefinition('text');

    $this->withHeaders(asUser($user))
        ->putJson("/api/pets/{$pet->id}/attributes", [
            'attributes' => [[
                'attribute_definition_id' => $definition->id,
                'value_text' => 'foo',
                'value_integer' => 42,
            ]],
        ])
        ->assertUnprocessable();
});

it('providing no value field is rejected with 422', function () {
    ['user' => $user, 'pet' => $pet, 'definition' => $definition] = makeDogWithDefinition('text');

    $this->withHeaders(asUser($user))
        ->putJson("/api/pets/{$pet->id}/attributes", [
            'attributes' => [['attribute_definition_id' => $definition->id]],
        ])
        ->assertUnprocessable();
});

it('attribute_option_id not belonging to its definition is rejected with 422', function () {
    ['user' => $user, 'pet' => $pet, 'definition' => $definition] = makeDogWithDefinition('text', true);

    $otherDefinition = AttributeDefinition::create([
        'code' => 'other_attr',
        'label' => 'Other',
        'value_type' => 'text',
        'has_predefined_options' => true,
        'is_required' => false,
    ]);
    $wrongOption = AttributeOption::create([
        'attribute_definition_id' => $otherDefinition->id,
        'value' => 'wrong',
        'label' => 'Wrong option',
        'sort_order' => 0,
    ]);

    $this->withHeaders(asUser($user))
        ->putJson("/api/pets/{$pet->id}/attributes", [
            'attributes' => [[
                'attribute_definition_id' => $definition->id,
                'attribute_option_id' => $wrongOption->id,
                'value_text' => 'some value',
            ]],
        ])
        ->assertUnprocessable();
});

// ─── value_* column routing ───────────────────────────────────────────────────

it('integer value_type writes to value_integer column', function () {
    ['user' => $user, 'pet' => $pet, 'definition' => $definition] = makeDogWithDefinition('integer');

    $this->withHeaders(asUser($user))
        ->putJson("/api/pets/{$pet->id}/attributes", [
            'attributes' => [['attribute_definition_id' => $definition->id, 'value_integer' => 42]],
        ])
        ->assertOk();

    $attr = PetAttribute::where('pet_id', $pet->id)->first();
    expect($attr->value_integer)->toBe(42)
        ->and($attr->value_text)->toBeNull()
        ->and($attr->value_decimal)->toBeNull()
        ->and($attr->value_boolean)->toBeNull()
        ->and($attr->value_date)->toBeNull();
});

it('boolean value_type writes to value_boolean column', function () {
    ['user' => $user, 'pet' => $pet, 'definition' => $definition] = makeDogWithDefinition('boolean');

    $this->withHeaders(asUser($user))
        ->putJson("/api/pets/{$pet->id}/attributes", [
            'attributes' => [['attribute_definition_id' => $definition->id, 'value_boolean' => true]],
        ])
        ->assertOk();

    $attr = PetAttribute::where('pet_id', $pet->id)->first();
    expect($attr->value_boolean)->toBeTrue()
        ->and($attr->value_text)->toBeNull()
        ->and($attr->value_integer)->toBeNull();
});

it('decimal value_type writes to value_decimal column', function () {
    ['user' => $user, 'pet' => $pet, 'definition' => $definition] = makeDogWithDefinition('decimal');

    $this->withHeaders(asUser($user))
        ->putJson("/api/pets/{$pet->id}/attributes", [
            'attributes' => [['attribute_definition_id' => $definition->id, 'value_decimal' => 3.14]],
        ])
        ->assertOk();

    $attr = PetAttribute::where('pet_id', $pet->id)->first();
    expect((float) $attr->value_decimal)->toBe(3.14)
        ->and($attr->value_text)->toBeNull()
        ->and($attr->value_integer)->toBeNull();
});

it('value field not matching definition type is rejected with 422', function () {
    ['user' => $user, 'pet' => $pet, 'definition' => $definition] = makeDogWithDefinition('integer');

    $this->withHeaders(asUser($user))
        ->putJson("/api/pets/{$pet->id}/attributes", [
            'attributes' => [['attribute_definition_id' => $definition->id, 'value_text' => 'wrong type']],
        ])
        ->assertUnprocessable();
});

it('date value_type writes to value_date column', function () {
    ['user' => $user, 'pet' => $pet, 'definition' => $definition] = makeDogWithDefinition('date');

    $this->withHeaders(asUser($user))
        ->putJson("/api/pets/{$pet->id}/attributes", [
            'attributes' => [['attribute_definition_id' => $definition->id, 'value_date' => '2024-01-15']],
        ])
        ->assertOk();

    $attr = PetAttribute::where('pet_id', $pet->id)->first();
    expect($attr->value_date->toDateString())->toBe('2024-01-15')
        ->and($attr->value_text)->toBeNull()
        ->and($attr->value_integer)->toBeNull();
});
