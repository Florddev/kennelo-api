<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospect_contacts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('prospect_id')->constrained('prospects')->cascadeOnDelete();
            $table->foreignUuid('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type');
            $table->timestamp('contacted_at');
            $table->string('outcome')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['prospect_id', 'contacted_at']);
            $table->index('author_id', 'prospect_contacts_author_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospect_contacts');
    }
};
