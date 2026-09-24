<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities_availabilities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained('activities')->onDelete('cascade');
            $table->date('date');
            $table->string('status', 10)->default('open');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['activity_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities_availabilities');
    }
};
