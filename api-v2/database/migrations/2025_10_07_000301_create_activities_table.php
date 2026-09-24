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
        // Un métier exercé par une entreprise, dans un lieu. Le légal et le financier sont portés par organizations.
        Schema::create('activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->restrictOnDelete();
            $table->foreignUuid('profession_id')->constrained('professions')->restrictOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('website')->nullable();
            $table->foreignUuid('address_id')->nullable()->constrained('addresses')->restrictOnDelete();
            // SIRET de l'établissement : plusieurs activités d'une même adresse le partagent.
            $table->char('establishment_siret', 14)->nullable();
            $table->string('timezone', 50)->default('UTC');
            $table->boolean('serves_at_pro')->default(true);
            $table->boolean('serves_at_client')->default(false);
            $table->boolean('serves_remote')->default(false);
            $table->unsignedSmallInteger('service_radius_km')->nullable();
            $table->string('cancellation_policy', 20)->default('moderate')->comment('flexible | moderate | strict');
            $table->boolean('is_active')->default(true);
            $table->string('status', 20)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Cible des clés étrangères composites ; sert aussi d'index sur organization_id.
            $table->unique(['organization_id', 'id']);
            $table->index(['profession_id', 'status'], 'activities_profession_status_index');
            $table->index(['status', 'is_active'], 'activities_status_active_index');
            $table->index('created_at', 'activities_created_at_index');
            $table->index('address_id', 'activities_address_id_index');
            $table->index('reviewed_by', 'activities_reviewed_by_index');
        });

        // SQLite ne permet pas d'ajouter une contrainte CHECK après coup.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE activities ADD CONSTRAINT activities_location_check CHECK (serves_at_pro OR serves_at_client OR serves_remote)');
            DB::statement('ALTER TABLE activities ADD CONSTRAINT activities_radius_check CHECK (NOT serves_at_client OR service_radius_km IS NOT NULL)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
