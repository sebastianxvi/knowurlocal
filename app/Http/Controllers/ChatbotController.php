<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Faq;
use App\Models\ChatbotLog;
use App\Events\SupportRequestCreated;
use App\Models\SupportRequest;
use Illuminate\Http\Request;
use App\Services\OpenRouterService;
use App\Services\FaqAiMatcherService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ChatbotController extends Controller
{
    /*
     * OpenRouter is used only for bounded classification tasks.
     * It never generates the official FAQ answer.
     */
    private OpenRouterService $ai;

    /*
     * The single FAQ matching service owns candidate retrieval and
     * AI selection. This avoids multiple overlapping matcher pipelines.
     */
    private FaqAiMatcherService $faqAiMatcher;


    public function __construct(
        OpenRouterService $ai,
        FaqAiMatcherService $faqAiMatcher
    ) {
        $this->ai = $ai;
        $this->faqAiMatcher = $faqAiMatcher;
    }



    /**
     * Build the exact public response from a stored FAQ.
     *
     * Nothing is translated, summarized, or rewritten here. Text blocks
     * are returned exactly as authored in the selected language. Attachments
     * are returned as their stored URLs/content so the client can render them.
     */
    private function faqResponsePayload(Faq $faq, string $question): array
    {
        $language = $this->detectResponseLanguage($question);
        $components = is_array($faq->response_components) ? $faq->response_components : [];
        $selectedLanguage = $language === 'fil' ? 'fil' : 'en';

        $texts = collect($components)
            ->filter(function ($component) use ($selectedLanguage) {
                return ($component['type'] ?? null) === 'text'
                    && (($component['language'] ?? 'en') === $selectedLanguage)
                    && trim((string) ($component['content'] ?? '')) !== '';
            })
            ->pluck('content')
            ->values()
            ->all();

        if ($texts === []) {
            $fallback = $selectedLanguage === 'fil' ? $faq->answer_fil : $faq->answer;
            if (filled($fallback)) {
                $texts = [(string) $fallback];
            }
        }

        $attachments = collect($components)
            ->filter(fn ($component) => in_array(($component['type'] ?? null), ['image','file','link','qr_code'], true))
            ->map(function ($component, $index) use ($faq) {
                $type = $component['type'];
                $url = null;
                if (in_array($type, ['image','file'], true)) {
                    $url = route('chatbot.faq-attachment', [
                        'faqId' => $faq->id,
                        'componentIndex' => $index,
                    ]);
                } else {
                    $url = $component['content'] ?? null;
                }
                return [
                    'type' => $type,
                    'label' => $component['label'] ?? null,
                    'url' => $url,
                ];
            })
            ->values()
            ->all();

        return [
            'content' => implode("\n\n", $texts),
            'attachments' => $attachments,
            'image' => filled($faq->image)
                ? asset('storage/' . ltrim((string) $faq->image, '/'))
                : null,
        ];
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
     * AI is only used for bounded FAQ selection and scope classification.
     */
    private function askAI(array $messages): array
    {
        return $this->ai->chat($messages, 0.3);
    }


    /**
     * Serve an attachment that belongs to an active FAQ.
     * The stored private path is never exposed to the client.
     */
    public function faqAttachment(int $faqId, int $componentIndex)
    {
        $faq = Faq::findOrFail($faqId);
        $components = is_array($faq->response_components) ? $faq->response_components : [];
        $component = $components[$componentIndex] ?? null;

        abort_unless(
            is_array($component) && in_array($component['type'] ?? null, ['image', 'file'], true),
            404
        );

        $path = $component['content'] ?? null;
        abort_unless(
            is_string($path) && str_starts_with($path, 'faqs/responses/'),
            404
        );

        abort_unless(Storage::disk('private')->exists($path), 404);

        return Storage::disk('private')->response(
            $path,
            basename($path),
            [
                'Content-Disposition' => 'inline; filename="' . addslashes(basename($path)) . '"',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
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
                ->latest('created_at')
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
            'integer',
            'exists:agencies,id',
        ],
    ]);

    /*
     * Normalize repeated whitespace and remove
     * unnecessary spaces at the beginning and end.
     */
    $question = trim(
        preg_replace(
            '/\s+/',
            ' ',
            $validated['question']
        )
    );

    /*
     * Check whether the authenticated user already
     * submitted the same pending question.
     */
    $duplicatePendingRequest = SupportRequest::query()
        ->where('user_id', auth()->id())
        ->where('question', $question)
        ->where('status', 'pending')
        ->exists();

    /*
     * Stop duplicate pending submissions.
     */
    if ($duplicatePendingRequest) {
        return response()->json([
            'success' => false,
            'message' => 'You already have a pending request with the same question.',
        ], 422);
    }

    /*
     * Create the support request.
     *
     * agency_id is intentionally nullable because
     * the user does not need to select an agency.
     */
    $supportRequest = SupportRequest::create([
        'user_id' => auth()->id(),
        'agency_id' => $validated['agency_id'] ?? null,
        'question' => $question,
        'status' => 'pending',
        'ip_address' => $request->ip(),
    ]);

    /*
     * Load related records for the broadcast payload.
     */
    $supportRequest->load([
        'user',
        'agency',
    ]);

    /*
     * Broadcast the newly-created request to authorized
     * admin dashboard clients.
     */
    /*
|--------------------------------------------------------------------------
| Broadcast new support request
|--------------------------------------------------------------------------
*/

Log::info('SupportRequestCreated broadcast starting', [
    'request_id' => $supportRequest->id,
    'channel' => 'admin.support-requests',
    'event' => 'support.request.created',
]);

broadcast(new SupportRequestCreated(
    id: $supportRequest->id,
    question: $supportRequest->question,
    status: $supportRequest->status,
    agencyId: $supportRequest->agency_id,
    agencyName: $supportRequest->agency?->agency_name,
    userName: $supportRequest->user?->first_name ?? 'User',
    createdAt: $supportRequest->created_at?->toIso8601String()
        ?? now()->toIso8601String(),
));

Log::info('SupportRequestCreated broadcast finished', [
    'request_id' => $supportRequest->id,
]);

    /*
     * Return a JSON response to the frontend.
     */
    return response()->json([
        'success' => true,
        'message' => 'Your question has been sent to a human assistant.',
    ], 201);
}


    /**
     * Determine whether a question belongs to the
     * KNOWURLOCAL public-information helpdesk scope.
     *
     * This is intentionally used only AFTER rule-based
     * and AI-assisted FAQ matching.
     */
    /**
     * Cheap local scope pre-check.
     *
     * This prevents an external AI call for obviously unrelated
     * messages such as weather, jokes, or casual conversation.
     */
    private function looksLikeHelpdeskQuestion(
        string $question,
        bool $hasMentionedAgency
    ): bool {
        if ($hasMentionedAgency) {
            return true;
        }

        $normalized = mb_strtolower(
            $question,
            'UTF-8'
        );

        $signals = [
            'agency',
            'office',
            'service',
            'services',
            'requirement',
            'requirements',
            'document',
            'documents',
            'permit',
            'application',
            'apply',
            'processing',
            'fee',
            'fees',
            'schedule',
            'office hours',
            'contact',
            'government',
            'ngo',
            'nga',
            'serbisyo',
            'opisina',
            'kailangan',
            'dokumento',
            'permit',
            'mag-apply',
            'magkano',
            'saan ang opisina',
        ];

        foreach ($signals as $signal) {
            if (str_contains($normalized, $signal)) {
                return true;
            }
        }

        return false;
    }

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

    $payload = $this->faqResponsePayload(
        $selectedFaq,
        $question
    );

    if (
        trim($payload['content']) === '' &&
        empty($payload['attachments'])
    ) {
        return response()->json([
            'choices' => [[
                'message' => [
                    'content' => 'That FAQ does not have a published response yet.'
                ]
            ]]
        ], 422);
    }

    $this->logChat(
        $question,
        $payload['content'],
        'answered',
        'rule',
        $selectedFaq->agency_id,
        $selectedFaq->id,
        100
    );

    return response()->json([
        'choices' => [[
            'message' => [
                'content' => $payload['content'],
                'attachments' => $payload['attachments'],
                'image' => $payload['image'],
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
         * Do not load the entire agency table for every chatbot request.
         * Instead, use meaningful words from the user's message to
         * retrieve a small candidate set from the database.
         */
        $mentionedAgency = null;

        $agencyTerms = collect(
            preg_split(
                '/\s+/u',
                mb_strtolower(
                    preg_replace('/[^\p{L}\p{N}\s-]+/u', ' ', $question) ?? '',
                    'UTF-8'
                ),
                -1,
                PREG_SPLIT_NO_EMPTY
            ) ?: []
        )
            ->filter(
                fn ($term) =>
                    mb_strlen($term, 'UTF-8') >= 4
            )
            ->unique()
            ->take(10)
            ->values()
            ->all();

        if ($agencyTerms !== []) {
            $mentionedAgency = Agency::query()
                ->select([
                    'id',
                    'agency_name',
                    'agency_abbreviation',
                ])
                ->where(function ($query) use ($agencyTerms) {
                    foreach ($agencyTerms as $term) {
                        $like = '%' . addcslashes($term, '%_\\') . '%';

                        $query
                            ->orWhere('agency_name', 'LIKE', $like)
                            ->orWhere('agency_abbreviation', 'LIKE', $like);
                    }
                })
                ->orderByRaw(
                    'CASE WHEN LOWER(agency_name) LIKE ? THEN 0 ELSE 1 END',
                    ['%' . mb_strtolower($question, 'UTF-8') . '%']
                )
                ->first();
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
        // 🤖 AI FAQ MATCHING
        // ============================================================

        /*
         * One bounded AI call is used to select an existing FAQ.
         *
         * The matcher compares:
         * - the agency context / mentioned agency,
         * - administrator-provided keywords,
         * - English and Filipino FAQ questions.
         *
         * The selected FAQ is then reloaded from the database and its
         * stored response is returned without rewriting or summarizing it.
         */
        try {
            $matchAgencyId = $agencyId
                ? (int) $agencyId
                : ($mentionedAgency?->id ? (int) $mentionedAgency->id : null);

            $aiMatch = $this->faqAiMatcher->match(
                $question,
                $matchAgencyId
            );

            if ($aiMatch) {
                $faq = $aiMatch['faq'];
                $payload = $this->faqResponsePayload($faq, $question);

                if (
                    trim($payload['content']) === '' &&
                    empty($payload['attachments'])
                ) {
                    throw new \RuntimeException(
                        'Matched FAQ has no publishable response content.'
                    );
                }

                $this->logChat(
                    $question,
                    $payload['content'],
                    'answered',
                    $aiMatch['method'] ?? 'ai',
                    $faq->agency_id,
                    $faq->id,
                    (int) round($aiMatch['confidence'] * 100)
                );

                return response()->json([
                    'choices' => [[
                        'message' => [
                            'content' => $payload['content'],
                            'attachments' => $payload['attachments'],
                            'image' => $payload['image'],
                        ],
                    ]],
                ]);
            }
        } catch (\Throwable $e) {
            /*
             * AI failure must degrade to the safe scope/fallback path.
             * The user never receives provider diagnostics.
             */
            Log::warning(
                'KNOWURLOCAL FAQ AI matching failed.',
                [
                    'error' => $e->getMessage(),
                ]
            );
        }


        // ============================================================
        // 🔐 FINAL KNOWURLOCAL SCOPE CHECK
        // ============================================================

        /*
         * At this point:
         *
         * 1. No strong rule-based FAQ answered.
         * 2. No clarification was necessary.
         * 3. AI-assisted FAQ selection did not produce an accepted answer.
         *
         * Only now do we ask the AI scope classifier whether
         * this is actually a KNOWURLOCAL helpdesk question.
         *
         * This prevents OpenRouter from blocking valid FAQ answers.
         */
        $scopeResult = $this->looksLikeHelpdeskQuestion(
            $question,
            $mentionedAgency !== null
        )
            ? $this->isRelevant($question)
            : false;

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