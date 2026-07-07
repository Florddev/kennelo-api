<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('category', 50);
            $table->timestamps();
            $table->json('name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_types');
    }
};
