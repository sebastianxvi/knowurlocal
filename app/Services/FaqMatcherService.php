<?php

namespace App\Services;

use App\Models\Faq;
use Illuminate\Support\Collection;

class FaqMatcherService
{
    /*
     * =========================================================
     * GENERIC TEXT NORMALIZATION
     * =========================================================
     *
     * These synonym groups are intentionally domain-neutral.
     *
     * They help the matcher understand that words such as
     * "documents", "papers", and "dokumento" refer to the
     * same general concept.
     *
     * Government-specific terminology must come from the
     * actual FAQ question and administrator-provided keywords.
     */
    private const GENERIC_SYNONYMS = [
        'service' => [
            'service',
            'services',
            'serbisyo',
        ],

        'document' => [
            'document',
            'documents',
            'doc',
            'docs',
            'paper',
            'papers',
            'papeles',
            'dokumento',
            'dokuments',
        ],

        'requirement' => [
            'requirement',
            'requirements',
            'required',
            'need',
            'needs',
            'needed',
            'kailangan',
            'mga kailangan',
        ],

        'apply' => [
            'apply',
            'applying',
            'application',
            'mag-apply',
            'mag apply',
            'nag-aapply',
            'nag aapply',
            'pag-apply',
            'pag apply',
        ],

        'request' => [
            'request',
            'requests',
            'requested',
            'mag-request',
            'mag request',
            'humingi',
            'hiling',
        ],

        'file' => [
            'file',
            'filing',
            'submit',
            'submission',
            'mag-file',
            'mag file',
            'mag-submit',
            'mag submit',
        ],

        'assistance' => [
            'assistance',
            'help',
            'tulong',
            'ayuda',
        ],
    ];


    /*
     * =========================================================
     * GENERIC STOP WORDS
     * =========================================================
     *
     * These words normally contribute very little meaning
     * during FAQ comparison.
     */
    private const STOP_WORDS = [
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
        'you',
        'your',
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
        'from',
        'about',
        'under',
        'this',
        'that',
        'it',
        'be',
        'have',
        'has',
        'had',

        /*
         * Filipino / Taglish.
         */
        'ang',
        'ng',
        'mga',
        'sa',
        'para',
        'kay',
        'at',
        'o',
        'ay',
        'ako',
        'ko',
        'mo',
        'niya',
        'ito',
        'iyon',
        'kung',
        'ba',
        'po',
        'opo',
        'bang',
        'raw',
        'daw',
    ];


    /*
     * =========================================================
     * SCORE THRESHOLDS
     * =========================================================
     *
     * Government information should use conservative matching.
     *
     * A FAQ must have enough evidence before it can be treated
     * as a direct rule-based answer.
     */
    private const MIN_RULE_MATCH_SCORE = 35;

    /*
     * A very weak candidate is removed before the controller
     * considers semantic fallback.
     */
    private const MIN_CANDIDATE_SCORE = 8;


    /*
     * =========================================================
     * SCORE LIMITS
     * =========================================================
     *
     * These constants make the scoring system easier to tune
     * without scattering unexplained numbers throughout the
     * calculation code.
     */
    private const MAX_SPECIFIC_TERM_SCORE = 24;

    private const SPECIFIC_TERM_WEIGHT = 4;

    private const MAX_CONCEPT_SCORE = 18;

    private const CONCEPT_WEIGHT = 6;

    private const MAX_KEYWORD_PHRASE_SCORE = 20;

    private const MAX_QUESTION_PHRASE_SCORE = 30;

    /*
     * Maximum penalty for candidate-specific details
     * that the user did not mention.
     *
     * This prevents a narrow FAQ from outranking a broader
     * FAQ simply because it contains additional details.
     */
    private const MAX_UNMENTIONED_SPECIFICITY_PENALTY = 18;

    /*
     * Penalty applied for each meaningful candidate-specific
     * phrase that is absent from the user's question.
     */
    private const UNMENTIONED_SPECIFIC_PHRASE_PENALTY = 6;


    public function __construct(
        private FaqSemanticMatcherService $semanticMatcher,
        private FaqIntentService $intentService
    ) {
    }


