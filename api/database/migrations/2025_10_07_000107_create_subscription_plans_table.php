<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('stripe_product_id', 50)->nullable()->unique();
            $table->string('stripe_price_id', 50)->nullable()->unique();
            $table->string('name', 100);
            $table->string('slug', 50)->unique();
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 10, 2);
            $table->decimal('price_yearly', 10, 2)->nullable();
            $table->char('currency', 3)->default('EUR');
            $table->json('features')->nullable();
            $table->json('limits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->decimal('commission_rate', 5, 4)->default('0.0000');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
