<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Le propriétaire a aussi sa ligne : « mes entreprises » se liste en une seule requête.
        Schema::create('organization_members', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending')->comment('pending | active | declined');
            $table->foreignUuid('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
            // Cible des clés étrangères composites (garantit qu'un rôle ou une ressource reste dans la même entreprise).
            $table->unique(['organization_id', 'id']);
            $table->index(['user_id', 'status'], 'organization_members_user_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_members');
    }
};
