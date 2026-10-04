<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_logs', function (Blueprint $table) {
            $table->string('target_type', 60)->nullable()->after('support_request_id');
            $table->unsignedBigInteger('target_id')->nullable()->after('target_type');

            $table->index(['target_type', 'target_id'], 'user_logs_target_idx');
            $table->index(['role', 'created_at'], 'user_logs_role_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('user_logs', function (Blueprint $table) {
            $table->dropIndex('user_logs_target_idx');
            $table->dropIndex('user_logs_role_created_idx');
            $table->dropColumn(['target_type', 'target_id']);
        });
    }
};
