<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('support_requests', 'trash_seen_at')) {
            Schema::table('support_requests', function (Blueprint $table) {
                $table->timestampTz('trash_seen_at')->nullable()->after('trash_reason');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('support_requests', 'trash_seen_at')) {
            Schema::table('support_requests', function (Blueprint $table) {
                $table->dropColumn('trash_seen_at');
            });
        }
    }
};
