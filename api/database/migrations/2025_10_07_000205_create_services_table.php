<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catalogue de l'entreprise. Les prix sont dans service_prices, les conditions de vente dans activity_services.
        Schema::create('services', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->boolean('is_package')->default(false);
            // A une durée et se place dans l'agenda.
            $table->boolean('requires_scheduling')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            // Une prestation déjà vendue ne se supprime pas.
            $table->softDeletes();

            // Cible des clés étrangères composites ; sert aussi d'index sur organization_id.
            $table->unique(['organization_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
