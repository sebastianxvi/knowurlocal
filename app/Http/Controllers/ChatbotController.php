<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Faq;
use App\Models\ChatbotLog;
use App\Models\SupportRequest;
use Illuminate\Http\Request;
use App\Services\OpenRouterService;
use App\Services\FaqMatcherService;
use App\Services\FaqSemanticMatcherService;
use App\Services\FaqIntentService;

class ChatbotController extends Controller
{
    /*
     * Service responsible for optional AI-powered operations.
     *
     * AI is intentionally treated as an enhancement rather than
     * the primary source of FAQ answers.
     */
    private OpenRouterService $ai;

    /*
     * Rule-based FAQ matcher.
     *
     * This is the primary FAQ retrieval mechanism.
     */
    private FaqMatcherService $faqMatcher;

    /*
     * Optional semantic FAQ matcher.
     *
     * This is only used when the rule-based matcher cannot
     * confidently answer the question.
     */
    private FaqSemanticMatcherService $faqSemanticMatcher;

    /*
     * Rule-based intent detector.
     *
     * This determines whether the user is asking about
     * requirements, procedure, eligibility, fees, etc.
     */
    private FaqIntentService $faqIntent;


    /**
     * Determine whether the user's wording is generic enough
     * that two strong FAQ candidates should be presented
     * for clarification instead of guessing.
     *
     * Example:
     *
     * "What do I need for a Private Land Timber Permit?"
     *
     * This is generic because the user did not specify
     * whether they mean documents, land title, or another
     * particular requirement.
     */
    private function isGenericFaqQuestion(string $question): bool
    {
        /*
         * Normalize the question before performing comparisons.
         *
         * Lowercase text makes matching case-insensitive.
         *
         * Collapsing whitespace prevents formatting differences
         * from affecting the pattern checks.
         */
        $text = mb_strtolower(
            trim(
                preg_replace('/\s+/', ' ', $question)
            ),
            'UTF-8'
        );

        /*
         * These indicators identify a specific requirement.
         *
         * If the user explicitly mentions one of these,
         * we should not force a clarification between
         * otherwise similar FAQs.
         *
         * Example:
         *
         * "What documents do I need?"
         *
         * contains "documents", so it is specific enough
         * to answer directly.
         */
        $specificIndicators = [
            'document',
            'documents',
            'paper',
            'papers',
            'land title',
            'title',
            'bring',
            'dalhin',
            'dokumento',
            'papeles',
        ];

        /*
         * Check specific indicators FIRST.
         *
         * This ordering is important.
         *
         * "What documents do I need?"
         * contains "what do I need", but "documents" makes
         * the question specific.
         */
        foreach ($specificIndicators as $indicator) {

            if (str_contains($text, $indicator)) {
                return false;
            }
        }

        /*
         * These patterns represent broad requirements questions
         * where the user has not specified the exact information
         * they need.
         */
        $genericPatterns = [
            'what do i need',
            'what are the requirements',
            'what requirements do i need',
            'what requirements should i prepare',

            'anong kailangan',
            'anong mga kailangan',
            'ano ang kailangan',
            'ano kailangan',
            'ano mga kailangan',
            'ano ang mga kailangan',

            'anong requirements',
            'anong mga requirements',
            'ano ang requirements',
            'ano mga requirements',
            'ano ang mga requirements',
        ];

        /*
         * Return true when the user's question matches one
         * of the broad requirement patterns.
         */
        foreach ($genericPatterns as $pattern) {

            if (str_contains($text, $pattern)) {
                return true;
            }
        }

        /*
         * The question is not generic.
         */
        return false;
    }


    /**
     * Detect whether the user is asking in Filipino/Taglish.
     *
     * This method only selects which already-approved FAQ
     * answer should be returned.
     *
     * It does not determine whether the question matches.
     */
    private function detectResponseLanguage(string $question): string
    {
        /*
         * Normalize the question for language detection.
         */
        $text = mb_strtolower(
            trim(
                preg_replace('/\s+/', ' ', $question)
            ),
            'UTF-8'
        );

        /*
         * Conservative Filipino/Taglish indicators.
         */
        $filipinoIndicators = [
            'ano',
            'anong',
            'paano',
            'saan',
            'sino',
            'kailan',
            'magkano',
            'kailangan',
            'kailangan ko',
            'kailangan kong',
            'papeles',
            'dokumento',
            'mga dokumento',
            'mga kailangan',
            'dalhin',
            'kumuha',
            'makakuha',
            'mag-apply',
            'mag apply',
            'para sa',
            'pagkuha',
            'pwede',
            'puwede',
            'maaari',
            'saan ang',
            'ano ang',
            'ano mga',
            'anong mga',
        ];

        /*
         * If any strong Filipino indicator is found,
         * use the Filipino approved answer.
         */
        foreach ($filipinoIndicators as $indicator) {

            if (str_contains($text, $indicator)) {
                return 'fil';
            }
        }

        /*
         * English is the safe default.
         */
        return 'en';
    }


