<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Justificatifs métier. Le fichier est stocké via media.
        Schema::create('activity_documents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('activity_id')->constrained('activities')->cascadeOnDelete();
            $table->string('document_type', 50);
            $table->string('status', 20)->default('pending')->comment('pending | approved | rejected | expired');
            $table->date('expires_at')->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->index(['activity_id', 'document_type'], 'activity_documents_activity_type_index');
            // Tâche quotidienne qui passe les justificatifs arrivés à échéance en expired.
            $table->index(['status', 'expires_at'], 'activity_documents_status_expires_index');
            $table->index('reviewed_by', 'activity_documents_reviewed_by_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_documents');
    }
};
