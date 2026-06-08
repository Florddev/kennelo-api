<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities_cycles_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_cycle_id')->constrained('activities_cycles')->onDelete('cascade');
            $table->foreignUuid('animal_type_id')->constrained();
            $table->integer('max_capacity');
            $table->decimal('price', 10, 2);
            $table->unsignedTinyInteger('sum_weekdays')->default(127);
            $table->timestamps();

            $table->index('activity_cycle_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities_cycles_settings');
    }
};
