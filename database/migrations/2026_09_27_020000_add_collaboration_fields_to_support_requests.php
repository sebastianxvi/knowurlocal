<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {
            $table->foreignId('assigned_admin_id')
                ->nullable()
                ->after('agency_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('assigned_at')
                ->nullable()
                ->after('assigned_admin_id');

            $table->index(['status', 'assigned_admin_id']);
        });
    }

    public function down(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {
            $table->dropForeign(['assigned_admin_id']);
            $table->dropIndex(['status', 'assigned_admin_id']);
            $table->dropColumn(['assigned_admin_id', 'assigned_at']);
        });
    }
};
