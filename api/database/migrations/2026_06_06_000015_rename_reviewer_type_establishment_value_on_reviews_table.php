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
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE reviews MODIFY reviewer_type ENUM('user', 'establishment', 'activity') NOT NULL");
        } else {
            Schema::table('reviews', function (Blueprint $table): void {
                $table->string('reviewer_type', 20)->change();
            });
        }

        DB::table('reviews')->where('reviewer_type', 'establishment')->update(['reviewer_type' => 'activity']);

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE reviews MODIFY reviewer_type ENUM('user', 'activity') NOT NULL");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE reviews MODIFY reviewer_type ENUM('user', 'establishment', 'activity') NOT NULL");
        }

        DB::table('reviews')->where('reviewer_type', 'activity')->update(['reviewer_type' => 'establishment']);

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE reviews MODIFY reviewer_type ENUM('user', 'establishment') NOT NULL");
        }
    }
};
