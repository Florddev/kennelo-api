<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lignes structurées : prépare le passage à la facturation électronique. Pas de timestamps (voir invoices).
        Schema::create('invoice_lines', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->comment('Ordre des lignes dans la facture');
            $table->string('description');
            $table->decimal('quantity', 8, 2)->default(1);
            $table->decimal('unit_price_ttc', 10, 2);
            $table->decimal('vat_rate', 5, 2);
            $table->decimal('total_ht', 10, 2);
            $table->decimal('total_vat', 10, 2);
            $table->decimal('total_ttc', 10, 2);

            $table->unique(['invoice_id', 'position'], 'invoice_lines_invoice_position_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
