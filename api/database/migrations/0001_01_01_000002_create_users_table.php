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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_id_verified')->default(false);
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->tinyInteger('status')->default(1)->comment('App\Enums\UserStatus');
            $table->timestamps();
            $table->string('locale', 5)->default('en')->index();
            $table->uuid('address_id')->nullable();
            $table->softDeletes();
            $table->string('stripe_account_id')->nullable()->unique();
            $table->boolean('stripe_charges_enabled')->default(false);
            $table->boolean('stripe_payouts_enabled')->default(false);
            $table->boolean('stripe_onboarding_completed')->default(false);
            $table->string('stripe_customer_id')->nullable()->unique();
            $table->string('google_id')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->string('ban_reason')->nullable();
            $table->timestamp('banned_at')->nullable();
            $table->timestamp('banned_until')->nullable();
            $table->uuid('banned_by')->nullable();
            $table->timestamp('last_seen_at')->nullable()->index();

            $table->foreign('address_id')->references('id')->on('addresses')->nullOnDelete();

            $table->index('address_id', 'users_address_id_index');
        });

        // Unicité limitée aux comptes non supprimés (soft delete) pour permettre la réinscription.
        DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX users_google_id_unique ON users (google_id) WHERE deleted_at IS NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('banned_by')->references('id')->on('users')->nullOnDelete();
            $table->index('banned_by', 'users_banned_by_index');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
