<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('review_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignUuid('responder_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('response');
            $table->timestamps();

            $table->index('responder_id', 'review_responses_responder_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_responses');
    }
};