    /**
     * Create the controller and inject its dependencies.
     *
     * Laravel's service container resolves these services
     * automatically.
     */
    public function __construct(
        OpenRouterService $ai,
        FaqMatcherService $faqMatcher,
        FaqSemanticMatcherService $faqSemanticMatcher,
        FaqIntentService $faqIntent
    ) {
        $this->ai = $ai;
        $this->faqMatcher = $faqMatcher;
        $this->faqSemanticMatcher = $faqSemanticMatcher;
        $this->faqIntent = $faqIntent;
    }


    /**
     * Record a chatbot interaction.
     *
     * Logging must never interrupt the chatbot.
     *
     * If the database logging operation fails, the user
     * should still receive the normal chatbot response.
     */
    private function logChat(
        string $question,
        string $answer,
        string $outcome,
        ?string $matchMethod = null,
        ?int $agencyId = null,
        ?int $faqId = null,
        ?int $score = null
    ): void {
        try {

            ChatbotLog::create([
                /*
                 * Authentication supplies the user ID.
                 *
                 * We never trust a user_id sent from the browser.
                 */
                'user_id' => auth()->id(),

                /*
                 * Store the original question.
                 */
                'question' => $question,

                /*
                 * Store the answer actually returned.
                 */
                'answer' => $answer,

                /*
                 * Store the agency associated with the chat.
                 */
                'agency_id' => $agencyId,

                /*
                 * Store the FAQ that supplied the answer.
                 *
                 * This remains null for clarification,
                 * greetings, fallback, and unrelated questions.
                 */
                'faq_id' => $faqId,

                /*
                 * Store the interaction outcome.
                 */
                'outcome' => $outcome,

                /*
                 * Store how the answer was obtained.
                 */
                'match_method' => $matchMethod,

                /*
                 * Store the confidence score when available.
                 */
                'score' => $score,

                /*
                 * Capture the request IP for auditing.
                 */
                'ip_address' => request()->ip(),
            ]);

        } catch (\Throwable $e) {

            /*
             * Logging failure must never expose internal
             * application details to the user.
             */
            \Log::warning(
                'KNOWURLOCAL chatbot interaction logging failed.',
                [
                    'error' => $e->getMessage(),
                ]
            );
        }
    }


    /**
     * Send a request to the optional AI service.
     *
     * AI is only used for classification or semantic matching.
     */
    private function askAI(array $messages): array
    {
        return $this->ai->chat($messages, 0.3);
    }


    /**
     * Return FAQ suggestions for the chatbot.
     *
     * Frequently used FAQs are prioritized.
     */
    public function suggestions()
    {
        /*
         * Find FAQs that have actually been answered
         * successfully by the chatbot.
         */
        $popularFaqIds = ChatbotLog::query()
            ->where('outcome', 'answered')
            ->whereNotNull('faq_id')
            ->select('faq_id')
            ->selectRaw('COUNT(*) as usage_count')
            ->groupBy('faq_id')
            ->orderByDesc('usage_count')
            ->limit(15)
            ->pluck('faq_id');

        /*
         * Retrieve only the fields needed by the frontend.
         */
        $popularFaqs = Faq::query()
            ->select('id', 'question')
            ->whereNotNull('question')
            ->whereIn('id', $popularFaqIds)
            ->get()
            ->sortBy(function ($faq) use ($popularFaqIds) {

                /*
                 * Preserve popularity ordering.
                 */
                return $popularFaqIds->search(
                    $faq->id
                );
            })
            ->values();

        /*
         * Calculate how many additional FAQs are required
         * to reach the desired 15 suggestions.
         */
        $remainingCount = max(
            0,
            15 - $popularFaqs->count()
        );

        if ($remainingCount > 0) {

            /*
             * Prevent duplicate FAQ suggestions.
             */
            $excludedIds = $popularFaqs
                ->pluck('id')
                ->all();

            /*
             * Fill the remaining slots with random FAQs.
             */
            $additionalFaqs = Faq::query()
                ->select('id', 'question')
                ->whereNotNull('question')
                ->when(
                    !empty($excludedIds),
                    fn ($query) =>
                        $query->whereNotIn('id', $excludedIds)
                )
                ->inRandomOrder()
                ->limit($remainingCount)
                ->get();

            $popularFaqs = $popularFaqs
                ->concat($additionalFaqs);
        }

        /*
         * Expose only the information the browser needs.
         */
        return response()->json(
            $popularFaqs->map(fn ($faq) => [
                'id' => $faq->id,
                'question' => $faq->question,
            ])->values()
        );
    }


