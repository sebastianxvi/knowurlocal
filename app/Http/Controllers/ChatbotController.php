<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Faq;
use App\Models\ChatbotLog;
use App\Models\SupportRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\OpenRouterService;
use App\Services\FaqMatcherService;
use App\Services\FaqSemanticMatcherService;
use App\Services\FaqIntentService;

class ChatbotController extends Controller
{

private OpenRouterService $ai;

private FaqMatcherService $faqMatcher;

private FaqSemanticMatcherService $faqSemanticMatcher;

private FaqIntentService $faqIntent;


/**
 * Determine whether the user's question is too generic
 * to identify a specific agency service or process.
 */
private function isAmbiguousHelpdeskQuestion(
    string $question,
    string $userIntent,
    ?int $agencyId
): bool {

    if (!in_array($userIntent, [
        'requirements',
        'procedure',
        'eligibility',
        'fees',
        'processing_time',
    ], true)) {
        return false;
    }

    $text = mb_strtolower(
        trim(
            preg_replace('/\s+/', ' ', $question)
        ),
        'UTF-8'
    );

    /*
     * Generic phrases indicate that the user has not
     * specified what service they are asking about.
     */
    $genericPatterns = [
        'what are the requirements',
        'what requirements do i need',
        'what requirements should i prepare',
        'what do i need',
        'what do i need to bring',

        'anong requirements',
        'anong mga requirements',
        'ano ang requirements',
        'ano mga requirements',
        'ano ang mga requirements',

        'anong kailangan',
        'anong mga kailangan',
        'ano ang kailangan',
        'ano kailangan',
        'ano mga kailangan',
        'ano ang mga kailangan',
    ];

    $isGeneric = false;

    foreach ($genericPatterns as $pattern) {

        if (str_contains($text, $pattern)) {
            $isGeneric = true;
            break;
        }
    }

    if (!$isGeneric) {
        return false;
    }

    /*
     * Look for context that identifies the agency or service.
     *
     * We deliberately use a conservative list here.
     * The purpose is only to avoid asking for clarification
     * when the user already provided meaningful context.
     */
    $contextTerms = [
        'pnp',
        'police clearance',
        'police report',
        'fire safety inspection',
        'bfp',
        'dswd',
        'assistance',
        'application',
        'clearance',
        'inspection',
        'permit',
        'registration',
        'id',
        'license',
    ];

    foreach ($contextTerms as $term) {

        if (str_contains($text, $term)) {
            return false;
        }
    }

    /*
     * The question is genuinely generic.
     */
    return true;
}

/**
 * Detect whether the user is asking in Filipino/Taglish.
 *
 * This is used only to select the already-approved FAQ answer.
 * It does not determine whether the question matches an FAQ.
 *
 * We intentionally use conservative Filipino indicators
 * instead of asking the AI to decide the response language.
 */
private function detectResponseLanguage(string $question): string
{
    $text = mb_strtolower(
        trim(
            preg_replace('/\s+/', ' ', $question)
        ),
        'UTF-8'
    );

    /*
     * Common Filipino/Taglish words and phrases that are
     * strong indicators that the user expects a Filipino
     * response.
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

    foreach ($filipinoIndicators as $indicator) {

        if (str_contains($text, $indicator)) {
            return 'fil';
        }
    }

    return 'en';
}

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
 * 📝 RECORD CHATBOT INTERACTION
 *
 * Stores a structured record of how KNOWURLOCAL handled
 * an authenticated user's chatbot question.
 *
 * The logger must never interrupt the chatbot itself.
 * If logging fails, the user's request should still receive
 * its normal chatbot response.
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
             * KNOWURLOCAL requires authentication before
             * accessing the chatbot, so every interaction
             * should have an authenticated user ID.
             */
            'user_id' => auth()->id(),

            /*
             * Store exactly what the user asked.
             */
            'question' => $question,

            /*
             * Store the answer actually returned by the chatbot.
             */
            'answer' => $answer,

            /*
             * Agency associated with the interaction.
             */
            'agency_id' => $agencyId,

            /*
             * FAQ that ultimately supplied the answer.
             *
             * This remains null for greetings, fallback,
             * irrelevant questions, etc.
             */
            'faq_id' => $faqId,

            /*
             * Describes the outcome of the interaction.
             */
            'outcome' => $outcome,

            /*
             * Describes how an FAQ answer was obtained.
             *
             * Examples:
             * rule
             * semantic
             * none
             */
            'match_method' => $matchMethod,

            /*
             * Matching confidence represented as 0–100.
             */
            'score' => $score,

            /*
             * Capture the request IP for operational/security
             * auditing.
             */
            'ip_address' => request()->ip(),
        ]);

    } catch (\Throwable $e) {

        /*
         * Logging must never break the chatbot.
         *
         * We record the failure in Laravel's application log
         * instead of exposing an internal error to the user.
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
     * 🤖 AI REQUEST (used ONLY for classification)
     */
    private function askAI(array $messages): array
{
    return $this->ai->chat($messages, 0.3);
}

    public function suggestions()
{
    /*
     * Determine which FAQs have actually been used
     * successfully by the chatbot.
     *
     * We only count logs that have a real FAQ reference
     * and were successfully answered.
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
     * Retrieve the actual FAQ records.
     *
     * Eloquent automatically excludes soft-deleted FAQs
     * when the Faq model uses SoftDeletes.
     */
    $popularFaqs = Faq::query()
        ->select('id', 'question')
        ->whereNotNull('question')
        ->whereIn('id', $popularFaqIds)
        ->get()
        ->sortBy(function ($faq) use ($popularFaqIds) {

            /*
             * Preserve the popularity order returned
             * by the grouped chatbot-log query.
             */
            return $popularFaqIds->search(
                $faq->id
            );
        })
        ->values();

    /*
     * We want exactly 15 suggestions when enough FAQs exist.
     *
     * If fewer than 15 popular FAQs have been used,
     * fill the remaining slots with random FAQs.
     */
    $remainingCount = max(
        0,
        15 - $popularFaqs->count()
    );

    if ($remainingCount > 0) {

        /*
         * Do not show an FAQ twice in the same slideshow.
         */
        $excludedIds = $popularFaqs
            ->pluck('id')
            ->all();

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
     * Return only the fields the frontend actually needs.
     *
     * This prevents unnecessary database information
     * from being exposed to the browser.
     */
    return response()->json(
        $popularFaqs->map(fn ($faq) => [
            'id' => $faq->id,
            'question' => $faq->question,
        ])->values()
    );
}


public function submitSupportRequest(Request $request)
{
    /*
     * Validate all browser-supplied values.
     *
     * Validation happens before any database operation.
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
     * Normalize whitespace so that trivial formatting
     * differences do not bypass duplicate detection.
     *
     * Example:
     *
     * "What are the requirements?"
     *
     * and
     *
     * "  What are the requirements?  "
     *
     * are treated as the same question.
     */
    $question = trim(
        preg_replace(
            '/\s+/',
            ' ',
            $validated['question']
        )
    );


    /*
     * Prevent duplicate pending requests.
     *
     * The authenticated user's ID comes from Laravel's
     * authentication system rather than browser input.
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
     * Do not create another support request when the
     * same question is already waiting for an answer.
     */
    if ($duplicatePendingRequest) {

        return response()->json([
            'success' => false,

            'message' =>
                'You already have a pending request with the same question.',
        ], 422);
    }


    /*
     * Create the support request only after:
     *
     * 1. Validation succeeds.
     * 2. Rate limiting succeeds at the route level.
     * 3. Duplicate protection succeeds.
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
     * Return a successful response to the chatbot frontend.
     */
    return response()->json([
        'success' => true,

        'message' =>
            'Your question has been sent to a human assistant.',
    ]);
}

    /**
     * 🧠 AI CLASSIFIER (YES / NO ONLY)
     */
    /**
 * Determine whether the user's question belongs to
 * the public-information scope of KNOWURLOCAL.
 *
 * IMPORTANT:
 * This does NOT determine whether an FAQ exists.
 *
 * It only answers:
 *
 * "Is this the kind of question KNOWURLOCAL is designed
 * to help with?"
 */
/**
 * Determine whether the user's question belongs to
 * the actual KNOWURLOCAL helpdesk scope.
 *
 * IMPORTANT:
 *
 * This method does NOT determine whether an FAQ exists.
 * It only determines whether the question is the type of
 * question KNOWURLOCAL is designed to handle.
 *
 * Scope:
 * - Agency services
 * - Requirements
 * - Procedures
 * - Eligibility
 * - Fees
 * - Processing information
 * - Agency responsibilities/programs
 * - Office hours
 * - Contact information
 * - Agency-specific office information
 */
private function isRelevant(string $question): bool
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
         * Only accept exact YES/NO responses.
         *
         * Anything unexpected fails closed.
         */
        if ($reply === 'YES') {
            return true;
        }

        if ($reply === 'NO') {
            return false;
        }

        /*
         * Security principle:
         *
         * When the classifier is uncertain or malformed,
         * do not allow the chatbot to proceed as if the
         * question were in scope.
         */
        return false;

    } catch (\Throwable $e) {

        /*
         * Do not expose provider/API errors to the user.
         */
        \Log::warning(
            'KNOWURLOCAL scope classification failed.',
            [
                'error' => $e->getMessage(),
            ]
        );

        /*
         * Fail closed.
         */
        return false;
    }
}

    /**
     * 🚀 MAIN FUNCTION
     */
    public function ask(Request $request)
    {
        // 🔒 VALIDATION
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
]);

        $question = trim($validated['message']);

