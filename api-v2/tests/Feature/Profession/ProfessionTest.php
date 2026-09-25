<?php

declare(strict_types=1);

use App\Enums\DocumentTypeEnum;
use App\Models\Activity;
use App\Models\AnimalType;
use App\Models\Profession;
use App\Models\ProfessionCategory;
use App\Models\User;

describe('public catalog', function () {
    it('lists the open professions grouped by category, without authentication', function () {
        $dog = AnimalType::factory()->dog()->create();
        $care = ProfessionCategory::factory()->create(['code' => 'care', 'sort_order' => 2]);
        $accommodation = ProfessionCategory::factory()->create(['code' => 'accommodation', 'sort_order' => 1]);
        Profession::factory()->for($care, 'category')->forSpecies($dog)->create(['code' => 'grooming']);
        Profession::factory()->for($accommodation, 'category')->stay()->create(['code' => 'boarding']);
        Profession::factory()->for($accommodation, 'category')->create(['code' => 'closed', 'is_active' => false]);
        ProfessionCategory::factory()->create(['code' => 'empty']);

        $this->getJson('/api/professions')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.code', 'accommodation')
            ->assertJsonPath('data.0.professions.*.code', ['boarding'])
            ->assertJsonPath('data.0.professions.0.booking_mode', 'stay')
            ->assertJsonPath('data.1.professions.0.locations', ['at_pro', 'at_client'])
            ->assertJsonPath('data.1.professions.0.animal_types.0.code', 'dog');
    });
});

describe('back-office', function () {
    it('creates a profession with its species and required documents', function () {
        $dog = AnimalType::factory()->dog()->create();
        $category = ProfessionCategory::factory()->create();

        $this->withHeaders(asUser(adminUser()))
            ->postJson('/api/admin/professions', [
                'profession_category_id' => $category->id,
                'code' => 'dog_training',
                'name' => ['en' => 'Dog training', 'fr' => 'Éducation canine'],
                'booking_mode' => 'appointment',
                'billing_unit' => 'slot',
                'locations' => ['at_pro', 'remote'],
                'pricing_dimensions' => ['size'],
                'animal_type_ids' => [$dog->id],
                'documents' => [['type' => 'rc_pro_insurance', 'validity_months' => 12]],
            ])
            ->assertCreated()
            ->assertJsonPath('data.locations', ['at_pro', 'remote'])
            ->assertJsonPath('data.document_requirements.0', ['type' => 'rc_pro_insurance', 'is_required' => true, 'validity_months' => 12]);

        $profession = Profession::query()->where('code', 'dog_training')->sole();

        expect($profession->allows_at_client)->toBeFalse()
            ->and($profession->getTranslation('name', 'fr'))->toBe('Éducation canine')
            ->and($profession->animalTypes()->pluck('animal_types.id')->all())->toBe([$dog->id]);
    });

    it('refuses a billing unit that does not match the booking mode', function () {
        $this->withHeaders(asUser(adminUser()))
            ->postJson('/api/admin/professions', [
                'profession_category_id' => ProfessionCategory::factory()->create()->id,
                'code' => 'grooming',
                'name' => ['en' => 'Grooming'],
                'booking_mode' => 'appointment',
                'billing_unit' => 'night',
                'locations' => ['at_pro'],
                'animal_type_ids' => [AnimalType::factory()->create()->id],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['billing_unit']);
    });

    it('requires the name in the fallback language', function () {
        $this->withHeaders(asUser(adminUser()))
            ->postJson('/api/admin/profession-categories', ['code' => 'care', 'name' => ['fr' => 'Soin']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name.en']);
    });

    it('replaces the required documents of a profession', function () {
        $profession = Profession::factory()->requiring(DocumentTypeEnum::RC_PRO_INSURANCE, 12)->create();

        $this->withHeaders(asUser(adminUser()))
            ->patchJson("/api/admin/professions/{$profession->id}", [
                'documents' => [['type' => 'acaced', 'is_required' => false]],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data.document_requirements')
            ->assertJsonPath('data.document_requirements.0.type', 'acaced');
    });

    it('keeps the booking mode and the places its activities use', function (array $payload, string $field) {
        $profession = Profession::factory()->create();
        Activity::factory()->for($profession)->atClient(20)->create();

        $this->withHeaders(asUser(adminUser()))
            ->patchJson("/api/admin/professions/{$profession->id}", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field]);
    })->with([
        'booking mode' => [['booking_mode' => 'stay', 'billing_unit' => 'night'], 'booking_mode'],
        'place' => [['locations' => ['at_pro']], 'locations'],
    ]);

    it('keeps the species its activities accept', function () {
        $dog = AnimalType::factory()->dog()->create();
        $cat = AnimalType::factory()->cat()->create();
        $profession = Profession::factory()->forSpecies($dog, $cat)->create();
        Activity::factory()->for($profession)->forSpecies($dog)->create();

        $this->withHeaders(asUser(adminUser()))
            ->patchJson("/api/admin/professions/{$profession->id}", ['animal_type_ids' => [$cat->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['animal_type_ids']);

        $this->withHeaders(asUser(adminUser()))
            ->patchJson("/api/admin/professions/{$profession->id}", ['animal_type_ids' => [$dog->id]])
            ->assertOk();
    });

    it('refuses to delete a category that still has professions', function () {
        $category = ProfessionCategory::factory()->create();
        Profession::factory()->for($category, 'category')->create();

        $this->withHeaders(asUser(adminUser()))
            ->deleteJson("/api/admin/profession-categories/{$category->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category']);
    });

    it('deletes an empty category', function () {
        $category = ProfessionCategory::factory()->create();

        $this->withHeaders(asUser(adminUser()))
            ->deleteJson("/api/admin/profession-categories/{$category->id}")
            ->assertNoContent();

        expect(ProfessionCategory::query()->find($category->id))->toBeNull();
    });

    it('is reserved to admins', function () {
        $this->withHeaders(asUser(User::factory()->create()))
            ->getJson('/api/admin/professions')
            ->assertForbidden();
    });
});
