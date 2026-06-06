<?php

declare(strict_types=1);

use App\Enums\WeekDayEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::transaction(function () use ($now): void {
            DB::table('activities')->select('id')->orderBy('id')->chunkById(500, function ($activities) use ($now): void {
                foreach ($activities as $activity) {
                    $cycleId = (string) Str::uuid();

                    DB::table('activities_cycles')->insert([
                        'id' => $cycleId,
                        'activity_id' => $activity->id,
                        'start_date' => null,
                        'end_date' => null,
                        'priority' => 0,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $settings = DB::table('establishment_capacities')
                        ->where('establishment_id', $activity->id)
                        ->get()
                        ->map(fn ($capacity): array => [
                            'id' => (string) Str::uuid(),
                            'activity_cycle_id' => $cycleId,
                            'animal_type_id' => $capacity->animal_type_id,
                            'max_capacity' => $capacity->max_capacity,
                            'price' => $capacity->price_per_night,
                            'sum_weekdays' => WeekDayEnum::ALL,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])
                        ->all();

                    if ($settings !== []) {
                        DB::table('activities_cycles_settings')->insert($settings);
                    }
                }
            });
        });

        Schema::dropIfExists('establishment_capacities');
    }

    public function down(): void
    {
        Schema::create('establishment_capacities', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('establishment_id')->constrained('activities')->onDelete('cascade');
            $table->foreignUuid('animal_type_id')->constrained();
            $table->integer('max_capacity');
            $table->decimal('price_per_night', 8, 2);
            $table->timestamps();

            $table->unique(['establishment_id', 'animal_type_id']);
        });

        $now = now();

        DB::table('activities_cycles')
            ->where('priority', 0)
            ->where('is_active', true)
            ->whereNull('start_date')
            ->whereNull('end_date')
            ->orderBy('id')
            ->chunkById(500, function ($cycles) use ($now): void {
                foreach ($cycles as $cycle) {
                    $capacities = DB::table('activities_cycles_settings')
                        ->where('activity_cycle_id', $cycle->id)
                        ->where('sum_weekdays', WeekDayEnum::ALL)
                        ->get()
                        ->map(fn ($setting): array => [
                            'id' => (string) Str::uuid(),
                            'establishment_id' => $cycle->activity_id,
                            'animal_type_id' => $setting->animal_type_id,
                            'max_capacity' => $setting->max_capacity,
                            'price_per_night' => $setting->price,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])
                        ->all();

                    if ($capacities !== []) {
                        DB::table('establishment_capacities')->insert($capacities);
                    }
                }
            });
    }
};
