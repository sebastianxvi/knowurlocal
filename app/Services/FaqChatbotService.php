<?php

namespace App\Services;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Hybrid FAQ retrieval.
 *
 * The model is ONLY a selector. It never receives FAQ answers and therefore
 * cannot rewrite, summarize, or invent the response shown to the user.
 *
 * Flow:
 *   user question
 *      -> active FAQ catalog
 *      -> normalized local relevance retrieval
 *      -> bounded candidate set
 *      -> one AI semantic tie-breaker
 *      -> local-evidence guard
 *      -> stored/published FAQ response
 *
 * The local matcher is intentionally generic: it normalizes common language
 * and scores the live question, Filipino question, keywords, and agency
 * metadata. It contains no FAQ/program/agency-specific rules.
 */
class FaqChatbotService
{
    private const AI_MIN_CONFIDENCE = 0.45;
    private const MAX_FAQ_FIELD_LENGTH = 700;
    private const AI_MAX_CANDIDATES = 60;
    private const FALLBACK_MIN_CONFIDENCE = 0.50;

    /*
     * The AI is a semantic tie-breaker, not an unconditional override.
     * When the local evidence clearly favors another FAQ, accepting the
     * model's choice would make generic provider/model behavior capable of
     * returning the wrong approved answer.
     */
    private const AI_LOCAL_OVERRIDE_MARGIN = 0.10;

    public function __construct(
        private OpenRouterService $ai
    ) {
    }

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

        $eligible = $faqs
            ->filter(fn (Faq $faq): bool => $this->agencyAllowed($faq, $agencyId))
            ->values();

        if ($eligible->isEmpty()) {
            return null;
        }

        /*
         * Production-safe retrieval:
         *
         * 1. Resolve an exact FAQ from the live database immediately.
         * 2. Build a small database-derived candidate set using question,
         *    Filipino question, keywords, and agency metadata.
         * 3. Let AI make one semantic decision over that bounded set.
         *
         * This preserves AI matching while preventing a single chatbot
         * request from chaining several slow provider calls or sending the
         * entire FAQ table to OpenRouter.
         */
        $exactMatch = $this->findExactMatch($question, $eligible);

        if ($exactMatch !== null) {
            return $this->formatMatch(
                $exactMatch,
                1.0,
                $question,
                'exact',
                $this->detectLanguage($question, $exactMatch)
            );
        }

        try {
            $candidates = $this->rankCandidates($question, $agencyId, $eligible);
        } catch (\Throwable $e) {
            /*
             * Candidate ranking is an optimization layer, not a prerequisite
             * for answering from the FAQ database. If a malformed legacy value
             * or runtime capability prevents ranking, send a small live FAQ
             * catalog to the AI instead of converting the request into a
             * database-access error.
             */
            Log::warning('KNOWURLOCAL FAQ candidate ranking failed; using live FAQ catalog.', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'question' => $question,
                'agency_id' => $agencyId,
            ]);

