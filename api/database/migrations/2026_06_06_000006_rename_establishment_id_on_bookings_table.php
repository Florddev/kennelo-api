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
            $table->dropIndex('bookings_availability_index');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->renameColumn('establishment_id', 'activity_id');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->index(['activity_id', 'check_in_date', 'check_out_date'], 'bookings_availability_index');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex('bookings_availability_index');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->renameColumn('activity_id', 'establishment_id');
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->index(['establishment_id', 'check_in_date', 'check_out_date'], 'bookings_availability_index');
        });
    }
};