$normalizedQuestion = mb_strtolower(
    $question,
    'UTF-8'
);

$agencyId = $validated['agency_id'] ?? null;

            // 🧠 SIMPLE INTENT DETECTION (RUN FIRST)
        
            // ============================================================
// 👋 SIMPLE CONVERSATIONAL INTENT
// ============================================================

/*
 * Greetings should only trigger when the ENTIRE message
 * is essentially a greeting.
 *
 * We intentionally do NOT use str_contains() here.
 *
 * Example:
 *
 * "hello"
 *      → greeting
 *
 * "hi there"
 *      → greeting
 *
 * "hi, how do I file a police report?"
 *      → NOT a greeting
 *
 * This prevents a conversational word from overriding
 * an actual public-information request.
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
 * Remove punctuation so:
 *
 * "Hello!"
 *
 * becomes:
 *
 * "hello"
 */
$cleanForIntent = trim(
    preg_replace(
        '/[^\p{L}\p{N}\s]/u',
        '',
        $question
    )
);

/*
 * Only treat the message as a greeting when the complete
 * cleaned message exactly matches one of the approved
 * greeting phrases.
 */
if (in_array($cleanForIntent, $greetings, true)) {

    $reply = "Hello! How can I assist you today?";

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


        /*
 * Thank-you messages should also be standalone messages.
 *
 * "salamat"
 *      → thanks
 *
 * "salamat, paano mag-file ng police report?"
 *      → NOT thanks
 */
$thanks = [
    'thanks',
    'thank you',
    'salamat',
    'maraming salamat',
];

if (in_array($cleanForIntent, $thanks, true)) {

    $reply = "You're welcome! Let me know if you need anything else.";

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


        

        
        $cleanQuestion = preg_replace('/[^\w\s]/', '', $question);
        

        // 🔍 CURRENT AGENCY
        $currentAgency = $agencyId ? Agency::find($agencyId) : null;

        // 🔍 DETECT MENTIONED AGENCY
        $mentionedAgency = null;
        $rawQuestion = strtolower($request->message);

        foreach (Agency::all() as $agency) {

            $name = strtolower($agency->agency_name);

            if (str_contains($rawQuestion, $name)) {
                $mentionedAgency = $agency;
                break;
            }

            foreach (explode(' ', $name) as $word) {
                if (strlen($word) >= 4 && str_contains($rawQuestion, $word)) {
                    $mentionedAgency = $agency;
                    break 2;
                }
            }
        }

        // ❗ WRONG AGENCY
        if ($agencyId && $mentionedAgency && $mentionedAgency->id != $agencyId) {

            $reply = "It looks like your question is about {$mentionedAgency->agency_name}.

You are currently chatting with {$currentAgency->agency_name}.

Please visit {$mentionedAgency->agency_name} for accurate information.";

            $this->logChat(
    $question,
    $reply,
    'wrong_agency',
    'none',
    $agencyId
);

            return response()->json([
                "choices" => [[
                    "message" => ["content" => $reply]
                ]]
            ]);
        }

        // ============================================================
// 🔐 KNOWURLOCAL SCOPE CHECK
// ============================================================

/*
 * Check the scope BEFORE searching the FAQ database.
 *
 * This is important because an existing FAQ must not
 * automatically make an out-of-scope question answerable.
 *
 * Example:
 *
 * "Who should I call during an emergency?"
 *
 * may accidentally match a police FAQ because of words
 * such as "police", "emergency", or "call".
 *
 * The scope check prevents that FAQ from being used.
 */
if (!$this->isRelevant($question)) {

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
// 🧠 INTENT DETECTION
// ============================================================

/*
 * Determine what type of helpdesk information the user
 * is requesting.
 *
 * This is independent of whether an FAQ currently exists.
 */
$userIntent = $this->faqIntent->detect(
    $question
);

// ============================================================
// ❓ AMBIGUOUS HELPDESK QUESTION
// ============================================================

/*
 * If the user is clearly asking for helpdesk information
 * but has not identified the agency or service, do not guess.
 *
 * Ask the user to provide the missing context instead.
 */
if (
    $this->isAmbiguousHelpdeskQuestion(
        $question,
        $userIntent,
        $agencyId ? (int) $agencyId : null
    )
) {

    $responseLanguage =
        $this->detectResponseLanguage($question);

    if ($responseLanguage === 'fil') {

        $reply =
            "Matutulungan kitang hanapin ang requirements. " .
            "Anong agency o serbisyo ang tinutukoy mo?";

    } else {

        $reply =
            "I can help you find the requirements. " .
            "Which agency or service are you asking about?";
    }

    $this->logChat(
    $question,
    $reply,
    'clarification',
    'none',
    $agencyId
);

    return response()->json([
        "choices" => [[
            "message" => [
                "content" => $reply,
                "clarification" => true
            ]
        ]]
    ]);
}

// ============================================================
// 🔍 FAQ MATCHING
// ============================================================

/*
 * Ask the dedicated FAQ matcher to find the most relevant
 * approved FAQ records.
 *
 * Passing the current agency ID means that when the user is
 * inside a specific agency, that agency's FAQs are prioritized.
 */
$faqs = $this->faqMatcher->match(
    $question,
    $agencyId ? (int) $agencyId : null,
    5
);

/*
 * The matcher already returns FAQs ordered from highest
 * confidence to lowest confidence.
 */
$bestFaq = $faqs->first();

/*
 * Read the calculated score from the best candidate.
 *
 * The matcher attaches this value dynamically to the FAQ model.
 */
$bestScore = $bestFaq
    ? (int) ($bestFaq->match_score ?? 0)
    : 0;


// ============================================================
// 🔐 MATCH CONFIDENCE THRESHOLD
// ============================================================

/*
 * This is deliberately conservative.
 *
 * A FAQ must have enough matching evidence before the chatbot
 * is allowed to use it as an answer.
 */
$minScore = 35;


// ============================================================
// 🎯 STRONG FAQ MATCH
// ============================================================

if ($bestFaq && $bestScore >= $minScore) {

    /*
     * The matcher determines which language produced
     * the stronger match.
     *
     * Filipino/Taglish → use the admin-approved Filipino answer.
     * English → use the admin-approved English answer.
     */
    /*
 * Determine the response language from the user's actual
 * message rather than relying on the matcher or AI.
 */
$responseLanguage = $this->detectResponseLanguage(
    $question
);

if (
    $responseLanguage === 'fil' &&
    filled($bestFaq->answer_fil)
) {
    $reply = $bestFaq->answer_fil;
} else {
    /*
     * Safe fallback to the approved English answer when
     * no Filipino answer has been provided.
     */
    $reply = $bestFaq->answer;
}

    /*
     * Store the raw matcher score for debugging and auditing.
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

    return response()->json([
        "choices" => [[
            "message" => [
                "content" => $reply,

                /*
                 * Return the FAQ image when one exists.
                 */
                "image" => $bestFaq->image
                    ? asset('storage/' . $bestFaq->image)
                    : null
            ]
        ]]
    ]);
}

