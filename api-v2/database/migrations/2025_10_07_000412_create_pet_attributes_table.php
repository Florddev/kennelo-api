<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pet_attributes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pet_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('attribute_definition_id')->constrained();
            $table->foreignUuid('attribute_option_id')->nullable()->constrained();
            $table->text('value_text')->nullable();
            $table->integer('value_integer')->nullable();
            $table->decimal('value_decimal', 10, 2)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->date('value_date')->nullable();
            $table->timestamps();

            $table->unique(['pet_id', 'attribute_definition_id']);
            $table->index('attribute_definition_id', 'pet_attributes_attribute_definition_id_index');
            $table->index('attribute_option_id', 'pet_attributes_attribute_option_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pet_attributes');
    }
};
