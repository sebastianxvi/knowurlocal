<?php

namespace App\Services;

use App\Models\Faq;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * AI-only FAQ retrieval for the public KNOWURLOCAL chatbot.
 *
 * The AI is a retrieval/judging layer only:
 *
 *     user question
 *          -> AI compares against the FAQ catalog
 *          -> AI returns an existing FAQ id
 *          -> Laravel loads that FAQ
 *          -> Laravel returns the stored answer
 *
 * The model is never allowed to generate the public answer.
 */
class FaqChatbotService
{
    /**
     * The current FAQ dataset is small enough to send as one retrieval catalog.
     *
     * Keeping the catalog in one request is deliberate. A multi-stage
     * chunk/shortlist pipeline can discard the correct FAQ before the final
     * judge ever sees it.
     */
    private const MAX_CATALOG_RECORDS = 500;

    /**
     * AI confidence is metadata, but an extremely low-confidence selection
     * is not safe to publish as a retrieval result.
     */
    private const MIN_AI_CONFIDENCE = 0.65;

    public function __construct(
        private OpenRouterService $ai
    ) {
    }

    /**
     * Find an existing FAQ using AI semantic retrieval.
     *
     * The AI receives only retrieval evidence: question variants, agency
     * metadata, and administrator keywords. It never receives answer text,
     * because the answer must come exclusively from the published FAQ version.
     *
     * A small deterministic fallback is used only when the AI provider is
     * unavailable. This keeps the chatbot usable without ever generating or
     * rewriting a response.
     */
    public function findMatch(string $question, ?int $agencyId = null): ?array
    {
        $question = trim($question);

        if ($question === '') {
            return null;
        }

        $faqs = $this->loadFaqCatalog();

        if ($faqs->isEmpty()) {
            return null;
        }

        if ($faqs->count() > self::MAX_CATALOG_RECORDS) {
            throw new RuntimeException(
                'The FAQ catalog is too large for the configured AI retrieval catalog.'
            );
        }

        $candidates = $faqs
            ->map(fn (Faq $faq) => $this->toCandidate($faq))
            ->values()
            ->all();

        $payload = $this->encode([
            'user_question' => $question,
            'current_agency_id' => $agencyId,
            'faq_candidates' => $candidates,
        ]);

        try {
            $response = $this->ai->chat(
                [
                    [
                        'role' => 'system',
                        'content' => $this->retrievalPrompt(),
                    ],
                    [
                        'role' => 'user',
                        'content' => $payload,
                    ],
                ],
                0.0,
                ['type' => 'json_object']
            );

            $decision = $this->decodeResponse($response);

            $match = $this->resolveDecision($decision, $faqs, $question);

            if ($match !== null) {
                return $match;
            }
        } catch (\Throwable $e) {
            // AI retrieval is optional; the public answer must still come
            // from the database if a provider/network failure occurs.
            report($e);
        }

        return $this->fallbackMatch($question, $agencyId, $faqs);
    }

    /**
     * Load the FAQ catalog without making optional newer columns a hard
     * dependency for chatbot retrieval.
     *
     * The core FAQ fields have existed since the original FAQ migration.
     * Filipino fields, images, and response components were added later,
     * so the chatbot tolerates an environment whose migration history is
     * one step behind instead of converting a valid FAQ lookup into a 500.
     */
    private function loadFaqCatalog(): Collection
    {
        // Only retrieval evidence belongs in the AI catalog. Response content
        // is deliberately excluded; it is fetched from the published version
        // only after an FAQ has been selected.
        $faqColumns = [
            'id',
            'agency_id',
            'question',
            'keywords',
        ];

        foreach ([
            'question_fil',
            'current_version_id',
        ] as $optionalColumn) {
            if (Schema::hasColumn('faqs', $optionalColumn)) {
                $faqColumns[] = $optionalColumn;
            }
        }

        $agencyColumns = [
            'id',
            'agency_name',
        ];

        if (Schema::hasColumn('agencies', 'agency_abbreviation')) {
            $agencyColumns[] = 'agency_abbreviation';
        }

        $query = Faq::query()
            ->with([
                'agency' => function ($query) use ($agencyColumns) {
                    $query->select($agencyColumns);
                },
                // The controller needs the exact published version that the
                // AI-selected FAQ points to. Eager-loading it also avoids an
                // extra query for every matched FAQ.
                'currentVersion',
            ])
            ->select($faqColumns);

        // An FAQ without a published version cannot produce a valid public
        // chatbot answer. Never expose such a row to the retrieval model or
        // deterministic fallback. This turns version integrity into an
        // invariant of the retrieval catalog instead of a late controller
        // failure.
        if (in_array('current_version_id', $faqColumns, true)) {
            $query
                ->whereNotNull('current_version_id')
                ->whereHas('currentVersion');
        }

        return $query->get();
    }

