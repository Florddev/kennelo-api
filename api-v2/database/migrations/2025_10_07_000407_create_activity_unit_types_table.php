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
        // Unités de place d'un séjour : Box, Paddock, Chambre Premium, ou simplement « Place chien ».
        Schema::create('activity_unit_types', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('quantity')->comment("Nombre d'unités");
            // 2 chats d'un même foyer peuvent partager une chambre.
            $table->unsignedSmallInteger('max_animals_per_unit')->default(1);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('activity_id', 'activity_unit_types_activity_id_index');
        });

        // SQLite ne permet pas d'ajouter une contrainte CHECK après coup.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE activity_unit_types ADD CONSTRAINT activity_unit_types_quantity_check CHECK (quantity > 0 AND max_animals_per_unit >= 1)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_unit_types');
    }
};
