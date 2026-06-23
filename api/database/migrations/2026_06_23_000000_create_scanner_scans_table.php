<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scanner_scans', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('scanner_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('pet_id')->nullable()->constrained()->nullOnDelete();
            $table->string('microchip_number')->index();
            $table->boolean('found')->default(false);
            $table->timestamp('scanned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scanner_scans');
    }
};
