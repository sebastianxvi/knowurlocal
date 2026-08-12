<?php

namespace App\Services;

use RuntimeException;

class FaqTranslationService
{
    public function __construct(
        private OpenRouterService $ai
    ) {
    }

    /**
     * Generate a Filipino/Taglish draft from the
     * English FAQ source content.
     *
     * IMPORTANT:
     * This method only generates a draft.
     * It does NOT save anything to the database.
     */
    public function translate(
        string $question,
        string $answer,
        string $keywords = ''
    ): array {

        $messages = [
            [
                'role' => 'system',

                'content' => <<<'PROMPT'
You are the translation and search-assistance tool for KNOWURLOCAL,
a Philippine public-information website.

Your task is to:

1. Translate the English FAQ into natural Filipino/Taglish.
2. Suggest useful search keywords and short search phrases
   that Filipino citizens may realistically use to find this FAQ.

STRICT RULES:

1. Preserve the original meaning exactly.
2. Do not invent facts.
3. Do not add requirements, fees, documents, procedures,
   dates, office hours, contact details, or services.
4. Keep official agency names and acronyms unchanged.
5. Keep names, addresses, phone numbers, email addresses,
   URLs, IDs, and other factual identifiers unchanged.
6. Filipino/Taglish should sound natural to ordinary Filipino users.
7. English words commonly used in Filipino conversations may remain
   in English.
8. Do not make the Filipino/Taglish translation unnecessarily formal.
9. Do not add explanations or commentary.
10. Keyword suggestions must be based ONLY on concepts already
    present in the question, answer, or existing keywords.
11. Do not invent new government services, requirements,
    documents, fees, procedures, or facts.
12. Prefer meaningful search phrases over isolated words.
13. Include useful English and Filipino/Taglish search phrases.
14. Preserve official names and acronyms exactly.
15. Do not generate more than 15 keyword suggestions.
16. Return ONLY valid JSON.

Required format:

{
    "question_fil": "...",
    "answer_fil": "...",
    "keyword_suggestions": [
        "...",
        "...",
        "..."
    ]
}
PROMPT
            ],

            [
                'role' => 'user',

                'content' => json_encode([
                    'question' => $question,
                    'answer' => $answer,
                    'existing_keywords' => $keywords,
                ], JSON_UNESCAPED_UNICODE),
            ],
        ];

        $response = $this->ai->chat(
            $messages,
            0.2
        );

        $content = data_get(
            $response,
            'choices.0.message.content'
        );

        if (
            !is_string($content) ||
            trim($content) === ''
        ) {
            throw new RuntimeException(
                'AI returned an empty translation.'
            );
        }

        $content = $this->cleanJsonResponse($content);

        $translation = json_decode(
    $content,
    true
);

/*
 * Validate the JSON structure before accessing
 * any of its values.
 */
if (
    !is_array($translation) ||
    !isset($translation['question_fil']) ||
    !isset($translation['answer_fil']) ||
    !isset($translation['keyword_suggestions']) ||
    !is_string($translation['question_fil']) ||
    !is_string($translation['answer_fil']) ||
    !is_array($translation['keyword_suggestions'])
) {
    throw new RuntimeException(
        'AI returned an invalid translation format.'
    );
}

/*
 * Normalize the translated text before validating it.
 */
$questionFil = trim(
    $translation['question_fil']
);

$answerFil = trim(
    $translation['answer_fil']
);

/*
 * Empty translations are not acceptable.
 */
if (
    $questionFil === '' ||
    $answerFil === ''
) {
    throw new RuntimeException(
        'AI returned an empty translation.'
    );
}

/*
 * Protect the database from unexpectedly large
 * AI-generated values.
 */
if (mb_strlen($questionFil) > 255) {
    throw new RuntimeException(
        'AI returned an excessively long translated question.'
    );
}

if (mb_strlen($answerFil) > 10000) {
    throw new RuntimeException(
        'AI returned an excessively long translated answer.'
    );
}

/*
 * Clean and normalize keyword suggestions.
 */
$keywords = array_values(
    array_filter(
        array_map(
            fn ($keyword) => is_string($keyword)
                ? trim($keyword)
                : '',
            $translation['keyword_suggestions']
        )
    )
);

return [
    'question_fil' => $questionFil,

    'answer_fil' => $answerFil,

    'keyword_suggestions' => $keywords,
];
    }



