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
        // Socle commun à tous les métiers. Les colonnes marquées « figé » sont copiées à la réservation
        // et ne changent plus. Le détail vendu est dans booking_units (séjour) et booking_items (prestations).
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->comment('Figé')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('activity_id')->constrained('activities')->restrictOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            // Toujours remplies ; identiques pour un rendez-vous.
            $table->date('start_date');
            $table->date('end_date');
            $table->string('location_mode', 20)->default('at_pro')->comment('at_pro | at_client | remote ; figé');
            // Copie de l'adresse du client (nouvelle ligne addresses, jamais modifiée).
            $table->foreignUuid('service_address_id')->nullable()->comment('Figé')->constrained('addresses')->restrictOnDelete();
            $table->string('status')->default('pending')->comment('App\Enums\BookingStatus');
            $table->text('special_requests')->nullable();
            $table->char('currency', 3)->default('EUR');
            $table->decimal('total_price', 10, 2);
            $table->decimal('service_fee', 10, 2)->default(0);
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->decimal('activity_amount', 10, 2)->default(0);
            $table->decimal('travel_fee', 10, 2)->default(0)->comment('Figé');
            $table->decimal('vat_rate', 5, 2)->comment('Figé, selon le régime de TVA de l\'entreprise');
            // Résumé pour les listes ; le détail est dans booking_payments et booking_refunds.
            $table->string('payment_status')->default('pending');
            $table->string('stripe_transfer_group', 255)->nullable();
            $table->string('cancellation_policy', 20)->comment('Figé');
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignUuid('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancelled_by_role', 20)->nullable()->comment('client | pro | platform');
            $table->timestamp('reminded_at')->nullable();
            $table->timestamps();

            $table->index(['activity_id', 'start_date', 'end_date'], 'bookings_availability_index');
            $table->index(['user_id', 'created_at'], 'bookings_user_created_index');
            $table->index(['status', 'created_at'], 'bookings_status_created_index');
            // Clôture automatique des réservations terminées.
            $table->index(['status', 'end_date'], 'bookings_status_end_date_index');
            $table->index('organization_id', 'bookings_organization_id_index');
            $table->index('payment_status', 'bookings_payment_status_index');
            $table->index('stripe_transfer_group', 'bookings_stripe_transfer_group_index');
        });

        // SQLite ne permet pas d'ajouter une contrainte CHECK après coup.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE bookings ADD CONSTRAINT bookings_dates_check CHECK (end_date >= start_date)');
            DB::statement('ALTER TABLE bookings ADD CONSTRAINT bookings_amounts_check CHECK (total_price >= 0 AND service_fee >= 0 AND platform_fee >= 0 AND activity_amount >= 0 AND travel_fee >= 0 AND vat_rate >= 0)');
            DB::statement("ALTER TABLE bookings ADD CONSTRAINT bookings_service_address_check CHECK (location_mode <> 'at_client' OR service_address_id IS NOT NULL)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
