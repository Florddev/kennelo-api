<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_pets', function (Blueprint $table) {
            $table->foreignUuid('booking_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('pet_id')->constrained()->restrictOnDelete();
            $table->decimal('price_per_night', 8, 2);
            $table->integer('number_of_nights');
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();

            $table->primary(['booking_id', 'pet_id']);

            $table->index('pet_id', 'booking_pets_pet_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_pets');
    }
};
