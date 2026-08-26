<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a direct Support Request reference to audit logs.
     */
    public function up(): void
    {
        Schema::table('user_logs', function (Blueprint $table) {

            /*
             * Directly identifies the Support Request
             * affected by the audited action.
             *
             * Nullable because most UserLog records are
             * unrelated to Support Requests.
             */
            $table->foreignId('support_request_id')
                ->nullable()
                ->after('category_id')
                ->constrained('support_requests')
                ->nullOnDelete();
        });
    }

    /**
     * Remove the Support Request reference.
     */
    public function down(): void
    {
        Schema::table('user_logs', function (Blueprint $table) {

            /*
             * Remove the foreign-key constraint and column
             * when rolling back the migration.
             */
            $table->dropForeign([
                'support_request_id'
            ]);

            $table->dropColumn(
                'support_request_id'
            );
        });
    }
};