            $candidates = $eligible
                ->sortByDesc('id')
                ->take(self::AI_MAX_CANDIDATES)
                ->values();
        }

        try {
            /*
             * Use exactly one provider call per chatbot request. The candidate
             * set is already narrowed from the live database, so the model can
             * spend its context on semantic matching instead of catalog search.
             */
            $directDecision = $this->retrieveWithAi(
                $question,
                $agencyId,
                $candidates
            );

            $directMatch = $this->resolveAiDecision(
                $directDecision,
                $question,
                $candidates
            );

            if ($directMatch !== null) {
                return $directMatch;
            }
        } catch (\Throwable $e) {
            Log::warning('KNOWURLOCAL FAQ AI matching failed; using deterministic fallback.', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'question' => $question,
                'agency_id' => $agencyId,
            ]);
        }

        /*
         * Provider outage / unusable model response only.
         *
         * Keep this fallback isolated as well. A failure in the optional
         * similarity calculation must never be reported to the browser as a
         * database outage.
         */
        try {
            $fallback = $this->deterministicFallback($question, $eligible);
        } catch (\Throwable $e) {
            Log::warning('KNOWURLOCAL deterministic FAQ fallback failed.', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'question' => $question,
                'agency_id' => $agencyId,
            ]);

            return null;
        }

        if ($fallback === null) {
            return null;
        }

        return $this->formatMatch(
            $fallback['faq'],
            $fallback['confidence'],
            $question,
            'fallback',
            $this->detectLanguage($question, $fallback['faq'])
        );
    }

    private function rankCandidates(
        string $question,
        ?int $agencyId,
        Collection $faqs
    ): Collection {
        return $faqs
            ->map(function (Faq $faq) use ($question, $agencyId): array {
                $score = $this->relevanceScore(
                    $question,
                    $faq,
                    $agencyId
                );

                return [
                    'faq' => $faq,
                    'score' => $score,
                ];
            })
            ->sortByDesc('score')
            ->take(self::AI_MAX_CANDIDATES)
            ->pluck('faq')
            ->values();
    }

    private function findExactMatch(string $question, Collection $faqs): ?Faq
    {
        $needle = $this->normalize($question);

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
                    && $this->normalize((string) $variant) === $needle
                ) {
                    return true;
                }
            }

            return false;
        });
    }

    private function buildCandidates(Collection $faqs): array
    {
        return $faqs->values()->map(function (Faq $faq, int $index): array {
            return [
                'candidate_index' => $index,
                'faq_id' => (int) $faq->id,
                'agency_id' => $faq->agency_id !== null ? (int) $faq->agency_id : null,
                'agency' => $this->limit((string) ($faq->agency?->agency_name ?? ''), 220),
                'agency_abbreviation' => $this->limit((string) ($faq->agency?->agency_abbreviation ?? ''), 80),
                'keywords' => $this->limit((string) ($faq->keywords ?? ''), self::MAX_FAQ_FIELD_LENGTH),
                'question_en' => $this->limit((string) ($faq->question ?? ''), self::MAX_FAQ_FIELD_LENGTH),
                'question_fil' => $this->limit((string) ($faq->question_fil ?? ''), self::MAX_FAQ_FIELD_LENGTH),
            ];
        })->all();
    }

    private function loadFaqCatalog(): Collection
    {
        /*
         * The agency abbreviation is useful metadata but it is not required
         * to retrieve an FAQ. Some older production databases may predate the
         * abbreviation column even though the current application can operate
         * without it. Retry the catalog without that optional field rather than
         * turning a valid FAQ lookup into a generic database error.
         */
        try {
            return Faq::query()
                ->with('agency:id,agency_name,agency_abbreviation')
                ->orderBy('id')
                ->get();
        } catch (\Throwable $e) {
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

    private function retrieveWithAi(string $question, ?int $agencyId, Collection $faqs): array
    {
        $payload = json_encode([
            'task' => 'select_the_single_best_existing_faq',
            'user_question' => $question,
            'current_agency_id' => $agencyId,
            'faq_candidates' => $this->buildCandidates($faqs),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return $this->decodeResponse($this->ai->chat(
            [
                ['role' => 'system', 'content' => $this->retrievalPrompt()],
                ['role' => 'user', 'content' => $payload],
            ],
            0.0,
            null,
            7,
            2,
            1
        ));
    }

    private function resolveAiDecision(array $decision, string $question, Collection $faqs): ?array
    {
        $index = $decision['candidate_index'] ?? null;
        $faq = null;

        if (is_numeric($index)) {
            $faq = $faqs->get((int) $index);
        }

        if (!$faq && isset($decision['faq_id']) && is_numeric($decision['faq_id'])) {
            $faq = $faqs->firstWhere('id', (int) $decision['faq_id']);
        }

        if (!$faq) {
            return null;
        }

        $confidence = $decision['confidence'] ?? null;
        if (!is_numeric($confidence)) {
            return null;
        }

        $confidence = (float) $confidence;
        if ($confidence > 1 && $confidence <= 100) {
            $confidence /= 100;
        }

        if ($confidence < self::AI_MIN_CONFIDENCE) {
            return null;
        }

        /*
         * Hybrid retrieval guard:
         *
         * AI is the semantic tie-breaker, not an unconditional override.
         * If the live database evidence clearly favors another candidate,
         * reject the model choice and let the deterministic path select the
         * stronger FAQ. This prevents provider/model variance from returning
         * a generic FAQ simply because it shares the same program name.
         */
        $selectedScore = $this->relevanceScore(
            $question,
            $faq,
            null
        );

        $bestLocalScore = $faqs->max(
            fn (Faq $candidate): float => $this->relevanceScore(
                $question,
                $candidate,
                null
            )
        );

        if (
            $bestLocalScore > $selectedScore
            && ($bestLocalScore - $selectedScore) >= self::AI_LOCAL_OVERRIDE_MARGIN
        ) {
            Log::info('KNOWURLOCAL AI FAQ choice rejected by stronger local evidence.', [
                'selected_faq_id' => $faq->id,
                'selected_score' => round($selectedScore, 4),
                'best_local_score' => round($bestLocalScore, 4),
                'question' => $question,
            ]);

            return null;
        }

        /*
         * If the model provides a runner-up, require a meaningful semantic
         * margin unless the top score is extremely strong. This prevents a
         * generic FAQ from winning simply because it is the closest topic.
         */
        $runnerUp = $decision['runner_up_confidence'] ?? null;
        if (is_numeric($runnerUp)) {
            $runnerUp = (float) $runnerUp;
            if ($runnerUp > 1 && $runnerUp <= 100) {
                $runnerUp /= 100;
            }

            if ($confidence < 0.82 && ($confidence - $runnerUp) < 0.04) {
                return null;
            }
        }

        $language = strtolower(trim((string) ($decision['language'] ?? '')));
        if (!in_array($language, ['en', 'fil'], true)) {
            $language = $this->detectLanguage($question, $faq);
        }

        return $this->formatMatch($faq, $confidence, $question, 'semantic', $language);
    }

    /**
     * Conservative fallback used only when AI cannot be used.
     * This is intentionally small and does not contain a predefined FAQ map.
     */
    private function deterministicFallback(string $question, Collection $faqs): ?array
    {
        $needle = $this->normalize($question);

        if ($needle === '') {
            return null;
        }

        $best = null;
        $bestScore = 0.0;

        foreach ($faqs as $faq) {
            $score = $this->relevanceScore(
                $question,
                $faq,
                null
            );

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $faq;
            }
        }

        if ($best === null || $bestScore < self::FALLBACK_MIN_CONFIDENCE) {
            return null;
        }

        /*
         * A keyword-heavy match is useful during an AI outage, but generic
         * words must not be enough to select an FAQ. Require a meaningful
         * question match unless the overall score is exceptionally strong.
         */
        $questionScore = max(
            $this->fieldSimilarity($question, (string) ($best->question ?? '')),
            $this->fieldSimilarity($question, (string) ($best->question_fil ?? ''))
        );

        if ($questionScore < 0.50 && $bestScore < 0.72) {
            return null;
        }

        return [
            'faq' => $best,
            'confidence' => min(1.0, $bestScore),
        ];
    }

    private function formatMatch(
        Faq $faq,
        float $confidence,
        string $question,
        string $method,
        ?string $language = null
    ): array {
        return [
            'faq' => $faq,
            'confidence' => max(0.0, min(1.0, $confidence)),
            'language' => $language ?? $this->detectLanguage($question, $faq),
            'method' => $method,
        ];
    }

    private function agencyAllowed(Faq $faq, ?int $agencyId): bool
    {
        return $agencyId === null
            || $faq->agency_id === null
            || (int) $faq->agency_id === $agencyId;
    }

    private function detectLanguage(string $question, Faq $faq): string
    {
        $en = $this->fieldSimilarity($question, (string) ($faq->question ?? ''));
        $fil = $this->fieldSimilarity($question, (string) ($faq->question_fil ?? ''));

        return $fil > $en ? 'fil' : 'en';
    }

    private function retrievalPrompt(): string
    {
        return <<<'PROMPT'
You are KNOWURLOCAL's semantic FAQ selector.

The application has already supplied a database-derived candidate set. Select the single existing FAQ that best answers the user's actual question.
You are not an answer generator. FAQ answers are not supplied to you.

Understand:
- paraphrases
- typos
- English, Filipino, and Taglish
- abbreviations
- intent differences
- specific program/service names
- important qualifiers

Do not match merely because of shared topic words such as "documents",
"registration", "government", or an agency name.

Prioritize the user's actual intent and qualifiers. For example, distinguish:
- requirements/documents from eligibility/application
- processing time/duration from requirements
- appointment/scheduling from application
- fees/cost from general service information

The candidate's keywords are retrieval metadata and should be treated as
strong evidence when they contain the user's specific intent or service term.

Read ALL candidates. If no FAQ genuinely answers the question, return null.

OUTPUT ONLY JSON:
{"candidate_index":12,"confidence":0.94,"runner_up_confidence":0.30,"language":"en"}
or
{"candidate_index":null,"confidence":0,"runner_up_confidence":0,"language":"en"}
PROMPT;
    }

    private function decodeResponse(array $response): array
    {
        $content = data_get($response, 'choices.0.message.content');

        if (is_array($content)) {
            $content = collect($content)
                ->map(fn ($part) => is_string($part) ? $part : (string) data_get($part, 'text', ''))
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
            // Free routed models occasionally add a short sentence around the
            // JSON. Recover only the first JSON object; never parse arbitrary
            // prose as an FAQ decision.
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

    private function clean(string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
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

    private function normalize(string $value): string
    {
        $value = trim(
            function_exists('mb_strtolower')
                ? mb_strtolower($value, 'UTF-8')
                : strtolower($value)
        );

        /*
         * Normalize common public-service language before tokenization.
         *
         * These are language/intent equivalences, not FAQ-specific rules.
         * They allow a future FAQ such as "What documents are needed?" to
         * match a user saying "What are the requirements?" without knowing
         * anything about the agency or program in advance.
         */
        $replacements = [
            // Requirements / needed / required
            '/\\brequirements?\\b/u' => 'requirement',
            '/\\brequired\\b/u' => 'requirement',
            '/\\bneeded\\b/u' => 'requirement',
            '/\\bneed\\b/u' => 'requirement',
            '/\\brequire\\b/u' => 'requirement',
            '/\\bkailangan\\b/u' => 'requirement',
            '/\\bkinakailangan\\b/u' => 'requirement',

            // Documents / papers
            '/\\bdocuments?\\b/u' => 'document',
            '/\\bdocumentary\\b/u' => 'document',
            '/\\bdokumento(?:ng)?\\b/u' => 'document',
            '/\\bdokumentos?\\b/u' => 'document',
            '/\\bpapeles?\\b/u' => 'document',

            // Application / apply
            '/\\bapplications?\\b/u' => 'application',
            '/\\bapplying\\b/u' => 'application',
            '/\\bapply\\b/u' => 'application',
            '/\\bmag[-\\s]?apply\\b/u' => 'application',
            '/\\bpag[-\\s]?apply\\b/u' => 'application',

            // Assistance / help
            '/\\bassistance\\b/u' => 'assistance',
            '/\\bhelp\\b/u' => 'assistance',
            '/\\btulong\\b/u' => 'assistance',

            // Registration
            '/\\bregistrations?\\b/u' => 'registration',
            '/\\bregister\\b/u' => 'registration',
            '/\\bregistered\\b/u' => 'registration',
            '/\\bmag[-\\s]?register\\b/u' => 'registration',
            '/\\bpag[-\\s]?register\\b/u' => 'registration',

            // Processing / duration
            '/\\bprocessing\\b/u' => 'processing',
            '/\\bprocess(?:ing)?\\b/u' => 'processing',
            '/\\bhow[-\\s]+long\\b/u' => 'duration',
            '/\\bhow[-\\s]+many[-\\s]+days?\\b/u' => 'duration',
            '/\\bduration\\b/u' => 'duration',
            '/\\btagal\\b/u' => 'duration',
            '/\\bgaano[-\\s]+katagal\\b/u' => 'duration',

            // Fees / cost
            '/\\bfees?\\b/u' => 'fee',
            '/\\bcosts?\\b/u' => 'fee',
            '/\\bprice\\b/u' => 'fee',
            '/\\bmagkano\\b/u' => 'fee',

            // Appointment / scheduling
            '/\\bappointments?\\b/u' => 'appointment',
            '/\\bscheduling\\b/u' => 'appointment',
            '/\\bschedule\\b/u' => 'appointment',
            '/\\bmag[-\\s]?pa[-\\s]?appointment\\b/u' => 'appointment',

            // Eligibility / qualification
            '/\\beligibility\\b/u' => 'eligibility',
            '/\\beligible\\b/u' => 'eligibility',
            '/\\bqualified\\b/u' => 'eligibility',
            '/\\bqualifications?\\b/u' => 'eligibility',
            '/\\bkwalipikado\\b/u' => 'eligibility',
            '/\\bkarapat[-\\s]?dapat\\b/u' => 'eligibility',

            // Obtain / get
            '/\\bobtain(?:ed|ing)?\\b/u' => 'obtain',
            '/\\bget\\b/u' => 'obtain',
            '/\\bkuha(?:n|hin)?\\b/u' => 'obtain',
            '/\\bkumuha\\b/u' => 'obtain',
            '/\\bmakakuha\\b/u' => 'obtain',
        ];

        $value = preg_replace(
            array_keys($replacements),
            array_values($replacements),
            $value
        ) ?? $value;

        if (function_exists('transliterator_transliterate')) {
            $value = transliterator_transliterate(
                'Any-Latin; Latin-ASCII',
                $value
            ) ?: $value;
        }

        $value = preg_replace('/[^\\p{L}\\p{N}]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\\s+/u', ' ', $value) ?? '';

        return trim($value);
    }

    private function tokens(string $value): array
    {
        $normalized = $this->normalize($value);
        if ($normalized === '') {
            return [];
        }

        $stop = [
            'the','a','an','and','are','can','could','do','does','for','from','how','i','in','is','it','me','my','of','on','or','to','what','when','where','which','who','with','would','you','your',
            'ang','ano','ay','ba','bakit','dahil','gaano','ito','iyan','iyon','ko','kung','mga','mo','na','ng','ni','nito','natin','para','po','opo','pwede','saan','sa','si','sila','at','o','may','mag','pag',
        ];

        $tokens = preg_split('/\s+/u', $normalized) ?: [];
        $tokens = array_filter($tokens, function (string $token) use ($stop): bool {
            $length = function_exists('mb_strlen')
                ? mb_strlen($token, 'UTF-8')
                : strlen($token);

            return $length >= 3 && !in_array($token, $stop, true);
        });

        return array_values(array_unique($tokens));
    }

    private function relevanceScore(
        string $question,
        Faq $faq,
        ?int $agencyId = null
    ): float {
        $questionScore = max(
            $this->fieldSimilarity(
                $question,
                (string) ($faq->question ?? '')
            ),
            $this->fieldSimilarity(
                $question,
                (string) ($faq->question_fil ?? '')
            )
        );

        $keywordScore = $this->fieldSimilarity(
            $question,
            (string) ($faq->keywords ?? '')
        );

        /*
         * Question intent gets the largest weight. Keywords are still
         * important because administrators deliberately enter them as
         * retrieval metadata (for example "requirements", "documents",
         * "processing time", etc.). Agency metadata disambiguates otherwise
         * similar services.
         */
        $agencyScore = max(
            $this->fieldSimilarity(
                $question,
                (string) ($faq->agency?->agency_name ?? '')
            ),
            $this->fieldSimilarity(
                $question,
                (string) ($faq->agency?->agency_abbreviation ?? '')
            )
        );

        $score = ($questionScore * 0.68)
            + ($keywordScore * 0.24)
            + ($agencyScore * 0.08);

        if (
            $agencyId !== null
            && $faq->agency_id !== null
            && (int) $faq->agency_id === $agencyId
        ) {
            $score += 0.05;
        }

        return min(1.0, $score);
    }

    private function fieldSimilarity(string $source, string $candidate): float
    {
        $left = $this->tokens($source);
        $right = $this->tokens($candidate);

        if ($left === [] || $right === []) {
            return 0.0;
        }

        $intersection = count(array_intersect($left, $right));
        $union = count(array_unique(array_merge($left, $right)));

        if ($union === 0) {
            return 0.0;
        }

        /*
         * Keep the local retrieval path intentionally cheap and portable.
         *
         * The previous implementation performed a Levenshtein comparison
         * between every pair of tokens for every FAQ. On a serverless
         * function that can become surprisingly expensive as the FAQ catalog
         * grows. AI remains responsible for semantic matching; this score only
         * narrows the live database catalog to a reasonable candidate set.
         */
        $jaccard = $intersection / $union;
        $coverage = $intersection / max(1, count($left));

        return min(1.0, ($jaccard * 0.55) + ($coverage * 0.45));
    }
}
