<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('siret', 14)->nullable()->unique();
            $table->text('description')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('website')->nullable();
            $table->uuid('address_id')->nullable();
            $table->string('timezone', 50)->default('UTC');
            $table->boolean('is_active')->default(true);
            $table->uuid('manager_id');
            $table->timestamps();
            $table->string('stripe_account_id', 50)->nullable()->unique();
            $table->boolean('stripe_onboarding_completed')->default(false);
            $table->boolean('stripe_charges_enabled')->default(false);
            $table->boolean('stripe_payouts_enabled')->default(false);
            $table->string('bank_account_last4', 4)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->boolean('bank_account_verified')->default(false);
            $table->timestamp('bank_account_verified_at')->nullable();
            $table->softDeletes();
            $table->string('type', 50)->nullable();
            $table->string('status')->default('pending');
            $table->string('siren', 9)->nullable();
            $table->string('ape_code', 6)->nullable();
            $table->timestamp('company_verified_at')->nullable();
            $table->json('company_verification_data')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('google_place_id')->nullable();
            $table->decimal('google_rating', 2, 1)->nullable();
            $table->unsignedInteger('google_reviews_count')->nullable();
            $table->string('google_maps_url')->nullable();
            $table->timestamp('google_synced_at')->nullable();

            $table->foreign('manager_id')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('address_id')->references('id')->on('addresses')->onDelete('restrict');
            $table->foreign('reviewed_by')->references('id')->on('users')->nullOnDelete();

            $table->index('is_active', 'activities_is_active_index');
            $table->index('siret', 'activities_siret_index');
            $table->index('created_at', 'activities_created_at_index');
            $table->index(['stripe_onboarding_completed', 'stripe_charges_enabled', 'stripe_payouts_enabled'], 'activities_stripe_ready_index');
            $table->index('status', 'activities_status_index');
            $table->index('google_place_id', 'activities_google_place_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
