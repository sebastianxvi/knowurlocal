<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add timestamps that describe the answer lifecycle.
     */
    public function up(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {

            /*
             * Records the exact moment an administrator
             * provides an answer.
             *
             * Nullable because pending inquiries have
             * not been answered yet.
             */
            $table->timestamp('answered_at')
                ->nullable()
                ->after('status');

            /*
             * Records when the user has viewed the answer.
             *
             * Nullable because a newly answered inquiry
             * has not necessarily been viewed yet.
             */
            $table->timestamp('answer_seen_at')
                ->nullable()
                ->after('answered_at');
        });
    }

    /**
     * Remove the answer lifecycle timestamps.
     */
    public function down(): void
    {
        Schema::table('support_requests', function (Blueprint $table) {

            $table->dropColumn([
                'answered_at',
                'answer_seen_at',
            ]);
        });
    }
};