<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Espèces acceptées par l'activité : un sous-ensemble de celles du métier.
        Schema::create('activity_animal_types', function (Blueprint $table): void {
            $table->foreignUuid('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->foreignUuid('animal_type_id')->constrained('animal_types')->cascadeOnDelete();

            $table->primary(['activity_id', 'animal_type_id']);
            $table->index('animal_type_id', 'activity_animal_types_animal_type_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_animal_types');
    }
};