    /**
     * Submit a question for human assistance.
     */
    public function submitSupportRequest(Request $request)
    {
        /*
         * Validate all browser-supplied data before
         * performing any database operation.
         */
        $validated = $request->validate([
            'question' => [
                'required',
                'string',
                'max:500',
            ],

            'agency_id' => [
                'nullable',
                'exists:agencies,id',
            ],
        ]);

        /*
         * Normalize whitespace.
         *
         * This makes duplicate detection more consistent.
         */
        $question = trim(
            preg_replace(
                '/\s+/',
                ' ',
                $validated['question']
            )
        );

        /*
         * Check whether this authenticated user already has
         * an identical pending support request.
         */
        $duplicatePendingRequest =
            SupportRequest::where(
                'user_id',
                auth()->id()
            )
            ->where(
                'question',
                $question
            )
            ->where(
                'status',
                'pending'
            )
            ->exists();

        /*
         * Prevent duplicate pending submissions.
         */
        if ($duplicatePendingRequest) {

            return response()->json([
                'success' => false,

                'message' =>
                    'You already have a pending request with the same question.',
            ], 422);
        }

        /*
         * Create the support request only after validation
         * and duplicate protection succeed.
         */
        SupportRequest::create([
            'user_id' =>
                auth()->id(),

            'agency_id' =>
                $validated['agency_id'] ?? null,

            'question' =>
                $question,

            'ip_address' =>
                $request->ip(),
        ]);

        /*
         * Return a safe success response.
         */
        return response()->json([
            'success' => true,

            'message' =>
                'Your question has been sent to a human assistant.',
        ]);
    }


