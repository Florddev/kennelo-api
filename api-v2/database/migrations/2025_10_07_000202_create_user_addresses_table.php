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
        Schema::create('user_addresses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('address_id')->constrained('addresses')->cascadeOnDelete();
            $table->string('label', 50);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'address_id']);
            $table->index('address_id', 'user_addresses_address_id_index');
        });

        // Une seule adresse par défaut par client.
        DB::statement('CREATE UNIQUE INDEX user_addresses_default_unique ON user_addresses (user_id) WHERE is_default');
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
