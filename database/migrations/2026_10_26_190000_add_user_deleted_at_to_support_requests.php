<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('support_requests', 'user_deleted_at')) {
            Schema::table('support_requests', function (Blueprint $table) {
                $table->timestampTz('user_deleted_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('support_requests', 'user_deleted_at')) {
            Schema::table('support_requests', function (Blueprint $table) {
                $table->dropColumn('user_deleted_at');
            });
        }
    }
};
