<?php

declare(strict_types=1);

use App\Enums\CollaboratorStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_collaborators', function (Blueprint $table): void {
            $table->string('status')->default(CollaboratorStatusEnum::PENDING->value);
            $table->foreignUuid('role_id')->nullable()->constrained('activity_roles')->nullOnDelete();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->dropColumn('role');
        });

        DB::table('activity_collaborators')->update(['status' => CollaboratorStatusEnum::ACCEPTED->value]);
    }

    public function down(): void
    {
        Schema::table('activity_collaborators', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('role_id');
            $table->dropColumn(['status', 'invited_at', 'responded_at']);
            $table->string('role', 50)->nullable();
        });
    }
};
