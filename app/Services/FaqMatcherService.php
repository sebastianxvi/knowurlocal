<?php

namespace App\Services;

use App\Models\Faq;
use Illuminate\Support\Collection;

class FaqMatcherService
{

/*
 * Controlled vocabulary used only when retrieving
 * possible FAQ candidates from the database.
 *
 * These synonyms do NOT decide whether an FAQ is correct.
 * They only help the retrieval layer find equivalent wording.
 */
private const RETRIEVAL_SYNONYMS = [

    'papers' => [
        'documents',
    ],

    'paper' => [
        'document',
    ],

    'docs' => [
        'documents',
    ],

    'doc' => [
        'document',
    ],

    'papeles' => [
        'documents',
    ],
];

public function __construct(
        private FaqSemanticMatcherService $semanticMatcher,
        private FaqIntentService $intentService
    ) {
    }

    /*
     * Minimum confidence required before an FAQ can
     * be returned to the chatbot.
     *
     * This prevents weak matches from producing
     * potentially misleading government information.
     */
    private const MIN_MATCH_SCORE = 35;

    /*
 * Minimum score required for a purely rule-based
 * match to be trusted without semantic verification.
 */
private const MIN_RULE_MATCH_SCORE = 35;

/*
 * Minimum amount of meaningful evidence required
 * before an FAQ is even considered a candidate
 * for semantic matching.
 */
private const MIN_CANDIDATE_SCORE = 8;

    /*
     * Find the most relevant approved FAQs.
     *
     * This service ONLY retrieves existing FAQ records.
     *
     * It never generates an answer.
     */
    public function match(
        string $question,
        ?int $agencyId = null,
        int $limit = 5
    ): Collection {

        /*
         * Normalize the user's question so that:
         *
         * "Paano mag-file?"
         *
         * and
         *
         * "Paano mag file?"
         *
         * are treated consistently.
         */
        $normalizedQuestion = $this->normalize($question);

/*
 * Determine the information intent of the user's question.
 *
 * Examples:
 *
 * "What documents do I need?"
 *      → requirements
 *
 * "How do I apply?"
 *      → procedure
 *
 * "How much does it cost?"
 *      → fees
 *
 * "Where is the office?"
 *      → location
 */
$userIntent = $this->intentService->detect(
    $question
);

/*
 * Stop immediately when the user submitted
 * an empty or meaningless question.
 */
if ($normalizedQuestion === '') {
    return collect();
}

        /*
         * Extract meaningful words.
         *
         * These are used only to find candidate FAQs.
         *
         * They do NOT determine the final answer.
         */
        $terms = $this->extractRetrievalTerms(
    $normalizedQuestion
);

        /*
         * Start with all FAQs.
         */
        $query = Faq::query()
            ->with('agency');

        /*
         * Restrict matching to an agency when the
         * chatbot has an agency context.
         */
        if ($agencyId) {
            $query->where(
                'agency_id',
                $agencyId
            );
        }

        /*
         * Retrieve only possible candidates.
         *
         * This is NOT the actual matching decision.
         */
        if (!empty($terms)) {

            $query->where(function ($q) use ($terms) {

                foreach ($terms as $term) {

                    /*
                     * Escape SQL LIKE wildcard characters.
                     *
                     * This prevents user input such as "%".
                     * from becoming a wildcard.
                     */
                    $safeTerm = addcslashes(
                        $term,
                        '%_\\'
                    );

                    $like = "%{$safeTerm}%";

                    $q->orWhere(
                        'question',
                        'LIKE',
                        $like
                    );

                    $q->orWhere(
                        'question_fil',
                        'LIKE',
                        $like
                    );

                    $q->orWhere(
                        'keywords',
                        'LIKE',
                        $like
                    );
                }
            });
        }

        /*
         * Limit the amount of database data that must
         * be processed by PHP.
         */
        $faqs = $query
            ->limit(100)
            ->get();

        /*
         * Score every candidate.
         */
        $scored = $faqs->map(
            function (Faq $faq) use (
    $normalizedQuestion,
    $userIntent
) {

                $result = $this->calculateScore(
    $normalizedQuestion,
    $faq,
    $userIntent
);

                /*
                 * Attach the calculated score to the
                 * model temporarily.
                 */
                $faq->match_score =
                    $result['score'];

                /*
                 * Store the language selected by the
                 * matcher.
                 */
                $faq->match_language =
                    $result['language'];

                /*
                 * Store diagnostic information.
                 *
                 * This is useful when debugging why
                 * an FAQ matched.
                 */
                $faq->match_reasons =
                    $result['reasons'];

                return $faq;
            }
        );

        /*
         * Reject anything below the confidence threshold.
         *
         * This is the safety gate.
         */
        /*
 * Keep FAQs that have at least some meaningful
 * evidence.
 *
 * We intentionally do NOT require the full
 * rule-based confidence score yet.
 *
 * The semantic matcher will make the final
 * decision for weaker candidates.
 */
$scored = $scored->filter(
    fn (Faq $faq) =>
        $faq->match_score >=
        self::MIN_CANDIDATE_SCORE
);

        /*
         * Return the strongest FAQs first.
         */
        return $scored
            ->sortByDesc('match_score')
            ->take($limit)
            ->values();
    }

