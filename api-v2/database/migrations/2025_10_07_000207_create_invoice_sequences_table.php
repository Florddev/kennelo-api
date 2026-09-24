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
        // Un compteur par émetteur et par année, partagé par les factures et les avoirs.
        // La ligne est verrouillée (SELECT ... FOR UPDATE) pendant l'émission : numérotation continue, sans trou.
        Schema::create('invoice_sequences', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('issuer_organization_id')->nullable()->comment('NULL = Kennelo')->constrained('organizations')->restrictOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
        });

        // Deux NULL (Kennelo) sont considérés égaux sous PostgreSQL ; SQLite garde un unique classique.
        $nullsNotDistinct = DB::getDriverName() === 'pgsql' ? ' NULLS NOT DISTINCT' : '';
        DB::statement("CREATE UNIQUE INDEX invoice_sequences_issuer_year_unique ON invoice_sequences (issuer_organization_id, year){$nullsNotDistinct}");
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_sequences');
    }
};
