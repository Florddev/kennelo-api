<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained();
            $table->foreignUuid('activity_id')->constrained('activities');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->decimal('total_price', 10, 2);
            $table->string('status')->default('pending')->comment('App\Enums\BookingStatus');
            $table->text('special_requests')->nullable();
            $table->timestamps();
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->decimal('activity_amount', 10, 2)->default(0);
            $table->string('stripe_payment_intent_id', 50)->nullable()->unique();
            $table->string('payment_status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->string('stripe_charge_id', 255)->nullable();
            $table->string('stripe_transfer_group', 255)->nullable();
            $table->string('stripe_transfer_id', 255)->nullable();
            $table->string('stripe_refund_id', 255)->nullable();
            $table->decimal('refunded_amount', 10, 2)->nullable();
            $table->decimal('service_fee', 10, 2)->default(0);
            $table->timestamp('reminded_at')->nullable();

            $table->index(['activity_id', 'check_in_date', 'check_out_date'], 'bookings_availability_index');
            $table->index('status', 'bookings_status_index');
            $table->index('payment_status', 'bookings_payment_status_index');
            $table->index('stripe_charge_id', 'bookings_stripe_charge_id_index');
            $table->index('stripe_transfer_group', 'bookings_stripe_transfer_group_index');
            $table->index(['user_id', 'created_at'], 'bookings_user_created_index');
            $table->index(['status', 'created_at'], 'bookings_status_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
