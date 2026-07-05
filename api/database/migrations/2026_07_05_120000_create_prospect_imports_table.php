<?php

declare(strict_types=1);

use App\Enums\ProspectImportStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prospect_imports', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('location');
            $table->json('search_terms')->nullable();
            $table->unsignedInteger('max_results')->default(20);
            $table->string('status')->default(ProspectImportStatusEnum::PENDING->value);
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->text('error')->nullable();
            $table->foreignUuid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['requested_by', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prospect_imports');
    }
};