    /**
     * Convert a database FAQ into compact semantic evidence.
     *
     * Answers are evidence only. They are never sent back as model output.
     */
    private function toCandidate(Faq $faq): array
    {
        return [
            'id' => (int) $faq->id,
            'agency_id' => $faq->agency_id !== null
                ? (int) $faq->agency_id
                : null,
            'agency' => $this->text(
                $faq->agency?->agency_name,
                300
            ),
            'agency_abbreviation' => $this->text(
                $faq->agency?->agency_abbreviation,
                100
            ),
            'keywords' => $this->text(
                $faq->keywords,
                700
            ),
            'question_en' => $this->text(
                $faq->question,
                1000
            ),
            'question_fil' => $this->text(
                $faq->question_fil,
                1000
            ),
        ];
    }

    /**
     * Resolve the AI's selection against the exact collection loaded from
     * the database. The AI cannot invent an answer or an arbitrary FAQ id.
     */
    private function resolveDecision(
        array $decision,
        Collection $faqs,
        string $question
    ): ?array {
        $faqId = $decision['faq_id'] ?? null;

        if ($faqId === null || $faqId === '' || !is_numeric($faqId)) {
            return null;
        }

        $faq = $faqs->firstWhere('id', (int) $faqId);

        if (!$faq) {
            throw new RuntimeException(
                'FAQ AI selected an ID outside the database catalog.'
            );
        }

        /*
         * Confidence is no longer just analytics. A low-confidence selection
         * must not become a public answer simply because the model supplied a
         * valid FAQ id.
         */
        $rawConfidence = $decision['confidence'] ?? 0.0;

        if (!is_numeric($rawConfidence)) {
            return null;
        }

        $confidence = (float) $rawConfidence;

        if ($confidence > 1.0 && $confidence <= 100.0) {
            $confidence /= 100.0;
        }

        $confidence = max(0.0, min(1.0, $confidence));

        if ($confidence < self::MIN_AI_CONFIDENCE) {
            return null;
        }

        /*
         * Do not blindly trust a semantic model selection. If the wording is
         * already lexically close, it is safe to accept it directly. If it is
         * a looser paraphrase, run a second, narrowly-scoped verification
         * step that checks whether the candidate answers the SAME INFORMATION
         * REQUEST rather than merely sharing the same agency/topic.
         */
        if (!$this->hasStrongLexicalEvidence($question, $faq)) {
            if (!$this->verifyAiSelection($question, $faq)) {
                return null;
            }
        }

        $language = strtolower(
            trim((string) ($decision['language'] ?? 'en'))
        );

        $language = match ($language) {
            'fil', 'filipino', 'tagalog', 'taglish' => 'fil',
            default => 'en',
        };

        return [
            'faq' => $faq,
            'confidence' => $confidence,
            'language' => $language,
            'method' => 'semantic',
        ];
    }

