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

    /** Maximum locally ranked candidates sent to the semantic model per question. */
    private const AI_CANDIDATE_LIMIT = 20;

    /**
     * Short retrieval-catalogue cache. The final answer is always fetched
     * from the live FAQ/version rows, so this cache only avoids rebuilding the
     * compact AI input on every request.
     */
    private const CATALOG_CACHE_SECONDS = 30;

    /** Cache only successful AI FAQ selections; answers are always reloaded live. */
    private const MATCH_CACHE_SECONDS = 600;

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
        // Let OpenRouter choose a healthy free model first. This keeps the
        // AI path broad instead of pinning the chatbot to a single provider.
        'openrouter/free',
        // Multiple providers currently serve this free model, giving the AI
        // path another independent recovery option.
        'nvidia/nemotron-3-ultra-550b-a55b:free',
        'poolside/laguna-s-2.1:free',
        'nvidia/nemotron-3.5-lightning:free',
    ];

    public function __construct(
        private OpenRouterService $openRouter,
        private GroqService $groq
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

        /*
         * Explicit fallback-only mode for local testing. This branch is
         * intentionally before cache lookup and every provider call, so the
         * test cannot accidentally consume AI requests or reuse AI decisions.
         * Default mode preserves the existing AI-first behavior.
         */
        $matchingMode = strtolower(trim((string) config('services.chatbot_ai.mode', 'ai')));

        if ($matchingMode === 'fallback_only') {
            Log::info('KNOWURLOCAL FAQ fallback-only mode enabled; skipping AI provider.', [
                'agency_id' => $agencyId,
                'catalog_size' => $faqs->count(),
            ]);

            $fallback = $this->findDynamicLocalFallback($question, $faqs);

            if ($fallback === null) {
                Log::info('KNOWURLOCAL dynamic FAQ fallback found no sufficiently strong match in fallback-only mode.', [
                    'agency_id' => $agencyId,
                    'catalog_size' => $faqs->count(),
                ]);

                return null;
            }

            Log::info('KNOWURLOCAL dynamic FAQ fallback winner in fallback-only mode.', [
                'agency_id' => $agencyId,
                'faq_id' => $fallback['faq']->id,
                'score' => $fallback['score'],
                'catalog_size' => $faqs->count(),
            ]);

            return [
                'faq' => $fallback['faq'],
                'confidence' => $fallback['score'] / 100,
                'language' => $this->detectQuestionLanguage($question),
                'method' => 'dynamic-local-fallback-only',
            ];
        }

        /*
         * Reuse successful semantic decisions for repeated questions. The key
         * includes the effective catalogue fingerprint and agency scope, so
         * edits to FAQ questions/keywords or agency assignment naturally
         * invalidate old decisions. Only the FAQ ID/score/language are cached;
         * the approved answer is still loaded from the database.
         */
        $matchCache = Cache::store(config('services.openrouter.cache_store', 'file'));
        $matchCacheKey = $this->matchCacheKey($question, $agencyId, $faqs);
        $cachedDecision = $matchCache->get($matchCacheKey);

        if (is_array($cachedDecision) && is_numeric($cachedDecision['faq_id'] ?? null)) {
            $cachedFaq = $faqs->firstWhere('id', (int) $cachedDecision['faq_id']);

            if ($cachedFaq !== null) {
                $cachedWinner = $this->resolveWinner([
                    'faq_id' => (int) $cachedDecision['faq_id'],
                    'score' => (float) ($cachedDecision['score'] ?? 0),
                    'language' => in_array($cachedDecision['language'] ?? null, ['en', 'fil'], true)
                        ? $cachedDecision['language']
                        : 'en',
                ]);

                if ($cachedWinner !== null && !$this->faqMatchesQuestionIntent($question, $cachedWinner['faq'])) {
                    Log::info('KNOWURLOCAL FAQ cached winner rejected by intent guard.', [
                        'agency_id' => $agencyId,
                        'faq_id' => (int) $cachedDecision['faq_id'],
                    ]);
                    $matchCache->forget($matchCacheKey);
                    $cachedWinner = null;
                }

                if ($cachedWinner !== null) {
                    Log::debug('KNOWURLOCAL FAQ AI match cache hit.', [
                        'agency_id' => $agencyId,
                        'faq_id' => (int) $cachedDecision['faq_id'],
                    ]);

                    return [
                        'faq' => $cachedWinner['faq'],
                        'confidence' => $cachedWinner['score'] / 100,
                        'language' => $cachedWinner['language'],
                        'method' => 'ai-semantic-cache',
                    ];
                }
            }

            $matchCache->forget($matchCacheKey);
        }

        Log::debug('KNOWURLOCAL FAQ AI match cache miss.', [
            'agency_id' => $agencyId,
            'catalog_size' => $faqs->count(),
        ]);

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

            if ($winner && !$this->faqMatchesQuestionIntent($question, $winner['faq'])) {
                Log::info('KNOWURLOCAL FAQ AI winner rejected by intent guard.', [
                    'agency_id' => $agencyId,
                    'faq_id' => (int) $winner['faq']->id,
                ]);
                $winner = null;
            }

            if (!$winner) {
                Log::info('KNOWURLOCAL FAQ AI returned no winner; trying dynamic local fallback.', [
                    'agency_id' => $agencyId,
                    'catalog_size' => $faqs->count(),
                ]);

                $fallback = $this->findDynamicLocalFallback($question, $faqs);

                if ($fallback === null) {
                    return null;
                }

                Log::info('KNOWURLOCAL dynamic FAQ fallback winner after AI no-match.', [
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

            $matchCache->put($matchCacheKey, [
                'faq_id' => (int) $winner['faq']->id,
                'score' => (float) $winner['score'],
                'language' => in_array($winner['language'] ?? null, ['en', 'fil'], true)
                    ? $winner['language']
                    : 'en',
            ], now()->addSeconds(self::MATCH_CACHE_SECONDS));

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
     * Build a context-aware cache key for successful FAQ match decisions.
     */
    private function matchCacheKey(string $question, ?int $agencyId, Collection $faqs): string
    {
        $catalogFingerprint = $faqs->map(static fn (Faq $faq): array => [
            'id' => (int) $faq->id,
            'agency_id' => $faq->agency_id !== null ? (int) $faq->agency_id : null,
            'question' => (string) ($faq->question ?? ''),
            'question_fil' => (string) ($faq->question_fil ?? ''),
            'keywords' => (string) ($faq->keywords ?? ''),
            'agency' => (string) ($faq->agency?->agency_name ?? ''),
            'agency_abbreviation' => (string) ($faq->agency?->agency_abbreviation ?? ''),
        ])->all();

        $fingerprint = hash('sha256', serialize($catalogFingerprint));
        $normalizedQuestion = $this->normalizeForMatching($question);

        return 'knowurlocal:chatbot:match:v1:' . hash('sha256', serialize([
            'question' => $normalizedQuestion,
            'agency_id' => $agencyId,
            'catalog' => $fingerprint,
        ]));
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
        /*
         * Do not send the entire FAQ catalogue to Groq. Rank the live catalogue
         * locally using general text/keyword similarity, then let AI make the
         * semantic decision among a generous shortlist. This keeps the prompt
         * small without hard-coding FAQ IDs or agency-specific rules.
         */
        $allFaqCount = $faqs->count();
        $faqs = $this->selectAiCandidates($question, $faqs);

        Log::debug('KNOWURLOCAL FAQ AI candidate shortlist prepared.', [
            'agency_id' => $agencyId,
            'catalog_size' => $allFaqCount,
            'candidate_count' => $faqs->count(),
        ]);

        if ($faqs->isEmpty()) {
            return null;
        }

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
     * Reduce the model input to the strongest general-purpose lexical candidates.
     *
     * This is candidate generation, not the final match decision: Groq still
     * compares the question's meaning against both language variants and FAQ
     * keywords before choosing the canonical record. No FAQ IDs or topics are
     * hard-coded. When the catalogue is already small, all records are kept.
     */
    private function selectAiCandidates(string $question, Collection $faqs): Collection
    {
        if ($faqs->count() <= self::AI_CANDIDATE_LIMIT) {
            return $faqs->values();
        }

        $questionTokens = $this->meaningfulTokens($question);
        if ($questionTokens === []) {
            return $faqs->take(self::AI_CANDIDATE_LIMIT)->values();
        }

        $rankedCandidates = $faqs
            ->map(function (Faq $faq) use ($question, $questionTokens): array {
                $questionText = trim(implode(' ', array_filter([
                    (string) ($faq->question ?? ''),
                    (string) ($faq->question_fil ?? ''),
                ])));
                $keywordText = implode(
                    ' ',
                    $this->keywordFragments((string) ($faq->keywords ?? ''))
                );
                $agencyText = trim(implode(' ', array_filter([
                    (string) ($faq->agency?->agency_name ?? ''),
                    (string) ($faq->agency?->agency_abbreviation ?? ''),
                ])));

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

                return [
                    'faq' => $faq,
                    'score' => ($questionScore * 0.78)
                        + ($keywordScore * 0.18)
                        + ($agencyScore * 0.04),
                ];
            })
            ->sortByDesc('score')
            ->take(self::AI_CANDIDATE_LIMIT)
            ->pluck('faq')
            ->values()
            ->all();

        // Keep the declared Eloquent Collection contract: plucking model
        // instances from a mapped collection otherwise produces a Support
        // Collection and throws a TypeError before the AI request is sent.
        return new Collection($rankedCandidates);
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
            'provider' => config('services.chatbot_ai.provider', 'groq'),
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
        $aiProvider = strtolower(trim((string) config('services.chatbot_ai.provider', 'groq')));
        $ai = $aiProvider === 'openrouter' ? $this->openRouter : $this->groq;

        if (!in_array($aiProvider, ['groq', 'openrouter'], true)) {
            throw new RuntimeException('Unsupported chatbot AI provider configured.');
        }

        $response = $ai->chat(
            [
                ['role' => 'system', 'content' => $this->retrievalPrompt()],
                ['role' => 'user', 'content' => $payload],
            ],
            0.0,
            null,
            8,
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
                // Reasoning-capable models can consume a significant portion
                // of the completion budget before emitting the compact JSON.
                // Keep this independently configurable from normal chatbot output.
                'max_tokens' => max(
                    128,
                    (int) config('services.chatbot_ai.retrieval_max_tokens', 256)
                ),
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
    /**
     * Reject obvious answer-type mismatches before returning/caching a FAQ.
     * This is deliberately generic and does not depend on specific FAQ IDs.
     */
    private function faqMatchesQuestionIntent(string $question, Faq $faq): bool
    {
        $q = $this->normalizeForMatching($question);
        $faqText = $this->normalizeForMatching(implode(' ', array_filter([
            (string) ($faq->question ?? ''),
            (string) ($faq->question_fil ?? ''),
            (string) ($faq->keywords ?? ''),
        ])));

        $asksCost = preg_match('/\\b(how much|cost|price|fee|fees|payment|magkano|presyo|bayad|magkano ang)\\b/u', $q) === 1;
        if ($asksCost) {
            // A fee question must match FAQ metadata that actually addresses
            // cost/payment; a requirements FAQ is not a safe substitute.
            return preg_match('/\\b(cost|price|fee|fees|payment|amount|peso|php|magkano|presyo|bayad|singil|halaga)\\b/u', $faqText) === 1;
        }

        return true;
    }

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

        /*
         * Compute term rarity from the current catalogue. Distinctive terms
         * such as a specific document or process carry more weight than words
         * that occur in many FAQs. This is rebuilt from live FAQ content, so
         * new/edited FAQs need no manually maintained vocabulary or IDs.
         */
        $documentFrequency = [];
        foreach ($faqs as $catalogFaq) {
            $documentTokens = array_unique($this->meaningfulTokens(implode(' ', array_filter([
                (string) ($catalogFaq->question ?? ''),
                (string) ($catalogFaq->question_fil ?? ''),
                implode(' ', $this->keywordFragments((string) ($catalogFaq->keywords ?? ''))),
            ]))));

            foreach ($documentTokens as $token) {
                $documentFrequency[$token] = ($documentFrequency[$token] ?? 0) + 1;
            }
        }

        $ranked = $faqs->map(function (Faq $faq) use ($question, $questionTokens, $documentFrequency, $faqs): array {
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

            $faqTokens = $this->meaningfulTokens(implode(' ', array_filter([
                $questionText,
                $keywordText,
            ])));
            $rarityScore = $this->weightedTokenOverlapScore(
                $questionTokens,
                $faqTokens,
                $documentFrequency,
                $faqs->count()
            );

            /*
             * The FAQ question remains the strongest evidence. Keyword overlap
             * and catalogue-derived term rarity improve recall for short,
             * natural questions without introducing FAQ-specific rules.
             */
            $score = ($questionScore * 0.66)
                + ($keywordScore * 0.14)
                + ($rarityScore * 0.16)
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
     * Weighted overlap using inverse document frequency from the live FAQ
     * catalogue. Rare terms contribute more than generic terms. This is a
     * lightweight local retrieval signal, not a calibrated probability.
     */
    private function weightedTokenOverlapScore(
        array $sourceTokens,
        array $targetTokens,
        array $documentFrequency,
        int $documentCount
    ): float {
        $sourceTokens = array_values(array_unique($sourceTokens));
        $targetTokens = array_values(array_unique($targetTokens));

        if ($sourceTokens === [] || $targetTokens === []) {
            return 0.0;
        }

        $totalWeight = 0.0;
        $matchedWeight = 0.0;

        foreach ($sourceTokens as $sourceToken) {
            $frequency = max(0, (int) ($documentFrequency[$sourceToken] ?? 0));
            $weight = log(1 + (($documentCount + 1) / ($frequency + 1))) + 1;
            $totalWeight += $weight;

            if (in_array($sourceToken, $targetTokens, true)) {
                $matchedWeight += $weight;
                continue;
            }

            // Permit a small edit distance for longer misspelled terms.
            if (mb_strlen($sourceToken, 'UTF-8') >= 5) {
                foreach ($targetTokens as $targetToken) {
                    if (
                        abs(strlen($sourceToken) - strlen($targetToken)) <= 1
                        && levenshtein($sourceToken, $targetToken) <= 1
                    ) {
                        $matchedWeight += $weight * 0.75;
                        break;
                    }
                }
            }
        }

        return $totalWeight > 0 ? ($matchedWeight / $totalWeight) * 100 : 0.0;
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
You are KNOWURLOCAL's FAQ retrieval selector. Select the ONE supplied FAQ that best answers the user's intent.

Each FAQ has an ID, agency, English/Filipino questions, and keywords. Understand meaning, not just shared words. Handle English, Filipino, Taglish, typos, abbreviations, paraphrases, and incomplete questions. Keywords are hints only.

Choose only an ID present in the supplied catalogue. Always choose the closest available match; do not invent IDs, write an answer, or explain reasoning. Infer whether the user's question is English ("en") or Filipino/Taglish ("fil").

Return only this compact JSON object:
{"faq_id":123,"score":87,"language":"en"}
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
