<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_breeds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('animal_type_id')->constrained()->onDelete('cascade');
            $table->string('breed', 100);
            $table->json('label');
            // Valeurs par défaut utilisées par la grille de prix quand la fiche de l'animal ne les précise pas.
            $table->string('default_size_class', 10)->nullable()->comment('small | medium | large | giant');
            $table->string('default_coat_type', 10)->nullable()->comment('short | medium | long | curly | wire');
            $table->timestamps();

            $table->unique(['animal_type_id', 'breed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_breeds');
    }
};
