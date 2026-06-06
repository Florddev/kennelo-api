<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE review_criteria_definitions MODIFY applicable_to ENUM('user', 'establishment', 'activity', 'both') NOT NULL");
        }

        DB::table('review_criteria_definitions')->where('applicable_to', 'establishment')->update(['applicable_to' => 'activity']);

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE review_criteria_definitions MODIFY applicable_to ENUM('user', 'activity', 'both') NOT NULL");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE review_criteria_definitions MODIFY applicable_to ENUM('user', 'establishment', 'activity', 'both') NOT NULL");
        }

        DB::table('review_criteria_definitions')->where('applicable_to', 'activity')->update(['applicable_to' => 'establishment']);

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE review_criteria_definitions MODIFY applicable_to ENUM('user', 'establishment', 'both') NOT NULL");
        }
    }
};
