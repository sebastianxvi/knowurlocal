<?php

namespace App\Services;

/**
 * Detect common questions whose subject is missing before spending tokens on FAQ retrieval.
 * This deliberately handles only clear, generic request shapes; it does not try to
 * semantically classify arbitrary questions or replace the AI FAQ matcher.
 */
class QuestionClarificationDetector
{
    /**
     * Return the clarification prompt when the question is clearly missing its subject.
     * Returns null for questions that should continue through normal FAQ retrieval.
     */
    public function promptFor(string $question): ?string
    {
        $normalized = function_exists('mb_strtolower')
            ? mb_strtolower(trim($question), 'UTF-8')
            : strtolower(trim($question));
        $normalized = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;
        $normalized = trim($normalized);

        if ($normalized === '') {
            return null;
        }

        $genericPatterns = [
            // English: requirements, documents, costs, duration, process, and location.
            '/^(?:(?:what are|what is) )?(?:the )?(?:requirements?|documents needed|needed documents)(?: for (?:it|this|that))?$/u',
            '/^(?:what do i need|what do i need to bring|what documents do i need)(?: for (?:it|this|that))?$/u',
            '/^(?:how much(?: does it cost)?|how much is it|what is the cost|how much will it cost)(?: for (?:it|this|that))?$/u',
            '/^(?:how long(?: does it take| will it take)?|how long is the process|how many days does it take)(?: for (?:it|this|that))?$/u',
            '/^(?:where do i apply|where can i apply|where is it located|where do i get it)$/u',
            '/^(?:how do i apply|how to apply|how can i get it|how do i get it|what is the process|how does it work)$/u',

            // Filipino / Taglish equivalents.
            '/^(?:(?:ano ang|ano) )?(?:mga )?requirements?(?: nito| dito| para dito)?$/u',
            '/^(?:ano ang kailangan|ano ang mga kailangan|ano mga kailangan|ano kailangan)(?: nito| dito| para dito)?$/u',
            '/^(?:magkano|magkano ito|magkano yan|magkano ang bayad)(?: nito| dito| para dito)?$/u',
            '/^(?:gaano katagal|ilang araw|ilang araw ang proseso)(?: ito| nito| ang proseso| ang application)?$/u',
            '/^(?:saan ako mag apply|saan mag apply|paano mag apply|paano kumuha)(?: nito| dito| nyan| niyan)?$/u',
            '/^(?:ano ang proseso|paano ito gumagana|paano ko ito makukuha)$/u',
        ];

        foreach ($genericPatterns as $pattern) {
            if (preg_match($pattern, $normalized) === 1) {
                return $this->looksFilipino($normalized)
                    ? 'Anong government service o dokumento ang tinutukoy mo? Pakisabi ang pangalan nito para mahanap ko ang tamang requirements o proseso.'
                    : 'Which government service or document are you asking about? Please include its name so I can find the right requirements, cost, or process.';
            }
        }

        return null;
    }

    private function looksFilipino(string $question): bool
    {
        return preg_match('/\b(ano|mga|kailangan|magkano|gaano|saan|paano|ito|nito|dito|nyan|niyan|ang|ako)\b/u', $question) === 1;
    }
}
