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
 *      -> AI semantic selector (all eligible FAQs)
 *      -> validate selected FAQ id against that catalog
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
    private const AI_PROFILE_MIN_CONFIDENCE = 0.35;
    private const AI_MAX_CANDIDATES_PER_BATCH = 90;

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
         * AI owns normal retrieval. We deliberately do not use lexical
         * pre-filtering, keyword maps, entity maps, or hard-coded intents.
         *
         * The first AI pass turns messy natural language into a compact
         * semantic representation. The second AI pass compares that meaning
         * against the complete live FAQ catalog. This makes paraphrases,
         * typos, Taglish, and newly-created FAQs first-class inputs.
         */
        try {
            $profile = $this->understandQuestion($question, $agencyId);

            if ($this->profileIsUsable($profile)) {
                $decision = $this->matchProfileAgainstCatalog(
                    $question,
                    $profile,
                    $agencyId,
                    $eligible
                );

                $match = $this->resolveAiDecision($decision, $question, $eligible);

                if ($match !== null) {
                    return $match;
                }
            }

            /*
             * If the semantic-profile call or its decision is inconclusive,
             * give the model one direct matching attempt. This is still AI
             * matching; it is not a rule-based substitute.
             */
            $directDecision = $this->retrieveWithAi($question, $agencyId, $eligible);
            $directMatch = $this->resolveAiDecision(
                $directDecision,
                $question,
                $eligible
            );

            if ($directMatch !== null) {
                return $directMatch;
            }

            /*
             * Free routed models can occasionally under-rank a short,
             * typo-heavy paraphrase even when the semantic profile is correct.
             * Give the same user request one final AI recovery pass so the
             * user never has to repeat the question just to get a match.
             */
            $recoveryDecision = $this->retrieveWithAiRecovery(
                $question,
                $profile ?? [],
                $agencyId,
                $eligible
            );
            $recoveryMatch = $this->resolveAiDecision(
                $recoveryDecision,
                $question,
                $eligible
            );

            if ($recoveryMatch !== null) {
                return $recoveryMatch;
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

    private function understandQuestion(string $question, ?int $agencyId): array
    {
        $payload = json_encode([
            'task' => 'understand_user_faq_question',
            'user_question' => $question,
            'current_agency_id' => $agencyId,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return $this->decodeResponse($this->ai->chat([
            ['role' => 'system', 'content' => $this->semanticProfilePrompt()],
            ['role' => 'user', 'content' => $payload],
        ], 0.1, null));
    }

    private function profileIsUsable(array $profile): bool
    {
        $confidence = $profile['confidence'] ?? 0;

        return is_numeric($confidence)
            && (float) $confidence >= self::AI_PROFILE_MIN_CONFIDENCE
            && (
                filled($profile['semantic_question'] ?? null)
                || filled($profile['intent'] ?? null)
                || !empty($profile['entities'] ?? [])
            );
    }

    private function matchProfileAgainstCatalog(
        string $question,
        array $profile,
        ?int $agencyId,
        Collection $faqs
    ): array {
        /*
         * For the current catalog this is one AI call. If the catalog grows
         * beyond the safe batch size, rank each AI batch and then let AI make
         * a final semantic decision over the batch winners. No FAQ is removed
         * using local/rule-based matching.
         */
        $chunks = $faqs->values()->chunk(self::AI_MAX_CANDIDATES_PER_BATCH);

        if ($chunks->count() === 1) {
            return $this->askAiToRankProfile(
                $question,
                $profile,
                $agencyId,
                $chunks->first()
            );
        }

        $winners = collect();

        foreach ($chunks as $chunk) {
            $decision = $this->askAiToRankProfile(
                $question,
                $profile,
                $agencyId,
                $chunk
            );

            $index = $decision['candidate_index'] ?? null;
            if (is_numeric($index)) {
                $faq = $chunk->get((int) $index);
                if ($faq) {
                    $winners->push($faq);
                }
            } elseif (isset($decision['faq_id']) && is_numeric($decision['faq_id'])) {
                $faq = $chunk->firstWhere('id', (int) $decision['faq_id']);
                if ($faq) {
                    $winners->push($faq);
                }
            }
        }

        if ($winners->isEmpty()) {
            return [
                'candidate_index' => null,
                'confidence' => 0,
                'language' => $profile['language'] ?? 'en',
            ];
        }

        return $this->askAiToRankProfile(
            $question,
            $profile,
            $agencyId,
            $winners->values()
        );
    }

    private function askAiToRankProfile(
        string $question,
        array $profile,
        ?int $agencyId,
        Collection $faqs
    ): array {
        $candidates = $this->buildCandidates($faqs);

        $payload = json_encode([
            'task' => 'select_best_faq_using_semantic_profile',
            'user_question' => $question,
            'semantic_profile' => $profile,
            'current_agency_id' => $agencyId,
            'faq_candidates' => $candidates,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return $this->decodeResponse($this->ai->chat([
            ['role' => 'system', 'content' => $this->profileMatchingPrompt()],
            ['role' => 'user', 'content' => $payload],
        ], 0.0, null));
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

    private function retrieveWithAiRecovery(
        string $question,
        array $profile,
        ?int $agencyId,
        Collection $faqs
    ): array {
        $payload = json_encode([
            'task' => 'recover_a_semantic_faq_match_without_forcing_an_answer',
            'user_question' => $question,
            'semantic_profile' => $profile,
            'current_agency_id' => $agencyId,
            'faq_candidates' => $this->buildCandidates($faqs),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return $this->decodeResponse($this->ai->chat([
            ['role' => 'system', 'content' => $this->recoveryPrompt()],
            ['role' => 'user', 'content' => $payload],
        ], 0.0, null));
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

        foreach ($faqs as $faq) {
            foreach ([$faq->question, $faq->question_fil] as $variant) {
                if ($variant !== null && $this->normalize((string) $variant) === $needle) {
                    return [
                        'faq' => $faq,
                        'confidence' => 1.0,
                    ];
                }
            }
        }

        // Small typo-tolerant recovery for provider outages. This is generic
        // and derives candidates from the current database, so new FAQs are
        // automatically covered without code changes.
        $best = null;
        $bestScore = 0.0;

        foreach ($faqs as $faq) {
            $score = max(
                $this->fieldSimilarity($question, (string) ($faq->question ?? '')),
                $this->fieldSimilarity($question, (string) ($faq->question_fil ?? ''))
            );

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $faq;
            }
        }

        return $best !== null && $bestScore >= 0.82
            ? ['faq' => $best, 'confidence' => $bestScore]
            : null;
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

    private function semanticProfilePrompt(): string
    {
        return <<<'PROMPT'
You are KNOWURLOCAL's semantic query-understanding engine.

Your job is to understand what the USER is asking so another AI can match it to an existing FAQ.

Do NOT answer the user.
Do NOT invent facts.
Do NOT use any FAQ answer because none is supplied.

Normalize meaning, not wording. Handle:
- spelling mistakes and missing letters
- abbreviations
- English, Filipino, Taglish
- informal speech
- grammatical mistakes
- singular/plural differences
- indirect questions
- "how long" / "how many days" / "what timeframe" as the same duration intent when appropriate
- "what paperwork" / "what documents" / "what do I need to submit" as requirements when appropriate
- eligibility questions such as "can I", "am I allowed", "do I qualify"
- procedure questions such as "how do I", "where do I start", "what is the process"

Identify:
- the semantic_question: a concise normalized representation of the user's actual information need
- intent: the requested information type
- entities: named programs, services, agencies, acronyms, offices, or other specific subjects
- constraints: important qualifiers such as tenant farmer, new learner, location, timeframe, etc.
- language: en or fil
- confidence: confidence that you understood the question, not whether an FAQ exists

IMPORTANT:
"SEnA", "RSBSA", "NIA", etc. are not interchangeable just because they occur in government FAQs.
Preserve every meaningful entity and qualifier.
Do not turn the question into a broad topic such as "government documents".

OUTPUT ONLY JSON:
{
  "semantic_question":"...",
  "intent":"...",
  "entities":["..."],
  "constraints":["..."],
  "language":"en",
  "confidence":0.95
}
PROMPT;
    }

    private function profileMatchingPrompt(): string
    {
        return <<<'PROMPT'
You are KNOWURLOCAL's AI FAQ retrieval engine.

The application supplies:
1. the original user question,
2. an AI-generated semantic profile of that question,
3. the COMPLETE current FAQ candidate set.

Your ONLY job is to select the existing FAQ that best answers the SAME INFORMATION NEED.

The answer itself is NOT supplied and must never be invented. The application will fetch the approved stored answer after you select an FAQ.

MATCHING RULES:
- Read every candidate before deciding.
- Match meaning, not literal wording.
- Treat ordinary spelling mistakes as noise when context makes the intended meaning clear.
- Use the semantic profile to understand paraphrases, but always check it against the original user question.
- Preserve specific entities. If the user asks about SEnA, an RSBSA FAQ is not a match merely because both involve documents or government services.
- Preserve intent. Duration, requirements, eligibility, procedure, location, fees, status, and other intents are different even within the same program.
- Preserve qualifiers. "tenant farmer", "farm worker", "does not own land", etc. can distinguish FAQs that otherwise share the same topic.
- Keywords are supporting evidence, never the deciding factor by themselves.
- Agency is supporting evidence unless the user's question clearly identifies that agency/program.
- Prefer a semantically precise FAQ over a generic FAQ with more shared words.
- A candidate must actually answer the user's question, not merely discuss the same topic.
- If none genuinely answers it, return null.
- Do not force a match just because a candidate is the closest available topic.

TYPO/NOISY INPUT:
Interpret obvious errors such as:
"dayz" -> days
"tak" -> take
"concilliation" -> conciliation
"documnts" -> documents
"regster" -> register
but only when the surrounding sentence supports that interpretation. Do not reject a question simply because several words are misspelled.

RANKING:
Consider, in order:
1. exact information need / intent
2. specific entities and program/service
3. important qualifiers
4. agency context
5. useful keywords
6. wording similarity

Return the best candidate plus the runner-up confidence so the server can detect close/ambiguous choices.

OUTPUT ONLY JSON:
{
  "candidate_index":12,
  "confidence":0.94,
  "runner_up_confidence":0.31,
  "language":"en"
}

If there is no genuine match:
{
  "candidate_index":null,
  "confidence":0,
  "runner_up_confidence":0,
  "language":"en"
}
PROMPT;
    }

    private function recoveryPrompt(): string
    {
        return <<<'PROMPT'
You are the final recovery pass for KNOWURLOCAL FAQ retrieval.

The user may have typed a short, informal, misspelled, or heavily paraphrased
version of an existing FAQ question. Your job is to recover the correct EXISTING
FAQ when the semantic meaning is genuinely the same.

Use the original question and semantic profile together. Ignore harmless spelling
noise such as missing letters, phonetic spellings, repeated/missing characters,
and informal grammar when the intended meaning is clear. Examples include
"dayz" for "days", "tenent" for "tenant", "regster" for "register", and
"concilliation" for "conciliation". Do not require the user to use the FAQ's
exact wording.

Match the actual information need first: duration must match duration, eligibility
must match eligibility, requirements must match requirements, procedure must match
procedure, and so on. Preserve named programs, agencies, and important qualifiers.
A shared word like "documents", "registration", or "government" is not enough.

This is a recovery pass, not an answer generator. FAQ answers are not supplied.
If one candidate clearly expresses the same information need, select it even if
the wording overlap is low. If none genuinely matches, return null.

OUTPUT ONLY JSON:
{"candidate_index":12,"confidence":0.88,"runner_up_confidence":0.20,"language":"en"}
or
{"candidate_index":null,"confidence":0,"runner_up_confidence":0,"language":"en"}
PROMPT;
    }

    private function retrievalPrompt(): string
    {
        return <<<'PROMPT'
You are KNOWURLOCAL's fallback semantic FAQ selector.

Select the single existing FAQ that best answers the user's actual question.
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
