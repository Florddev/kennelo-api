<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospects', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('department', 3)->nullable();
            $table->string('region', 100)->nullable();
            $table->string('country', 2)->default('FR');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->decimal('google_rating', 2, 1)->nullable();
            $table->unsignedInteger('google_reviews_count')->nullable();
            $table->string('google_place_id')->nullable()->unique();
            $table->string('category')->nullable();
            $table->json('animal_types')->nullable();
            $table->json('services')->nullable();
            $table->string('siret', 14)->nullable();
            $table->string('siren', 9)->nullable();
            $table->string('ape_code', 6)->nullable();
            $table->string('status')->default('non_contacte');
            $table->string('source')->default('apify');
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('kennelo_activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'department']);
            $table->index('assigned_to');
            $table->index('kennelo_activity_id');
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospects');
    }
};
