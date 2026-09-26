<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes used by the admin dashboards, notifications,
     * chatbot analytics, and paginated logs.
     *
     * These indexes do not change application behavior; they reduce
     * full-table scans on the columns used for filtering/grouping.
     */
    public function up(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'support_requests_status_created_idx');
            $table->index(['user_id', 'status', 'created_at'], 'support_requests_user_status_created_idx');
            $table->index(['agency_id', 'status', 'created_at'], 'support_requests_agency_status_created_idx');
            $table->index(['answered_at', 'status'], 'support_requests_answered_status_idx');
        });

        Schema::table('chatbot_logs', function (Blueprint $table) {
            $table->index(['created_at', 'outcome'], 'chatbot_logs_created_outcome_idx');
            $table->index(['outcome', 'faq_id'], 'chatbot_logs_outcome_faq_idx');
            $table->index(['agency_id', 'created_at'], 'chatbot_logs_agency_created_idx');
            $table->index(['match_method', 'created_at'], 'chatbot_logs_method_created_idx');
        });

        Schema::table('user_logs', function (Blueprint $table) {
            $table->index(['created_at', 'action'], 'user_logs_created_action_idx');
            $table->index(['user_id', 'created_at'], 'user_logs_user_created_idx');
            $table->index(['action', 'created_at'], 'user_logs_action_created_idx');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->index(['agency_id', 'deleted_at'], 'faqs_agency_deleted_idx');
        });
    }

    /**
     * Remove the indexes on rollback.
     */
    public function down(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {
            $table->dropIndex('support_requests_status_created_idx');
            $table->dropIndex('support_requests_user_status_created_idx');
            $table->dropIndex('support_requests_agency_status_created_idx');
            $table->dropIndex('support_requests_answered_status_idx');
        });

        Schema::table('chatbot_logs', function (Blueprint $table) {
            $table->dropIndex('chatbot_logs_created_outcome_idx');
            $table->dropIndex('chatbot_logs_outcome_faq_idx');
            $table->dropIndex('chatbot_logs_agency_created_idx');
            $table->dropIndex('chatbot_logs_method_created_idx');
        });

        Schema::table('user_logs', function (Blueprint $table) {
            $table->dropIndex('user_logs_created_action_idx');
            $table->dropIndex('user_logs_user_created_idx');
            $table->dropIndex('user_logs_action_created_idx');
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->dropIndex('faqs_agency_deleted_idx');
        });
    }
};
