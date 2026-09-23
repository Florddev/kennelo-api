<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profession_document_requirements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('profession_id')->constrained('professions')->cascadeOnDelete();
            $table->string('document_type', 50);
            $table->boolean('is_required')->default(true);
            $table->unsignedSmallInteger('validity_months')->nullable()->comment('NULL = sans expiration');
            $table->timestamps();

            $table->unique(['profession_id', 'document_type'], 'profession_document_requirements_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profession_document_requirements');
    }
};
