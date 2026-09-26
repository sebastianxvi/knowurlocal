<?php

namespace App\Services;

use App\Models\Faq;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * AI-assisted FAQ selector.
 *
 * The AI is strictly a selector. It never writes or rewrites an answer.
 * Candidate retrieval stays local and conservative; the model only
 * compares agency, administrator keywords, and bilingual FAQ questions.
 */
class FaqAiMatcherService
{
    public function __construct(private OpenRouterService $ai)
    {
    }

    public function match(string $question, ?int $agencyId = null, int $limit = 40): ?array
    {
        $question = trim($question);

        if ($question === '') {
            return null;
        }

        $terms = $this->terms($question);

        /*
         * A short message without meaningful terms cannot be matched
         * safely. Greetings/thanks are handled before this service.
         */
        if ($terms === []) {
            return null;
        }

        $query = Faq::query()
            ->with('agency')
            ->whereNotNull('question')
            ->whereNotNull('agency_id');

        if ($agencyId !== null) {
            $query->where('agency_id', $agencyId);
        } else {
            /*
             * Candidate retrieval is intentionally broad enough for
             * AI matching, but bounded so a large FAQ table never gets
             * serialized into an expensive provider request.
             */
            $query->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $like = '%' . addcslashes($term, '%_\\') . '%';

                    $q->orWhere('question', 'LIKE', $like)
                        ->orWhere('question_fil', 'LIKE', $like)
                        ->orWhere('keywords', 'LIKE', $like)
                        ->orWhereHas('agency', function ($agencyQuery) use ($like) {
                            $agencyQuery
                                ->where('agency_name', 'LIKE', $like)
                                ->orWhere('agency_abbreviation', 'LIKE', $like);
                        });
                }
            });
        }

        $faqs = $query
            ->select([
                'id',
                'agency_id',
                'question',
                'question_fil',
                'keywords',
            ])
            ->limit(min(max($limit, 5), 40))
            ->get();

        if ($faqs->isEmpty()) {
            return null;
        }

        /*
         * Exact matches do not need an external AI call.
         * This is both faster and deterministic.
         */
        $normalizedQuestion = $this->normalize($question);

        $exact = $faqs->first(function (Faq $faq) use ($normalizedQuestion) {
            return $normalizedQuestion !== ''
                && in_array(
                    $normalizedQuestion,
                    array_filter([
                        $this->normalize($faq->question),
                        $this->normalize($faq->question_fil),
                    ]),
                    true
                );
        });

        if ($exact) {
            return [
                'faq' => $exact,
                'confidence' => 1.0,
                'method' => 'rule',
            ];
        }

        $candidates = $faqs->map(fn (Faq $faq) => [
            'id' => (int) $faq->id,
            'agency' => $faq->agency?->agency_name,
            'agency_abbreviation' => $faq->agency?->agency_abbreviation,
            'keywords' => $this->cleanKeywords((string) $faq->keywords),
            'question_en' => (string) $faq->question,
            'question_fil' => (string) ($faq->question_fil ?? ''),
        ])->values()->all();

        $payload = json_encode([
            'user_input' => trim($question),
            'candidates' => $candidates,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($payload === false) {
            throw new RuntimeException('Unable to prepare FAQ matching data.');
        }

        $response = $this->ai->chat([
            [
                'role' => 'system',
                'content' => <<<'PROMPT'
You are KNOWURLOCAL's FAQ selector.

Your ONLY job is to select an existing FAQ candidate that matches the user's request.
You are NOT an answer generator.

Compare all three signals together:
1. agency / agency abbreviation
2. administrator-provided keywords
3. English and Filipino/Taglish FAQ questions

Rules:
- The selected FAQ must already exist in the candidate list.
- Prefer an exact agency match when the user names an agency.
- If the user names an agency that conflicts with a candidate, reject it.
- Keywords are evidence, not sufficient proof by themselves.
- Compare the actual meaning and requested information, not just shared words.
- English and Filipino/Taglish can be semantically equivalent.
- Do not combine multiple FAQs.
- Do not generate or rewrite any answer.
- If no candidate clearly matches, return faq_id null.
- Use a high confidence only when agency/context + keywords/question meaning agree.

Return ONLY JSON:
{"faq_id":123,"confidence":0.94}

If no reliable match exists:
{"faq_id":null,"confidence":0.0}
PROMPT,
            ],
            [
                'role' => 'user',
                'content' => $payload,
            ],
        ], 0.0);

        $content = data_get($response, 'choices.0.message.content');
        if (!is_string($content) || trim($content) === '') {
            throw new RuntimeException('FAQ matcher returned an empty response.');
        }

        $content = trim(preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content));
        $result = json_decode($content, true);
        if (!is_array($result) || !array_key_exists('faq_id', $result) || !is_numeric($result['confidence'] ?? null)) {
            throw new RuntimeException('FAQ matcher returned invalid JSON.');
        }

        $faqId = $result['faq_id'] === null ? null : (int) $result['faq_id'];
        $confidence = (float) $result['confidence'];

        if ($confidence < 0 || $confidence > 1) {
            throw new RuntimeException('FAQ matcher returned invalid confidence.');
        }

        if ($faqId === null) {
            return null;
        }

        $selected = $faqs->firstWhere('id', $faqId);
        if (!$selected || $confidence < 0.82) {
            return null;
        }

        return [
            'faq' => $selected,
            'confidence' => $confidence,
            'method' => 'ai',
        ];
    }

    private function normalize(?string $text): string
    {
        $text = mb_strtolower(trim((string) $text), 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{N}\s-]+/u', ' ', $text) ?? '';
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';

        return trim($text);
    }

    private function terms(string $text): array
    {
        $normalized = mb_strtolower($text, 'UTF-8');
        $normalized = preg_replace('/[^\p{L}\p{N}\s-]+/u', ' ', $normalized) ?? '';
        $words = preg_split('/\s+/u', trim($normalized), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $stop = [
            'the','a','an','and','or','is','are','am','i','me','my','you','your','do','does','did','how','what','where','when','why','who','can','could','would','should','to','for','of','in','on','at','with','from','about','this','that','it','be','have','has','had',
            'ang','ng','mga','sa','para','kay','at','o','ay','ako','ko','mo','niya','ito','iyon','kung','ba','po','opo','bang','raw','daw','ano','anong','paano','saan','sino','kailan'
        ];
        return collect($words)
            ->filter(fn ($word) => mb_strlen($word, 'UTF-8') >= 3 && !in_array($word, $stop, true))
            ->unique()
            ->take(12)
            ->values()
            ->all();
    }

    private function cleanKeywords(string $keywords): array
    {
        return collect(preg_split('/[,\n]+/', $keywords, -1, PREG_SPLIT_NO_EMPTY) ?: [])
            ->map(fn ($value) => trim($value))
            ->filter()
            ->map(fn ($value) => mb_strtolower($value, 'UTF-8'))
            ->unique()
            ->values()
            ->all();
    }
}
