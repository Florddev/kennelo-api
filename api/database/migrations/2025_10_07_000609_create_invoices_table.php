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
        // Factures et avoirs. Une facture ne se modifie jamais : un remboursement produit un avoir.
        Schema::create('invoices', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('issuer_organization_id')->nullable()->comment('NULL = Kennelo')->constrained('organizations')->restrictOnDelete();
            $table->string('number', 30);
            $table->string('type', 20)->default('invoice')->comment('invoice | credit_note');
            $table->foreignUuid('credited_invoice_id')->nullable()->comment("Facture annulée par l'avoir")->constrained('invoices')->restrictOnDelete();
            // NULL pour le récapitulatif mensuel de commission.
            $table->foreignUuid('booking_id')->nullable()->constrained('bookings')->restrictOnDelete();
            $table->foreignUuid('recipient_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignUuid('recipient_organization_id')->nullable()->constrained('organizations')->restrictOnDelete();
            // Copies figées : raison sociale, adresse, SIREN, numéro de TVA.
            $table->json('issuer_details');
            $table->json('recipient_details');
            $table->char('currency', 3)->default('EUR');
            $table->decimal('total_ht', 10, 2);
            $table->decimal('total_vat', 10, 2);
            $table->decimal('total_ttc', 10, 2);
            $table->string('vat_mention')->nullable()->comment('Ex. TVA non applicable, art. 293 B du CGI');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->timestamp('issued_at');
            // Pas de updated_at : une facture ne change pas.
            $table->timestamp('created_at')->useCurrent();

            $table->index('booking_id', 'invoices_booking_id_index');
            $table->index(['recipient_organization_id', 'issued_at'], 'invoices_recipient_organization_issued_index');
            $table->index(['recipient_user_id', 'issued_at'], 'invoices_recipient_user_issued_index');
        });

        // Deux NULL (Kennelo) sont considérés égaux sous PostgreSQL ; SQLite garde un unique classique.
        $nullsNotDistinct = DB::getDriverName() === 'pgsql' ? ' NULLS NOT DISTINCT' : '';
        DB::statement("CREATE UNIQUE INDEX invoices_issuer_number_unique ON invoices (issuer_organization_id, number){$nullsNotDistinct}");

        // SQLite ne permet pas d'ajouter une contrainte CHECK après coup.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE invoices ADD CONSTRAINT invoices_recipient_check CHECK ((recipient_user_id IS NULL) <> (recipient_organization_id IS NULL))');
            DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_credit_note_check CHECK ((type = 'credit_note') = (credited_invoice_id IS NOT NULL))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