    /**
     * Calculate the relevance score for one FAQ.
     */
    private function calculateScore(
    string $question,
    Faq $faq,
    string $userIntent
): array {

        $score = 0;

        $reasons = [];

        /*
 * Determine the intent of the stored FAQ.
 *
 * We use the English FAQ question as the primary
 * source because it is the canonical FAQ record.
 *
 * The intent detector is rule-based, so this does
 * not introduce another AI request.
 */
$faqIntent = $this->intentService->detect(
    $faq->question ?? ''
);

/*
 * Store both intents temporarily on this FAQ model.
 *
 * match_user_intent:
 *     The intent of the user's current question.
 *
 * match_intent:
 *     The intent of this particular FAQ.
 *
 * These are temporary runtime properties.
 * They are NOT written to the database.
 */
$faq->match_user_intent = $userIntent;
$faq->match_intent = $faqIntent;

/*
 * Intent compatibility is used as a ranking signal.
 *
 * We intentionally do NOT apply a penalty yet.
 *
 * First we record whether the intents agree.
 * This lets us inspect the behavior safely before
 * making intent mismatch affect the final score.
 */
$intentMatches =
    $userIntent !== 'other' &&
    $faqIntent !== 'other' &&
    $userIntent === $faqIntent;


    /*
 * Intent safety boundary.
 *
 * When both the user's intent and the FAQ's intent
 * are known, they must agree.
 *
 * Example:
 *
 * User intent:
 * procedure
 *
 * FAQ intent:
 * requirements
 *
 * These questions may concern the same agency or
 * service, but the FAQ does not answer what the
 * user is asking.
 *
 * Returning a score of zero prevents the FAQ from
 * reaching the semantic matcher as a misleading
 * candidate.
 */

    

        /*
         * Normalize trusted FAQ questions.
         */
        $englishQuestion =
            $this->normalize(
                $faq->question ?? ''
            );

        $filipinoQuestion =
            $this->normalize(
                $faq->question_fil ?? ''
            );

        /*
         * -----------------------------------------------------
         * 1. EXACT QUESTION MATCH
         * -----------------------------------------------------
         */

        if (
            $question !== '' &&
            $question === $englishQuestion
        ) {

            return [
                'score' => 100,
                'language' => 'en',
                'reasons' => [
                    'exact English question'
                ],
            ];
        }

        if (
            $question !== '' &&
            $filipinoQuestion !== '' &&
            $question === $filipinoQuestion
        ) {

            return [
                'score' => 100,
                'language' => 'fil',
                'reasons' => [
                    'exact Filipino question'
                ],
            ];
        }

        if (
    $userIntent !== 'other' &&
    $faqIntent !== 'other' &&
    !$intentMatches
) {

    return [
        'score' => 0,
        'language' => 'en',
        'reasons' => [
            "intent mismatch: user={$userIntent}, faq={$faqIntent}"
        ],
    ];
}

/*
 * Record matching intent as diagnostic evidence.
 *
 * This does not add points by itself.
 * Intent compatibility is already enforced above.
 */
if ($intentMatches) {

    $reasons[] =
        "intent match: {$userIntent}";
}

        /*
         * -----------------------------------------------------
         * 2. ADMIN-APPROVED KEYWORD PHRASES
         * -----------------------------------------------------
         *
         * Only MULTI-WORD keywords are accepted.
         *
         * "police"      → ignored
         * "report"      → ignored
         * "police report" → accepted
         */

        $keywords = $this->extractKeywordPhrases(
            $faq->keywords ?? ''
        );

        foreach ($keywords as $keyword) {

            /*
             * Count the words in this keyword.
             */
            $wordCount = count(
                preg_split(
                    '/\s+/u',
                    $keyword
                )
            );

            /*
             * Ignore single-word keywords.
             */
            if ($wordCount < 2) {
                continue;
            }

            /*
             * Exact approved phrase.
             */
            if (
                $question === $keyword
            ) {

                $score += min(
                    60,
                    30 + ($wordCount * 10)
                );

                $reasons[] =
                    'exact keyword phrase: ' .
                    $keyword;

                continue;
            }

            /*
             * Approved phrase appears inside the
             * user's question.
             */
            if (
                $this->containsPhrase(
                    $question,
                    $keyword
                )
            ) {

                $score += min(
                    50,
                    20 + ($wordCount * 8)
                );

                $reasons[] =
                    'keyword phrase: ' .
                    $keyword;
            }
        }

        /*
         * -----------------------------------------------------
         * 3. QUESTION PHRASE MATCHING
         * -----------------------------------------------------
         *
         * Compare 2-word and 3-word phrases from the
         * user's question against the stored FAQ questions.
         *
         * This helps with natural variations.
         */

        $questionPhrases =
            $this->extractPhrases(
                $question
            );

        $englishPhraseHits = 0;
        $filipinoPhraseHits = 0;

        foreach ($questionPhrases as $phrase) {

            /*
             * English FAQ question.
             */
            if (
                $this->containsPhrase(
                    $englishQuestion,
                    $phrase
                )
            ) {

                $englishPhraseHits++;

                $score += 8;

                $reasons[] =
                    'English question phrase: ' .
                    $phrase;
            }

            /*
             * Filipino FAQ question.
             */
            if (
                $filipinoQuestion !== '' &&
                $this->containsPhrase(
                    $filipinoQuestion,
                    $phrase
                )
            ) {

                $filipinoPhraseHits++;

                $score += 8;

                $reasons[] =
                    'Filipino question phrase: ' .
                    $phrase;
            }
        }

        /*
 * -----------------------------------------------------
 * 2. INDIVIDUAL CONCEPT MATCHING
 * -----------------------------------------------------
 *
 * Individual words provide weak evidence.
 *
 * They are useful for finding candidates but should
 * never be strong enough to directly answer a question.
 */

/*
 * Use the controlled retrieval vocabulary when calculating
 * weak concept evidence as well.
 *
 * This allows equivalent words such as:
 *
 * papers → documents
 *
 * to contribute a small amount of evidence.
 *
 * This is still only weak evidence. Intent matching and
 * semantic verification remain responsible for the final decision.
 */
$userTerms = $this->extractRetrievalTerms(
    $question
);

$englishTerms = $this->extractTerms(
    $englishQuestion
);

$filipinoTerms = $this->extractTerms(
    $filipinoQuestion
);

$englishTermHits = count(
    array_intersect(
        $userTerms,
        $englishTerms
    )
);

$filipinoTermHits = count(
    array_intersect(
        $userTerms,
        $filipinoTerms
    )
);

/*
 * Each shared concept contributes only a small amount.
 */
$termScore = min(
    20,
    ($englishTermHits + $filipinoTermHits) * 3
);

$score += $termScore;

if ($englishTermHits > 0) {

    $reasons[] =
        'English concept matches: ' .
        $englishTermHits;
}

if ($filipinoTermHits > 0) {

    $reasons[] =
        'Filipino concept matches: ' .
        $filipinoTermHits;
}

        /*
         * -----------------------------------------------------
         * 4. LANGUAGE DECISION
         * -----------------------------------------------------
         *
         * Determine which FAQ question has stronger
         * phrase-level evidence.
         *
         * Shared single words are intentionally ignored.
         */

        if (
            $filipinoPhraseHits >
            $englishPhraseHits
        ) {

            $language = 'fil';

        } else {

            $language = 'en';
        }

        /*
         * -----------------------------------------------------
         * 5. IMPORTANT SAFETY RULE
         * -----------------------------------------------------
         *
         * If the match consists ONLY of shared words,
         * do not allow it to reach the threshold.
         *
         * This prevents:
         *
         * "Where is the nearest police station?"
         *
         * from matching:
         *
         * "How do I file a police report?"
         *
         * simply because both contain "police".
         */

        /*
 * If there are no phrase matches, we may still have
 * useful individual concepts.
 *
 * These are allowed to create a candidate, but they
 * are NOT strong enough to directly answer the user.
 */
if (
    $englishPhraseHits === 0 &&
    $filipinoPhraseHits === 0
) {

    /*
     * Do not completely reject the FAQ here.
     *
     * The candidate may still be semantically related.
     */
    if ($score < self::MIN_CANDIDATE_SCORE) {

        return [
            'score' => 0,
            'language' => 'en',
            'reasons' => [
                'insufficient candidate evidence'
            ],
        ];
    }

    return [
        'score' => min($score, 34),
        'language' => 'en',
        'reasons' => array_merge(
            $reasons,
            [
                'candidate only - requires semantic verification'
            ]
        ),
    ];
}

        return [
            'score' => min($score, 100),
            'language' => $language,
            'reasons' => $reasons,
        ];
    }

