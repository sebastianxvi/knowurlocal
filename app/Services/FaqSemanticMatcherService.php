<?php

namespace App\Services;

use App\Models\Faq;
use RuntimeException;

class FaqSemanticMatcherService
{
    public function __construct(
        private OpenRouterService $ai
    ) {
    }

    /**
     * Compare the user's question against a small set
     * of existing FAQ candidates.
     *
     * IMPORTANT:
     * The AI is ONLY allowed to select an existing FAQ.
     *
     * It must never generate the actual government answer.
     */
    public function match(
    string $question,
    $faqs,
    string $userIntent
): ?array {

        /*
         * There is nothing to compare when the rule-based
         * matcher produced no candidates.
         */
        if ($faqs->isEmpty()) {
            return null;
        }

        /*
         * Build a very small candidate list.
         *
         * We intentionally send only the information needed
         * for semantic comparison.
         */
        $candidates = $faqs->map(
    function (Faq $faq) {

        return [
            'id' => $faq->id,
            'question_en' => $faq->question,
            'question_fil' => $faq->question_fil,

            /*
             * FaqMatcherService already detected this FAQ's
             * intent before sending it here.
             *
             * We simply read the value attached to the
             * model instead of detecting it again.
             */
            'intent' => $faq->match_intent ?? 'other',
        ];
    }
)->values()->all();

        /*
         * Encode the candidate list as JSON so the model
         * receives structured data instead of ambiguous text.
         */
        $candidateJson = json_encode(
            $candidates,
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );

        if ($candidateJson === false) {
            throw new RuntimeException(
                'Unable to prepare FAQ candidates.'
            );
        }

        /*
         * Ask the AI to perform semantic comparison only.
         */
        $messages = [
            [
                'role' => 'system',

                'content' => <<<'PROMPT'
You are the semantic FAQ matcher for KNOWURLOCAL.

You are the semantic FAQ matcher for KNOWURLOCAL.

Your ONLY task is to determine whether the user's question
has the same meaning and information intent as one of the
EXISTING FAQ candidates.

You are NOT an answer generator.

The rule-based system has already detected the user's intent
and provides it as "user_intent".

Each FAQ candidate also contains its detected "intent".

You MUST use intent compatibility when selecting a FAQ.

You MUST NOT:
- create a new FAQ
- invent information
- answer the user's question
- combine information from multiple FAQs
- assume facts not present in the candidates
- select an FAQ merely because it shares one common word
- select an FAQ merely because it concerns the same agency
- select an FAQ with a conflicting known intent

INTENT COMPATIBILITY RULE:

If the user's intent and a candidate's intent are both known
and they are different, that candidate MUST NOT be selected.

For example:

User question:
"How do I apply for police clearance?"

User intent:
"procedure"

Candidate:
"What documents do I need for police clearance?"

Candidate intent:
"requirements"

These are NOT the same information request.

The candidate MUST NOT be selected.

Even though both questions concern police clearance,
"procedure" and "requirements" are different intents.

Another example:

User intent:
"fees"

Candidate intent:
"procedure"

The candidate MUST NOT be selected.

Semantic similarity must be evaluated only after intent
compatibility is satisfied.

SEMANTIC MATCHING RULES:

After checking intent compatibility, compare the actual
meaning of the user's question with the candidate question.

The candidate should be selected only when it answers the
same type of question about the same service or process.

Do not rely on individual shared words.

For example:

User:
"What papers do I need for a PNP clearance?"

Candidate:
"What documents do I need to bring when requesting
a police clearance?"

These have the same intent and meaning.

Therefore they can match.

But:

User:
"How do I apply for a police clearance?"

Candidate:
"What documents do I need to bring when requesting
a police clearance?"

These concern the same service but request different
information.

Therefore they must NOT match.

You are NOT an answer generator.

You MUST NOT:
- create a new FAQ
- invent information
- answer the user's question
- combine information from multiple FAQs
- assume facts not present in the candidates
- select an FAQ merely because it shares one common word
- select an FAQ merely because it concerns the same agency

IMPORTANT:

Semantic similarity means the user is asking for the
same information or service.

Example:

FAQ:
"How can I request police assistance during an emergency?"

User:
"Paano humingi ng tulong sa police tuwing emergency?"

These have the same intent.

But:

FAQ:
"How can I request police assistance during an emergency?"

User:
"Where is the nearest police station?"

These do NOT have the same intent.

Another example:

FAQ:
"How do I file a police report?"

User:
"Paano humingi ng tulong sa pulis kapag may emergency?"

These do NOT have the same intent.

Language differences between English and Filipino/Taglish
must NOT prevent a correct semantic match.

Return ONLY valid JSON in this exact format:

{
    "faq_id": 123,
    "confidence": 0.94,
    "language": "fil"
}

If none of the candidates has the same intent:

{
    "faq_id": null,
    "confidence": 0.0,
    "language": "en"
}

Rules:

1. faq_id MUST be one of the candidate IDs.
2. If no candidate has the same intent, faq_id MUST be null.
3. confidence MUST be a number from 0 to 1.
4. Use "fil" when the user's question is Filipino/Taglish.
5. Use "en" when the user's question is English.
6. Do not use keyword overlap alone as evidence.
7. A shared word such as "police", "emergency", "report",
   or "application" is NOT sufficient for a match.
PROMPT
            ],

            [
                'role' => 'user',

                'content' => json_encode([
    'user_question' => $question,
    'user_intent' => $userIntent,
    'candidates' => $candidates,
], JSON_UNESCAPED_UNICODE)
            ]
        ];

        /*
         * Low temperature keeps the classification
         * deterministic and reduces creative behavior.
         */
        $response = $this->ai->chat(
            $messages,
            0.0
        );

        /*
         * Extract the model's response.
         */
        $content = data_get(
            $response,
            'choices.0.message.content'
        );

        if (
            !is_string($content) ||
            trim($content) === ''
        ) {
            throw new RuntimeException(
                'Semantic matcher returned an empty response.'
            );
        }

        /*
         * Remove Markdown JSON fences if the model
         * unexpectedly adds them.
         */
        $content = trim($content);

        $content = preg_replace(
            '/^```(?:json)?\s*/i',
            '',
            $content
        );

        $content = preg_replace(
            '/\s*```$/',
            '',
            $content
        );

        /*
         * Decode the restricted JSON response.
         */
        $result = json_decode(
            trim($content),
            true
        );

        if (!is_array($result)) {
            throw new RuntimeException(
                'Semantic matcher returned invalid JSON.'
            );
        }

        /*
         * Validate the returned fields.
         */
        $faqId = $result['faq_id'] ?? null;

        $confidence = $result['confidence'] ?? null;

        $language = $result['language'] ?? 'en';

        /*
         * Confidence must be numeric.
         */
        if (
            !is_numeric($confidence) ||
            $confidence < 0 ||
            $confidence > 1
        ) {
            throw new RuntimeException(
                'Semantic matcher returned invalid confidence.'
            );
        }

        /*
         * Only English and Filipino are accepted.
         */
        if (
            !in_array(
                $language,
                ['en', 'fil'],
                true
            )
        ) {
            $language = 'en';
        }

        /*
         * NULL is valid and means:
         *
         * "None of these FAQs actually answer this question."
         */
        if ($faqId === null) {

            return [
                'faq_id' => null,
                'confidence' => (float) $confidence,
                'language' => $language,
            ];
        }

        /*
         * SECURITY BOUNDARY:
         *
         * Never trust the AI's returned FAQ ID.
         *
         * Verify that it was actually one of the IDs
         * Laravel supplied to the model.
         */
        $allowedIds = $faqs
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

            if (
    !in_array(
        (int) $faqId,
        $allowedIds,
        true
    )
) {
    throw new RuntimeException(
        'Semantic matcher returned an unauthorized FAQ ID.'
    );
}

/*
 * SECOND SECURITY BOUNDARY:
 *
 * The AI must not be able to bypass the intent rules
 * established by the rule-based matcher.
 *
 * Even if the AI selects an ID that was legitimately
 * supplied as a candidate, we verify that the selected
 * FAQ still has a compatible intent.
 */
$selectedFaq = $faqs->firstWhere(
    'id',
    (int) $faqId
);

$selectedIntent =
    $selectedFaq?->match_intent ?? 'other';

if (
    $userIntent !== 'other' &&
    $selectedIntent !== 'other' &&
    $userIntent !== $selectedIntent
) {

    return [
        'faq_id' => null,
        'confidence' => 0.0,
        'language' => $language,
    ];
}


        return [
            'faq_id' => (int) $faqId,
            'confidence' => (float) $confidence,
            'language' => $language,
        ];
    }
}