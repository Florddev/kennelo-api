<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subscription_id')->constrained('subscriptions')->onDelete('cascade');
            $table->string('stripe_invoice_id', 50)->unique();
            $table->string('stripe_payment_intent_id', 50)->nullable();
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('EUR');
            $table->enum('status', ['paid', 'failed', 'refunded', 'pending'])->index();
            $table->timestamp('paid_at')->nullable();
            $table->text('invoice_pdf_url')->nullable();
            $table->timestamps();

            $table->index('subscription_id', 'subscription_payments_subscription_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
