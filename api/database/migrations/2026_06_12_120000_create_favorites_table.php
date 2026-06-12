<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table): void {
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('activity_id')->constrained('activities')->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['user_id', 'activity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
