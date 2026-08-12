<?php

namespace App\Services;

use App\Models\Faq;
use Illuminate\Support\Str;

class FaqSimilarityService
{
    /**
     * Find FAQs that are potentially similar to a Support Request.
     *
     * This service is read-only.
     * It never creates, updates, or deletes FAQ records.
     */
    public function findSimilar(
        string $question,
        ?int $agencyId = null,
        int $limit = 5
    ): array {
        /*
         * Normalize the Support Request question once.
         *
         * The same normalized representation will be compared
         * against each FAQ field.
         */
        $sourceTokens = $this->tokenize($question);

        /*
         * A question without meaningful tokens cannot produce
         * a trustworthy similarity result.
         */
        if (empty($sourceTokens)) {
            return [];
        }

        /*
         * Retrieve active FAQs only.
         *
         * Soft-deleted FAQs are automatically excluded because
         * the Faq model uses Laravel's SoftDeletes trait.
         */
        $faqs = Faq::query()
            ->select([
                'id',
                'agency_id',
                'question',
                'question_fil',
                'keywords',
            ])
            ->with('agency:id,agency_name')
            ->get();

        /*
         * Score every FAQ independently.
         */
        $matches = $faqs
            ->map(function (Faq $faq) use ($sourceTokens, $agencyId) {

                /*
                 * Compare the Support Request against the
                 * English FAQ question.
                 *
                 * The English question receives the strongest weight
                 * because it is the official FAQ source content.
                 */
                $englishTokens = $this->tokenize(
    $faq->question ?? ''
);

$englishScore = $this->jaccardSimilarity(
    $sourceTokens,
    $englishTokens
);

$filipinoTokens = $this->tokenize(
    $faq->question_fil ?? ''
);

$filipinoScore = $this->jaccardSimilarity(
    $sourceTokens,
    $filipinoTokens
);

$keywordScore = $this->jaccardSimilarity(
    $sourceTokens,
    $this->tokenize($faq->keywords ?? '')
);

$exactMatch =
    $sourceTokens === $englishTokens ||
    $sourceTokens === $filipinoTokens;

                /*
 * Use the strongest question-language match.
 *
 * A Support Request may be written in English, Filipino,
 * or Taglish. Therefore, the strongest match between the
 * English and Filipino FAQ questions is the most useful
 * question-level signal.
 */
$questionScore = max(
    $englishScore,
    $filipinoScore
);

/*
 * Combine the strongest question similarity with keywords.
 *
 * The actual FAQ questions receive the majority of the weight.
 * Keywords provide supporting evidence but cannot dominate
 * the actual question similarity.
 */
$score = (
    ($questionScore * 0.85) +
    ($keywordScore * 0.15)
);

/*
 * Exact normalized matches are definitive.
 *
 * This is evaluated after the normal score calculation so
 * the normal ranking logic remains intact for non-exact
 * questions.
 */
if ($exactMatch) {
    $score = 1.0;
}

                /*
                 * Give a small relevance boost when the FAQ belongs
                 * to the same agency as the Support Request.
                 *
                 * This is intentionally only a ranking signal.
                 * FAQs from other agencies are still allowed to appear.
                 */
                if (
                    $agencyId !== null &&
                    $faq->agency_id === $agencyId
                ) {
                    $score += 0.08;
                }

                /*
                 * Never allow the score to exceed 100%.
                 */
                $score = min($score, 1);

                /*
                 * Return only information the frontend needs.
                 */
                return [
                    'id' => $faq->id,

                    'agency_id' => $faq->agency_id,

                    'agency_name' =>
                        $faq->agency?->agency_name,

                    'question' =>
                        $faq->question,

                    'question_fil' =>
                        $faq->question_fil,

                    'score' =>
                        round($score, 4),

                    'percentage' =>
                        round($score * 100),
                ];
            })

            /*
             * Ignore weak candidates.
             *
             * We don't want the admin seeing dozens of unrelated FAQs.
             */
            ->filter(function (array $match) {
                return $match['score'] >= 0.30;
            })

            /*
             * Highest similarity appears first.
             */
            ->sortByDesc('score')

            /*
             * Only return the strongest candidates.
             */
            ->take($limit)

            ->values()

            ->all();

        return $matches;
    }

