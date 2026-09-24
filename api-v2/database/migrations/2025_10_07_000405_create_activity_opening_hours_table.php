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
        // Plusieurs plages par jour possibles (9 h - 12 h, puis 14 h - 18 h).
        Schema::create('activity_opening_hours', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday')->comment('App\Enums\WeekDayEnum');
            $table->time('opens_at');
            $table->time('closes_at');
            $table->timestamps();

            $table->index(['activity_id', 'weekday'], 'activity_opening_hours_activity_weekday_index');
        });

        // SQLite ne permet pas d'ajouter une contrainte CHECK après coup.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE activity_opening_hours ADD CONSTRAINT activity_opening_hours_range_check CHECK (closes_at > opens_at)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_opening_hours');
    }
};