    /**
     * Convert text into a normalized representation.
     */
    private function normalize(
        string $text
    ): string {

        /*
         * Convert everything to lowercase.
         */
        $text = mb_strtolower(
            $text,
            'UTF-8'
        );

        /*
         * Normalize common punctuation.
         */
        $text = str_replace(
            [
                '–',
                '—',
                '’',
                '“',
                '”'
            ],
            [
                '-',
                '-',
                "'",
                '"',
                '"'
            ],
            $text
        );

        /*
         * Convert punctuation into spaces.
         *
         * This allows:
         *
         * "mag-file"
         *
         * and
         *
         * "mag file"
         *
         * to behave consistently.
         */
        $text = preg_replace(
            '/[^\p{L}\p{N}\s-]+/u',
            ' ',
            $text
        );

        /*
         * Normalize repeated whitespace.
         */
        $text = preg_replace(
            '/\s+/u',
            ' ',
            $text
        );

        return trim($text);
    }

    /**
     * Extract meaningful individual terms.
     *
     * These terms are used ONLY for database candidate
     * retrieval.
     */
    private function extractTerms(
        string $question
    ): array {

        $words = preg_split(
            '/\s+/u',
            $question
        );

        /*
         * Common words that are not useful for retrieving
         * candidate FAQs.
         */
        $stopWords = [

            /*
             * English.
             */
            'the',
            'a',
            'an',
            'and',
            'or',
            'is',
            'are',
            'am',
            'i',
            'me',
            'my',
            'do',
            'does',
            'did',
            'how',
            'what',
            'where',
            'when',
            'why',
            'who',
            'can',
            'could',
            'would',
            'should',
            'to',
            'for',
            'of',
            'in',
            'on',
            'at',
            'with',

            /*
             * Filipino/Taglish.
             *
             * Notice that "paano" is NOT removed.
             *
             * It is useful for language detection and
             * question intent.
             */
            'ang',
            'ng',
            'mga',
            'sa',
            'na',
            'ay',
            'ako',
            'ko',
            'mo',
            'ito',
            'iyon',
            'kung',
            'at',
            'o',
            'ba',
            'po',
            'opo',
        ];

        $terms = [];

        foreach ($words as $word) {

            $word = trim($word);

            /*
             * Ignore very short terms.
             */
            if (
                mb_strlen(
                    $word,
                    'UTF-8'
                ) < 3
            ) {
                continue;
            }

            /*
             * Ignore common filler words.
             */
            if (
                in_array(
                    $word,
                    $stopWords,
                    true
                )
            ) {
                continue;
            }

            $terms[] = $word;
        }

        return array_values(
            array_unique($terms)
        );
    }

