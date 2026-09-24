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
        Schema::create('organizations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            // Seul le propriétaire peut céder ou fermer l'entreprise.
            $table->foreignUuid('owner_id')->constrained('users')->restrictOnDelete();
            $table->string('legal_name');
            $table->string('legal_form', 20)->comment('individual | micro_enterprise | company | association');
            $table->char('siren', 9)->nullable()->comment('NULL pour un particulier');
            $table->char('siret', 14)->nullable()->comment('Siège');
            $table->string('ape_code', 6)->nullable();
            $table->string('vat_number', 20)->nullable();
            $table->string('vat_regime', 20)->default('franchise')->comment('franchise | standard');
            $table->foreignUuid('address_id')->nullable()->constrained('addresses')->restrictOnDelete();
            $table->string('stripe_account_id', 50)->nullable()->unique();
            $table->string('stripe_customer_id', 50)->nullable()->unique();
            $table->boolean('stripe_charges_enabled')->default(false);
            $table->boolean('stripe_payouts_enabled')->default(false);
            $table->boolean('stripe_onboarding_completed')->default(false);
            $table->string('bank_account_last4', 4)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->timestamp('bank_account_verified_at')->nullable();
            $table->string('status', 20)->default('pending')->comment('pending | verified | rejected | suspended');
            $table->json('verification_data')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('billing_mandate_accepted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('owner_id', 'organizations_owner_id_index');
            $table->index('status', 'organizations_status_index');
            $table->index('address_id', 'organizations_address_id_index');
            $table->index('reviewed_by', 'organizations_reviewed_by_index');
        });

        // Unicité limitée aux entreprises non supprimées (soft delete).
        DB::statement('CREATE UNIQUE INDEX organizations_siren_unique ON organizations (siren) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
