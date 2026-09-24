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
        // Prix par unité de place, éventuellement différent selon le jour de la semaine.
        Schema::create('activity_period_prices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_period_setting_id')->constrained('activity_period_settings')->cascadeOnDelete();
            $table->foreignUuid('activity_unit_type_id')->constrained('activity_unit_types')->cascadeOnDelete();
            // NULL = tous les jours.
            $table->unsignedTinyInteger('weekday')->nullable()->comment('App\Enums\WeekDayEnum');
            $table->decimal('price', 10, 2)->comment('TTC, par unité et par nuit ou jour');
            $table->decimal('extra_animal_price', 10, 2)->nullable()->comment("Par animal supplémentaire dans l'unité");
            $table->timestamps();

            $table->index('activity_unit_type_id', 'activity_period_prices_activity_unit_type_id_index');
        });

        // Deux NULL (« tous les jours ») sont considérés égaux sous PostgreSQL ; SQLite garde un unique classique.
        $nullsNotDistinct = DB::getDriverName() === 'pgsql' ? ' NULLS NOT DISTINCT' : '';
        DB::statement("CREATE UNIQUE INDEX activity_period_prices_unique ON activity_period_prices (activity_period_setting_id, activity_unit_type_id, weekday){$nullsNotDistinct}");

        // SQLite ne permet pas d'ajouter une contrainte CHECK après coup.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE activity_period_prices ADD CONSTRAINT activity_period_prices_amounts_check CHECK (price >= 0 AND (extra_animal_price IS NULL OR extra_animal_price >= 0))');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_period_prices');
    }
};
