<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_payouts', function (Blueprint $table): void {
            $table->dropIndex('booking_payouts_establishment_stripe_account_id_index');
        });

        Schema::table('booking_payouts', function (Blueprint $table): void {
            $table->renameColumn('establishment_stripe_account_id', 'activity_stripe_account_id');
        });

        Schema::table('booking_payouts', function (Blueprint $table): void {
            $table->index('activity_stripe_account_id', 'booking_payouts_activity_stripe_account_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('booking_payouts', function (Blueprint $table): void {
            $table->dropIndex('booking_payouts_activity_stripe_account_id_index');
        });

        Schema::table('booking_payouts', function (Blueprint $table): void {
            $table->renameColumn('activity_stripe_account_id', 'establishment_stripe_account_id');
        });

        Schema::table('booking_payouts', function (Blueprint $table): void {
            $table->index('establishment_stripe_account_id', 'booking_payouts_establishment_stripe_account_id_index');
        });
    }
};
