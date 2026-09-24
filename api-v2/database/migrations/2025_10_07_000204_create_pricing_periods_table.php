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
        Schema::create('pricing_periods', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name', 100);
            // Sans dates : période de base (toute l'année).
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            // Se répète chaque année ; peut alors enjamber le 31 décembre.
            $table->boolean('is_recurring')->default(false);
            // Force un choix en cas de chevauchement ; sinon la période la plus courte l'emporte.
            $table->smallInteger('priority')->nullable();
            $table->string('color', 7)->nullable();
            $table->timestamps();

            // Cible des clés étrangères composites ; sert aussi d'index sur organization_id.
            $table->unique(['organization_id', 'id']);
        });

        // Une seule période de base par entreprise.
        DB::statement('CREATE UNIQUE INDEX pricing_periods_base_unique ON pricing_periods (organization_id) WHERE start_date IS NULL');

        // SQLite ne permet pas d'ajouter une contrainte CHECK après coup.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE pricing_periods ADD CONSTRAINT pricing_periods_dates_pair_check CHECK ((start_date IS NULL) = (end_date IS NULL))');
            DB::statement('ALTER TABLE pricing_periods ADD CONSTRAINT pricing_periods_dates_order_check CHECK (is_recurring OR start_date IS NULL OR end_date >= start_date)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_periods');
    }
};
