<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Normalize chatbot audit metadata so the method describes how a matched
     * FAQ was actually selected:
     *
     *   semantic   = AI semantic retrieval
     *   similarity = deterministic lexical/question similarity fallback
     *   rule       = legacy rule-based matcher, when present
     *
     * "fallback" remains an outcome for unanswered questions. It must not be
     * used as the match method for an answered FAQ.
     */
    public function up(): void
    {
        DB::table('chatbot_logs')
            ->where('outcome', 'answered')
            ->where('match_method', 'ai')
            ->update(['match_method' => 'semantic']);

        DB::table('chatbot_logs')
            ->where('outcome', 'answered')
            ->where('match_method', 'fallback')
            ->whereNotNull('faq_id')
            ->update(['match_method' => 'similarity']);

        /*
         * Older answered logs were created before response_language existed
         * (or before it was populated). Recover the language from the exact
         * FAQ version that produced the historical answer whenever possible.
         *
         * If the stored answer cannot be matched to a language-specific
         * component, English is the application default and is used as the
         * least-assumptive historical value.
         */
        if (!\Schema::hasColumn('chatbot_logs', 'response_language')) {
            return;
        }

        DB::table('chatbot_logs')
            ->where('outcome', 'answered')
            ->whereNull('response_language')
            ->whereNotNull('faq_version_id')
            ->orderBy('id')
            ->chunkById(200, function ($logs): void {
                foreach ($logs as $log) {
                    $version = DB::table('faq_versions')
                        ->where('id', $log->faq_version_id)
                        ->first([
                            'answer',
                            'answer_fil',
                            'response_components',
                        ]);

                    if (!$version) {
                        continue;
                    }

                    $language = $this->detectLanguage(
                        (string) ($log->answer ?? ''),
                        $version
                    );

                    DB::table('chatbot_logs')
                        ->where('id', $log->id)
                        ->update(['response_language' => $language]);
                }
            });
    }

    private function detectLanguage(string $answer, object $version): string
    {
        $normalizedAnswer = $this->normalize($answer);

        if ($normalizedAnswer !== '') {
            if (
                $this->normalize((string) ($version->answer_fil ?? '')) === $normalizedAnswer
                && $this->normalize((string) ($version->answer_fil ?? '')) !== ''
            ) {
                return 'fil';
            }

            if (
                $this->normalize((string) ($version->answer ?? '')) === $normalizedAnswer
                && $this->normalize((string) ($version->answer ?? '')) !== ''
            ) {
                return 'en';
            }
        }

        $components = json_decode(
            (string) ($version->response_components ?? '[]'),
            true
        );

        if (is_array($components) && $normalizedAnswer !== '') {
            $languages = [];

            foreach ($components as $component) {
                if (!is_array($component) || ($component['type'] ?? null) !== 'text') {
                    continue;
                }

                $content = $this->normalize((string) ($component['content'] ?? ''));
                if ($content !== '' && $content === $normalizedAnswer) {
                    $language = strtolower((string) ($component['language'] ?? 'en'));
                    $languages[] = in_array($language, ['fil', 'filipino', 'tagalog', 'taglish'], true)
                        ? 'fil'
                        : 'en';
                }
            }

            if ($languages !== []) {
                return $languages[0];
            }
        }

        return 'en';
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = preg_replace('/\\s+/u', ' ', $value) ?? '';
        return trim($value);
    }

    public function down(): void
    {
        // Historical normalization is intentionally not reversed. Reverting
        // would recreate ambiguous "ai"/"fallback" metadata and lose the
        // recovered language information.
    }
};
