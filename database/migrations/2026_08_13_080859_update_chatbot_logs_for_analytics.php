<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Apply the chatbot logging schema improvements.
     */
    public function up(): void
    {
        Schema::table('chatbot_logs', function (Blueprint $table) {

            // The FAQ that ultimately provided the answer.
            $table->foreignId('faq_id')
                ->nullable()
                ->after('agency_id')
                ->constrained('faqs')
                ->nullOnDelete();

            // Describes what happened to the user's question.
            $table->string('outcome')
                ->nullable()
                ->after('faq_id');

            // Describes how the FAQ answer was matched.
            $table->string('match_method')
                ->nullable()
                ->after('outcome');

            // Remove the old field because its meaning was overloaded.
            $table->dropColumn('type');
        });
    }

    /**
     * Reverse the chatbot logging schema improvements.
     */
    public function down(): void
    {
        Schema::table('chatbot_logs', function (Blueprint $table) {

            // Restore the old type column.
            $table->string('type')
                ->nullable()
                ->after('answer');

            // Remove the new FAQ relationship.
            $table->dropForeign(['faq_id']);
            $table->dropColumn('faq_id');

            // Remove the new analytics fields.
            $table->dropColumn([
                'outcome',
                'match_method',
            ]);
        });
    }
};