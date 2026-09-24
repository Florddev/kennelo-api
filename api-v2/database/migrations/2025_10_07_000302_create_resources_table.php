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
        // Ce qui se réserve dans l'agenda. Rattaché à l'entreprise : une personne qui travaille
        // dans deux activités ne peut pas être réservée deux fois au même moment.
        Schema::create('resources', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('type', 20)->comment('staff | equipment | space');
            $table->uuid('organization_member_id')->nullable()->unique();
            $table->string('name', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Cible des clés étrangères composites ; sert aussi d'index sur organization_id.
            $table->unique(['organization_id', 'id']);

            $table->foreign(['organization_id', 'organization_member_id'], 'resources_member_foreign')
                ->references(['organization_id', 'id'])
                ->on('organization_members')
                ->cascadeOnDelete();
        });

        // SQLite ne permet pas d'ajouter une contrainte CHECK après coup.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE resources ADD CONSTRAINT resources_staff_member_check CHECK ((type = 'staff') = (organization_member_id IS NOT NULL))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