    /**
     * Strong lexical evidence means the user's wording is already close
     * enough to the FAQ that a second model call is unnecessary.
     *
     * This is deliberately stricter than the fallback scorer. The fallback
     * may select a best candidate only when the AI provider is unavailable;
     * this gate protects against an over-eager AI selection.
     */
    private function hasStrongLexicalEvidence(string $question, Faq $faq): bool
    {
        $questionTokens = $this->tokens($question);

        if ($questionTokens === []) {
            return false;
        }

        $variants = array_filter([
            (string) $faq->question,
            (string) ($faq->question_fil ?? ''),
        ], static fn (string $value): bool => trim($value) !== '');

        foreach ($variants as $variant) {
            $variantTokens = $this->tokens($variant);

            if ($variantTokens === []) {
                continue;
            }

            if ($this->normalize($variant) === $this->normalize($question)) {
                return true;
            }

            $similarity = $this->tokenSimilarity($questionTokens, $variantTokens);
            $coverage = $this->tokenCoverage($questionTokens, $variantTokens);

            if ($similarity >= 0.45 || $coverage >= 0.70) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ask the model to verify only the selected FAQ against the user's
     * question. This second pass is intentionally answer-free and much
     * stricter than the broad catalog retrieval prompt.
     */
    private function verifyAiSelection(string $question, Faq $faq): bool
    {
        try {
            $payload = $this->encode([
                'user_question' => $question,
                'candidate_faq' => $this->toCandidate($faq),
            ]);

            $response = $this->ai->chat(
                [
                    [
                        'role' => 'system',
                        'content' => $this->verificationPrompt(),
                    ],
                    [
                        'role' => 'user',
                        'content' => $payload,
                    ],
                ],
                0.0,
                ['type' => 'json_object']
            );

            $decision = $this->decodeResponse($response);

            return ($decision['match'] ?? false) === true;
        } catch (\Throwable $e) {
            // If verification cannot be completed, fail closed rather than
            // turning an unverified semantic guess into a public answer.
            report($e);
            return false;
        }
    }

    /**
     * Conservative local fallback used only when AI retrieval cannot run.
     * It never creates an answer; it only selects an existing FAQ using the
     * same question/agency/keyword evidence exposed to the AI.
     */
    private function fallbackMatch(
        string $question,
        ?int $agencyId,
        Collection $faqs
    ): ?array {
        $normalizedQuestion = $this->normalize($question);
        $questionTokens = $this->tokens($question);

        if ($normalizedQuestion === '' || $questionTokens === []) {
            return null;
        }

        $best = null;

        foreach ($faqs as $faq) {
            $questionVariants = array_filter([
                [
                    'value' => (string) $faq->question,
                    'language' => 'en',
                ],
                [
                    'value' => (string) ($faq->question_fil ?? ''),
                    'language' => 'fil',
                ],
            ], static fn (array $variant): bool => trim($variant['value']) !== '');

            $bestQuestionSimilarity = 0.0;
            $bestQuestionCoverage = 0.0;
            $detectedLanguage = 'en';

            foreach ($questionVariants as $variant) {
                $variantNormalized = $this->normalize($variant['value']);
                $variantTokens = $this->tokens($variant['value']);

                if ($variantNormalized !== '' && $variantNormalized === $normalizedQuestion) {
                    $bestQuestionSimilarity = 1.0;
                    $bestQuestionCoverage = 1.0;
                    $detectedLanguage = $variant['language'];
                    break;
                }

                $similarity = $this->tokenSimilarity($questionTokens, $variantTokens);
                $coverage = $this->tokenCoverage($questionTokens, $variantTokens);

                // Coverage matters because users commonly omit qualifiers
                // such as "conciliation process" while retaining the core
                // entities and action, e.g. "How long does DOLE SEnA usually
                // take?". Plain Jaccard similarity undervalues that query.
                if (
                    $similarity > $bestQuestionSimilarity
                    || ($similarity === $bestQuestionSimilarity && $coverage > $bestQuestionCoverage)
                ) {
                    $bestQuestionSimilarity = $similarity;
                    $bestQuestionCoverage = $coverage;
                    $detectedLanguage = $variant['language'];
                }
            }

            $keywordTokens = $this->tokens((string) $faq->keywords);
            $keywordSimilarity = $this->tokenSimilarity(
                $questionTokens,
                $keywordTokens
            );

            // Measure whether the user's words hit the FAQ's identifying
            // agency/keyword vocabulary. This protects the fallback from
            // matching merely because both questions contain generic words
            // such as "how", "long", or "process".
            $identityTokens = array_values(array_unique(array_merge(
                $keywordTokens,
                $this->tokens((string) $faq->agency?->agency_name),
                $this->tokens((string) $faq->agency?->agency_abbreviation)
            )));
            $identityCoverage = $this->tokenCoverage(
                $questionTokens,
                $identityTokens
            );

            $agencyScore = 0.0;
            if ($agencyId !== null && (int) $faq->agency_id === $agencyId) {
                $agencyScore = 1.0;
            }

            $score = ($bestQuestionSimilarity * 0.45)
                + ($bestQuestionCoverage * 0.35)
                + ($identityCoverage * 0.15)
                + ($agencyScore * 0.05);

            if ($bestQuestionSimilarity >= 0.999) {
                $score = 1.0;
            }

            if ($best === null || $score > $best['score']) {
                $best = [
                    'faq' => $faq,
                    'score' => $score,
                    'language' => $detectedLanguage,
                ];
            }
        }

        // This is still deliberately conservative. It is a safety net for
        // an unavailable/uncertain AI provider, not a second AI system.
        // Require meaningful question overlap plus identifying vocabulary.
        if (
            $best === null
            || $best['score'] < 0.50
        ) {
            return null;
        }

        return [
            'faq' => $best['faq'],
            'confidence' => max(0.0, min(1.0, $best['score'])),
            'language' => $best['language'],
            'method' => 'similarity',
        ];
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value) ?? '';
        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    private function tokens(string $value): array
    {
        $normalized = $this->normalize($value);

        if ($normalized === '') {
            return [];
        }

        $stopWords = [
            'a', 'an', 'and', 'are', 'can', 'do', 'does', 'for', 'how', 'i',
            'in', 'is', 'it', 'me', 'my', 'of', 'on', 'the', 'to', 'what',
            'when', 'where', 'which', 'who', 'with', 'you',
            'ang', 'ano', 'ay', 'ba', 'bakit', 'dahil', 'gaano', 'ito', 'ko',
            'kung', 'mag', 'mga', 'mo', 'na', 'ng', 'ni', 'para', 'saan',
            'si', 'sila', 'upo', 'wala', 'at', 'o', 'sa', 'may',
        ];

        return array_values(array_unique(array_filter(
            preg_split('/\s+/u', $normalized) ?: [],
            static fn (string $token): bool => $token !== '' && !in_array($token, $stopWords, true)
        )));
    }

    private function tokenSimilarity(array $left, array $right): float
    {
        if ($left === [] || $right === []) {
            return 0.0;
        }

        $intersection = count(array_intersect($left, $right));
        $union = count(array_unique(array_merge($left, $right)));

        return $union > 0 ? $intersection / $union : 0.0;
    }

    /**
     * Return the proportion of the user's meaningful tokens represented in
     * the candidate vocabulary. Unlike Jaccard similarity, this does not
     * penalize a user for leaving out descriptive words from the FAQ.
     */
    private function tokenCoverage(array $left, array $right): float
    {
        if ($left === [] || $right === []) {
            return 0.0;
        }

        $intersection = count(array_intersect($left, $right));

        return $intersection / count($left);
    }

    /**
     * Decode an OpenRouter JSON response defensively.
     *
     * Some providers/models wrap JSON in markdown fences even when the
     * response_format request was accepted. We remove only the wrapper and
     * then require valid JSON.
     */
    private function decodeResponse(array $response): array
    {
        $content = data_get(
            $response,
            'choices.0.message.content'
        );

        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException(
                'FAQ AI returned an empty response.'
            );
        }

        $content = trim($content);

        $content = preg_replace(
            '/^\s*```(?:json)?\s*|\s*```\s*$/i',
            '',
            $content
        );

        $content = trim($content);

        $json = json_decode(
            $content,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        if (!is_array($json)) {
            throw new RuntimeException(
                'FAQ AI returned an invalid JSON object.'
            );
        }

        return $json;
    }

    private function encode(array $payload): string
    {
        return json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
        );
    }

    private function text(
        mixed $value,
        int $limit
    ): string {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return mb_strlen($value, 'UTF-8') > $limit
            ? mb_substr($value, 0, $limit, 'UTF-8') . '…'
            : $value;
    }


    private function verificationPrompt(): string
    {
        return <<<'PROMPT'
You are a strict FAQ relevance verifier for the KNOWURLOCAL chatbot.

Your only task is to decide whether the supplied FAQ answers the SAME SPECIFIC
QUESTION the user is asking. Do not answer the user.

A match is valid when:
- the user may have paraphrased or shortened the FAQ question; AND
- the requested information, outcome, or action is the same; AND
- the agency/service context is compatible when relevant.

Reject the candidate when it merely shares an agency, service, person, place,
or broad topic with the user's question. In particular, different information
types are NOT matches: duration vs requirements, eligibility vs procedure,
location vs processing time, fees vs documents, or how-to vs status.

Examples:
- "How long does DOLE SEnA take?" -> MATCH for a FAQ about the SEnA
  conciliation period.
- "Usually, how much time does DOLE give the parties to settle through SEnA?"
  -> MATCH for that same SEnA duration FAQ.
- "What documents do I need for SEnA?" -> NO MATCH for a duration FAQ.
- "Can I file a SEnA request?" -> NO MATCH for a duration FAQ.
- "Where is the DOLE office?" -> NO MATCH for a duration FAQ.

Return ONLY JSON:
{"match":true}
or
{"match":false}
PROMPT;
    }

    private function retrievalPrompt(): string
    {
        return <<<'PROMPT'
You are the FAQ retrieval engine for the KNOWURLOCAL chatbot.

Your ONLY job is to select the ID of the EXISTING FAQ that best matches the
user's question. You do not answer the user.

Compare the user's question against ONLY these retrieval fields:
- English FAQ question
- Filipino/Taglish FAQ question
- agency name and abbreviation
- administrator-provided keywords
- current agency id, when supplied

Matching rules:
1. Match the user's INTENT, not just the same agency, topic, or named service.
2. Exact and near-exact question matches are strongest.
3. Understand legitimate paraphrases and natural English, Filipino, and Taglish.
4. The candidate must answer the SAME specific information request. For example,
   "How long does SEnA take?" matches a duration FAQ, while "What documents
   do I need for SEnA?" does NOT match that duration FAQ even though both mention SEnA.
5. Use agency information to distinguish otherwise similar FAQs.
6. Keywords support a match; they do not have to be copied verbatim.
7. Do not match because of a shared agency/service name alone.
8. Do not match because both questions are generally about labor, registration,
   requirements, processing, or another broad topic. The requested outcome must match.
9. If no supplied FAQ actually answers the user's specific request, return null.
10. Prefer null over a weak or speculative match.

IMPORTANT: Answer text is NOT provided and must never be generated. The
application will retrieve the latest published answer from PostgreSQL after
you return the FAQ id.

Return ONLY this JSON object:
{"faq_id":123,"confidence":0.98,"language":"en"}

faq_id must be one of the supplied FAQ ids or null.
confidence must be between 0.0 and 1.0.
language must be "en" or "fil" and describes the user's question language.

No prose outside the JSON object.
PROMPT;
    }
}
