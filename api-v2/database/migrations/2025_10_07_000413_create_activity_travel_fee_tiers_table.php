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
        Schema::create('activity_travel_fee_tiers', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->unsignedSmallInteger('up_to_km');
            $table->decimal('fee', 10, 2);
            $table->timestamps();

            $table->unique(['activity_id', 'up_to_km'], 'activity_travel_fee_tiers_activity_distance_unique');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE activity_travel_fee_tiers ADD CONSTRAINT activity_travel_fee_tiers_values_check CHECK (up_to_km > 0 AND fee >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_travel_fee_tiers');
    }
};
