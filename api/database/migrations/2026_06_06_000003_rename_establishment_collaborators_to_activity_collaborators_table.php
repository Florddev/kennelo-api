<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('establishment_collaborators', 'activity_collaborators');

        Schema::table('activity_collaborators', function (Blueprint $table): void {
            $table->renameColumn('establishment_id', 'activity_id');
        });
    }

    public function down(): void
    {
        Schema::table('activity_collaborators', function (Blueprint $table): void {
            $table->renameColumn('activity_id', 'establishment_id');
        });

        Schema::rename('activity_collaborators', 'establishment_collaborators');
    }
};
