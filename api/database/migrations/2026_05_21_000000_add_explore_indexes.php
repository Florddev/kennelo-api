<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table): void {
            $table->index(['latitude', 'longitude'], 'addresses_lat_lng_index');
        });

        Schema::table('establishments', function (Blueprint $table): void {
            $table->index('is_active', 'establishments_is_active_index');
            $table->index('siret', 'establishments_siret_index');
            $table->index('created_at', 'establishments_created_at_index');
        });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->index('is_published', 'reviews_is_published_index');
        });

        Schema::table('establishment_availabilities', function (Blueprint $table): void {
            $table->index(['establishment_id', 'date', 'status'], 'availabilities_explore_index');
        });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table): void {
            $table->dropIndex('addresses_lat_lng_index');
        });

        Schema::table('establishments', function (Blueprint $table): void {
            $table->dropIndex('establishments_is_active_index');
            $table->dropIndex('establishments_siret_index');
            $table->dropIndex('establishments_created_at_index');
        });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropIndex('reviews_is_published_index');
        });

        Schema::table('establishment_availabilities', function (Blueprint $table): void {
            $table->dropIndex('availabilities_explore_index');
        });
    }
};
