<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table): void {
            $table->decimal('commission_rate', 5, 4)->default('0.0000')->after('currency');
            $table->string('stripe_product_id', 50)->nullable()->change();
            $table->string('stripe_price_id', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table): void {
            $table->dropColumn('commission_rate');
            $table->string('stripe_product_id', 50)->nullable(false)->change();
            $table->string('stripe_price_id', 50)->nullable(false)->change();
        });
    }
};
