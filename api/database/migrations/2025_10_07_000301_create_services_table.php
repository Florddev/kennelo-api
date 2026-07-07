<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained('activities')->onDelete('cascade');
            $table->foreignUuid('animal_type_id')->constrained('animal_types')->onDelete('cascade');
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('is_included')->default(true);
            $table->decimal('price', 8, 2)->nullable();
            $table->timestamps();

            $table->index('activity_id', 'services_activity_id_index');
            $table->index('animal_type_id', 'services_animal_type_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
