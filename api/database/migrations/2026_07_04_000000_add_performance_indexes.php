<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pets', function (Blueprint $table): void {
            $table->index('microchip_number', 'pets_microchip_number_index');
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->index(['conversation_id', 'created_at'], 'messages_conversation_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('pets', function (Blueprint $table): void {
            $table->dropIndex('pets_microchip_number_index');
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->dropIndex('messages_conversation_created_index');
        });
    }
};
