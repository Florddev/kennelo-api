<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->constrained();
            $table->foreignUuid('reviewer_id')->constrained('users');
            $table->string('reviewer_type', 20);
            $table->decimal('overall_rating', 2, 1);
            $table->text('comment')->nullable();
            $table->text('private_feedback')->nullable();
            $table->boolean('would_recommend')->default(true);
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'reviewer_type']);
            $table->index('is_published', 'reviews_is_published_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
