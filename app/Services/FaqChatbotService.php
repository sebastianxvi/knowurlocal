<?php

namespace App\Services;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * FAQ retrieval.
 *
 * AI is the primary semantic matcher. PHP never competes with the AI when
 * the AI path succeeds. A conservative, database-driven lexical matcher is
 * used only when the AI transport/retrieval path is unavailable, so an
 * OpenRouter outage does not make an otherwise answerable FAQ unavailable.
 *
 * The fallback is built from the live FAQ catalogue; it contains no
 * FAQ-specific IDs, hard-coded phrases, or manually maintained synonyms.
 * Regardless of which matcher selects the FAQ, the final response is always
 * loaded from the canonical FAQ/version data in the database.
 */
class FaqChatbotService
{
    /**
     * Infrastructure-only batch size. This is not a semantic rule.
     */
    private const AI_BATCH_SIZE = 100;

    /**
     * Short retrieval-catalogue cache. The final answer is always fetched
     * from the live FAQ/version rows, so this cache only avoids rebuilding the
     * compact AI input on every request.
     */
    private const CATALOG_CACHE_SECONDS = 30;

    /**
     * The emergency matcher must be conservative. It is never used when AI
     * successfully returns a valid FAQ decision.
     */
    private const LOCAL_FALLBACK_MIN_SCORE = 64.0;

    /**
     * Keep only the fields that help the AI identify the FAQ.
     *
     * Answers are intentionally NOT sent to the model. The database remains
     * the source of truth for the answer and the model only selects the FAQ ID.
     */
    private const MAX_FIELD_LENGTH = 800;

    /**
     * Pin the retrieval path to currently available free models instead of
     * letting `openrouter/free` randomly select a reasoning-heavy model.
     *
     * Keep this list short: the first healthy model should answer this tiny
     * classification task, while the remaining entries are true failovers.
     */
    private const RETRIEVAL_MODELS = [
        'poolside/laguna-s-2.1:free',
        'nvidia/nemotron-3.5-lightning:free',
        'google/gemma-4-26b-a4b-it:free',
    ];

    public function __construct(
        private OpenRouterService $ai
    ) {
    }

    /**
     * Let the AI select the best FAQ.
     *
     * There is deliberately no local semantic fallback, score threshold,
     * keyword scoring, token overlap, or FAQ-specific rule.
     */
    public function findMatch(string $question, ?int $agencyId = null): ?array
    {
        $question = trim(preg_replace('/\s+/u', ' ', $question) ?? '');

        if ($question === '') {
            return null;
        }

        $faqs = $this->loadFaqCatalog()
            ->filter(fn (Faq $faq): bool => $this->agencyAllowed($faq, $agencyId))
            ->values();

        if ($faqs->isEmpty()) {
            return null;
        }

        try {
            /*
             * AI is deliberately the first and only normal matching path.
             * This preserves semantic handling of paraphrases, Taglish,
             * misspellings, abbreviations, and natural conversational wording.
             */
            $winner = $this->selectBestFaqWithAi(
                $question,
                $agencyId,
                $faqs
            );

            if (!$winner) {
                return null;
            }

            return [
                'faq' => $winner['faq'],
                'confidence' => $winner['score'] / 100,
                'language' => $winner['language'],
                'method' => 'ai-semantic',
            ];
        } catch (\Throwable $e) {
            /*
             * OpenRouter is an enhancement to retrieval, not the source of
             * truth for the answer. If transport/model availability fails,
             * fall back to the SAME live FAQ catalogue already loaded above.
             *
             * Do not fall back for a database/catalogue failure because a
             * partial catalogue would make a deterministic match unsafe.
             */
            Log::warning('KNOWURLOCAL FAQ AI unavailable; using dynamic local fallback.', [
                'question' => $question,
                'agency_id' => $agencyId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'catalog_size' => $faqs->count(),
            ]);

            $fallback = $this->findDynamicLocalFallback($question, $faqs);

            if ($fallback === null) {
                Log::warning('KNOWURLOCAL dynamic FAQ fallback found no sufficiently strong match.', [
                    'question' => $question,
                    'agency_id' => $agencyId,
                    'catalog_size' => $faqs->count(),
                ]);

                return null;
            }

            Log::info('KNOWURLOCAL dynamic FAQ fallback winner.', [
                'question' => $question,
                'agency_id' => $agencyId,
                'faq_id' => $fallback['faq']->id,
                'score' => $fallback['score'],
                'catalog_size' => $faqs->count(),
            ]);

            return [
                'faq' => $fallback['faq'],
                'confidence' => $fallback['score'] / 100,
                'language' => $this->detectQuestionLanguage($question),
                'method' => 'dynamic-local-fallback',
            ];
        }
    }

