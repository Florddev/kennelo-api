<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_criteria_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('review_id')->constrained()->onDelete('cascade');
            $table->string('criteria_code', 50);
            $table->decimal('score', 2, 1);
            $table->timestamp('created_at')->nullable();

            $table->unique(['review_id', 'criteria_code']);
            $table->foreign('criteria_code')->references('code')->on('review_criteria_definitions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_criteria_scores');
    }
};
