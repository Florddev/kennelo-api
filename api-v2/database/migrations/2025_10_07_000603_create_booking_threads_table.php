<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_threads', function (Blueprint $table) {
            $table->foreignUuid('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('booking_id')->primary()->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index('conversation_id', 'booking_threads_conversation_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_threads');
    }
};
