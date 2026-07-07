<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_services', function (Blueprint $table) {
            $table->foreignUuid('booking_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('service_id')->constrained();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 8, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();

            $table->primary(['booking_id', 'service_id']);

            $table->index('service_id', 'booking_services_service_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_services');
    }
};
