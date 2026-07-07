<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('animal_type_id')->constrained();
            $table->string('name', 255);
            $table->string('breed', 255)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('sex')->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->boolean('is_sterilized')->nullable();
            $table->boolean('has_microchip')->default(false);
            $table->string('microchip_number', 50)->nullable();
            $table->date('adoption_date')->nullable();
            $table->text('about')->nullable();
            $table->text('health_notes')->nullable();
            $table->timestamps();
            $table->uuid('animal_breed_id')->nullable();

            $table->foreign('animal_breed_id')->references('id')->on('animal_breeds')->nullOnDelete();

            $table->index('microchip_number', 'pets_microchip_number_index');
            $table->index('user_id', 'pets_user_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pets');
    }
};
