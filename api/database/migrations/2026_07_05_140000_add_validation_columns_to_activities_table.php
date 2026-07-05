<?php

declare(strict_types=1);

use App\Enums\ActivityStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table): void {
            $table->string('status')->default(ActivityStatusEnum::PENDING->value)->after('is_active');
            $table->string('siren', 9)->nullable()->after('siret');
            $table->string('ape_code', 6)->nullable()->after('siren');
            $table->timestamp('company_verified_at')->nullable()->after('ape_code');
            $table->json('company_verification_data')->nullable()->after('company_verified_at');
            $table->text('rejection_reason')->nullable()->after('company_verification_data');
            $table->foreignUuid('reviewed_by')->nullable()->after('rejection_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');

            $table->index('status');
        });

        DB::table('activities')
            ->where('is_active', true)
            ->update(['status' => ActivityStatusEnum::APPROVED->value]);
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropIndex(['status']);
            $table->dropColumn([
                'status',
                'siren',
                'ape_code',
                'company_verified_at',
                'company_verification_data',
                'rejection_reason',
                'reviewed_at',
            ]);
        });
    }
};