    /**
 * Build terms specifically for database candidate retrieval.
 *
 * This starts with the normal terms and then adds
 * controlled synonyms.
 *
 * The expanded terms are used only to discover
 * possible FAQ candidates. They do not determine
 * the final answer.
 */
private function extractRetrievalTerms(
    string $question
): array {

    /*
     * Start with the existing term extraction logic.
     */
    $terms = $this->extractTerms(
        $question
    );

    /*
     * Keep the original terms.
     */
    $expandedTerms = $terms;

    /*
     * Add approved synonyms when available.
     */
    foreach ($terms as $term) {

        /*
         * Skip terms that have no configured synonym.
         */
        if (
            !isset(
                self::RETRIEVAL_SYNONYMS[$term]
            )
        ) {
            continue;
        }

        /*
         * Add every approved synonym.
         */
        foreach (
            self::RETRIEVAL_SYNONYMS[$term]
            as $synonym
        ) {

            $expandedTerms[] =
                $synonym;
        }
    }

    /*
     * Remove duplicate terms and reset array indexes.
     */
    return array_values(
        array_unique(
            $expandedTerms
        )
    );
}

    /**
     * Convert keyword storage into clean phrases.
     */
    private function extractKeywordPhrases(
        string $keywords
    ): array {

        /*
         * Treat line breaks and semicolons as separators.
         */
        $keywords = str_replace(
            [
                "\r\n",
                "\r",
                "\n",
                ';'
            ],
            ',',
            $keywords
        );

        $items = explode(
            ',',
            $keywords
        );

        $result = [];

        foreach ($items as $item) {

            $item = $this->normalize(
                $item
            );

            if ($item === '') {
                continue;
            }

            $result[] = $item;
        }

        return array_values(
            array_unique($result)
        );
    }

