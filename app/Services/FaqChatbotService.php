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

    public function __construct(
        private OpenRouterService $ai
    ) {
    }

    /**
     * Find an existing FAQ using AI semantic retrieval.
     *
     * There is intentionally no local lexical/rule-based matching here:
     * no LIKE query, keyword score, exact-string branch, stop-word logic,
     * intent classifier, or agency-name parser.
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

        return $this->resolveDecision($decision, $faqs);
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
        $faqColumns = [
            'id',
            'agency_id',
            'question',
            'answer',
            'keywords',
        ];

        foreach ([
            'question_fil',
            'answer_fil',
            'image',
            'response_components',
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

        return Faq::query()
            ->with([
                'agency' => function ($query) use ($agencyColumns) {
                    $query->select($agencyColumns);
                },
            ])
            ->select($faqColumns)
            ->get();
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
            'answer_en' => $this->text(
                $faq->answer,
                1400
            ),
            'answer_fil' => $this->text(
                $faq->answer_fil,
                1400
            ),
            'response_context' => $this->responseContext(
                $faq->response_components ?? null
            ),
        ];
    }

    private function responseContext(mixed $components): string
    {
        if (!is_array($components)) {
            return '';
        }

        return $this->text(
            collect($components)
                ->filter(
                    fn ($component) =>
                        is_array($component)
                        && ($component['type'] ?? null) === 'text'
                )
                ->map(
                    fn ($component) =>
                        trim((string) ($component['content'] ?? ''))
                )
                ->filter()
                ->implode("\n"),
            1400
        );
    }

    /**
     * Resolve the AI's selection against the exact collection loaded from
     * the database. The AI cannot invent an answer or an arbitrary FAQ id.
     */
    private function resolveDecision(
        array $decision,
        Collection $faqs
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
         * Confidence is analytics metadata, not a gate.
         *
         * A valid FAQ selection must not be discarded merely because a model
         * returned 98 instead of 0.98 or omitted confidence altogether.
         */
        $rawConfidence = $decision['confidence'] ?? 1.0;

        if (!is_numeric($rawConfidence)) {
            $confidence = 1.0;
        } else {
            $confidence = (float) $rawConfidence;

            if ($confidence > 1.0 && $confidence <= 100.0) {
                $confidence /= 100.0;
            }

            $confidence = max(0.0, min(1.0, $confidence));
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
            'method' => 'ai',
        ];
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

    private function retrievalPrompt(): string
    {
        return <<<'PROMPT'
You are the semantic retrieval engine for the KNOWURLOCAL FAQ chatbot.

Your ONLY task is to select the EXISTING FAQ record that best matches the
user's question.

You are NOT an answer generator.

NEVER:
- write an answer to the user;
- rewrite an FAQ answer;
- summarize an FAQ answer;
- translate an FAQ answer;
- invent information;
- combine multiple FAQ answers;
- create a new FAQ;
- return an FAQ id that is not in the supplied catalog.

The application will load the selected FAQ from its database and display its
already-approved stored answer. Your response is only a retrieval decision.

Each supplied FAQ contains:
- id
- agency_id
- agency name
- agency abbreviation
- administrator keywords
- English question
- Filipino/Taglish question
- stored English answer
- stored Filipino answer
- optional response-component text

Use ALL of those fields as semantic evidence.

MATCHING REQUIREMENTS:

1. Exact question matches are the strongest possible evidence.
   If the user's question is the same as a supplied FAQ question, select that
   FAQ unless the record is obviously malformed.

2. Ignore harmless formatting differences such as:
   - capitalization;
   - surrounding whitespace;
   - ordinary punctuation;
   - repeated spaces.

3. Understand paraphrases and natural language.
   The user does not need to use the exact FAQ wording.

4. Understand English, Filipino, and Taglish.
   The English and Filipino versions of one FAQ represent the same underlying
   information.

5. Use agency information to distinguish otherwise similar FAQs.
   The current agency is context only; never force a match solely because of
   the current agency.

6. Use the stored answer as semantic evidence about what the FAQ actually
   covers. Do NOT output any answer text.

7. Keywords are supporting evidence, not a mandatory lexical rule.

8. Do not choose a record merely because it shares one or two words with the
   user's question.

9. If one FAQ clearly answers the user's requested information, select it.

10. If no supplied FAQ actually addresses the user's request, return null.

Return ONLY one JSON object in exactly this shape:

{"faq_id":123,"confidence":0.98,"language":"en"}

Rules:
- faq_id MUST be an id from the supplied faq_candidates list, or null.
- confidence is a number from 0.0 to 1.0.
- language must be "en" or "fil".
- "en" means the user's question is primarily English.
- "fil" means Filipino/Taglish.
- Never put prose outside the JSON object.

If there is no reliable FAQ match:

{"faq_id":null,"confidence":0.0,"language":"en"}
PROMPT;
    }
}
