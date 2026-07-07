<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('booking_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignUuid('sender_id')->nullable()->constrained('users');
            $table->string('sender_type', 20);
            $table->string('message_type')->default('text');
            $table->text('content')->nullable();
            $table->timestamps();

            $table->index('sender_type', 'messages_sender_type_index');
            $table->index('message_type', 'messages_message_type_index');
            $table->index(['conversation_id', 'created_at'], 'messages_conversation_created_index');
            $table->index('booking_id', 'messages_booking_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
