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
        // Composition d'un forfait : évite de proposer en supplément un acte déjà inclus.
        Schema::create('service_package_items', function (Blueprint $table): void {
            $table->foreignUuid('package_id')->constrained('services')->cascadeOnDelete();
            $table->foreignUuid('service_id')->constrained('services')->restrictOnDelete();

            $table->primary(['package_id', 'service_id']);
            $table->index('service_id', 'service_package_items_service_id_index');
        });

        // SQLite ne permet pas d'ajouter une contrainte CHECK après coup.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE service_package_items ADD CONSTRAINT service_package_items_self_check CHECK (package_id <> service_id)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_package_items');
    }
};
