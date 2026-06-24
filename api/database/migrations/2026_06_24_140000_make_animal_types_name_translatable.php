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
        Schema::table('animal_types', function (Blueprint $table) {
            $table->json('name_translations')->nullable()->after('name');
        });

        foreach (DB::table('animal_types')->get(['id', 'name']) as $animalType) {
            DB::table('animal_types')
                ->where('id', $animalType->id)
                ->update([
                    'name_translations' => json_encode(
                        ['en' => $animalType->name, 'fr' => $animalType->name, 'ar' => $animalType->name],
                        JSON_UNESCAPED_UNICODE
                    ),
                ]);
        }

        Schema::table('animal_types', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('animal_types', function (Blueprint $table) {
            $table->renameColumn('name_translations', 'name');
        });
    }

    public function down(): void
    {
        Schema::table('animal_types', function (Blueprint $table) {
            $table->string('name_single', 100)->nullable()->after('name');
        });

        foreach (DB::table('animal_types')->get(['id', 'name']) as $animalType) {
            $translations = json_decode((string) $animalType->name, true) ?: [];
            DB::table('animal_types')
                ->where('id', $animalType->id)
                ->update([
                    'name_single' => $translations['fr'] ?? $translations['en'] ?? reset($translations) ?: '',
                ]);
        }

        Schema::table('animal_types', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        Schema::table('animal_types', function (Blueprint $table) {
            $table->renameColumn('name_single', 'name');
        });
    }
};
