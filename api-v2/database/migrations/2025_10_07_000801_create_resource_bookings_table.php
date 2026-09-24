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
        // Occupation des ressources. Rendez-vous et absences partagent la table : la contrainte d'exclusion
        // refuse aussi un rendez-vous pendant une absence. Une annulation supprime la ligne.
        Schema::create('resource_bookings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('resource_id')->constrained('resources')->restrictOnDelete();
            $table->foreignUuid('booking_item_id')->nullable()->constrained('booking_items')->cascadeOnDelete();
            $table->string('kind', 20)->comment('booking | absence | block');
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('note')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Agenda d'une ressource sur une période.
            $table->index(['resource_id', 'starts_at'], 'resource_bookings_resource_starts_index');
            $table->index('booking_item_id', 'resource_bookings_booking_item_id_index');
        });

        // SQLite ne gère ni les contraintes d'exclusion ni l'ajout de CHECK après coup.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE resource_bookings ADD CONSTRAINT resource_bookings_range_check CHECK (ends_at > starts_at)');
            DB::statement("ALTER TABLE resource_bookings ADD CONSTRAINT resource_bookings_kind_check CHECK ((kind = 'booking') = (booking_item_id IS NOT NULL))");
            // Aucune ressource réservée deux fois sur des plages qui se chevauchent (bornes [début, fin) : les créneaux bout à bout restent possibles).
            DB::statement('ALTER TABLE resource_bookings ADD CONSTRAINT resource_bookings_no_overlap EXCLUDE USING gist (resource_id WITH =, tstzrange(starts_at, ends_at) WITH &&)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_bookings');
    }
};
