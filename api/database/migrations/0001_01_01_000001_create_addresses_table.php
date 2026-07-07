<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('postal_code', 10);
            $table->string('city', 100);
            $table->string('region', 100)->nullable();
            $table->char('country', 2);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamps();
            $table->string('department', 3)->nullable();

            $table->index(['latitude', 'longitude'], 'addresses_lat_lng_index');
            $table->index('department');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