    /**
     * Determine whether a question belongs to the
     * KNOWURLOCAL public-information helpdesk scope.
     *
     * This is intentionally used only AFTER rule-based
     * and semantic FAQ matching.
     */
    private function isRelevant(string $question): ?bool
    {
        try {

            $response = $this->askAI([
                [
                    'role' => 'system',

                    'content' => <<<'PROMPT'
You are the scope classifier for KNOWURLOCAL.

KNOWURLOCAL is a public-information helpdesk for
citizens of San Jose, Occidental Mindoro.

Its purpose is to help users understand the documented
services and office information of registered NGAs and
NGOs in the KNOWURLOCAL system.

Your task is ONLY to determine whether the user's question
belongs to the KNOWURLOCAL HELPDESK scope.

Return ONLY:

YES

or

NO


========================
IN-SCOPE QUESTIONS
========================

Return YES when the user is asking about a documented
agency or NGO service, process, or office information.

This includes questions about:

1. SERVICES
- What services does this agency provide?
- What can I apply for at this office?
- What assistance or programs does this agency offer?

2. REQUIREMENTS
- What documents are required?
- What do I need to bring?
- Do I need a valid ID?
- What are the requirements for this application?

3. PROCEDURES
- How do I apply?
- What are the steps?
- How do I register?
- How do I obtain this document or service?
- What should I do to complete the process?

4. ELIGIBILITY
- Who can apply?
- Who is qualified?
- Am I eligible for this service?

5. FEES
- Is there a fee?
- How much does the application cost?
- Is this service free?

6. PROCESSING INFORMATION
- How long does processing take?
- When can I claim the document?
- How long before the application is completed?

7. AGENCY INFORMATION
- What does this agency do?
- What is this agency responsible for?
- What programs does this agency handle?

8. OFFICE INFORMATION
- What are the office hours?
- What is the agency's contact number?
- What is the agency's email?
- Where is this specific agency office located?
- How can I contact or visit this agency?


========================
OUT-OF-SCOPE QUESTIONS
========================

Return NO when the question is not a KNOWURLOCAL
helpdesk question.

This includes:

1. GENERAL KNOWLEDGE
- What is the weather?
- Who is the president?
- What is inflation?
- Explain a general topic.

2. GENERAL EMERGENCY ADVICE
- What should I do during an emergency?
- Who should I call during an emergency?
- How do I get emergency help?
- How can I get police assistance right now?

IMPORTANT:

Simply mentioning a government agency does NOT
automatically make a question in scope.

For example:

"Paano humingi ng tulong sa police kapag emergency?"

should be NO if the question is asking for general
emergency assistance rather than information about a
documented service or procedure in KNOWURLOCAL.

3. GENERAL NAVIGATION
- Where is the nearest police station?
- What is the nearest hospital?
- Which office is closest to me?

KNOWURLOCAL has map and agency-location features for
agency discovery. The chatbot should not become a
general navigation assistant.

However:

"Where is the DSWD office?"

may be YES because it asks for the location of a
specific agency registered in KNOWURLOCAL.

4. PERSONAL ADVICE
- What should I do about my personal legal problem?
- What medicine should I take?
- What decision should I make?

5. GENERAL RECOMMENDATIONS
- Which agency is best for me?
- Which hospital should I choose?
- Which organization should I use?

6. UNRELATED TOPICS
- Sports
- Entertainment
- Weather
- Mathematics
- General trivia
- Creative writing
- Casual questions unrelated to the helpdesk

7. INFORMATION NOT DOCUMENTED BY KNOWURLOCAL

A question may be related to government but still be
outside the chatbot's intended scope if it asks for
general knowledge that is not part of the documented
agency FAQ/helpdesk information.


========================
IMPORTANT DISTINCTION
========================

Do NOT confuse:

"Is this a KNOWURLOCAL helpdesk question?"

with:

"Does KNOWURLOCAL currently have an answer?"

For example:

"What are the requirements for a government service?"

is IN SCOPE even if the FAQ database currently has
no answer for that service.

The correct result is:

YES

The controller will then handle the "no FAQ found"
case separately.

Likewise:

"What's the weather today?"

is OUT OF SCOPE.

The correct result is:

NO


========================
EXAMPLES
========================

YES:
"What are the requirements for this application?"

YES:
"Paano mag-apply?"

YES:
"What documents do I need?"

YES:
"What time does the office open?"

YES:
"What services does this agency provide?"

YES:
"Where is the DSWD office?"

YES:
"How much is the processing fee?"

NO:
"What's the weather today?"

NO:
"Where is the nearest police station?"

NO:
"Who should I call during an emergency?"

NO:
"Paano humingi ng tulong sa police kapag emergency?"

NO:
"What medicine should I take?"

NO:
"Which government agency is best for me?"

NO:
"Tell me a joke."


========================
FINAL RULE
========================

Return ONLY YES or NO.

Do not explain your decision.
Do not answer the user's question.
Do not generate any additional text.
PROMPT
                ],

                [
                    'role' => 'user',
                    'content' => $question
                ]
            ]);

            /*
             * Extract the classifier's response.
             */
            $reply = strtoupper(
                trim(
                    $response['choices'][0]['message']['content'] ?? ''
                )
            );

            /*
             * Only accept exact YES or NO responses.
             *
             * Unexpected output fails closed.
             */
            if ($reply === 'YES') {
                return true;
            }

            if ($reply === 'NO') {
                return false;
            }

            /*
             * Malformed classifier output is treated as
             * an unsuccessful scope decision.
             */
            return false;

        } catch (\Throwable $e) {

            /*
             * An external AI failure is not necessarily
             * a user error.
             *
             * null means that scope determination was
             * unavailable.
             */
            \Log::warning(
                'KNOWURLOCAL scope classification failed.',
                [
                    'error' => $e->getMessage(),
                ]
            );

            return null;
        }
    }


