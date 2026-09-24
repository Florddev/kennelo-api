<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->enum('value_type', ['text', 'integer', 'decimal', 'boolean', 'date']);
            $table->boolean('has_predefined_options')->default(false);
            $table->boolean('is_required')->default(false);
            $table->text('validation_rules')->nullable();
            $table->timestamps();
            $table->string('category', 50)->default('info')->index();
            $table->string('input_type')->nullable();
            $table->string('icon_name')->nullable();
            $table->json('label')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_definitions');
    }
};
