<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animal_breeds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('animal_type_id')->constrained()->onDelete('cascade');
            $table->string('breed', 100);
            $table->json('label');
            $table->timestamps();

            $table->unique(['animal_type_id', 'breed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animal_breeds');
    }
};
