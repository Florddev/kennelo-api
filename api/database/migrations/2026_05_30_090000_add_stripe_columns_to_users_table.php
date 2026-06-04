<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('stripe_account_id', 255)->nullable()->unique()->index();
            $table->boolean('stripe_charges_enabled')->default(false);
            $table->boolean('stripe_payouts_enabled')->default(false);
            $table->boolean('stripe_onboarding_completed')->default(false);
            $table->string('stripe_customer_id', 255)->nullable()->unique()->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'stripe_account_id',
                'stripe_charges_enabled',
                'stripe_payouts_enabled',
                'stripe_onboarding_completed',
                'stripe_customer_id',
            ]);
        });
    }
};
