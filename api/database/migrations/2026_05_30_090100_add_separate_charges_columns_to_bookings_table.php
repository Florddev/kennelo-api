<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'stripe_charge_id')) {
                $table->string('stripe_charge_id', 255)->nullable()->index();
            }
            if (! Schema::hasColumn('bookings', 'stripe_transfer_group')) {
                $table->string('stripe_transfer_group', 255)->nullable()->index();
            }
            if (! Schema::hasColumn('bookings', 'stripe_transfer_id')) {
                $table->string('stripe_transfer_id', 255)->nullable();
            }
            if (! Schema::hasColumn('bookings', 'stripe_refund_id')) {
                $table->string('stripe_refund_id', 255)->nullable();
            }
            if (! Schema::hasColumn('bookings', 'refunded_amount')) {
                $table->decimal('refunded_amount', 10, 2)->nullable();
            }
            if (! Schema::hasColumn('bookings', 'refunded_at')) {
                $table->timestamp('refunded_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'refunded_at',
                'refunded_amount',
                'stripe_refund_id',
                'stripe_transfer_id',
                'stripe_transfer_group',
                'stripe_charge_id',
            ]);
        });
    }
};