    /**
     * Load only the fields required for semantic retrieval.
     *
     * The full answer/attachment payload is intentionally not loaded here.
     * That keeps the model input small and makes the FAQ catalogue much more
     * "mobile" while preserving the complete response in the database.
     */
    private function loadFaqCatalog(): Collection
    {
        return Cache::store(config('services.openrouter.cache_store', 'file'))->remember(
            'knowurlocal:chatbot:faq-catalog:v3',
            now()->addSeconds(self::CATALOG_CACHE_SECONDS),
            function (): Collection {
                try {
                    return Faq::query()
                        ->select([
                            'id',
                            'agency_id',
                            'question',
                            'question_fil',
                            'keywords',
                        ])
                        ->with('agency:id,agency_name,agency_abbreviation')
                        ->orderBy('id')
                        ->get();
                } catch (\Throwable $e) {
                    // Compatibility with deployments where the abbreviation column
                    // has not been migrated yet.
                    Log::warning('KNOWURLOCAL FAQ agency metadata fallback used.', [
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                    ]);

                    return Faq::query()
                        ->select([
                            'id',
                            'agency_id',
                            'question',
                            'question_fil',
                            'keywords',
                        ])
                        ->with('agency:id,agency_name')
                        ->orderBy('id')
                        ->get();
                }
            }
        );
    }

    /**
     * AI selects the best FAQ from the supplied catalogue.
     *
     * With the current catalogue this is normally one call. If the catalogue
     * becomes too large for one model context, infrastructure batches are
     * ranked by the model and a final model call compares those winners.
     */
    private function selectBestFaqWithAi(
        string $question,
        ?int $agencyId,
        Collection $faqs
    ): ?array {
        $batches = $faqs->chunk(self::AI_BATCH_SIZE)->values();
        $batchWinners = [];

        foreach ($batches as $batchIndex => $batch) {
            $winner = $this->askAiToChoose(
                question: $question,
                agencyId: $agencyId,
                faqs: $batch,
                stage: $batches->count() === 1
                    ? 'final'
                    : 'batch_' . ($batchIndex + 1)
            );

            if ($winner === null) {
                throw new RuntimeException('FAQ AI did not return a valid winner.');
            }

            $batchWinners[] = $winner;
        }

        if (count($batchWinners) === 1) {
            return $this->resolveWinner($batchWinners[0]);
        }

        $finalCandidates = collect($batchWinners)
            ->map(function (array $winner): array {
                return [
                    'faq_id' => $winner['faq_id'],
                    'score_from_batch' => $winner['score'],
                    'faq' => $winner['faq'],
                ];
            })
            ->values()
            ->all();

        $finalWinner = $this->askAiToChoose(
            question: $question,
            agencyId: $agencyId,
            faqs: collect($finalCandidates)->pluck('faq'),
            stage: 'final'
        );

        if ($finalWinner === null) {
            throw new RuntimeException('FAQ AI did not return a valid final winner.');
        }

        return $this->resolveWinner($finalWinner);
    }

