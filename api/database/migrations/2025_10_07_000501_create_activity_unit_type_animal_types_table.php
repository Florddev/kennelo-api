<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Espèces acceptées par une unité de place.
        Schema::create('activity_unit_type_animal_types', function (Blueprint $table): void {
            $table->foreignUuid('activity_unit_type_id')->constrained('activity_unit_types')->cascadeOnDelete();
            $table->foreignUuid('animal_type_id')->constrained('animal_types')->cascadeOnDelete();

            $table->primary(['activity_unit_type_id', 'animal_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_unit_type_animal_types');
    }
};
