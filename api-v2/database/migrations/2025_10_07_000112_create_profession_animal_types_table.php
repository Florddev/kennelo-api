<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profession_animal_types', function (Blueprint $table): void {
            $table->foreignUuid('profession_id')->constrained('professions')->cascadeOnDelete();
            $table->foreignUuid('animal_type_id')->constrained('animal_types')->cascadeOnDelete();

            $table->primary(['profession_id', 'animal_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profession_animal_types');
    }
};
