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
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('subscription_plan_id')->constrained('subscription_plans')->restrictOnDelete();
            $table->string('stripe_subscription_id', 50)->unique();
            $table->enum('status', ['active', 'canceled', 'past_due', 'unpaid', 'trialing'])->index();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable()->index();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status'], 'subscriptions_organization_status_index');
            $table->index('subscription_plan_id', 'subscriptions_subscription_plan_id_index');
        });

        // Un seul abonnement actif, en essai ou impayé par entreprise.
        DB::statement("CREATE UNIQUE INDEX subscriptions_organization_current_unique ON subscriptions (organization_id) WHERE status IN ('active', 'trialing', 'past_due')");
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
