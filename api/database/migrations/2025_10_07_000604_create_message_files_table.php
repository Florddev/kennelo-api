<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('message_id')->constrained()->onDelete('cascade');
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->string('file_type', 50);
            $table->bigInteger('file_size');
            $table->string('mime_type', 100);
            $table->timestamp('created_at')->nullable();

            $table->index('message_id', 'message_files_message_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_files');
    }
};
