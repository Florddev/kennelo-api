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
        Schema::create('professions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('profession_category_id')->constrained('profession_categories')->restrictOnDelete();
            $table->string('code', 50)->unique();
            $table->json('name');
            $table->json('description')->nullable();
            // Chaînes libres (valeurs définies dans le code) : ajouter un mode ou une unité ne demande pas de migration.
            $table->string('booking_mode', 20)->comment('stay | appointment');
            $table->string('billing_unit', 20)->comment('night | day | slot');
            $table->boolean('allows_at_pro')->default(true);
            $table->boolean('allows_at_client')->default(false);
            $table->boolean('allows_remote')->default(false);
            $table->json('pricing_dimensions')->nullable()->comment('Critères de prix, ex. ["size", "coat"]');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('profession_category_id', 'professions_profession_category_id_index');
        });

        // SQLite ne permet pas d'ajouter une contrainte CHECK après coup.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE professions ADD CONSTRAINT professions_location_check CHECK (allows_at_pro OR allows_at_client OR allows_remote)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('professions');
    }
};
