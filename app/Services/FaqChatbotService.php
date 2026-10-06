<?php

namespace App\Services;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * AI-first FAQ retrieval.
 *
 * The FAQ database is the knowledge base. The model is responsible for
 * matching the user's question to that live knowledge base.
 *
 * The AI receives the searchable metadata for every eligible FAQ:
 *   - agency
 *   - agency abbreviation
 *   - English question
 *   - Filipino/Taglish question
 *   - administrator-provided keywords
 *
 * The AI never receives the approved answer. After it selects an FAQ, Laravel
 * resolves the published FAQ version and returns the stored response.
 *
 * There are intentionally NO FAQ-, agency-, program-, or intent-specific
 * matching rules in this service. Adding a new FAQ therefore requires no code
 * change.
 */
class FaqChatbotService
{
    private const MAX_FAQ_FIELD_LENGTH = 700;

    /**
     * The provider only needs to return one small JSON decision.
     */
    private const AI_MIN_CONFIDENCE = 0.35;

    public function __construct(
        private OpenRouterService $ai
    ) {
    }

    /**
     * Find the FAQ that best matches the user's question.
     *
     * Normal operation is AI-first: there is no local semantic ranking,
     * intent dictionary, keyword weighting, or hardcoded FAQ map.
     */
    public function findMatch(string $question, ?int $agencyId = null): ?array
    {
        $question = $this->clean($question);

        if ($question === '') {
            return null;
        }

        $faqs = $this->loadFaqCatalog();

        if ($faqs->isEmpty()) {
            return null;
        }

        /*
         * An agency selected by the user is an explicit application constraint,
         * not a matching heuristic. When supplied, only that agency's FAQs are
         * presented to the model.
         */
        $eligible = $faqs
            ->filter(fn (Faq $faq): bool => $this->agencyAllowed($faq, $agencyId))
            ->values();

        if ($eligible->isEmpty()) {
            return null;
        }

        /*
         * Give the AI the complete eligible FAQ catalogue.
         *
         * This is deliberately NOT pre-ranked. Pre-ranking would mean that
         * local code decides which FAQs are "relevant" before the model sees
         * them, which is exactly what causes closely related FAQs to compete
         * incorrectly.
         */
        try {
            $decision = $this->retrieveWithAi(
                $question,
                $agencyId,
                $eligible
            );

            return $this->resolveAiDecision(
                $decision,
                $question,
                $eligible
            );
        } catch (\Throwable $e) {
            /*
             * AI matching is optional from an availability perspective. Do not
             * invent a semantic match when the provider is unavailable.
             *
             * The only safe fallback is an exact normalized question match.
             * It is not used during normal AI retrieval and contains no
             * program/agency/intent-specific rules.
             */
            Log::warning('KNOWURLOCAL FAQ AI matching failed; attempting exact-question recovery.', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'question' => $question,
                'agency_id' => $agencyId,
            ]);

            $exact = $this->findExactMatch($question, $eligible);

            if (!$exact) {
                return null;
            }

            return $this->formatMatch(
                $exact,
                1.0,
                'exact-recovery'
            );
        }
    }

    /**
     * Load the complete live FAQ catalogue.
     *
     * Soft-deleted FAQs are automatically excluded by the Faq model's
     * SoftDeletes trait.
     */
    private function loadFaqCatalog(): Collection
    {
        try {
            return Faq::query()
                ->with('agency:id,agency_name,agency_abbreviation')
                ->orderBy('id')
                ->get();
        } catch (\Throwable $e) {
            /*
             * agency_abbreviation was added later than agency_name. Keep the
             * chatbot compatible with an older production schema without
             * changing the matching algorithm.
             */
            Log::warning('KNOWURLOCAL FAQ catalog agency metadata query failed; retrying without abbreviation.', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return Faq::query()
                ->with('agency:id,agency_name')
                ->orderBy('id')
                ->get();
        }
    }

    /**
     * Ask the AI to choose from the entire eligible FAQ catalogue.
     */
    private function retrieveWithAi(
        string $question,
        ?int $agencyId,
        Collection $faqs
    ): array {
        $payload = json_encode([
            'task' => 'select_the_single_best_existing_faq',
            'user_question' => $question,
            'selected_agency_id' => $agencyId,
            'faq_catalog' => $this->buildCatalog($faqs),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return $this->decodeResponse($this->ai->chat(
            [
                ['role' => 'system', 'content' => $this->retrievalPrompt()],
                ['role' => 'user', 'content' => $payload],
            ],
            0.0,
            ['type' => 'json_object'],
            8,
            2,
            1
        ));
    }

    /**
     * Convert and validate the model's selection.
     *
     * The only authoritative identifier is an FAQ ID that actually exists in
     * the catalogue sent to the model. The model cannot provide arbitrary text
     * that becomes a chatbot answer.
     */
    private function resolveAiDecision(
        array $decision,
        string $question,
        Collection $faqs
    ): ?array {
        $faqId = $decision['faq_id'] ?? null;

        if (!is_numeric($faqId)) {
            return null;
        }

        $faq = $faqs->firstWhere('id', (int) $faqId);

        if (!$faq) {
            Log::warning('KNOWURLOCAL AI selected an FAQ outside the supplied catalogue.', [
                'faq_id' => $faqId,
                'question' => $question,
            ]);

            return null;
        }

        $matched = $decision['matched'] ?? true;

        if ($matched === false || $matched === 'false' || $matched === 0 || $matched === '0') {
            return null;
        }

        $confidence = $decision['confidence'] ?? 1.0;

        if (!is_numeric($confidence)) {
            $confidence = 0.0;
        }

        $confidence = (float) $confidence;

        if ($confidence > 1 && $confidence <= 100) {
            $confidence /= 100;
        }

        if ($confidence < self::AI_MIN_CONFIDENCE) {
            return null;
        }

        $language = strtolower(trim((string) ($decision['language'] ?? 'en')));

        if (!in_array($language, ['en', 'fil'], true)) {
            $language = 'en';
        }

        return $this->formatMatch(
            $faq,
            $confidence,
            'ai',
            $language
        );
    }

    /**
     * Build the searchable metadata supplied to the model.
     *
     * No answers are included. The model selects the FAQ; Laravel supplies the
     * approved answer only after the selection is validated.
     */
    private function buildCatalog(Collection $faqs): array
    {
        return $faqs->values()->map(function (Faq $faq): array {
            return [
                'faq_id' => (int) $faq->id,
                'agency_id' => $faq->agency_id !== null
                    ? (int) $faq->agency_id
                    : null,
                'agency' => $this->limit(
                    (string) ($faq->agency?->agency_name ?? ''),
                    220
                ),
                'agency_abbreviation' => $this->limit(
                    (string) ($faq->agency?->agency_abbreviation ?? ''),
                    80
                ),
                'keywords' => $this->limit(
                    (string) ($faq->keywords ?? ''),
                    self::MAX_FAQ_FIELD_LENGTH
                ),
                'question_en' => $this->limit(
                    (string) ($faq->question ?? ''),
                    self::MAX_FAQ_FIELD_LENGTH
                ),
                'question_fil' => $this->limit(
                    (string) ($faq->question_fil ?? ''),
                    self::MAX_FAQ_FIELD_LENGTH
                ),
            ];
        })->all();
    }

    /**
     * The model is explicitly told to reason over the complete catalogue.
     *
     * There are deliberately no hardcoded examples such as "requirements
     * means documents" or agency/program-specific matching rules. The model
     * itself must understand the user's language and compare it to the actual
     * FAQ records.
     */
    private function retrievalPrompt(): string
    {
        return <<<'PROMPT'
You are KNOWURLOCAL's FAQ retrieval engine.

Your ONLY job is to select the single existing FAQ record that best matches the user's question.

The application provides the complete eligible FAQ catalogue. Every record contains:
- faq_id
- agency
- agency abbreviation
- administrator-provided keywords
- English question
- Filipino/Taglish question

You must reason semantically over the user's actual question and compare it against the complete catalogue.

Important rules:
1. Read the entire catalogue before selecting a FAQ.
2. Match the user's actual meaning, not merely shared words.
3. Understand natural paraphrases, spelling mistakes, Filipino, English, Taglish, abbreviations, and conversational wording yourself.
4. Pay attention to the complete request and all qualifiers in it.
5. Distinguish closely related FAQs by what the user is actually asking.
6. Use agency and program/service information as contextual evidence, not as sufficient evidence by themselves.
7. Treat administrator-provided keywords as supporting evidence, not as a substitute for understanding the question.
8. Never invent an FAQ ID.
9. Never create, rewrite, summarize, or answer the user's question.
10. If none of the supplied FAQs genuinely answers the user's question, return matched=false and faq_id=null.
11. If several FAQs are related, select the one whose stored question most directly answers the user's specific request.
12. The selected FAQ ID MUST come from the supplied catalogue.

Return ONLY valid JSON in this exact shape:
{
  "matched": true,
  "faq_id": 123,
  "confidence": 0.94,
  "language": "en"
}

If no supplied FAQ genuinely matches:
{
  "matched": false,
  "faq_id": null,
  "confidence": 0,
  "language": "en"
}

confidence must be a number from 0 to 1.
language must be "en" or "fil".
PROMPT;
    }

    private function decodeResponse(array $response): array
    {
        $content = data_get($response, 'choices.0.message.content');

        if (is_array($content)) {
            $content = collect($content)
                ->map(fn ($part) => is_string($part)
                    ? $part
                    : (string) data_get($part, 'text', ''))
                ->implode('');
        }

        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('FAQ AI returned an empty response.');
        }

        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content) ?? $content;
        $content = preg_replace('/\s*```$/', '', $content) ?? $content;
        $content = trim($content);

        try {
            $json = json_decode(
                $content,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException $e) {
            /*
             * Some providers wrap JSON in a short amount of prose despite the
             * requested response format. Recover only a JSON object.
             */
            $start = strpos($content, '{');
            $end = strrpos($content, '}');

            if ($start === false || $end === false || $end <= $start) {
                throw new RuntimeException(
                    'FAQ AI returned invalid JSON.',
                    0,
                    $e
                );
            }

            try {
                $json = json_decode(
                    substr($content, $start, $end - $start + 1),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
            } catch (\JsonException $inner) {
                throw new RuntimeException(
                    'FAQ AI returned invalid JSON.',
                    0,
                    $inner
                );
            }
        }

        if (!is_array($json)) {
            throw new RuntimeException('FAQ AI returned invalid JSON.');
        }

        return $json;
    }

    private function clean(string $value): string
    {
        return trim(
            preg_replace('/\s+/u', ' ', $value) ?? ''
        );
    }

    private function limit(string $value, int $max): string
    {
        $value = trim($value);

        $length = function_exists('mb_strlen')
            ? mb_strlen($value, 'UTF-8')
            : strlen($value);

        if ($length <= $max) {
            return $value;
        }

        return function_exists('mb_substr')
            ? mb_substr($value, 0, $max, 'UTF-8') . '…'
            : substr($value, 0, $max) . '…';
    }

    private function normalizeExact(string $value): string
    {
        $value = trim(
            function_exists('mb_strtolower')
                ? mb_strtolower($value, 'UTF-8')
                : strtolower($value)
        );

        /*
         * This is ONLY for emergency exact-question recovery when the AI
         * provider is unavailable. It intentionally performs no semantic
         * normalization or intent mapping.
         */
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return trim($value);
    }

    private function findExactMatch(
        string $question,
        Collection $faqs
    ): ?Faq {
        $needle = $this->normalizeExact($question);

        if ($needle === '') {
            return null;
        }

        return $faqs->first(function (Faq $faq) use ($needle): bool {
            foreach ([
                $faq->question,
                $faq->question_fil,
            ] as $variant) {
                if (
                    $variant !== null
                    && $this->normalizeExact((string) $variant) === $needle
                ) {
                    return true;
                }
            }

            return false;
        });
    }

    private function agencyAllowed(Faq $faq, ?int $agencyId): bool
    {
        return $agencyId === null
            || $faq->agency_id === null
            || (int) $faq->agency_id === $agencyId;
    }

    private function formatMatch(
        Faq $faq,
        float $confidence,
        string $method,
        string $language = 'en'
    ): array {
        return [
            'faq' => $faq,
            'confidence' => max(0.0, min(1.0, $confidence)),
            'language' => in_array($language, ['en', 'fil'], true)
                ? $language
                : 'en',
            'method' => $method,
        ];
    }
}
