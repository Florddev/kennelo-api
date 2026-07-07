<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('attribute_definition_id')->constrained()->onDelete('cascade');
            $table->string('value', 100);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->json('label')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_options');
    }
};
