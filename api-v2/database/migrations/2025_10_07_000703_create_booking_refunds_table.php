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
        // La part des frais Kennelo est séparée pour émettre le bon avoir chez chaque émetteur.
        Schema::create('booking_refunds', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_payment_id')->constrained('booking_payments')->restrictOnDelete();
            $table->decimal('amount', 10, 2)->comment('Total remboursé');
            $table->decimal('service_fee_amount', 10, 2)->default(0)->comment('Part des frais Kennelo');
            $table->string('reason', 30)->comment('client_cancellation | pro_cancellation | adjustment');
            $table->string('stripe_refund_id', 50)->nullable()->unique();
            $table->timestamp('refunded_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('booking_payment_id', 'booking_refunds_booking_payment_id_index');
        });

        // SQLite ne permet pas d'ajouter une contrainte CHECK après coup.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE booking_refunds ADD CONSTRAINT booking_refunds_amounts_check CHECK (amount > 0 AND service_fee_amount >= 0 AND service_fee_amount <= amount)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_refunds');
    }
};
