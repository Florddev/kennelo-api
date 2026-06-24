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
        foreach (['attribute_definitions', 'attribute_options'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->json('label_translations')->nullable()->after('label');
            });

            foreach (DB::table($table)->get(['id', 'label']) as $row) {
                DB::table($table)
                    ->where('id', $row->id)
                    ->update([
                        'label_translations' => json_encode(
                            ['en' => $row->label, 'fr' => $row->label, 'ar' => $row->label],
                            JSON_UNESCAPED_UNICODE
                        ),
                    ]);
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('label');
            });

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->renameColumn('label_translations', 'label');
            });
        }
    }

    public function down(): void
    {
        foreach (['attribute_definitions', 'attribute_options'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('label_single', 255)->nullable()->after('label');
            });

            foreach (DB::table($table)->get(['id', 'label']) as $row) {
                $translations = json_decode((string) $row->label, true) ?: [];
                DB::table($table)
                    ->where('id', $row->id)
                    ->update([
                        'label_single' => $translations['en'] ?? $translations['fr'] ?? reset($translations) ?: '',
                    ]);
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('label');
            });

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->renameColumn('label_single', 'label');
            });
        }
    }
};
