<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('activity_collaborator_permissions');
    }

    public function down(): void
    {
        Schema::create('activity_collaborator_permissions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained('activities')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('permission', 100);
            $table->timestamps();

            $table->unique(['activity_id', 'user_id', 'permission']);
        });
    }
};
