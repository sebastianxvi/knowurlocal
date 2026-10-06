<?php

namespace App\Services;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * AI-first FAQ retrieval.
 *
 * The model is ONLY a selector. It never receives FAQ answers and therefore
 * cannot rewrite, summarize, or invent the response shown to the user.
 *
 * Flow:
 *   user question
 *      -> active FAQ catalog
     *      -> database-derived candidate retrieval
     *      -> one AI semantic selector
     *      -> validate selected FAQ id against that candidate set
 *      -> return the selected FAQ
 *      -> controller renders the stored/published FAQ response
 *
 * Deterministic matching exists only as a failure fallback when the AI
 * provider is unavailable or returns unusable output. It is never the normal
 * matching path and it is not used to pre-filter the AI catalog.
 */
class FaqChatbotService
{
    private const AI_MIN_CONFIDENCE = 0.45;
    private const MAX_FAQ_FIELD_LENGTH = 700;
    private const AI_MAX_CANDIDATES = 30;
    private const FALLBACK_MIN_CONFIDENCE = 0.50;

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

        $candidates = $this->rankCandidates($question, $agencyId, $eligible);

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

        // Provider outage / unusable model response only.
        $fallback = $this->deterministicFallback($question, $eligible);

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
                $questionScore = max(
                    $this->fieldSimilarity($question, (string) ($faq->question ?? '')),
                    $this->fieldSimilarity($question, (string) ($faq->question_fil ?? ''))
                );

                $keywordScore = $this->fieldSimilarity(
                    $question,
                    (string) ($faq->keywords ?? '')
                );

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

                /*
                 * Question meaning is the strongest local signal. Keywords and
                 * agency metadata help retrieve paraphrases without becoming
                 * hard-coded FAQ rules.
                 */
                $score = ($questionScore * 0.72)
                    + ($keywordScore * 0.20)
                    + ($agencyScore * 0.08);

                /*
                 * A clearly named agency should receive a small bonus, but
                 * never enough to override a substantially better question
                 * match from another agency.
                 */
                if (
                    $agencyId !== null
                    && $faq->agency_id !== null
                    && (int) $faq->agency_id === $agencyId
                ) {
                    $score += 0.05;
                }

                return [
                    'faq' => $faq,
                    'score' => min(1.0, $score),
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
        return Faq::query()
            ->with('agency:id,agency_name,agency_abbreviation')
            ->orderBy('id')
            ->get();
    }

    private function retrieveWithAi(string $question, ?int $agencyId, Collection $faqs): array
    {
        $payload = json_encode([
            'task' => 'select_the_single_best_existing_faq',
            'user_question' => $question,
            'current_agency_id' => $agencyId,
            'faq_candidates' => $this->buildCandidates($faqs),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return $this->decodeResponse($this->ai->chat([
            ['role' => 'system', 'content' => $this->retrievalPrompt()],
            ['role' => 'user', 'content' => $payload],
        ], 0.0, null));
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
            $questionScore = max(
                $this->fieldSimilarity($question, (string) ($faq->question ?? '')),
                $this->fieldSimilarity($question, (string) ($faq->question_fil ?? ''))
            );

            $keywordScore = $this->fieldSimilarity(
                $question,
                (string) ($faq->keywords ?? '')
            );

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

            $score = ($questionScore * 0.78)
                + ($keywordScore * 0.17)
                + ($agencyScore * 0.05);

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

Do not match merely because of shared words such as "documents", "registration", "government", or an agency name.
A question about SEnA must not be matched to RSBSA just because both have document requirements.
A duration question must not be matched to a requirements question merely because both mention the same program.

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

        return mb_strlen($value, 'UTF-8') > $max
            ? mb_substr($value, 0, $max, 'UTF-8') . '…'
            : $value;
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = function_exists('transliterator_transliterate')
            ? (transliterator_transliterate('Any-Latin; Latin-ASCII', $value) ?: $value)
            : $value;
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
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
        $tokens = array_filter($tokens, static fn (string $token): bool => mb_strlen($token, 'UTF-8') >= 3 && !in_array($token, $stop, true));

        return array_values(array_unique($tokens));
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
        $jaccard = $union > 0 ? $intersection / $union : 0.0;

        $fuzzy = 0.0;
        foreach ($left as $token) {
            $best = 0.0;
            foreach ($right as $candidateToken) {
                $max = max(strlen($token), strlen($candidateToken));
                if ($max === 0) {
                    continue;
                }
                $best = max($best, 1 - levenshtein($token, $candidateToken) / $max);
            }
            $fuzzy += $best;
        }

        $fuzzy /= count($left);

        return max($jaccard, $fuzzy * 0.8 + $jaccard * 0.2);
    }
}