    /**
     * Ask the model to choose ONE winner from the supplied records.
     */
    private function askAiToChoose(
        string $question,
        ?int $agencyId,
        Collection $faqs,
        string $stage
    ): ?array {
        $catalog = $this->buildCatalog($faqs);

        if ($catalog === []) {
            return null;
        }

        $payload = json_encode([
            'user_question' => $question,
            'selected_agency_id' => $agencyId,
            'faq_catalog' => $catalog,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        Log::debug('KNOWURLOCAL FAQ AI retrieval request prepared.', [
            'stage' => $stage,
            'catalog_size' => count($catalog),
            'payload_bytes' => strlen($payload),
        ]);

        /*
         * We intentionally do not require provider-specific structured output.
         * The system prompt requires JSON and decodeResponse() safely extracts
         * the JSON object. This keeps the chatbot compatible with models routed
         * through OpenRouter's free/variable model pool.
         */
        $response = $this->ai->chat(
            [
                ['role' => 'system', 'content' => $this->retrievalPrompt()],
                ['role' => 'user', 'content' => $payload],
            ],
            0.0,
            null,
            6,
            3,
            1,
            function (array $candidate): bool {
                try {
                    $decision = $this->decodeResponse($candidate);

                    return is_numeric($decision['faq_id'] ?? null)
                        && is_numeric($decision['score'] ?? null)
                        && is_string($decision['language'] ?? null);
                } catch (\Throwable) {
                    return false;
                }
            },
            $this->retrievalModels(),
            [
                // Retrieval only needs a tiny JSON decision. Spending the
                // completion budget on hidden reasoning is what caused the
                // previous free-router responses to finish with `length`
                // before emitting the JSON object.
                'max_tokens' => 192,
                'reasoning' => [
                    'effort' => 'none',
                ],
            ]
        );

        $decision = $this->decodeResponse($response);

        $faqId = $decision['faq_id'] ?? null;
        $score = $decision['score'] ?? null;

        if (!is_numeric($faqId) || !is_numeric($score)) {
            Log::warning('KNOWURLOCAL FAQ AI returned an invalid winner.', [
                'stage' => $stage,
                'question' => $question,
                'response' => $decision,
            ]);

            return null;
        }

        $faq = $faqs->firstWhere('id', (int) $faqId);

        /*
         * ID integrity validation only. This is not semantic matching.
         */
        if (!$faq) {
            Log::warning('KNOWURLOCAL FAQ AI selected an ID outside its catalogue.', [
                'stage' => $stage,
                'question' => $question,
                'faq_id' => (int) $faqId,
            ]);

            return null;
        }

        $numericScore = (float) $score;
        if ($numericScore <= 1) {
            $numericScore *= 100;
        }

        // No minimum score. The highest-scoring FAQ always wins.
        $numericScore = max(0.0, min(100.0, $numericScore));

        $language = strtolower(trim((string) ($decision['language'] ?? 'en')));
        if (!in_array($language, ['en', 'fil'], true)) {
            $language = 'en';
        }

        Log::info('KNOWURLOCAL AI FAQ winner.', [
            'stage' => $stage,
            'question' => $question,
            'faq_id' => $faq->id,
            'score' => $numericScore,
            'catalog_size' => count($catalog),
            'payload_bytes' => strlen($payload),
        ]);

        return [
            'faq_id' => (int) $faq->id,
            'score' => $numericScore,
            'language' => $language,
            'faq' => $faq,
        ];
    }

    /**
     * Resolve the AI-selected ID against the live database record.
     *
     * This second query is intentional: the compact retrieval object does not
     * contain answers or attachments. The database remains authoritative for
     * the final response.
     */
    private function resolveWinner(array $winner): ?array
    {
        $faqId = (int) ($winner['faq_id'] ?? 0);

        if ($faqId <= 0) {
            return null;
        }

        $faq = Faq::query()
            ->with([
                'agency:id,agency_name,agency_abbreviation',
                'currentVersion',
            ])
            ->find($faqId);

        if (!$faq) {
            return null;
        }

        return [
            'faq' => $faq,
            'score' => (float) ($winner['score'] ?? 0),
            'language' => $winner['language'] ?? 'en',
        ];
    }

    /**
     * Build the compact semantic catalogue sent to the AI.
     *
     * IMPORTANT:
     * - keywords are context for the AI, not a PHP matching algorithm;
     * - answers are not sent because the AI only needs to identify the FAQ;
     * - the selected ID is later used to fetch the approved stored response.
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
                    300
                ),
                'agency_abbreviation' => $this->limit(
                    (string) ($faq->agency?->agency_abbreviation ?? ''),
                    100
                ),
                'keywords' => $this->limit(
                    (string) ($faq->keywords ?? ''),
                    self::MAX_FIELD_LENGTH
                ),
                'question_en' => $this->limit(
                    (string) ($faq->question ?? ''),
                    self::MAX_FIELD_LENGTH
                ),
                'question_fil' => $this->limit(
                    (string) ($faq->question_fil ?? ''),
                    self::MAX_FIELD_LENGTH
                ),
            ];
        })->all();
    }

    /**
     * Conservative emergency matcher built entirely from the current FAQ
     * catalogue. It is intentionally weaker than AI and therefore is only
     * called after the AI retrieval path throws/fails.
     *
     * The matcher combines:
     * - normalized token overlap,
     * - keyword overlap,
     * - phrase similarity,
     * - typo-tolerant token similarity.
     *
     * No FAQ ID, agency, phrase, or synonym is hard-coded here.
     */
    private function findDynamicLocalFallback(string $question, Collection $faqs): ?array
    {
        $questionTokens = $this->meaningfulTokens($question);

        if ($questionTokens === []) {
            return null;
        }

        $ranked = $faqs->map(function (Faq $faq) use ($question, $questionTokens): array {
            $questionFields = array_filter([
                (string) ($faq->question ?? ''),
                (string) ($faq->question_fil ?? ''),
            ]);

            $keywordFields = $this->keywordFragments((string) ($faq->keywords ?? ''));
            $agencyFields = array_filter([
                (string) ($faq->agency?->agency_name ?? ''),
                (string) ($faq->agency?->agency_abbreviation ?? ''),
            ]);

            $questionText = implode(' ', $questionFields);
            $keywordText = implode(' ', $keywordFields);
            $agencyText = implode(' ', $agencyFields);

            $questionScore = $this->textMatchScore(
                $question,
                $questionTokens,
                $questionText
            );

            $keywordScore = $this->tokenOverlapScore(
                $questionTokens,
                $this->meaningfulTokens($keywordText)
            );

            $agencyScore = $this->tokenOverlapScore(
                $questionTokens,
                $this->meaningfulTokens($agencyText)
            );

            /*
             * The FAQ question is the strongest evidence. Keywords provide
             * additional context, while agency names are only a small boost.
             */
            $score = ($questionScore * 0.78)
                + ($keywordScore * 0.18)
                + ($agencyScore * 0.04);

            return [
                'faq' => $faq,
                'score' => max(0.0, min(100.0, $score)),
            ];
        })
            ->sortByDesc('score')
            ->values();

        $best = $ranked->first();
        $second = $ranked->get(1);

        if (!$best || $best['score'] < self::LOCAL_FALLBACK_MIN_SCORE) {
            return null;
        }

        /*
         * Avoid selecting a weakly distinguishable FAQ when two records are
         * nearly tied. AI would normally resolve that ambiguity; the emergency
         * matcher should prefer a safe no-match instead.
         */
        if (
            $second !== null
            && ($best['score'] - $second['score']) < 5.0
            && $best['score'] < 82.0
        ) {
            return null;
        }

        return $best;
    }

    /**
     * Compare a user's normalized question against an FAQ question.
     *
     * The full-string similarity catches close paraphrases while token
     * similarity catches reordered words. Per-token fuzzy comparison handles
     * common typos without requiring a manually maintained typo dictionary.
     */
    private function textMatchScore(
        string $question,
        array $questionTokens,
        string $faqQuestion
    ): float {
        $faqTokens = $this->meaningfulTokens($faqQuestion);

        if ($faqTokens === []) {
            return 0.0;
        }

        $normalizedQuestion = $this->normalizeForMatching($question);
        $normalizedFaq = $this->normalizeForMatching($faqQuestion);

        if ($normalizedQuestion !== '' && $normalizedQuestion === $normalizedFaq) {
            return 100.0;
        }

        $questionTokenScore = $this->tokenOverlapScore($questionTokens, $faqTokens);
        $phraseScore = 0.0;

        if ($normalizedQuestion !== '' && $normalizedFaq !== '') {
            similar_text($normalizedQuestion, $normalizedFaq, $phraseScore);
        }

        $fuzzyTokenScore = $this->fuzzyTokenScore($questionTokens, $faqTokens);

        /*
         * Token overlap is deliberately strongest because users often reorder
         * words. Phrase similarity adds context; fuzzy matching absorbs typos.
         */
        return min(
            100.0,
            ($questionTokenScore * 0.50)
            + ($fuzzyTokenScore * 0.30)
            + ($phraseScore * 0.20)
        );
    }

    /**
     * Directional overlap: every meaningful user token gets an opportunity to
     * match an FAQ token. This makes shorter user paraphrases work naturally.
     */
    private function tokenOverlapScore(array $sourceTokens, array $targetTokens): float
    {
        if ($sourceTokens === [] || $targetTokens === []) {
            return 0.0;
        }

        $target = array_values(array_unique($targetTokens));
        $matched = 0;

        foreach (array_unique($sourceTokens) as $sourceToken) {
            if (in_array($sourceToken, $target, true)) {
                $matched++;
            }
        }

        return ($matched / max(1, count(array_unique($sourceTokens)))) * 100;
    }

    /**
     * Typo-tolerant directional token matching.
     *
     * Only short edit distances are accepted, and very short tokens require
     * an exact match to avoid turning generic words into false positives.
     */
    private function fuzzyTokenScore(array $sourceTokens, array $targetTokens): float
    {
        if ($sourceTokens === [] || $targetTokens === []) {
            return 0.0;
        }

        $target = array_values(array_unique($targetTokens));
        $matched = 0;

        foreach (array_unique($sourceTokens) as $sourceToken) {
            if (strlen($sourceToken) <= 3) {
                if (in_array($sourceToken, $target, true)) {
                    $matched++;
                }
                continue;
            }

            foreach ($target as $targetToken) {
                if ($sourceToken === $targetToken) {
                    $matched++;
                    break;
                }

                $maxDistance = strlen($sourceToken) >= 8 ? 2 : 1;

                if (
                    abs(strlen($sourceToken) - strlen($targetToken)) <= $maxDistance
                    && levenshtein($sourceToken, $targetToken) <= $maxDistance
                ) {
                    $matched++;
                    break;
                }
            }
        }

        return ($matched / max(1, count(array_unique($sourceTokens)))) * 100;
    }

    /**
     * Normalize only for emergency matching. This is not used to decide which
     * FAQ to select while AI is healthy.
     */
    private function normalizeForMatching(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * Remove high-frequency function words so overlap reflects the subject of
     * the question rather than words such as "ba", "ng", "the", or "do".
     */
    private function meaningfulTokens(string $value): array
    {
        $normalized = $this->normalizeForMatching($value);

        if ($normalized === '') {
            return [];
        }

        $stopWords = [
            'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'can', 'do',
            'does', 'for', 'from', 'how', 'i', 'if', 'in', 'is', 'it', 'me',
            'my', 'of', 'on', 'or', 'the', 'to', 'what', 'when', 'where',
            'which', 'who', 'with', 'you', 'your',
            'ang', 'ba', 'bago', 'bilang', 'dahil', 'din', 'dito', 'doon',
            'gusto', 'ikaw', 'ito', 'ko', 'kung', 'may', 'mo', 'na', 'ng',
            'nga', 'ni', 'nito', 'para', 'po', 'sa', 'saan', 'si', 'sila',
            'sinong', 'tatak', 'the', 'yung',
        ];

        $tokens = preg_split('/\s+/u', $normalized, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter(
            $tokens ?: [],
            static fn (string $token): bool =>
                mb_strlen($token, 'UTF-8') >= 2
                && !in_array($token, $stopWords, true)
        ));
    }

    /**
     * FAQ keyword fields have historically been stored as comma/semicolon/
     * newline-separated text. Treat all common separators as boundaries.
     */
    private function keywordFragments(string $keywords): array
    {
        $parts = preg_split('/[,;|\n\r]+/u', $keywords);

        return array_values(array_filter(
            array_map('trim', $parts ?: []),
            static fn (string $value): bool => $value !== ''
        ));
    }

    private function detectQuestionLanguage(string $question): string
    {
        /*
         * This is only used for choosing between the already-stored English
         * and Filipino response variants after the emergency matcher wins.
         * It is intentionally conservative.
         */
        $tokens = $this->meaningfulTokens($question);

        if ($tokens === []) {
            return 'en';
        }

        $filipinoMarkers = [
            'kailangan', 'magpa', 'para', 'kumuha', 'kuha', 'saan', 'paano',
            'mag', 'mga', 'ng', 'ba', 'po', 'pwede', 'puwede', 'ano', 'may',
        ];

        $hits = 0;
        foreach ($tokens as $token) {
            if (in_array($token, $filipinoMarkers, true)) {
                $hits++;
            }
        }

        return $hits >= 2 ? 'fil' : 'en';
    }

    private function retrievalPrompt(): string
    {
        return <<<'PROMPT'
You are the semantic retrieval engine for KNOWURLOCAL.

Your ONLY job is to determine which ONE FAQ record in the supplied catalogue is the best match for the user's question.

The catalogue is the complete set of records you are allowed to choose from.

You must perform the matching yourself using your language understanding and the actual information contained in each supplied FAQ record.

Each record may contain:
- English question
- Filipino/Taglish question
- administrator-provided keywords
- agency name and abbreviation

Treat keywords as contextual hints, not as a fixed matching rule.

Do NOT use a fixed keyword-matching algorithm.
Do NOT assume that shared words mean shared intent.
Do NOT require exact wording.
Do NOT rely on word overlap alone.
Understand what information the user is actually asking for and determine which FAQ most directly represents that intent.

This must work for:
- English
- Filipino
- Taglish
- misspellings
- paraphrases
- abbreviations
- incomplete questions
- conversational wording

IMPORTANT:
1. Compare ALL supplied FAQ records semantically before choosing.
2. Select the single HIGHEST-relevance record.
3. There is NO minimum score; always choose the best available record.
4. Never return "no match".
5. Never invent an FAQ ID.
6. Never generate or rewrite an answer.
7. Do not explain your reasoning.
8. Return only the winning FAQ ID, its score, and the user's language.

Output exactly one JSON object and nothing else. Do not use Markdown or code fences:
{
  "faq_id": 123,
  "score": 87,
  "language": "fil"
}

The language field describes the language of the user's question and must be "en" or "fil".
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
            $json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $start = strpos($content, '{');
            $end = strrpos($content, '}');

            if ($start === false || $end === false || $end <= $start) {
                throw new RuntimeException('FAQ AI returned invalid JSON.', 0, $e);
            }

            try {
                $json = json_decode(
                    substr($content, $start, $end - $start + 1),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );
            } catch (\JsonException $inner) {
                throw new RuntimeException('FAQ AI returned invalid JSON.', 0, $inner);
            }
        }

        if (!is_array($json)) {
            throw new RuntimeException('FAQ AI returned invalid JSON.');
        }

        return $json;
    }

    private function retrievalModels(): array
    {
        $configured = config('services.openrouter.retrieval_models', self::RETRIEVAL_MODELS);

        if (!is_array($configured)) {
            return self::RETRIEVAL_MODELS;
        }

        // Prefer an explicitly configured retrieval chain. Do not merge it with
        // stale defaults: doing so can silently reintroduce retired/free-only
        // model slugs that OpenRouter now rejects with HTTP 404.
        $models = array_values(array_unique(array_filter(
            array_map('trim', $configured),
            static fn (string $model): bool => $model !== ''
        )));

        // These exact :free slugs are confirmed by OpenRouter to be retired
        // / paid-only. Do not let stale .env values reintroduce them into the
        // FAQ failover chain. Paid slugs without :free remain valid overrides.
        $retiredFreeModels = [
            'qwen/qwen3.8-27b:free',
            'inclusionai/ling-3.0-flash-fin:free',
        ];

        $models = array_values(array_filter(
            $models,
            static fn (string $model): bool => !in_array($model, $retiredFreeModels, true)
        ));

        return $models !== [] ? $models : self::RETRIEVAL_MODELS;
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

    private function agencyAllowed(Faq $faq, ?int $agencyId): bool
    {
        return $agencyId === null
            || $faq->agency_id === null
            || (int) $faq->agency_id === $agencyId;
    }
}
