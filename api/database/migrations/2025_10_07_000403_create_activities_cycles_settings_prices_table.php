<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
    }

    public function down(): void
    {
        Schema::dropIfExists('activities_cycles_settings_prices');
    }
};
