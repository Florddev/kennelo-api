<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rattache une réservation à la conversation de son client avec l'activité. Le fil est archivé quand la
        // réservation se termine (terminée, annulée, refusée ou expirée) : actif tant que archived_at est NULL.
        Schema::create('booking_threads', function (Blueprint $table) {
            $table->foreignUuid('conversation_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('booking_id')->primary()->constrained()->onDelete('cascade');
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
