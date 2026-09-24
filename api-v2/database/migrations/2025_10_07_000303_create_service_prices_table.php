<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Grille de prix et de durée selon l'animal. La ligne la plus précise l'emporte :
        // race, puis taille et poil, puis taille, puis espèce.
        Schema::create('service_prices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignUuid('animal_type_id')->constrained('animal_types')->restrictOnDelete();
            $table->string('size_class', 10)->nullable();
            $table->string('coat_type', 10)->nullable();
            $table->foreignUuid('animal_breed_id')->nullable()->constrained('animal_breeds')->restrictOnDelete();
            $table->decimal('price', 10, 2)->comment('TTC');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->timestamps();
        });

        // Deux NULL sont considérés égaux sous PostgreSQL ; SQLite garde un unique classique.
        $nullsNotDistinct = DB::getDriverName() === 'pgsql' ? ' NULLS NOT DISTINCT' : '';
        DB::statement("CREATE UNIQUE INDEX service_prices_unique ON service_prices (service_id, animal_type_id, size_class, coat_type, animal_breed_id){$nullsNotDistinct}");

        // SQLite ne permet pas d'ajouter une contrainte CHECK après coup.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE service_prices ADD CONSTRAINT service_prices_values_check CHECK (price >= 0 AND (duration_minutes IS NULL OR duration_minutes > 0))');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_prices');
    }
};
