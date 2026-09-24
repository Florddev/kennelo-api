<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Relie l'animal à la réservation et à l'unité qu'il occupe. Les prix sont dans booking_units et booking_items.
        Schema::create('booking_pets', function (Blueprint $table) {
            $table->foreignUuid('booking_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('pet_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('booking_unit_id')->nullable()->constrained('booking_units')->nullOnDelete();
            $table->timestamps();

            $table->primary(['booking_id', 'pet_id']);
            $table->index('pet_id', 'booking_pets_pet_id_index');
            $table->index('booking_unit_id', 'booking_pets_booking_unit_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_pets');
    }
};
