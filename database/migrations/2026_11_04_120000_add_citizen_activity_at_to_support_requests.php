<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('support_requests', 'citizen_activity_at')) {
            Schema::table('support_requests', function (Blueprint $table) {
                $table->timestamp('citizen_activity_at')->nullable()->index();
            });
        }

        // Preserve the existing unread state during rollout, without making
        // historical tickets unread merely because the new column is added.
        DB::table('support_requests')
            ->whereNull('citizen_activity_at')
            ->update([
                'citizen_activity_at' => DB::raw(
                    'CASE WHEN agency_seen_at IS NOT NULL AND updated_at <= agency_seen_at '.
                    'THEN agency_seen_at ELSE updated_at END'
                ),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('support_requests', 'citizen_activity_at')) {
            Schema::table('support_requests', function (Blueprint $table) {
                $table->dropIndex(['citizen_activity_at']);
                $table->dropColumn('citizen_activity_at');
            });
        }
    }
};
