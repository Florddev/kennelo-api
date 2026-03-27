<?php

declare(strict_types=1);

use App\Enums\AnimalAttributeCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attribute_definitions', function (Blueprint $table): void {
            $table->string('category', 50)
                ->default(AnimalAttributeCategory::INFO->value)
                ->after('label')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('attribute_definitions', function (Blueprint $table): void {
            $table->dropColumn('category');
        });
    }
};
