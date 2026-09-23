<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('review_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('reason', ['inappropriate', 'offensive', 'fake', 'spam', 'other']);
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'reviewed', 'rejected', 'removed'])->default('pending')->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['review_id', 'reporter_id'], 'review_reports_review_reporter_unique');
            $table->index('reporter_id', 'review_reports_reporter_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_reports');
    }
};
