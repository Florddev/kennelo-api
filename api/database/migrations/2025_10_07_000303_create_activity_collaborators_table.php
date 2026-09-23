<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_collaborators', function (Blueprint $table) {
            $table->foreignUuid('activity_id')->constrained('activities')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->string('status')->default('pending');
            $table->uuid('role_id')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('responded_at')->nullable();

            $table->foreign('role_id')->references('id')->on('activity_roles')->nullOnDelete();

            $table->primary(['activity_id', 'user_id']);

            $table->index(['user_id', 'status'], 'activity_collaborators_user_status_index');
            $table->index('role_id', 'activity_collaborators_role_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_collaborators');
    }
};
