<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Unités de place réservées pour un séjour.
        Schema::create('booking_units', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignUuid('activity_unit_type_id')->constrained('activity_unit_types')->restrictOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            // Nuits ou jours selon le métier.
            $table->unsignedSmallInteger('nights');
            $table->decimal('subtotal', 10, 2);
            // Détail par nuit (période, jour, prix) ; lu en bloc, jamais filtré.
            $table->json('price_breakdown');
            $table->timestamps();

            $table->index('booking_id', 'booking_units_booking_id_index');
            // Calcul de l'occupation d'une unité.
            $table->index('activity_unit_type_id', 'booking_units_activity_unit_type_id_index');
        });

        // SQLite ne permet pas d'ajouter une contrainte CHECK après coup.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE booking_units ADD CONSTRAINT booking_units_values_check CHECK (quantity > 0 AND nights > 0 AND subtotal >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_units');
    }
};
