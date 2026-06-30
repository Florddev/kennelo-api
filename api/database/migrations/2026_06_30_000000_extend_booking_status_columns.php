<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('status')->default('pending')->change();
            $table->string('payment_status')->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed', 'in_progress'])
                ->default('pending')
                ->change();
            $table->enum('payment_status', ['pending', 'requires_action', 'processing', 'succeeded', 'failed', 'refunded'])
                ->default('pending')
                ->change();
        });
    }
};
