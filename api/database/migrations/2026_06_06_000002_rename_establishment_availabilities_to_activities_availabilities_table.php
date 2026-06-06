<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('establishment_availabilities', 'activities_availabilities');

        Schema::table('activities_availabilities', function (Blueprint $table): void {
            $table->dropUnique('establishment_availabilities_establishment_id_date_unique');
            $table->dropIndex('establishment_availabilities_establishment_id_index');
            $table->dropIndex('availabilities_explore_index');
        });

        Schema::table('activities_availabilities', function (Blueprint $table): void {
            $table->renameColumn('establishment_id', 'activity_id');
        });

        Schema::table('activities_availabilities', function (Blueprint $table): void {
            $table->unique(['activity_id', 'date'], 'activities_availabilities_activity_id_date_unique');
            $table->index('activity_id', 'activities_availabilities_activity_id_index');
            $table->index(['activity_id', 'date', 'status'], 'activities_availabilities_explore_index');
        });
    }

    public function down(): void
    {
        Schema::table('activities_availabilities', function (Blueprint $table): void {
            $table->dropUnique('activities_availabilities_activity_id_date_unique');
            $table->dropIndex('activities_availabilities_activity_id_index');
            $table->dropIndex('activities_availabilities_explore_index');
        });

        Schema::table('activities_availabilities', function (Blueprint $table): void {
            $table->renameColumn('activity_id', 'establishment_id');
        });

        Schema::table('activities_availabilities', function (Blueprint $table): void {
            $table->unique(['establishment_id', 'date'], 'establishment_availabilities_establishment_id_date_unique');
            $table->index('establishment_id', 'establishment_availabilities_establishment_id_index');
            $table->index(['establishment_id', 'date', 'status'], 'availabilities_explore_index');
        });

        Schema::rename('activities_availabilities', 'establishment_availabilities');
    }
};
