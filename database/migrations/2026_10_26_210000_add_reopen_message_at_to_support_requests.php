<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('support_requests', 'reopen_message_at')) {
            Schema::table('support_requests', function (Blueprint $table) {
                $table->timestamp('reopen_message_at')->nullable()->after('reopen_message')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('support_requests', 'reopen_message_at')) {
            Schema::table('support_requests', function (Blueprint $table) {
                $table->dropIndex(['reopen_message_at']);
                $table->dropColumn('reopen_message_at');
            });
        }
    }
};
