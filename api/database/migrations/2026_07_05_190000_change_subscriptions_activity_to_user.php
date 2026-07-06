<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropForeign(['activity_id']);
            $table->renameColumn('activity_id', 'user_id');
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->renameColumn('user_id', 'activity_id');
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->foreign('activity_id')->references('id')->on('activities')->cascadeOnDelete();
        });
    }
};
