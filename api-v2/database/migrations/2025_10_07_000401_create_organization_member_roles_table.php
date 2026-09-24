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
        // Couples rôle + activité d'un membre. Les permissions de chaque rôle sont définies dans le code.
        Schema::create('organization_member_roles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('organization_id');
            $table->uuid('organization_member_id');
            $table->string('role', 20)->comment('manager | activity_manager | employee | accountant');
            // NULL = toute l'entreprise.
            $table->uuid('activity_id')->nullable();
            $table->timestamps();

            // Clés composites : le membre et l'activité appartiennent forcément à la même entreprise.
            $table->foreign(['organization_id', 'organization_member_id'], 'organization_member_roles_member_foreign')
                ->references(['organization_id', 'id'])
                ->on('organization_members')
                ->cascadeOnDelete();
            $table->foreign(['organization_id', 'activity_id'], 'organization_member_roles_activity_foreign')
                ->references(['organization_id', 'id'])
                ->on('activities')
                ->cascadeOnDelete();

            $table->index('activity_id', 'organization_member_roles_activity_id_index');
        });

        // Un seul rôle par membre et par activité, et un seul rôle global (activity_id NULL) par membre.
        $nullsNotDistinct = DB::getDriverName() === 'pgsql' ? ' NULLS NOT DISTINCT' : '';
        DB::statement("CREATE UNIQUE INDEX organization_member_roles_unique ON organization_member_roles (organization_member_id, activity_id){$nullsNotDistinct}");

        // SQLite ne permet pas d'ajouter une contrainte CHECK après coup.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE organization_member_roles ADD CONSTRAINT organization_member_roles_scope_check CHECK (
                (role IN ('manager', 'accountant') AND activity_id IS NULL)
                OR (role IN ('activity_manager', 'employee') AND activity_id IS NOT NULL)
            )");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_member_roles');
    }
};
