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
        // Un ou plusieurs paiements par réservation : l'initial, puis les compléments ajoutés sur place.
        Schema::create('booking_payments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->constrained('bookings')->restrictOnDelete();
            $table->string('kind', 20)->default('initial')->comment('initial | supplement');
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('EUR');
            $table->string('status', 20)->default('pending');
            $table->string('stripe_payment_intent_id', 50)->unique();
            $table->string('stripe_charge_id', 50)->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('booking_id', 'booking_payments_booking_id_index');
        });

        // SQLite ne permet pas d'ajouter une contrainte CHECK après coup.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE booking_payments ADD CONSTRAINT booking_payments_amount_check CHECK (amount > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_payments');
    }
};
