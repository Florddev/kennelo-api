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
        Schema::create('activities_cycles_settings_prices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_cycle_setting_id')
                ->constrained('activities_cycles_settings')
                ->onDelete('cascade');
            $table->unsignedTinyInteger('weekday');
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->unique(['activity_cycle_setting_id', 'weekday']);
        });

        $now = now();

        DB::table('activities_cycles_settings')->orderBy('id')->chunkById(500, function ($settings) use ($now): void {
            foreach ($settings as $setting) {
                $rows = [];

                foreach (WeekDayEnum::fromMask((int) $setting->sum_weekdays) as $day) {
                    $rows[] = [
                        'id' => (string) Str::uuid(),
                        'activity_cycle_setting_id' => $setting->id,
                        'weekday' => $day->value,
                        'price' => $setting->price,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('activities_cycles_settings_prices')->insert($rows);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities_cycles_settings_prices');
    }
};
