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
        Schema::table('addresses', function (Blueprint $table): void {
            $table->string('department', 3)->nullable()->after('postal_code');
            $table->index('department');
        });

        DB::table('addresses')
            ->whereNotNull('postal_code')
            ->orderBy('id')
            ->chunkById(500, function ($addresses): void {
                foreach ($addresses as $address) {
                    $department = $this->departmentFromPostalCode($address->postal_code);

                    if ($department !== null) {
                        DB::table('addresses')->where('id', $address->id)->update(['department' => $department]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table): void {
            $table->dropIndex(['department']);
            $table->dropColumn('department');
        });
    }

    private function departmentFromPostalCode(?string $postalCode): ?string
    {
        if ($postalCode === null || strlen($postalCode) < 2) {
            return null;
        }

        $prefix = substr($postalCode, 0, 2);

        if ($prefix === '20') {
            return in_array(substr($postalCode, 0, 3), ['200', '201'], true) ? '2A' : '2B';
        }

        if (str_starts_with($postalCode, '97') || str_starts_with($postalCode, '98')) {
            return substr($postalCode, 0, 3);
        }

        return $prefix;
    }
};