    /**
     * Find the strongest approved FAQ candidates.
     *
     * This method performs retrieval and rule-based scoring.
     *
     * It does NOT generate an answer.
     */
    public function match(
        string $question,
        ?int $agencyId = null,
        int $limit = 5
    ): Collection {

        /*
         * Normalize the user's question before comparison.
         */
        $normalizedQuestion =
            $this->normalize($question);


        /*
         * Never perform a database search for an empty question.
         */
        if ($normalizedQuestion === '') {
            return collect();
        }


        /*
         * Determine what type of information the user is asking
         * for using the rule-based intent classifier.
         */
        $userIntent =
            $this->intentService->detect(
                $normalizedQuestion
            );


        /*
         * Extract generic retrieval terms.
         *
         * These terms are used only to reduce the database
         * search space. They do not determine the final answer.
         */
        $terms =
            $this->extractRetrievalTerms(
                $normalizedQuestion
            );


        /*
         * Start with approved FAQ records.
         *
         * The Faq model uses SoftDeletes, so deleted records
         * are automatically excluded from this query.
         */
        $query =
            Faq::query()
                ->with('agency');


        /*
         * If an agency has already been identified, restrict
         * the search to that agency.
         */
        if ($agencyId !== null) {

            $query->where(
                'agency_id',
                $agencyId
            );
        }


        /*
         * Search the FAQ question and keyword fields.
         *
         * User-controlled terms are escaped before being placed
         * inside SQL LIKE expressions.
         *
         * This prevents %, _, and \ from changing the intended
         * LIKE pattern.
         */
        if (!empty($terms)) {

            $query->where(function ($q) use ($terms) {

                foreach ($terms as $term) {

                    $safeTerm =
                        addcslashes(
                            $term,
                            '%_\\'
                        );


                    $like =
                        "%{$safeTerm}%";


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
         * Bound the number of records that PHP must score.
         *
         * This prevents an unnecessarily large FAQ dataset from
         * causing expensive application-level processing.
         */
        $faqs =
            $query
                ->limit(100)
                ->get();


        /*
         * Calculate a rule-based score for every retrieved FAQ.
         */
        $scored =
            $faqs->map(
                function (Faq $faq) use (
                    $normalizedQuestion,
                    $userIntent
                ) {

                    $result =
                        $this->calculateScore(
                            $normalizedQuestion,
                            $faq,
                            $userIntent
                        );


                    /*
                     * These values exist only during the current
                     * request.
                     *
                     * They are not saved to the database.
                     */
                    $faq->match_score =
                        $result['score'];

                    $faq->match_language =
                        $result['language'];

                    $faq->match_reasons =
                        $result['reasons'];


                    return $faq;
                }
            );


        /*
         * Remove candidates that have almost no evidence.
         */
        $scored =
            $scored->filter(
                fn (Faq $faq) =>
                    $faq->match_score >=
                    self::MIN_CANDIDATE_SCORE
            );


        /*
         * Return the strongest candidates first.
         *
         * The controller can use these candidates later for
         * direct answering, clarification, or semantic fallback.
         */
        return $scored
            ->sortByDesc('match_score')
            ->take($limit)
            ->values();
    }


    /**
     * Calculate the rule-based relevance score for one FAQ.
     */
    private function calculateScore(
        string $question,
        Faq $faq,
        string $userIntent
    ): array {

        $score = 0;

        $reasons = [];


        /*
         * Detect the intent of the stored FAQ.
         */
        $faqIntent =
            $this->intentService->detect(
                $faq->question ?? ''
            );


        /*
         * Keep the detected intents attached to the model for
         * diagnostic purposes and later semantic processing.
         */
        $faq->match_user_intent =
            $userIntent;

        $faq->match_intent =
            $faqIntent;


        /*
         * =====================================================
         * 1. EXACT QUESTION MATCH
         * =====================================================
         *
         * An exact English or Filipino FAQ question is the
         * strongest possible rule-based match.
         */
        $englishQuestion =
            $this->normalize(
                $faq->question ?? ''
            );

        $filipinoQuestion =
            $this->normalize(
                $faq->question_fil ?? ''
            );


        if (
            $question !== '' &&
            $question === $englishQuestion
        ) {

            return [
                'score' => 100,
                'language' => 'en',
                'reasons' => [
                    'exact English question',
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
                    'exact Filipino question',
                ],
            ];
        }


        /*
         * =====================================================
         * 2. INTENT SAFETY BOUNDARY
         * =====================================================
         *
         * If both intents are known but disagree, this FAQ is
         * not allowed to directly answer the user's question.
         */
        $intentKnown =
            $userIntent !== 'other' &&
            $faqIntent !== 'other';


        $intentMatches =
            $intentKnown &&
            $userIntent === $faqIntent;


        if (
            $intentKnown &&
            !$intentMatches
        ) {

            return [
                'score' => 0,
                'language' => 'en',
                'reasons' => [
                    "intent mismatch: user={$userIntent}, faq={$faqIntent}",
                ],
            ];
        }


        /*
         * Matching intent is useful evidence, but intent alone
         * can never authorize a direct answer.
         */
        if ($intentMatches) {

            $score += 12;

            $reasons[] =
                "intent match: {$userIntent}";
        }


        /*
         * =====================================================
         * 3. BUILD USER SEARCH REPRESENTATION
         * =====================================================
         */
        $userTerms =
            $this->extractTerms(
                $question
            );


        $userConcepts =
            $this->extractConcepts(
                $question
            );


        $userPhrases =
            $this->extractPhrases(
                $question
            );


        /*
         * =====================================================
         * 4. BUILD FAQ SEARCH REPRESENTATION
         * =====================================================
         */
        $englishTerms =
            $this->extractTerms(
                $englishQuestion
            );


        $filipinoTerms =
            $this->extractTerms(
                $filipinoQuestion
            );


        $englishConcepts =
            $this->extractConcepts(
                $englishQuestion
            );


        $filipinoConcepts =
            $this->extractConcepts(
                $filipinoQuestion
            );


        /*
         * Administrator-approved keywords provide additional
         * domain vocabulary.
         */
        $keywordPhrases =
            $this->extractKeywordPhrases(
                $faq->keywords ?? ''
            );


        /*
         * =====================================================
         * 5. SPECIFIC TERM MATCHING
         * =====================================================
         *
         * We still reward specific individual terms, but we
         * intentionally cap their total contribution.
         */
        $faqTerms =
            array_values(
                array_unique(
                    array_merge(
                        $englishTerms,
                        $filipinoTerms
                    )
                )
            );


        $termHits =
            array_values(
                array_intersect(
                    $userTerms,
                    $faqTerms
                )
            );


        /*
         * Generic terms such as "document", "request", and
         * "service" should not receive specific-term weight.
         */
        $genericConceptTerms =
            $this->getGenericSynonymTerms();


        $specificTermHits =
            array_values(
                array_filter(
                    $termHits,
                    fn ($term) =>
                        !in_array(
                            $term,
                            $genericConceptTerms,
                            true
                        )
                )
            );


        /*
         * Every specific term contributes a modest amount.
         *
         * The total is capped so that a long question containing
         * many overlapping words cannot automatically dominate.
         */
        $specificTermScore =
            min(
                self::MAX_SPECIFIC_TERM_SCORE,
                count($specificTermHits) *
                    self::SPECIFIC_TERM_WEIGHT
            );


        $score +=
            $specificTermScore;


        if (!empty($specificTermHits)) {

            $reasons[] =
                'specific term matches: ' .
                implode(
                    ', ',
                    $specificTermHits
                );
        }


        /*
         * =====================================================
         * 6. GENERIC CONCEPT MATCHING
         * =====================================================
         *
         * Generic concepts help bridge English, Filipino, and
         * Taglish wording differences.
         */
        $faqConcepts =
            array_values(
                array_unique(
                    array_merge(
                        $englishConcepts,
                        $filipinoConcepts
                    )
                )
            );


        $conceptHits =
            array_values(
                array_intersect(
                    $userConcepts,
                    $faqConcepts
                )
            );


        $conceptScore =
            min(
                self::MAX_CONCEPT_SCORE,
                count($conceptHits) *
                    self::CONCEPT_WEIGHT
            );


        $score +=
            $conceptScore;


        if (!empty($conceptHits)) {

            $reasons[] =
                'generic concept matches: ' .
                implode(
                    ', ',
                    $conceptHits
                );
        }


        /*
         * =====================================================
         * 7. DISTINCTIVE KEYWORD PHRASE MATCHING
         * =====================================================
         *
         * Administrator-provided keyword phrases can provide
         * strong evidence when the complete phrase occurs in
         * the user's question.
         */
        $keywordPhraseScore =
            0;


        $keywordPhraseHits =
            0;


        foreach ($keywordPhrases as $keyword) {

            /*
             * Single-word keywords are handled by term scoring.
             */
            $phraseWords =
                $this->wordCount(
                    $keyword
                );


            if ($phraseWords < 2) {
                continue;
            }


            /*
             * Award points only when the complete keyword phrase
             * appears in the user's question.
             */
            if (
                $this->containsPhrase(
                    $question,
                    $keyword
                )
            ) {

                $keywordPhraseHits++;


                /*
                 * Longer phrases carry more evidence.
                 */
                $phraseWeight =
                    min(
                        12,
                        4 + ($phraseWords * 2)
                    );


                $keywordPhraseScore +=
                    $phraseWeight;


                $reasons[] =
                    'FAQ keyword phrase: ' .
                    $keyword;
            }
        }


        /*
         * Prevent keyword-heavy FAQs from dominating the score.
         */
        $keywordPhraseScore =
            min(
                self::MAX_KEYWORD_PHRASE_SCORE,
                $keywordPhraseScore
            );


        $score +=
            $keywordPhraseScore;


        /*
         * =====================================================
         * 8. QUESTION PHRASE OVERLAP
         * =====================================================
         *
         * Compare meaningful phrases from the user's question
         * against the FAQ's actual questions.
         */
        $englishPhraseHits =
            0;

        $filipinoPhraseHits =
            0;


        foreach ($userPhrases as $phrase) {

            /*
             * Ignore phrases that are too short to be useful.
             */
            if (
                $this->wordCount($phrase) < 2
            ) {
                continue;
            }


            if (
                $this->containsPhrase(
                    $englishQuestion,
                    $phrase
                )
            ) {

                $englishPhraseHits++;

                $reasons[] =
                    'English phrase overlap: ' .
                    $phrase;
            }


            if (
                $filipinoQuestion !== '' &&
                $this->containsPhrase(
                    $filipinoQuestion,
                    $phrase
                )
            ) {

                $filipinoPhraseHits++;

                $reasons[] =
                    'Filipino phrase overlap: ' .
                    $phrase;
            }
        }


        /*
         * Calculate phrase score separately before adding it.
         */
        $phraseHitCount =
            $englishPhraseHits +
            $filipinoPhraseHits;


        $phraseScore =
            min(
                self::MAX_QUESTION_PHRASE_SCORE,
                $phraseHitCount * 5
            );


        $score +=
            $phraseScore;


        /*
         * =====================================================
         * 9. SPECIFICITY BONUS
         * =====================================================
         *
         * A longer phrase directly present in the FAQ is
         * stronger evidence than several unrelated word matches.
         */
        $specificPhraseHits =
            $this->getSpecificPhraseHits(
                $userPhrases,
                $englishQuestion,
                $filipinoQuestion
            );


        if (!empty($specificPhraseHits)) {

            /*
             * Give a modest bonus to distinctive multi-word
             * matches.
             */
            $specificityBonus =
                min(
                    18,
                    count($specificPhraseHits) * 6
                );


            $score +=
                $specificityBonus;


            $reasons[] =
                'distinctive phrase evidence: ' .
                implode(
                    ', ',
                    $specificPhraseHits
                );
        }


        /*
         * =====================================================
         * 10. EVIDENCE SAFETY CHECK
         * =====================================================
         *
         * Generic intent/concept matches must never be enough
         * to produce a direct answer.
         */
        $hasSpecificEvidence =
            !empty($specificTermHits) ||
            $keywordPhraseHits > 0 ||
            !empty($specificPhraseHits) ||
            count($conceptHits) >= 2;


        $hasPhraseEvidence =
            $englishPhraseHits > 0 ||
            $filipinoPhraseHits > 0 ||
            $keywordPhraseHits > 0 ||
            !empty($specificPhraseHits);


        /*
         * Without specific evidence, the candidate is too weak
         * for a direct rule-based answer.
         */
        if (!$hasSpecificEvidence) {

            $score =
                min(
                    $score,
                    self::MIN_RULE_MATCH_SCORE - 1
                );


            return [
                'score' => $score,
                'language' =>
                    $filipinoPhraseHits >
                    $englishPhraseHits
                        ? 'fil'
                        : 'en',
                'reasons' => array_merge(
                    $reasons,
                    [
                        'candidate only - insufficient specific evidence',
                    ]
                ),
            ];
        }


        /*
         * =====================================================
         * 11. UNMENTIONED SPECIFICITY PENALTY
         * =====================================================
         *
         * A candidate FAQ may contain additional specific
         * details that the user never mentioned.
         *
         * Example:
         *
         * User:
         * "What do I need for a Private Land Timber Permit?"
         *
         * Candidate A:
         * "What documents are required to apply for a Private
         * Land Timber Permit?"
         *
         * Candidate B:
         * "Do I need to bring my land title when applying for a
         * Private Land Timber Permit?"
         *
         * Candidate B introduces the specific detail
         * "land title".
         *
         * Since the user did not mention that detail, Candidate B
         * receives a small ranking penalty.
         *
         * This is intentionally generic and does not contain
         * government-specific hardcoded words.
         */
        $unmentionedSpecificityPenalty =
            $this->calculateUnmentionedSpecificityPenalty(
                $userTerms,
                $userPhrases,
                $englishQuestion,
                $filipinoQuestion
            );


        $score -=
            $unmentionedSpecificityPenalty;


        if (
            $unmentionedSpecificityPenalty > 0
        ) {

            $reasons[] =
                'unmentioned specific detail penalty: ' .
                $unmentionedSpecificityPenalty;
        }


        /*
         * =====================================================
         * 12. FINAL SCORE SAFETY
         * =====================================================
         *
         * Never allow the internal score to exceed 100.
         */
        $score =
            min(
                100,
                $score
            );


        /*
         * Determine the strongest question language evidence.
         */
        $language =
            $filipinoPhraseHits >
            $englishPhraseHits
                ? 'fil'
                : 'en';


        /*
         * Specific terms without any meaningful phrase evidence
         * are still considered too uncertain for a direct answer.
         *
         * They remain available as candidates for semantic
         * verification.
         */
        if (!$hasPhraseEvidence) {

            $score =
                min(
                    $score,
                    self::MIN_RULE_MATCH_SCORE - 1
                );


            $reasons[] =
                'requires semantic verification';
        }


        return [
            'score' => $score,
            'language' => $language,
            'reasons' => $reasons,
        ];
    }


    /**
     * Find meaningful multi-word phrases that provide
     * distinctive evidence for a candidate FAQ.
     */
    private function getSpecificPhraseHits(
        array $userPhrases,
        string $englishQuestion,
        string $filipinoQuestion
    ): array {

        $hits = [];


        foreach ($userPhrases as $phrase) {

            /*
             * Two-word phrases are useful, while three-word
             * phrases are generally stronger.
             */
            $wordCount =
                $this->wordCount(
                    $phrase
                );


            if ($wordCount < 2) {
                continue;
            }


            /*
             * Ignore phrases composed entirely of generic
             * synonym terms.
             */
            $phraseTerms =
                $this->extractTerms(
                    $phrase
                );


            $genericTerms =
                $this->getGenericSynonymTerms();


            $specificTerms =
                array_filter(
                    $phraseTerms,
                    fn ($term) =>
                        !in_array(
                            $term,
                            $genericTerms,
                            true
                        )
                );


            /*
             * At least one meaningful non-generic term is
             * required before this becomes distinctive evidence.
             */
            if (empty($specificTerms)) {
                continue;
            }


            /*
             * Check whether the phrase appears in either
             * language version of the FAQ.
             */
            $matchesEnglish =
                $this->containsPhrase(
                    $englishQuestion,
                    $phrase
                );


            $matchesFilipino =
                $filipinoQuestion !== '' &&
                $this->containsPhrase(
                    $filipinoQuestion,
                    $phrase
                );


            if (
                $matchesEnglish ||
                $matchesFilipino
            ) {

                $hits[] =
                    $phrase;
            }
        }


        return array_values(
            array_unique(
                $hits
            )
        );
    }


    /**
 * Calculate a penalty for specific FAQ details that the
 * user did not mention.
 *
 * The penalty considers both the FAQ question and the
 * administrator-provided keywords.
 *
 * This allows the matcher to distinguish a broad FAQ from
 * a narrower FAQ without hardcoding government-specific terms.
 */
private function calculateUnmentionedSpecificityPenalty(
    array $userTerms,
    array $userPhrases,
    string $englishQuestion,
    string $filipinoQuestion,
    string $keywords = ''
): int {

    /*
     * Generic synonym terms should not be treated as
     * candidate-specific details.
     */
    $genericTerms =
        $this->getGenericSynonymTerms();


    /*
     * "need" is part of the requirement concept but is not
     * currently represented as an individual generic synonym.
     */
    $genericTerms[] =
        'need';


    /*
     * Convert the user's individual terms into a lookup set.
     */
    $userTermSet = [];


    foreach ($userTerms as $term) {

        $userTermSet[$term] =
            true;
    }


    /*
     * Convert the user's phrases into a lookup set.
     */
    $userPhraseSet = [];


    foreach ($userPhrases as $phrase) {

        $userPhraseSet[$phrase] =
            true;
    }


    /*
     * Combine the English and Filipino FAQ questions.
     */
    $faqText =
        trim(
            $englishQuestion . ' ' . $filipinoQuestion
        );


    /*
     * Generate meaningful phrases from the FAQ questions.
     */
    $faqPhrases =
        $this->extractPhrases(
            $faqText
        );


    /*
     * Administrator-provided keywords can contain important
     * domain-specific phrases such as "land title".
     */
    $keywordPhrases =
        $this->extractKeywordPhrases(
            $keywords
        );


    /*
     * Combine question phrases and administrator keywords.
     */
    $candidatePhrases =
        array_values(
            array_unique(
                array_merge(
                    $faqPhrases,
                    $keywordPhrases
                )
            )
        );


    /*
     * Start with no penalty.
     */
    $penalty =
        0;


    /*
     * Examine every candidate-specific phrase.
     */
    foreach ($candidatePhrases as $phrase) {

        /*
         * Ignore single-word phrases.
         */
        if (
            $this->wordCount($phrase) < 2
        ) {
            continue;
        }


        /*
         * Extract the individual words from the phrase.
         */
        $phraseTerms =
            $this->extractTerms(
                $phrase
            );


        /*
         * Remove generic words.
         */
        $specificTerms =
            array_values(
                array_filter(
                    $phraseTerms,
                    fn ($term) =>
                        !in_array(
                            $term,
                            $genericTerms,
                            true
                        )
                )
            );


        /*
         * Require at least two specific words.
         *
         * This prevents ordinary phrases from creating
         * unnecessary penalties.
         */
        if (
            count($specificTerms) < 2
        ) {
            continue;
        }


        /*
         * If the complete phrase was explicitly mentioned
         * by the user, it is relevant and should not be penalized.
         */
        if (
            isset(
                $userPhraseSet[$phrase]
            )
        ) {
            continue;
        }


        /*
         * Check whether the user already mentioned every
         * important word contained in this phrase.
         */
        $allSpecificTermsMentioned =
            true;


        foreach ($specificTerms as $term) {

            if (
                !isset(
                    $userTermSet[$term]
                )
            ) {

                $allSpecificTermsMentioned =
                    false;

                break;
            }
        }


        /*
         * If the user already supplied all the specific
         * vocabulary, this is not an unmentioned detail.
         */
        if (
            $allSpecificTermsMentioned
        ) {
            continue;
        }


        /*
         * Apply the penalty.
         */
        $penalty +=
            self::UNMENTIONED_SPECIFIC_PHRASE_PENALTY;


        /*
         * Keep the penalty bounded.
         */
        if (
            $penalty >=
            self::MAX_UNMENTIONED_SPECIFICITY_PENALTY
        ) {

            return
                self::MAX_UNMENTIONED_SPECIFICITY_PENALTY;
        }
    }


    return $penalty;
}


    /**
     * Normalize text consistently.
     */
    private function normalize(
        string $text
    ): string {

        /*
         * Convert uppercase characters to lowercase.
         */
        $text =
            mb_strtolower(
                $text,
                'UTF-8'
            );


        /*
         * Normalize common punctuation variants.
         */
        $text =
            str_replace(
                [
                    '–',
                    '—',
                    '’',
                    '“',
                    '”',
                ],
                [
                    '-',
                    '-',
                    "'",
                    '"',
                    '"',
                ],
                $text
            );


        /*
         * Convert punctuation into spaces.
         *
         * This keeps words from accidentally being joined.
         */
        $text =
            preg_replace(
                '/[^\p{L}\p{N}\s-]+/u',
                ' ',
                $text
            );


        /*
         * Collapse repeated whitespace.
         */
        $text =
            preg_replace(
                '/\s+/u',
                ' ',
                $text
            );


        return trim($text);
    }


    /**
     * Extract useful terms from text.
     */
    private function extractTerms(
        string $text
    ): array {

        $words =
            preg_split(
                '/\s+/u',
                $text
            );


        $terms = [];


        foreach ($words as $word) {

            $word =
                trim(
                    $word
                );


            /*
             * Remove leading/trailing hyphens.
             */
            $word =
                trim(
                    $word,
                    '-'
                );


            /*
             * Ignore empty tokens.
             */
            if ($word === '') {
                continue;
            }


            /*
             * Ignore extremely short tokens.
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
             * Ignore generic grammatical words.
             */
            if (
                in_array(
                    $word,
                    self::STOP_WORDS,
                    true
                )
            ) {
                continue;
            }


            $terms[] =
                $word;
        }


        /*
         * Remove duplicate terms.
         */
        return array_values(
            array_unique(
                $terms
            )
        );
    }


    /**
     * Extract database retrieval terms.
     *
     * This method intentionally performs only generic linguistic
     * expansion.
     */
    private function extractRetrievalTerms(
        string $question
    ): array {

        $terms =
            $this->extractTerms(
                $question
            );


        $expanded =
            $terms;


        foreach ($terms as $term) {

            foreach (
                self::GENERIC_SYNONYMS
                as $variants
            ) {

                /*
                 * Normalize every synonym before comparison.
                 */
                $normalizedVariants =
                    array_map(
                        fn ($value) =>
                            $this->normalize(
                                $value
                            ),
                        $variants
                    );


                /*
                 * Expand only when the user's term belongs
                 * to a generic synonym group.
                 */
                if (
                    in_array(
                        $term,
                        $normalizedVariants,
                        true
                    )
                ) {

                    foreach (
                        $normalizedVariants
                        as $variant
                    ) {

                        $expanded[] =
                            $variant;
                    }
                }
            }
        }


        /*
         * Add meaningful phrases to improve database retrieval.
         */
        foreach (
            $this->extractPhrases(
                $question
            ) as $phrase
        ) {

            $expanded[] =
                $phrase;
        }


        /*
         * Remove duplicates before constructing the query.
         */
        return array_values(
            array_unique(
                $expanded
            )
        );
    }


    /**
     * Convert generic synonym matches into concept identifiers.
     */
    private function extractConcepts(
        string $text
    ): array {

        $text =
            $this->normalize(
                $text
            );


        $concepts = [];


        foreach (
            self::GENERIC_SYNONYMS
            as $concept => $variants
        ) {

            foreach ($variants as $variant) {

                $normalizedVariant =
                    $this->normalize(
                        $variant
                    );


                if (
                    $this->containsPhrase(
                        $text,
                        $normalizedVariant
                    )
                ) {

                    $concepts[] =
                        $concept;

                    break;
                }
            }
        }


        return array_values(
            array_unique(
                $concepts
            )
        );
    }


    /**
     * Return individual words belonging to generic synonym
     * groups.
     */
    private function getGenericSynonymTerms(): array
    {
        $terms = [];


        foreach (
            self::GENERIC_SYNONYMS
            as $variants
        ) {

            foreach ($variants as $variant) {

                $normalized =
                    $this->normalize(
                        $variant
                    );


                /*
                 * Only individual words belong in this list.
                 */
                if (
                    !str_contains(
                        $normalized,
                        ' '
                    )
                ) {

                    $terms[] =
                        $normalized;
                }
            }
        }


        return array_values(
            array_unique(
                $terms
            )
        );
    }


    /**
     * Extract administrator-provided keyword phrases.
     */
    private function extractKeywordPhrases(
        string $keywords
    ): array {

        /*
         * Treat commas, semicolons, and line breaks as separators.
         */
        $keywords =
            str_replace(
                [
                    "\r\n",
                    "\r",
                    "\n",
                    ';',
                ],
                ',',
                $keywords
            );


        $items =
            explode(
                ',',
                $keywords
            );


        $result = [];


        foreach ($items as $item) {

            $item =
                $this->normalize(
                    $item
                );


            if ($item === '') {
                continue;
            }


            $result[] =
                $item;
        }


        return array_values(
            array_unique(
                $result
            )
        );
    }


    /**
     * Generate meaningful two-word and three-word phrases.
     */
    private function extractPhrases(
        string $question
    ): array {

        /*
         * Stopwords are removed before phrase generation.
         */
        $words =
            $this->extractTerms(
                $question
            );


        $phrases = [];


        $count =
            count(
                $words
            );


        /*
         * Generate adjacent two-word phrases.
         */
        for (
            $i = 0;
            $i < $count - 1;
            $i++
        ) {

            $phrase =
                $words[$i] . ' ' .
                $words[$i + 1];


            /*
             * Both words should contain enough information.
             */
            if (
                mb_strlen(
                    $words[$i],
                    'UTF-8'
                ) >= 4 &&
                mb_strlen(
                    $words[$i + 1],
                    'UTF-8'
                ) >= 4
            ) {

                $phrases[] =
                    $phrase;
            }
        }


        /*
         * Generate adjacent three-word phrases.
         */
        for (
            $i = 0;
            $i < $count - 2;
            $i++
        ) {

            $phraseWords = [
                $words[$i],
                $words[$i + 1],
                $words[$i + 2],
            ];


            /*
             * At least two words must be reasonably informative.
             */
            $meaningfulCount =
                count(
                    array_filter(
                        $phraseWords,
                        fn ($word) =>
                            mb_strlen(
                                $word,
                                'UTF-8'
                            ) >= 5
                    )
                );


            if (
                $meaningfulCount >= 2
            ) {

                $phrases[] =
                    implode(
                        ' ',
                        $phraseWords
                    );
            }
        }


        return array_values(
            array_unique(
                $phrases
            )
        );
    }


    /**
     * Count words in a normalized phrase.
     */
    private function wordCount(
        string $text
    ): int {

        $words =
            preg_split(
                '/\s+/u',
                trim($text)
            );


        return count(
            array_filter(
                $words,
                fn ($word) =>
                    $word !== ''
            )
        );
    }


    /**
     * Determine whether a complete phrase exists inside text.
     */
    private function containsPhrase(
        string $haystack,
        string $phrase
    ): bool {

        $haystack =
            $this->normalize(
                $haystack
            );


        $phrase =
            $this->normalize(
                $phrase
            );


        if (
            $haystack === '' ||
            $phrase === ''
        ) {
            return false;
        }


        /*
         * Surrounding spaces provide simple word boundaries.
         *
         * Therefore "farmer" does not match "farmerhood".
         */
        return str_contains(
            ' ' . $haystack . ' ',
            ' ' . $phrase . ' '
        );
    }
}