    /**
     * Normalize text into meaningful comparison tokens.
     */
    private function tokenize(string $text): array
    {
        /*
         * Convert everything to lowercase.
         *
         * "Requirements" and "requirements" should be identical.
         */
        $text = Str::lower($text);

        /*
         * Normalize common Filipino/Taglish expressions before
         * punctuation is removed.
         *
         * These are deliberately small, high-confidence mappings.
         */
        $text = $this->normalizeCommonPhrases($text);

        /*
         * Convert accented characters to their simpler forms.
         */
        $text = Str::ascii($text);

        /*
         * Replace punctuation and symbols with spaces.
         *
         * This makes:
         *
         * "blotter?"
         * "blotter"
         *
         * equivalent.
         */
        $text = preg_replace(
            '/[^\p{L}\p{N}]+/u',
            ' ',
            $text
        );

        /*
         * Split the normalized sentence into words.
         */
        $tokens = preg_split(
            '/\s+/u',
            trim($text),
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        /*
         * Words that provide little useful information when
         * comparing FAQ meaning.
         */
        $stopWords = [
            // English
            'the',
            'a',
            'an',
            'is',
            'are',
            'to',
            'of',
            'for',
            'in',
            'on',
            'at',
            'and',
            'or',
            'how',
            'what',
            'where',
            'when',
            'who',
            'can',
            'do',
            'does',
            'i',
            'you',
            'my',
            'your',

            // Filipino / Taglish
            'ang',
            'ng',
            'mga',
            'sa',
            'para',
            'ito',
            'iyan',
            'iyon',
            'ay',
            'at',
            'o',
            'na',
            'ba',
            'po',
            'opo',
            'ako',
            'mo',
            'ko',
            'niya',
            'natin',
            'namin',
            'pwede',
            'paano',
            'ano',
            'may',
            'meron',
        ];

        /*
         * Remove insignificant tokens.
         *
         * Very short words usually contribute little to similarity.
         */
        $tokens = array_filter(
            $tokens,
            function (string $token) use ($stopWords) {
                return mb_strlen($token) >= 3
                    && !in_array($token, $stopWords, true);
            }
        );

        /*
         * Remove duplicate words.
         *
         * Repeating "requirements" five times should not artificially
         * increase the similarity score.
         */
        return array_values(
            array_unique($tokens)
        );
    }

    /**
     * Normalize high-confidence Filipino/Taglish phrases.
     *
     * This is intentionally limited to common public-information
     * terminology. It should not attempt to become a complete
     * Filipino translation dictionary.
     */
    private function normalizeCommonPhrases(string $text): string
    {
        /*
         * Convert common variations into a shared canonical term.
         *
         * Example:
         *
         * "requirements"
         * "requirement"
         * "kailangan"
         *
         * all become:
         *
         * "requirement"
         */
        $replacements = [
            // Requirements
            '/\brequirements?\b/u' => 'requirement',
            '/\bkailangan\b/u' => 'requirement',
            '/\bkinakailangan\b/u' => 'requirement',

            
            // Documents
            '/\bdocuments?\b/u' => 'document',
            '/\bdokumento(?:ng)?\b/u' => 'document',
            '/\bdokumentos?\b/u' => 'document',

            // Requests
            '/\brequests?\b/u' => 'request',
            '/\brequesting\b/u' => 'request',
            '/\bhumingi\b/u' => 'request',
            '/\bhihingi\b/u' => 'request',
            '/\bmag[-\s]?request\b/u' => 'request',
            '/\bmakapag[-\s]?request\b/u' => 'request',
            '/\bpag[-\s]?request\b/u' => 'request',

            // Assistance
            '/\bassistance\b/u' => 'assistance',
            '/\btulong\b/u' => 'assistance',

            // Apply / application
            '/\bapplications?\b/u' => 'application',
            '/\bapplying\b/u' => 'application',
            '/\bapply\b/u' => 'application',
            '/\bmag[-\s]?apply\b/u' => 'application',
            '/\bpag[-\s]?apply\b/u' => 'application',

            // File / filing
            '/\bfiling\b/u' => 'file',
            '/\bfile\b/u' => 'file',
            '/\bmag[-\s]?file\b/u' => 'file',
            '/\bpag[-\s]?file\b/u' => 'file',
            '/\bmakapag[-\s]?file\b/u' => 'file',

            // Obtain / get
            '/\bobtain\b/u' => 'obtain',
            '/\bobtaining\b/u' => 'obtain',
            '/\bkuha\b/u' => 'obtain',
            '/\bkumuha\b/u' => 'obtain',
            '/\bmakakuha\b/u' => 'obtain',
        ];

        /*
         * Apply each controlled normalization rule.
         */
        return preg_replace(
            array_keys($replacements),
            array_values($replacements),
            $text
        );
    }

    /**
     * Calculate Jaccard similarity between two token sets.
     */
    private function jaccardSimilarity(
        array $source,
        array $candidate
    ): float {
        /*
         * An empty candidate cannot be considered similar.
         */
        if (
            empty($source) ||
            empty($candidate)
        ) {
            return 0;
        }

        /*
         * Find words shared by both texts.
         */
        $intersection = array_intersect(
            $source,
            $candidate
        );

        /*
         * Build the complete set of unique words.
         */
        $union = array_unique(
            array_merge(
                $source,
                $candidate
            )
        );

        /*
         * Prevent division by zero.
         */
        if (count($union) === 0) {
            return 0;
        }

        /*
         * Jaccard similarity:
         *
         * shared concepts / total concepts
         */
        return count($intersection) / count($union);
    }
}