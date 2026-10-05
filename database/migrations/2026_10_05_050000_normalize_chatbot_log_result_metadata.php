<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalize legacy chatbot logs so metadata describes what actually
     * happened. Fallback/error interactions do not have a matched FAQ and
     * therefore must not pretend that an AI/rule match was evaluated.
     */
    public function up(): void
    {
        DB::table('chatbot_logs')
            ->whereIn('outcome', ['fallback', 'error'])
            ->update([
                'faq_id' => null,
                'faq_version_id' => null,
                'match_method' => null,
                'score' => null,
            ]);
    }

    public function down(): void
    {
        // The original metadata is not reliably recoverable. Leaving the
        // normalized values in place is safer than inventing historical data.
    }
};
