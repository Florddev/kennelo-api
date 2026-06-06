<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            $table->dropUnique('conversations_user_id_establishment_id_unique');
        });

        Schema::table('conversations', function (Blueprint $table): void {
            $table->renameColumn('establishment_id', 'activity_id');
        });

        Schema::table('conversations', function (Blueprint $table): void {
            $table->unique(['user_id', 'activity_id'], 'conversations_user_id_activity_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table): void {
            $table->dropUnique('conversations_user_id_activity_id_unique');
        });

        Schema::table('conversations', function (Blueprint $table): void {
            $table->renameColumn('activity_id', 'establishment_id');
        });

        Schema::table('conversations', function (Blueprint $table): void {
            $table->unique(['user_id', 'establishment_id'], 'conversations_user_id_establishment_id_unique');
        });
    }
};