    /**
     * Generate 2-word and 3-word phrases.
     */
    /**
 * Generate meaningful 2-word and 3-word phrases.
 *
 * Unlike the previous implementation, this method
 * removes common stop words before creating phrases.
 *
 * This prevents generic phrases such as:
 *
 * "how do"
 * "do i"
 * "for a"
 *
 * from being treated as meaningful FAQ evidence.
 */
private function extractPhrases(
    string $question
): array {

    /*
     * Reuse the same stop-word rules used by
     * individual concept matching.
     */
    $words = $this->extractTerms(
        $question
    );

    $phrases = [];

    $count = count($words);

    /*
     * Generate 2-word phrases from meaningful terms.
     */
    for (
        $i = 0;
        $i < $count - 1;
        $i++
    ) {

        $phrases[] =
            $words[$i] . ' ' .
            $words[$i + 1];
    }

    /*
     * Generate 3-word phrases from meaningful terms.
     */
    for (
        $i = 0;
        $i < $count - 2;
        $i++
    ) {

        $phrases[] =
            $words[$i] . ' ' .
            $words[$i + 1] . ' ' .
            $words[$i + 2];
    }

    /*
     * Remove duplicate phrases.
     */
    return array_values(
        array_unique(
            $phrases
        )
    );
}

    /**
     * Check whether a complete phrase exists.
     */
    private function containsPhrase(
        string $haystack,
        string $phrase
    ): bool {

        return str_contains(
            ' ' . $haystack . ' ',
            ' ' . $phrase . ' '
        );
    }
}