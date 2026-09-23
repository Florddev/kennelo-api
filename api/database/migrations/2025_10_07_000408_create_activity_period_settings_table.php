<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Comment une activité applique une période de l'entreprise.
        Schema::create('activity_period_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('activity_id');
            $table->uuid('pricing_period_id');
            $table->boolean('is_active')->default(true);
            // En nuits ou en jours selon le métier.
            $table->unsignedSmallInteger('min_stay')->nullable();
            // +15 = majoration du tarif de base, utilisée quand aucun prix n'est saisi pour la période.
            $table->decimal('price_modifier_percent', 5, 2)->nullable();
            $table->unsignedTinyInteger('closed_weekdays')->default(0)->comment('Masque de bits des jours fermés');
            $table->timestamps();

            $table->unique(['activity_id', 'pricing_period_id'], 'activity_period_settings_unique');

            // Clés composites : l'activité et la période appartiennent forcément à la même entreprise.
            $table->foreign(['organization_id', 'activity_id'], 'activity_period_settings_activity_foreign')
                ->references(['organization_id', 'id'])
                ->on('activities')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'pricing_period_id'], 'activity_period_settings_period_foreign')
                ->references(['organization_id', 'id'])
                ->on('pricing_periods')
                ->cascadeOnDelete();

            $table->index('pricing_period_id', 'activity_period_settings_pricing_period_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_period_settings');
    }
};
