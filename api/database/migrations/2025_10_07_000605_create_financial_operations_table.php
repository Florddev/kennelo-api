<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_operations', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
            $table->string('type')->index();
            $table->decimal('amount', 10, 2)->nullable();
            $table->char('currency', 3)->nullable();
            $table->string('stripe_reference')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('booking_id', 'financial_operations_booking_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_operations');
    }
};