// ============================================================
// 🧠 SEMANTIC FAQ MATCHING
// ============================================================

/*
 * At this point the rule-based matcher did not have
 * enough confidence to answer directly.
 *
 * However, it may still have found useful candidates.
 *
 * Ask the semantic matcher to determine whether one of
 * those candidates has the same meaning as the user's
 * question.
 */
if ($faqs->isNotEmpty()) {

    try {

        /*
 * All FAQs in this candidate collection were matched
 * against the same user question.
 *
 * Therefore the user's detected intent is attached
 * to the candidate models by FaqMatcherService.
 */
$userIntent =
    $faqs->first()->match_user_intent ?? 'other';

$semanticMatch =
    $this->faqSemanticMatcher->match(
        $question,
        $faqs,
        $userIntent
    );

        /*
         * Only accept a semantic match when the model
         * reports sufficiently high confidence.
         */
        if (
            $semanticMatch &&
            $semanticMatch['faq_id'] !== null &&
            $semanticMatch['confidence'] >= 0.85
        ) {

            /*
             * Find the FAQ that the AI selected.
             *
             * We search ONLY inside the candidate collection.
             */
            $semanticFaq = $faqs->first(
                fn ($faq) =>
                    (int) $faq->id ===
                    (int) $semanticMatch['faq_id']
            );

            /*
             * Never continue if the FAQ somehow disappeared
             * from the candidate collection.
             */
            if ($semanticFaq) {

                /*
                 * Use the language selected by the semantic
                 * matcher.
                 */
                /*
 * Determine the response language from the user's actual
 * message instead of trusting the AI's language classification.
 */
$responseLanguage = $this->detectResponseLanguage(
    $question
);

if (
    $responseLanguage === 'fil' &&
    filled($semanticFaq->answer_fil)
) {
    $reply = $semanticFaq->answer_fil;
} else {
    /*
     * Safe fallback to the approved English answer.
     */
    $reply = $semanticFaq->answer;
}

                /*
                 * Log the semantic match separately so you can
                 * measure how often AI-assisted retrieval is used.
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

                return response()->json([
                    "choices" => [[
                        "message" => [
                            "content" => $reply,

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
         * Semantic matching is an enhancement, not a
         * requirement for the chatbot to function.
         *
         * If the AI service fails, continue to the normal
         * scope/no-FAQ flow.
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
// ✅ SINGLE BEST FAQ MATCH
// ============================================================





        // 📩 FINAL FALLBACK
        $reply = "I couldn’t find an exact answer for your question.";

$this->logChat(
    $question,
    $reply,
    'fallback',
    'none',
    $agencyId
);

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