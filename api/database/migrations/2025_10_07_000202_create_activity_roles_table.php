<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_roles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained('activities')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();

            $table->unique(['activity_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_roles');
    }
};
