<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_payouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->unique()->constrained('bookings')->restrictOnDelete();
            $table->string('stripe_transfer_id', 50)->unique();
            $table->string('activity_stripe_account_id', 50)->index();
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('EUR');
            $table->enum('status', ['pending', 'in_transit', 'paid', 'failed', 'canceled'])->index();
            $table->timestamp('transferred_at')->nullable();
            $table->timestamp('estimated_arrival')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_payouts');
    }
};