    /**
 * Prepare a bilingual FAQ draft from a support request.
 *
 * The AI determines whether the original support request
 * is primarily English or Filipino/Taglish.
 *
 * The original content is preserved in its appropriate
 * language field, while the missing language version
 * is generated.
 *
 * Nothing is saved to the database here.
 */
public function prepareSupportRequestFaq(
    string $question,
    string $answer
): array {

    $messages = [
        [
            'role' => 'system',

            'content' => <<<'PROMPT'
You are the bilingual FAQ preparation tool for KNOWURLOCAL,
a Philippine public-information website.

You will receive a user's support question and the administrator's
answer.

Your task is to prepare the content for a bilingual FAQ.

FIRST:
Determine the language of the ORIGINAL USER QUESTION.

Use the user's question as the primary language signal.

The administrator's answer may be in a different language.
Do NOT classify the entire request based only on the administrator's answer.

Classify the user's question as:

- english
- filipino
- taglish

THEN:

The output fields have FIXED meanings and MUST NEVER change:

- "question" = English Official question.
- "answer" = English Official answer.
- "question_fil" = Filipino/Taglish question.
- "answer_fil" = Filipino/Taglish answer.

If the ORIGINAL USER QUESTION is primarily English:

- Preserve the original question in "question".
- Generate a natural Filipino/Taglish version in "question_fil".

If the ORIGINAL USER QUESTION is primarily Filipino or Taglish:

- Preserve the original question in "question_fil".
- Translate the question into natural English and place it in "question".

For the ADMINISTRATOR'S ANSWER:

- If the answer is already English, preserve it in "answer"
  and translate it naturally into Filipino/Taglish for "answer_fil".

- If the answer is Filipino or Taglish, preserve it in "answer_fil"
  and translate it naturally into English for "answer".

- If the answer mixes English and Filipino/Taglish, determine
  which version is appropriate for each fixed output field.

IMPORTANT:
The language of the user's question MUST NOT determine the
language of the administrator's answer.

The output field names always have the same meaning.

IMPORTANT RULES:

1. Never invent facts.
2. Never add requirements, fees, documents, procedures,
   dates, office hours, contact information, or services.
3. Preserve the meaning of the original content exactly.
4. Preserve official agency names and acronyms.
5. Preserve names, addresses, phone numbers, email addresses,
   URLs, IDs, and other factual identifiers.
6. Filipino/Taglish should sound natural to ordinary Filipino users.
7. Do not make the translation unnecessarily formal.
8. English words commonly used in Filipino conversations may remain English.
9. Do not add explanations or commentary.
10. Do not rewrite the factual answer beyond what is necessary
    to translate it naturally.
11. Generate useful search keywords based ONLY on the supplied
    question and answer.
12. Include both English and Filipino/Taglish search phrases.
13. Do not generate more than 15 keyword suggestions.
14. Return ONLY valid JSON.
15. Do not change the intent of the user's question.
16. Do not make the English version more specific than the original.
17. Do not make the Filipino/Taglish version more specific than the original.
18. If the original question is ambiguous, preserve that ambiguity.
19. Do not infer missing agency information from general knowledge.
20. The English and Filipino/Taglish versions must express the same meaning.
21. "question" MUST always be written in English.
22. "answer" MUST always be written in English.
23. "question_fil" MUST always be written in Filipino/Taglish.
24. "answer_fil" MUST always be written in Filipino/Taglish.
25. Never place a Filipino/Taglish question in "question".
26. Never place an English question in "question_fil".
27. Never place a Filipino/Taglish answer in "answer".
28. Never place an English answer in "answer_fil".
29. When preserving the original user's question, preserve its meaning
    and wording as much as reasonably possible in the appropriate
    language field.
30. The original user's question must be translated when necessary
    to satisfy the fixed English/Filipino field contract.

Required format:

{
    "detected_language": "english|filipino|taglish",
    "question": "...",
    "answer": "...",
    "question_fil": "...",
    "answer_fil": "...",
    "keyword_suggestions": [
        "...",
        "..."
    ]
}
PROMPT
        ],

        [
            'role' => 'user',

            'content' => json_encode([
    'original_user_question' => $question,
    'administrator_answer' => $answer,
], JSON_UNESCAPED_UNICODE),
        ],
    ];

    $response = $this->ai->chat(
        $messages,
        0.2
    );

    $content = data_get(
        $response,
        'choices.0.message.content'
    );

    if (
        !is_string($content) ||
        trim($content) === ''
    ) {
        throw new RuntimeException(
            'AI returned an empty FAQ preparation response.'
        );
    }

    $content = $this->cleanJsonResponse($content);

    $result = json_decode($content, true);

if (!is_array($result)) {
    throw new RuntimeException(
        'AI returned invalid JSON: ' . json_last_error_msg()
    );
}

/*
 * Required fields.
 *
 * We validate the structure before accessing individual
 * values so malformed AI output cannot cause unexpected
 * PHP errors.
 */
$requiredFields = [
    'detected_language',
    'question',
    'answer',
    'question_fil',
    'answer_fil',
    'keyword_suggestions',
];

foreach ($requiredFields as $field) {

    if (!array_key_exists($field, $result)) {
        throw new RuntimeException(
            "AI FAQ response is missing field: {$field}"
        );
    }
}

/*
 * Validate the expected data types.
 *
 * AI output is external/untrusted data, even though
 * it came from our own prompt.
 */
if (
    !is_string($result['detected_language']) ||
    !is_string($result['question']) ||
    !is_string($result['answer']) ||
    !is_string($result['question_fil']) ||
    !is_string($result['answer_fil']) ||
    !is_array($result['keyword_suggestions'])
) {
    throw new RuntimeException(
        'AI FAQ response contains invalid field types.'
    );
}

    $allowedLanguages = [
        'english',
        'filipino',
        'taglish',
    ];

    if (!in_array(
        $result['detected_language'],
        $allowedLanguages,
        true
    )) {
        throw new RuntimeException(
            'AI returned an invalid detected language.'
        );
    }

    /*
     * Keep generated content within reasonable limits.
     */
    if (
        mb_strlen($result['question']) > 255 ||
        mb_strlen($result['question_fil']) > 255
    ) {
        throw new RuntimeException(
            'AI returned an excessively long question.'
        );
    }

    if (
        mb_strlen($result['answer']) > 10000 ||
        mb_strlen($result['answer_fil']) > 10000
    ) {
        throw new RuntimeException(
            'AI returned an excessively long answer.'
        );
    }

    /*
     * Clean and normalize keyword suggestions.
     */
    $keywords = array_values(

    
        array_filter(
            array_map(
                fn ($keyword) => is_string($keyword)
                    ? trim($keyword)
                    : '',
                $result['keyword_suggestions']
            )
        )
    );

    /*
 * Detect the most obvious field-mapping failure.
 *
 * If the AI places the exact original question into both
 * language fields, the bilingual draft is not trustworthy.
 *
 * We reject it instead of silently showing incorrect data
 * to the administrator.
 */
$originalQuestion = trim($question);

if (
    mb_strtolower(trim($result['question'])) ===
    mb_strtolower($originalQuestion)
    &&
    mb_strtolower(trim($result['question_fil'])) ===
    mb_strtolower($originalQuestion)
) {
    throw new RuntimeException(
        'AI returned the original question in both language fields.'
    );
}

    return [
        'detected_language' =>
            $result['detected_language'],

        'question' =>
            trim($result['question']),

        'answer' =>
            trim($result['answer']),

        'question_fil' =>
            trim($result['question_fil']),

        'answer_fil' =>
            trim($result['answer_fil']),

        'keyword_suggestions' =>
            $keywords,
    ];
}



    /**
     * AI models sometimes wrap JSON inside
     * Markdown code fences. Remove those fences
     * before attempting JSON decoding.
     */
    private function cleanJsonResponse(string $content): string
{
    /*
     * Remove surrounding whitespace first.
     */
    $content = trim($content);

    /*
     * Remove Markdown code fences if the model
     * wrapped the JSON inside ```json ... ```.
     */
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

    $content = trim($content);

    /*
     * If the model added commentary before or after
     * the JSON, isolate the JSON object.
     *
     * We deliberately use the first "{" and the last "}"
     * rather than trusting the model to return JSON only.
     */
    $firstBrace = strpos($content, '{');
    $lastBrace = strrpos($content, '}');

    if (
        $firstBrace !== false &&
        $lastBrace !== false &&
        $lastBrace > $firstBrace
    ) {
        $content = substr(
            $content,
            $firstBrace,
            $lastBrace - $firstBrace + 1
        );
    }

    return trim($content);
}
}