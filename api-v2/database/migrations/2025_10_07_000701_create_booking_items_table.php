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
        // Une ligne par prestation vendue. Un rendez-vous est une ligne avec un horaire ;
        // une option à placer par le pro est une ligne sans horaire, au statut to_schedule.
        Schema::create('booking_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignUuid('service_id')->constrained('services')->restrictOnDelete();
            $table->foreignUuid('pet_id')->nullable()->constrained('pets')->restrictOnDelete();
            $table->string('status', 20)->comment('to_schedule | scheduled | done | cancelled');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->comment('Figé');
            $table->decimal('subtotal', 10, 2);
            $table->unsignedSmallInteger('duration_minutes')->nullable()->comment('Figé');
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            // Paiement (initial ou complément) qui a couvert la ligne.
            $table->foreignUuid('booking_payment_id')->nullable()->constrained('booking_payments')->restrictOnDelete();
            $table->timestamps();

            $table->index('booking_id', 'booking_items_booking_id_index');
            $table->index('service_id', 'booking_items_service_id_index');
            $table->index('pet_id', 'booking_items_pet_id_index');
        });

        // SQLite ne permet pas d'ajouter une contrainte CHECK après coup.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE booking_items ADD CONSTRAINT booking_items_schedule_check CHECK ((starts_at IS NULL) = (ends_at IS NULL) AND (starts_at IS NULL OR ends_at > starts_at))');
            DB::statement('ALTER TABLE booking_items ADD CONSTRAINT booking_items_amounts_check CHECK (quantity > 0 AND unit_price >= 0 AND subtotal >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
