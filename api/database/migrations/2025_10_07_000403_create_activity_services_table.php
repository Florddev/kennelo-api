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
        // Où une prestation du catalogue est vendue, et à quelles conditions.
        Schema::create('activity_services', function (Blueprint $table): void {
            $table->uuid('organization_id');
            $table->uuid('activity_id');
            $table->uuid('service_id');
            $table->string('offered_as', 20)->default('standalone')->comment('standalone | stay_option | both');
            // -20 = remise de 20 %.
            $table->decimal('adjustment_percent', 5, 2)->default(0);
            // Incluse dans le prix du séjour.
            $table->boolean('is_included')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->primary(['activity_id', 'service_id']);

            // Clés composites : l'activité et la prestation appartiennent forcément à la même entreprise.
            $table->foreign(['organization_id', 'activity_id'], 'activity_services_activity_foreign')
                ->references(['organization_id', 'id'])
                ->on('activities')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'service_id'], 'activity_services_service_foreign')
                ->references(['organization_id', 'id'])
                ->on('services')
                ->cascadeOnDelete();

            $table->index('service_id', 'activity_services_service_id_index');
        });

        // SQLite ne permet pas d'ajouter une contrainte CHECK après coup.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE activity_services ADD CONSTRAINT activity_services_adjustment_check CHECK (adjustment_percent BETWEEN -100 AND 100)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_services');
    }
};
