<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities_cycles_settings', function (Blueprint $table): void {
            $table->dropColumn(['price', 'sum_weekdays']);
        });
    }

    public function down(): void
    {
        Schema::table('activities_cycles_settings', function (Blueprint $table): void {
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedTinyInteger('sum_weekdays')->default(127);
        });
    }
};
