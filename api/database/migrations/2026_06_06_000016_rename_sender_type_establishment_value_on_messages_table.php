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
            DB::statement("ALTER TABLE messages MODIFY sender_type ENUM('user', 'establishment', 'activity', 'system') NOT NULL");
        } else {
            Schema::table('messages', function (Blueprint $table): void {
                $table->string('sender_type', 20)->change();
            });

            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE messages DROP CONSTRAINT IF EXISTS messages_sender_type_check');
            }
        }

        DB::table('messages')->where('sender_type', 'establishment')->update(['sender_type' => 'activity']);

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE messages MODIFY sender_type ENUM('user', 'activity', 'system') NOT NULL");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE messages MODIFY sender_type ENUM('user', 'establishment', 'activity', 'system') NOT NULL");
        }

        DB::table('messages')->where('sender_type', 'activity')->update(['sender_type' => 'establishment']);

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE messages MODIFY sender_type ENUM('user', 'establishment', 'system') NOT NULL");
        }
    }
};
