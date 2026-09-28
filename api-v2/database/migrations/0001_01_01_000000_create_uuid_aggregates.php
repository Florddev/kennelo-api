<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION uuid_larger(uuid, uuid) RETURNS uuid
                LANGUAGE sql IMMUTABLE STRICT PARALLEL SAFE
                AS $$ SELECT CASE WHEN $1 > $2 THEN $1 ELSE $2 END $$;
            CREATE OR REPLACE FUNCTION uuid_smaller(uuid, uuid) RETURNS uuid
                LANGUAGE sql IMMUTABLE STRICT PARALLEL SAFE
                AS $$ SELECT CASE WHEN $1 < $2 THEN $1 ELSE $2 END $$;
            CREATE OR REPLACE AGGREGATE max(uuid) (SFUNC = uuid_larger, STYPE = uuid, COMBINEFUNC = uuid_larger, SORTOP = >, PARALLEL = SAFE);
            CREATE OR REPLACE AGGREGATE min(uuid) (SFUNC = uuid_smaller, STYPE = uuid, COMBINEFUNC = uuid_smaller, SORTOP = <, PARALLEL = SAFE);
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::unprepared(<<<'SQL'
            DROP AGGREGATE IF EXISTS max(uuid);
            DROP AGGREGATE IF EXISTS min(uuid);
            DROP FUNCTION IF EXISTS uuid_larger(uuid, uuid);
            DROP FUNCTION IF EXISTS uuid_smaller(uuid, uuid);
            SQL);
    }
};
