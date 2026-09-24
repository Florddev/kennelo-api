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
        // Planning récurrent d'une ressource dans une activité.
        Schema::create('resource_schedules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('resource_id');
            $table->uuid('activity_id');
            $table->unsignedTinyInteger('weekday')->comment('App\Enums\WeekDayEnum');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            // Clés composites : la ressource et l'activité appartiennent forcément à la même entreprise.
            $table->foreign(['organization_id', 'resource_id'], 'resource_schedules_resource_foreign')
                ->references(['organization_id', 'id'])
                ->on('resources')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'activity_id'], 'resource_schedules_activity_foreign')
                ->references(['organization_id', 'id'])
                ->on('activities')
                ->cascadeOnDelete();

            $table->index(['resource_id', 'weekday'], 'resource_schedules_resource_weekday_index');
            $table->index('activity_id', 'resource_schedules_activity_id_index');
        });

        // SQLite ne permet pas d'ajouter une contrainte CHECK après coup.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE resource_schedules ADD CONSTRAINT resource_schedules_range_check CHECK (end_time > start_time)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_schedules');
    }
};
