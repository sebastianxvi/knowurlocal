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
        string $answer
    ): array {

        $messages = [
            [
                'role' => 'system',

                'content' => <<<'PROMPT'
You are the translation tool for KNOWURLOCAL,
a Philippine public-information website.

Your task is to translate the English FAQ into natural Filipino/Taglish.

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
10. Return ONLY valid JSON.

Required format:

{
    "question_fil": "...",
    "answer_fil": "..."
}
PROMPT
            ],

            [
                'role' => 'user',

                'content' => json_encode([
                    'question' => $question,
                    'answer' => $answer,
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
    !is_string($translation['question_fil']) ||
    !is_string($translation['answer_fil'])
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

return [
    'question_fil' => $questionFil,

    'answer_fil' => $answerFil,
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
    $questionLanguage = $this->detectLanguage($question);
    $answerLanguage = $this->detectLanguage($answer);

    $questionPair = $questionLanguage === 'en'
        ? [
            'question' => trim($question),
            'question_fil' => $this->translateToFilipino($question),
        ]
        : [
            'question' => $this->translateToEnglish($question),
            'question_fil' => trim($question),
        ];

    $answerPair = $answerLanguage === 'en'
        ? [
            'answer' => trim($answer),
            'answer_fil' => $this->translateToFilipino($answer),
        ]
        : [
            'answer' => $this->translateToEnglish($answer),
            'answer_fil' => trim($answer),
        ];

    return [
        'detected_language' => $questionLanguage,
        'question' => $questionPair['question'],
        'question_fil' => $questionPair['question_fil'],
        'answer' => $answerPair['answer'],
        'answer_fil' => $answerPair['answer_fil'],
    ];
}

/**
 * Detect language independently for each source field.
 */
private function detectLanguage(string $text): string
{
    $normalized = mb_strtolower(trim($text), 'UTF-8');
    if ($normalized === '') {
        return 'en';
    }

    $signals = [
        'ano', 'anong', 'paano', 'saan', 'sino', 'kailan', 'magkano',
        'kailangan', 'mga', 'ang', 'ng', 'sa', 'para sa', 'pwede',
        'puwede', 'maaari', 'dalhin', 'kumuha', 'makakuha', 'mag-apply',
        'mag apply', 'gusto ko', 'may bayad', 'ilang araw', 'gaano',
        'dokumento', 'papeles', 'serbisyo', 'opisina', 'tulong', 'po', 'ba',
    ];

    $hits = 0;
    foreach ($signals as $signal) {
        if (preg_match('/(?<!\p{L})' . preg_quote($signal, '/') . '(?!\p{L})/u', $normalized)) {
            $hits++;
        }
    }

    $wordCount = max(1, count(preg_split('/\s+/u', $normalized)));
    if ($hits >= 2 || ($hits >= 1 && $wordCount <= 8)) {
        return 'fil';
    }

    try {
        $response = $this->ai->chat([
            [
                'role' => 'system',
                'content' => 'Classify the text language. Return ONLY JSON: {"language":"en|fil"}. Use fil for Filipino or Taglish and en for English. Do not translate or explain.',
            ],
            ['role' => 'user', 'content' => $text],
        ], 0.0);

        $content = $this->cleanJsonResponse((string) data_get($response, 'choices.0.message.content', ''));
        $result = json_decode($content, true);
        if (($result['language'] ?? null) === 'fil') {
            return 'fil';
        }
    } catch (\Throwable $e) {
        // Conservative English fallback if classification is unavailable.
    }

    return 'en';
}

private function translateToFilipino(string $text): string
{
    return $this->translateSingle($text, 'Filipino/Taglish');
}

private function translateToEnglish(string $text): string
{
    return $this->translateSingle($text, 'English');
}

private function translateSingle(string $text, string $target): string
{
    $response = $this->ai->chat([
        [
            'role' => 'system',
            'content' => <<<PROMPT
Translate the supplied public-information text into {$target}.
Preserve meaning exactly. Do not add facts, explanations, requirements, or commentary.
Keep official names, acronyms, numbers, URLs, addresses, and factual identifiers unchanged.
Return ONLY JSON in this exact format: {"translation":"..."}
PROMPT,
        ],
        ['role' => 'user', 'content' => $text],
    ], 0.0);

    $content = $this->cleanJsonResponse((string) data_get($response, 'choices.0.message.content', ''));
    $result = json_decode($content, true);
    $translation = trim((string) ($result['translation'] ?? ''));

    if ($translation === '') {
        throw new RuntimeException('AI returned an empty translation.');
    }

    return $translation;
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