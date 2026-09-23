<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('activity_id')->constrained('activities')->onDelete('cascade');
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_id', 'activity_id']);
            $table->index('activity_id', 'conversations_activity_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