    /**
     * Main chatbot endpoint.
     */
    public function ask(Request $request)
    {
        /*
         * Validate all browser-supplied input.
         *
         * The agency ID must exist when supplied.
         */
        $validated = $request->validate([
    'message' => [
        'required',
        'string',
        'max:1000',
    ],

    'agency_id' => [
        'nullable',
        'integer',
        'exists:agencies,id',
    ],

    /*
     * When the user clicks a clarification choice,
     * the frontend sends the selected FAQ ID.
     *
     * Laravel validates the value before using it.
     */
    'faq_id' => [
        'nullable',
        'integer',
        'exists:faqs,id',
    ],
]);

        /*
         * Trim unnecessary whitespace from the user's message.
         */
        $question = trim($validated['message']);

        /*
         * Store the selected agency context, if any.
         */
        $agencyId = $validated['agency_id'] ?? null;

        /*
 * ============================================================
 * 🎯 SELECTED FAQ FROM CLARIFICATION
 * ============================================================
 *
 * When the user clicks one of the "So you mean?" capsules,
 * the frontend sends the selected FAQ ID.
 *
 * The server remains authoritative:
 *
 * Browser → FAQ ID
 * Laravel → verifies FAQ
 * Laravel → retrieves approved answer
 * Laravel → returns answer
 *
 * The browser never supplies the actual answer.
 */
if (!empty($validated['faq_id'])) {

    /*
     * Convert the validated FAQ ID to an integer.
     *
     * Validation has already guaranteed that this is an
     * existing integer ID.
     */
    $selectedFaqId =
        (int) $validated['faq_id'];

    /*
     * Retrieve the FAQ from the database.
     *
     * When an agency context exists, also require the FAQ
     * to belong to that agency.
     *
     * This prevents a user from selecting an FAQ belonging
     * to another agency while chatting inside the current
     * agency context.
     */
    $selectedFaq =
        Faq::query()
            ->where('id', $selectedFaqId)
            ->when(
                $agencyId !== null,
                fn ($query) =>
                    $query->where(
                        'agency_id',
                        $agencyId
                    )
            )
            ->first();

    /*
     * If the FAQ does not belong to the current context,
     * do not expose its contents.
     */
    if (!$selectedFaq) {

        return response()->json([
            'choices' => [[
                'message' => [
                    'content' =>
                        'That FAQ is not available in the current agency context.'
                ]
            ]]
        ], 403);
    }

    /*
     * Determine which approved answer language should be
     * displayed based on the original selected question.
     */
    $responseLanguage =
        $this->detectResponseLanguage(
            $question
        );

    /*
     * Prefer the approved Filipino answer when the user's
     * question is Filipino/Taglish and that translation exists.
     *
     * Otherwise use the approved English answer.
     */
    if (
        $responseLanguage === 'fil' &&
        filled($selectedFaq->answer_fil)
    ) {

        $reply =
            $selectedFaq->answer_fil;

    } else {

        $reply =
            $selectedFaq->answer;
    }

    /*
     * Log the exact FAQ selected by the user.
     *
     * This is recorded as a rule-based answer because
     * no AI was required to select it.
     */
    $this->logChat(
        $question,
        $reply,
        'answered',
        'rule',
        $selectedFaq->agency_id,
        $selectedFaq->id,
        100
    );

    /*
     * Return only the approved database answer.
     *
     * The frontend is responsible for displaying it.
     */
    return response()->json([
        'choices' => [[
            'message' => [
                'content' => $reply,

                /*
                 * Preserve the existing FAQ image behavior.
                 */
                'image' =>
                    $selectedFaq->image
                        ? asset(
                            'storage/' .
                            $selectedFaq->image
                        )
                        : null,
            ],
        ]],
    ]);
}


        // ============================================================
        // 👋 SIMPLE CONVERSATIONAL INTENT
        // ============================================================

        /*
         * Greetings must be standalone messages.
         *
         * We do not use str_contains() because:
         *
         * "hi, how do I file a police report?"
         *
         * should be treated as a real helpdesk question.
         */
        $greetings = [
            'hi',
            'hello',
            'hey',
            'good morning',
            'good afternoon',
            'good evening',
            'kamusta',
            'kumusta',
        ];

        /*
         * Remove punctuation for greeting comparison.
         */
        $cleanForIntent = trim(
            preg_replace(
                '/[^\p{L}\p{N}\s]/u',
                '',
                $question
            )
        );

        /*
         * Only respond as a greeting when the entire
         * cleaned message matches an approved greeting.
         */
        if (in_array($cleanForIntent, $greetings, true)) {

            $reply =
                "Hello! How can I assist you today?";

            $this->logChat(
                $question,
                $reply,
                'greeting',
                'none',
                $agencyId
            );

            return response()->json([
                "choices" => [[
                    "message" => [
                        "content" => $reply
                    ]
                ]]
            ]);
        }


        // ============================================================
        // 🙏 THANK-YOU INTENT
        // ============================================================

        /*
         * Thank-you messages must also be standalone.
         */
        $thanks = [
            'thanks',
            'thank you',
            'salamat',
            'maraming salamat',
        ];

        /*
         * Respond only when the entire message is a
         * recognized thank-you expression.
         */
        if (in_array($cleanForIntent, $thanks, true)) {

            $reply =
                "You're welcome! Let me know if you need anything else.";

            $this->logChat(
                $question,
                $reply,
                'thanks',
                'none',
                $agencyId
            );

            return response()->json([
                "choices" => [[
                    "message" => [
                        "content" => $reply
                    ]
                ]]
            ]);
        }


        // ============================================================
        // 🔍 CURRENT AGENCY
        // ============================================================

        /*
         * Load the agency associated with the current chatbot
         * context.
         *
         * If no agency was selected, this remains null.
         */
        $currentAgency =
            $agencyId
                ? Agency::find($agencyId)
                : null;


        // ============================================================
        // 🔍 DETECT MENTIONED AGENCY
        // ============================================================

        /*
         * Attempt to determine whether the user explicitly
         * mentioned another registered agency.
         */
        $mentionedAgency = null;

        /*
         * Normalize the raw question for agency comparison.
         */
        $rawQuestion =
            mb_strtolower(
                $question,
                'UTF-8'
            );

        /*
         * Retrieve registered agencies.
         */
        foreach (Agency::all() as $agency) {

            /*
             * Normalize the agency name.
             */
            $name =
                mb_strtolower(
                    $agency->agency_name,
                    'UTF-8'
                );

            /*
             * First attempt an exact agency-name substring match.
             */
            if (str_contains($rawQuestion, $name)) {

                $mentionedAgency = $agency;

                break;
            }

            /*
             * If the full name was not found, inspect individual
             * words from the agency name.
             *
             * Four-character minimum reduces accidental matches
             * from extremely short words.
             */
            foreach (explode(' ', $name) as $word) {

                if (
                    mb_strlen($word, 'UTF-8') >= 4 &&
                    str_contains($rawQuestion, $word)
                ) {
                    $mentionedAgency = $agency;

                    break 2;
                }
            }
        }


        // ============================================================
        // ❗ WRONG AGENCY
        // ============================================================

        /*
         * If the user is currently inside one agency's chatbot
         * but explicitly asks about another agency, do not answer
         * using potentially incorrect agency context.
         */
        if (
            $agencyId &&
            $mentionedAgency &&
            $currentAgency &&
            $mentionedAgency->id != $agencyId
        ) {

            $reply =
                "It looks like your question is about {$mentionedAgency->agency_name}.\n\n" .
                "You are currently chatting with {$currentAgency->agency_name}.\n\n" .
                "Please visit {$mentionedAgency->agency_name} for accurate information.";

            $this->logChat(
                $question,
                $reply,
                'wrong_agency',
                'none',
                $agencyId
            );

            return response()->json([
                "choices" => [[
                    "message" => [
                        "content" => $reply
                    ]
                ]]
            ]);
        }


        // ============================================================
        // 🧠 INTENT DETECTION
        // ============================================================

        /*
         * Determine what kind of information the user is requesting.
         *
         * This operation is entirely rule-based.
         *
         * Examples:
         *
         * "What documents do I need?"
         *      → requirements
         *
         * "How do I apply?"
         *      → procedure
         */
        $userIntent =
            $this->faqIntent->detect(
                $question
            );


        // ============================================================
        // 🔍 RULE-BASED FAQ MATCHING
        // ============================================================

        /*
         * Search the approved FAQ database BEFORE calling AI.
         *
         * This is the most important architectural change.
         *
         * A valid rule-based FAQ must be allowed to answer
         * without an OpenRouter scope-classification request.
         */
        $faqs =
            $this->faqMatcher->match(
                $question,
                $agencyId ? (int) $agencyId : null,
                5
            );

        /*
         * The matcher returns candidates ordered from highest
         * score to lowest score.
         */
        $bestFaq =
            $faqs->first();

        /*
         * Read the best candidate's score.
         */
        $bestScore =
            $bestFaq
                ? (int) ($bestFaq->match_score ?? 0)
                : 0;


        // ============================================================
        // 🔐 MATCH CONFIDENCE THRESHOLD
        // ============================================================

        /*
         * A rule-based FAQ needs at least this score before
         * it can be used as a direct answer.
         */
        $minScore = 35;


        // ============================================================
        // ❓ AMBIGUOUS FAQ MATCH
        // ============================================================

        /*
         * Retrieve the second-best candidate.
         *
         * If there is no second candidate, get(1) returns null.
         */
        $secondFaq =
            $faqs->get(1);

        /*
         * Safely read the second candidate's score.
         */
        $secondScore =
            $secondFaq
                ? (int) ($secondFaq->match_score ?? 0)
                : 0;

        /*
         * Calculate the difference between the two candidates.
         *
         * A smaller difference means the matcher is less certain
         * which FAQ the user actually means.
         */
        $scoreDifference =
            abs(
                $bestScore - $secondScore
            );

        /*
         * Two candidates within eight points are considered
         * close enough to potentially require clarification.
         */
        $ambiguityThreshold = 8;

        /*
         * Ask for clarification only when ALL conditions are met:
         *
         * 1. Two candidates exist.
         * 2. Both are strong enough.
         * 3. Their scores are close.
         * 4. The user's wording is genuinely generic.
         *
         * This prevents:
         *
         * "What documents do I need?"
         *
         * from unnecessarily asking "So you mean?"
         */
        if (
            $bestFaq &&
            $secondFaq &&
            $bestScore >= $minScore &&
            $secondScore >= $minScore &&
            $scoreDifference <= $ambiguityThreshold &&
            $this->isGenericFaqQuestion($question)
        ) {

            /*
             * Determine whether the user expects Filipino
             * or English wording.
             */
            $responseLanguage =
                $this->detectResponseLanguage(
                    $question
                );

            /*
             * Use a short clarification prompt.
             */
            if ($responseLanguage === 'fil') {

                $reply =
                    'Alin dito ang tinutukoy mo?';

            } else {

                $reply =
                    'So you mean?';
            }

            /*
             * Log this as clarification rather than answered.
             *
             * No FAQ is selected yet because the user still
             * needs to choose between the candidates.
             */
            $this->logChat(
                $question,
                $reply,
                'clarification',
                'none',
                $agencyId
            );

            /*
             * Return the two candidate FAQs to the frontend.
             *
             * The frontend can use these IDs when the user
             * selects one of the clarification options.
             */
            return response()->json([
                "choices" => [[
                    "message" => [
                        "content" => $reply,

                        /*
                         * Flag this response as a clarification.
                         */
                        "clarification" => true,

                        /*
                         * Send only the information needed
                         * to render the choices.
                         */
                        "faqs" => [
                            [
                                "id" =>
                                    $bestFaq->id,

                                "question" =>
                                    $responseLanguage === 'fil'
                                        ? $bestFaq->question_fil
                                        : $bestFaq->question,
                            ],

                            [
                                "id" =>
                                    $secondFaq->id,

                                "question" =>
                                    $responseLanguage === 'fil'
                                        ? $secondFaq->question_fil
                                        : $secondFaq->question,
                            ],
                        ],
                    ],
                ]],
            ]);
        }


        // ============================================================
        // 🎯 STRONG RULE-BASED FAQ MATCH
        // ============================================================

        /*
         * If the best FAQ is sufficiently confident and the
         * question was not ambiguous, answer immediately.
         *
         * No OpenRouter request is made here.
         */
        if (
            $bestFaq &&
            $bestScore >= $minScore
        ) {

            /*
             * Determine response language from the actual
             * user message.
             */
            $responseLanguage =
                $this->detectResponseLanguage(
                    $question
                );

            /*
             * Use the approved Filipino answer when the user
             * appears to be speaking Filipino/Taglish and
             * an approved Filipino answer exists.
             */
            if (
                $responseLanguage === 'fil' &&
                filled($bestFaq->answer_fil)
            ) {

                $reply =
                    $bestFaq->answer_fil;

            } else {

                /*
                 * Fall back to the approved English answer.
                 */
                $reply =
                    $bestFaq->answer;
            }

            /*
             * Record the successful rule-based FAQ answer.
             */
            $this->logChat(
                $question,
                $reply,
                'answered',
                'rule',
                $bestFaq->agency_id,
                $bestFaq->id,
                $bestScore
            );

            /*
             * Return the approved answer to the frontend.
             */
            return response()->json([
                "choices" => [[
                    "message" => [
                        "content" => $reply,

                        /*
                         * Include the optional FAQ image.
                         */
                        "image" =>
                            $bestFaq->image
                                ? asset(
                                    'storage/' .
                                    $bestFaq->image
                                )
                                : null
                    ]
                ]]
            ]);
        }


        // ============================================================
        // 🧠 SEMANTIC FAQ MATCHING
        // ============================================================

        /*
         * The rule-based matcher did not find a strong enough
         * direct match.
         *
         * Only now do we allow the optional semantic matcher
         * to inspect the candidates.
         */
        if ($faqs->isNotEmpty()) {

            try {

                /*
                 * The matcher attaches the detected intent
                 * to candidate FAQ models.
                 */
                $userIntent =
                    $faqs->first()->match_user_intent
                    ?? 'other';

                /*
                 * Ask the semantic matcher whether one of
                 * the existing candidates has the same meaning.
                 *
                 * The candidate collection limits the semantic
                 * system to FAQs already retrieved by the
                 * rule-based system.
                 */
                $semanticMatch =
                    $this->faqSemanticMatcher->match(
                        $question,
                        $faqs,
                        $userIntent
                    );

                /*
                 * Only accept a semantic answer when:
                 *
                 * 1. A FAQ ID was returned.
                 * 2. Confidence is at least 85%.
                 */
                if (
                    $semanticMatch &&
                    $semanticMatch['faq_id'] !== null &&
                    $semanticMatch['confidence'] >= 0.85
                ) {

                    /*
                     * Search only inside the already-approved
                     * candidate collection.
                     *
                     * This prevents the semantic matcher from
                     * selecting an arbitrary FAQ outside the
                     * retrieved candidates.
                     */
                    $semanticFaq =
                        $faqs->first(
                            fn ($faq) =>
                                (int) $faq->id ===
                                (int) $semanticMatch['faq_id']
                        );

                    /*
                     * Continue only when the selected FAQ
                     * actually exists in our candidate collection.
                     */
                    if ($semanticFaq) {

                        /*
                         * Determine the response language locally.
                         */
                        $responseLanguage =
                            $this->detectResponseLanguage(
                                $question
                            );

                        /*
                         * Use the approved Filipino answer when
                         * appropriate and available.
                         */
                        if (
                            $responseLanguage === 'fil' &&
                            filled($semanticFaq->answer_fil)
                        ) {

                            $reply =
                                $semanticFaq->answer_fil;

                        } else {

                            /*
                             * Otherwise use the approved English
                             * answer.
                             */
                            $reply =
                                $semanticFaq->answer;
                        }

                        /*
                         * Record the semantic answer separately
                         * from rule-based answers.
                         */
                        $this->logChat(
                            $question,
                            $reply,
                            'answered',
                            'semantic',
                            $semanticFaq->agency_id,
                            $semanticFaq->id,
                            (int) round(
                                $semanticMatch['confidence'] * 100
                            )
                        );

                        /*
                         * Return the approved FAQ answer.
                         */
                        return response()->json([
                            "choices" => [[
                                "message" => [
                                    "content" => $reply,

                                    /*
                                     * Include the FAQ image when available.
                                     */
                                    "image" =>
                                        $semanticFaq->image
                                            ? asset(
                                                'storage/' .
                                                $semanticFaq->image
                                            )
                                            : null
                                ]
                            ]]
                        ]);
                    }
                }

            } catch (\Throwable $e) {

                /*
                 * Semantic matching is optional.
                 *
                 * If the external AI provider fails, the chatbot
                 * continues normally instead of returning an error.
                 */
                \Log::warning(
                    'FAQ semantic matching failed.',
                    [
                        'error' => $e->getMessage(),
                    ]
                );
            }
        }


        // ============================================================
        // 🔐 FINAL KNOWURLOCAL SCOPE CHECK
        // ============================================================

        /*
         * At this point:
         *
         * 1. No strong rule-based FAQ answered.
         * 2. No clarification was necessary.
         * 3. Semantic matching did not produce an accepted answer.
         *
         * Only now do we ask the AI scope classifier whether
         * this is actually a KNOWURLOCAL helpdesk question.
         *
         * This prevents OpenRouter from blocking valid FAQ answers.
         */
        $scopeResult =
            $this->isRelevant(
                $question
            );

        /*
         * Reject only when the classifier explicitly says NO.
         */
        if ($scopeResult === false) {

            $reply =
                "Sorry, this question is outside the scope of KNOWURLOCAL.";

            $this->logChat(
                $question,
                $reply,
                'irrelevant',
                'none',
                $agencyId
            );

            return response()->json([
                "choices" => [[
                    "message" => [
                        "content" => $reply
                    ]
                ]]
            ]);
        }


        // ============================================================
        // 📩 FINAL FALLBACK
        // ============================================================

        /*
         * The question is either:
         *
         * - in scope but not currently covered by an FAQ, or
         * - unable to be classified because the external AI service
         *   was unavailable.
         *
         * In either case, do not invent an answer.
         */
        $reply =
            "I couldn’t find an exact answer for your question.";

        /*
         * Record the fallback interaction.
         */
        $this->logChat(
            $question,
            $reply,
            'fallback',
            'none',
            $agencyId
        );

        /*
         * Tell the frontend that this is a fallback response.
         */
        return response()->json([
            "choices" => [[
                "message" => [
                    "content" => $reply,
                    "fallback" => true
                ]
            ]]
        ]);
    }
}