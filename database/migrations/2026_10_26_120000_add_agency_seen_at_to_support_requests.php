<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('support_requests', 'agency_seen_at')) {
                $table->timestamp('agency_seen_at')->nullable()->index();
            }
            if (!Schema::hasColumn('support_requests', 'reopen_message')) {
                $table->text('reopen_message')->nullable();
            }
        });

        // Avoid presenting every historical ticket as unread on first rollout.
        DB::table('support_requests')
            ->whereNull('agency_seen_at')
            ->whereNotNull('updated_at')
            ->update(['agency_seen_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {
            if (Schema::hasColumn('support_requests', 'agency_seen_at')) {
                $table->dropIndex(['agency_seen_at']);
                $table->dropColumn('agency_seen_at');
            }
            if (Schema::hasColumn('support_requests', 'reopen_message')) {
                $table->dropColumn('reopen_message');
            }
        });
    }
};
