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
        Schema::create('booking_disputes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->constrained('bookings')->restrictOnDelete();
            $table->foreignUuid('booking_payment_id')->constrained('booking_payments')->restrictOnDelete();
            $table->string('stripe_dispute_id', 50)->unique();
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('EUR');
            $table->string('reason', 50);
            $table->string('status', 30)->index();
            $table->timestamp('evidence_due_by')->nullable();
            $table->decimal('recovered_amount', 10, 2)->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index('booking_id', 'booking_disputes_booking_id_index');
            $table->index('booking_payment_id', 'booking_disputes_booking_payment_id_index');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE booking_disputes ADD CONSTRAINT booking_disputes_amounts_check CHECK (amount > 0 AND recovered_amount >= 0 AND recovered_amount <= amount)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_disputes');
    }
};
