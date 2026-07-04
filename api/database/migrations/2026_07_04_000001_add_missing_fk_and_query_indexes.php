<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->index(['user_id', 'created_at'], 'bookings_user_created_index');
            $table->index(['status', 'created_at'], 'bookings_status_created_index');
        });

        Schema::table('conversations', function (Blueprint $table): void {
            $table->index('activity_id', 'conversations_activity_id_index');
        });

        Schema::table('pets', function (Blueprint $table): void {
            $table->index('user_id', 'pets_user_id_index');
        });

        Schema::table('activity_collaborators', function (Blueprint $table): void {
            $table->index(['user_id', 'status'], 'activity_collaborators_user_status_index');
        });

        Schema::table('booking_pets', function (Blueprint $table): void {
            $table->index('pet_id', 'booking_pets_pet_id_index');
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->index('activity_id', 'services_activity_id_index');
            $table->index('animal_type_id', 'services_animal_type_id_index');
        });

        Schema::table('favorites', function (Blueprint $table): void {
            $table->index('activity_id', 'favorites_activity_id_index');
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->index('booking_id', 'messages_booking_id_index');
        });

        Schema::table('booking_services', function (Blueprint $table): void {
            $table->index('service_id', 'booking_services_service_id_index');
        });

        Schema::table('financial_operations', function (Blueprint $table): void {
            $table->index('booking_id', 'financial_operations_booking_id_index');
        });

        Schema::table('review_reports', function (Blueprint $table): void {
            $table->index(['review_id', 'reporter_id'], 'review_reports_review_reporter_index');
        });

        Schema::table('identity_verifications', function (Blueprint $table): void {
            $table->index('user_id', 'identity_verifications_user_id_index');
        });

        Schema::table('scanners', function (Blueprint $table): void {
            $table->index('user_id', 'scanners_user_id_index');
        });

        Schema::table('scanner_scans', function (Blueprint $table): void {
            $table->index('pet_id', 'scanner_scans_pet_id_index');
        });

        Schema::table('user_notes', function (Blueprint $table): void {
            $table->index('author_id', 'user_notes_author_id_index');
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->index('establishment_id', 'subscriptions_establishment_id_index');
            $table->index('subscription_plan_id', 'subscriptions_plan_id_index');
        });

        Schema::table('subscription_payments', function (Blueprint $table): void {
            $table->index('subscription_id', 'subscription_payments_subscription_id_index');
        });

        Schema::table('activities_cycles_settings', function (Blueprint $table): void {
            $table->index('animal_type_id', 'activities_cycles_settings_animal_type_id_index');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->index('address_id', 'users_address_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex('bookings_user_created_index');
            $table->dropIndex('bookings_status_created_index');
        });

        Schema::table('conversations', function (Blueprint $table): void {
            $table->dropIndex('conversations_activity_id_index');
        });

        Schema::table('pets', function (Blueprint $table): void {
            $table->dropIndex('pets_user_id_index');
        });

        Schema::table('activity_collaborators', function (Blueprint $table): void {
            $table->dropIndex('activity_collaborators_user_status_index');
        });

        Schema::table('booking_pets', function (Blueprint $table): void {
            $table->dropIndex('booking_pets_pet_id_index');
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->dropIndex('services_activity_id_index');
            $table->dropIndex('services_animal_type_id_index');
        });

        Schema::table('favorites', function (Blueprint $table): void {
            $table->dropIndex('favorites_activity_id_index');
        });

        Schema::table('messages', function (Blueprint $table): void {
            $table->dropIndex('messages_booking_id_index');
        });

        Schema::table('booking_services', function (Blueprint $table): void {
            $table->dropIndex('booking_services_service_id_index');
        });

        Schema::table('financial_operations', function (Blueprint $table): void {
            $table->dropIndex('financial_operations_booking_id_index');
        });

        Schema::table('review_reports', function (Blueprint $table): void {
            $table->dropIndex('review_reports_review_reporter_index');
        });

        Schema::table('identity_verifications', function (Blueprint $table): void {
            $table->dropIndex('identity_verifications_user_id_index');
        });

        Schema::table('scanners', function (Blueprint $table): void {
            $table->dropIndex('scanners_user_id_index');
        });

        Schema::table('scanner_scans', function (Blueprint $table): void {
            $table->dropIndex('scanner_scans_pet_id_index');
        });

        Schema::table('user_notes', function (Blueprint $table): void {
            $table->dropIndex('user_notes_author_id_index');
        });

        Schema::table('subscriptions', function (Blueprint $table): void {
            $table->dropIndex('subscriptions_establishment_id_index');
            $table->dropIndex('subscriptions_plan_id_index');
        });

        Schema::table('subscription_payments', function (Blueprint $table): void {
            $table->dropIndex('subscription_payments_subscription_id_index');
        });

        Schema::table('activities_cycles_settings', function (Blueprint $table): void {
            $table->dropIndex('activities_cycles_settings_animal_type_id_index');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_address_id_index');
        });
    }
};
