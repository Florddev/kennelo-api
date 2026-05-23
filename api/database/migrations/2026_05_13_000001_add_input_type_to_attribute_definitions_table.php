<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attribute_definitions', function (Blueprint $table) {
            $table->string('input_type')->nullable()->after('value_type');
        });
    }

    public function down(): void
    {
        Schema::table('attribute_definitions', function (Blueprint $table) {
            $table->dropColumn('input_type');
        });
    }
};
