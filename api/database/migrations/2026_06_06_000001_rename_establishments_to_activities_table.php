<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('establishments', 'activities');

        Schema::table('activities', function (Blueprint $table): void {
            $table->renameIndex('establishments_siret_unique', 'activities_siret_unique');
            $table->renameIndex('establishments_stripe_account_id_unique', 'activities_stripe_account_id_unique');
            $table->renameIndex('establishments_is_active_index', 'activities_is_active_index');
            $table->renameIndex('establishments_siret_index', 'activities_siret_index');
            $table->renameIndex('establishments_created_at_index', 'activities_created_at_index');
            $table->renameIndex('establishments_stripe_ready_index', 'activities_stripe_ready_index');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table): void {
            $table->renameIndex('activities_siret_unique', 'establishments_siret_unique');
            $table->renameIndex('activities_stripe_account_id_unique', 'establishments_stripe_account_id_unique');
            $table->renameIndex('activities_is_active_index', 'establishments_is_active_index');
            $table->renameIndex('activities_siret_index', 'establishments_siret_index');
            $table->renameIndex('activities_created_at_index', 'establishments_created_at_index');
            $table->renameIndex('activities_stripe_ready_index', 'establishments_stripe_ready_index');
        });

        Schema::rename('activities', 'establishments');
    }
};
