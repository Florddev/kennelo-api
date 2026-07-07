<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_animal_types', function (Blueprint $table) {
            $table->foreignUuid('attribute_definition_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('animal_type_id')->constrained()->onDelete('cascade');

            $table->primary(['attribute_definition_id', 'animal_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_animal_types');
    }
};
