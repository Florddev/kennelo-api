<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->constrained();
            // Évite la jointure par bookings pour afficher les avis d'une activité.
            $table->foreignUuid('activity_id')->constrained('activities')->restrictOnDelete();
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
            $table->index(['activity_id', 'is_published', 'published_at'], 'reviews_activity_published_index');
            $table->index('reviewer_id', 'reviews_reviewer_id_index');
        });

        // SQLite ne permet pas d'ajouter une contrainte CHECK après coup.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE reviews ADD CONSTRAINT reviews_overall_rating_check CHECK (overall_rating BETWEEN 1 AND 5)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
