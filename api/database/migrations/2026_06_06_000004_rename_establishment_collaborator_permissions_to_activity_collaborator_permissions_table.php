<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('establishment_collaborator_permissions', 'activity_collaborator_permissions');

        Schema::table('activity_collaborator_permissions', function (Blueprint $table): void {
            $table->dropUnique('establishment_collaborator_permissions_establishment_id_user_id_permission_unique');
        });

        Schema::table('activity_collaborator_permissions', function (Blueprint $table): void {
            $table->renameColumn('establishment_id', 'activity_id');
        });

        Schema::table('activity_collaborator_permissions', function (Blueprint $table): void {
            $table->unique(['activity_id', 'user_id', 'permission'], 'activity_collaborator_permissions_activity_id_user_id_permission_unique');
        });
    }

    public function down(): void
    {
        Schema::table('activity_collaborator_permissions', function (Blueprint $table): void {
            $table->dropUnique('activity_collaborator_permissions_activity_id_user_id_permission_unique');
        });

        Schema::table('activity_collaborator_permissions', function (Blueprint $table): void {
            $table->renameColumn('activity_id', 'establishment_id');
        });

        Schema::table('activity_collaborator_permissions', function (Blueprint $table): void {
            $table->unique(['establishment_id', 'user_id', 'permission'], 'establishment_collaborator_permissions_establishment_id_user_id_permission_unique');
        });

        Schema::rename('activity_collaborator_permissions', 'establishment_collaborator_permissions');
    }
};
