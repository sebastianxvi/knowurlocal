<?php

namespace App\Http\Controllers;

use App\Events\SupportRequestCreated;
use App\Models\ChatbotLog;
use App\Models\Faq;
use App\Models\FaqVersion;
use App\Models\SupportRequest;
use App\Services\AuditLogService;
use App\Services\AgencyChatbotService;
use App\Services\FaqChatbotService;
use App\Services\QuestionClarificationDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ChatbotController extends Controller
{
    public function __construct(
        private FaqChatbotService $chatbot,
        private QuestionClarificationDetector $clarificationDetector,
        private AgencyChatbotService $agencyChatbot
    ) {
    }

    /**
     * Build the public response exclusively from the selected FAQ record.
     *
     * The AI never supplies answer text. It only selects an existing FAQ
     * and a stored language variant. All response content comes from the configured PostgreSQL database.
     */
    private function faqResponsePayload(Faq $faq, ?FaqVersion $version, string $language): array
    {
        $selectedLanguage = $language === 'fil' ? 'fil' : 'en';
        $source = $version ?? $faq;
        $components = is_array($source->response_components)
            ? $source->response_components
            : [];

        $texts = collect($components)
            ->filter(function ($component) use ($selectedLanguage) {
                return is_array($component)
                    && ($component['type'] ?? null) === 'text'
                    && (($component['language'] ?? 'en') === $selectedLanguage)
                    && trim((string) ($component['content'] ?? '')) !== '';
            })
            ->pluck('content')
            ->values()
            ->all();

        if ($texts === []) {
            $fallback = $selectedLanguage === 'fil'
                ? $source->answer_fil
                : $source->answer;

            if (filled($fallback)) {
                $texts = [(string) $fallback];
            } else {
                // If the requested stored language does not exist, use the
                // other already-approved database variant without rewriting it.
                $other = $selectedLanguage === 'fil'
                    ? $source->answer
                    : $source->answer_fil;

                if (filled($other)) {
                    $texts = [(string) $other];
                }
            }
        }

        $attachments = collect($components)
            ->filter(fn ($component) => is_array($component)
                && in_array(($component['type'] ?? null), ['image', 'file', 'link', 'qr_code'], true))
            ->map(function ($component, $index) use ($faq, $version) {
                $type = $component['type'];

                if (in_array($type, ['image', 'file'], true)) {
                    $url = $version
                        ? route('chatbot.faq-version-attachment', [
                            'faqId' => $faq->id,
                            'versionId' => $version->id,
                            'componentIndex' => $index,
                        ])
                        : route('chatbot.faq-attachment', [
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
                    // Files should download; images should render inline.
                    'download' => $type === 'file',
                ];
            })
            ->values()
            ->all();

        return [
            'content' => implode("\n\n", $texts),
            'attachments' => $attachments,
            'image' => filled($source->image)
                ? Storage::disk('public')->url(ltrim((string) $source->image, '/'))
                : null,
        ];
    }

    /**
     * Log chatbot activity without allowing analytics failures to break chat.
     */
    private function logChat(
        string $question,
        string $answer,
        string $outcome,
        ?string $matchMethod = null,
        ?int $agencyId = null,
        ?int $faqId = null,
        ?int $faqVersionId = null,
        ?int $score = null,
        ?string $responseLanguage = null
    ): ?int {
        try {
            $log = ChatbotLog::create([
                'user_id' => auth()->id(),
                'question' => $question,
                'answer' => $answer,
                'agency_id' => $agencyId,
                'faq_id' => $faqId,
                'faq_version_id' => $faqVersionId,
                'outcome' => $outcome,
                'match_method' => $matchMethod,
                'score' => $score,
                'response_language' => $responseLanguage,
                'ip_address' => request()->ip(),
            ]);

            return (int) $log->id;
        } catch (\Throwable $e) {
            Log::warning('KNOWURLOCAL chatbot interaction logging failed.', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Serve an attachment belonging to the exact FAQ response version that
     * was shown to a chatbot user.
     */
    public function faqVersionAttachment(int $faqId, int $versionId, int $componentIndex)
    {
        $faq = Faq::findOrFail($faqId);
        $version = FaqVersion::query()
            ->whereKey($versionId)
            ->where('faq_id', $faq->id)
            ->firstOrFail();

        $components = is_array($version->response_components)
            ? $version->response_components
            : [];
        $component = $components[$componentIndex] ?? null;

        abort_unless(
            is_array($component)
                && in_array(($component['type'] ?? null), ['image', 'file'], true),
            404
        );

        $path = $component['content'] ?? null;

        abort_unless(
            is_string($path) && str_starts_with($path, 'faqs/responses/'),
            404
        );

        $disk = Storage::disk('private');
        if (!$disk->exists($path)) {
            Log::warning('Chatbot FAQ version attachment is missing from configured storage.', [
                'faq_id' => $faq->id,
                'faq_version_id' => $version->id,
                'component_index' => $componentIndex,
                'storage_disk' => config('filesystems.disks.private.driver'),
            ]);
            abort(404);
        }

        $filename = basename($path);
        $disposition = $component['type'] === 'file' ? 'attachment' : 'inline';

        return $disk->response(
            $path,
            $filename,
            [
                'Content-Disposition' => $disposition . '; filename="' . addslashes($filename) . '"',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, no-store',
            ]
        );
    }

    /**
     * Serve a private FAQ attachment through an application-controlled URL.
     */
    public function faqAttachment(int $faqId, int $componentIndex)
    {
        $faq = Faq::findOrFail($faqId);
        $version = $faq->currentVersion;

        abort_unless($version, 404);

        $components = is_array($version->response_components)
            ? $version->response_components
            : [];
        $component = $components[$componentIndex] ?? null;

        abort_unless(
            is_array($component)
                && in_array($component['type'] ?? null, ['image', 'file'], true),
            404
        );

        $path = $component['content'] ?? null;

        abort_unless(
            is_string($path) && str_starts_with($path, 'faqs/responses/'),
            404
        );

        $disk = Storage::disk('private');
        if (!$disk->exists($path)) {
            Log::warning('Chatbot FAQ attachment is missing from configured storage.', [
                'faq_id' => $faq->id,
                'faq_version_id' => $version->id,
                'component_index' => $componentIndex,
                'storage_disk' => config('filesystems.disks.private.driver'),
            ]);
            abort(404);
        }

        $filename = basename($path);
        $disposition = $component['type'] === 'file' ? 'attachment' : 'inline';

        return $disk->response(
            $path,
            $filename,
            [
                'Content-Disposition' => $disposition . '; filename="' . addslashes($filename) . '"',
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }

    /**
     * Return FAQ suggestions for the initial chatbot UI.
     */
    public function suggestions()
    {
        $popularFaqIds = ChatbotLog::query()
            ->where('outcome', 'answered')
            ->whereNotNull('faq_id')
            ->select('faq_id')
            ->selectRaw('COUNT(*) as usage_count')
            ->groupBy('faq_id')
            ->orderByDesc('usage_count')
            ->limit(15)
            ->pluck('faq_id');

        $popularFaqs = Faq::query()
            ->select('id', 'question')
            ->whereNotNull('question')
            ->whereIn('id', $popularFaqIds)
            ->get()
            ->sortBy(fn ($faq) => $popularFaqIds->search($faq->id))
            ->values();

        $remainingCount = max(0, 15 - $popularFaqs->count());

        if ($remainingCount > 0) {
            $excludedIds = $popularFaqs->pluck('id')->all();

            $additionalFaqs = Faq::query()
                ->select('id', 'question')
                ->whereNotNull('question')
                ->when(
                    $excludedIds !== [],
                    fn ($query) => $query->whereNotIn('id', $excludedIds)
                )
                ->latest('created_at')
                ->limit($remainingCount)
                ->get();

            $popularFaqs = $popularFaqs->concat($additionalFaqs);
        }

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
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'agency_id' => ['nullable', 'integer', 'exists:agencies,id'],
        ]);

        $question = trim(preg_replace('/\s+/', ' ', $validated['question']));

        $duplicatePendingRequest = SupportRequest::query()
            ->where('user_id', auth()->id())
            ->where('question', $question)
            ->where('status', 'pending')
            ->exists();

        if ($duplicatePendingRequest) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending request with the same question.',
            ], 422);
        }

        $supportRequest = SupportRequest::create([
            'user_id' => auth()->id(),
            'agency_id' => $validated['agency_id'] ?? null,
            'question' => $question,
            'status' => 'pending',
            'ip_address' => $request->ip(),
        ]);

        $supportRequest->load(['user', 'agency']);

        app(AuditLogService::class)->record(
            action: 'submit_support_request',
            page: 'chatbot',
            targetType: 'support_request',
            targetId: (int) $supportRequest->id,
            agencyId: $supportRequest->agency_id ? (int) $supportRequest->agency_id : null,
            supportRequestId: (int) $supportRequest->id,
            newValues: [
                'question' => $supportRequest->question,
                'status' => $supportRequest->status,
                'agency_id' => $supportRequest->agency_id,
            ],
            description: 'Submitted Support Request #' . $supportRequest->id,
        );

        try {
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
        } catch (\Throwable $exception) {
            // The request is already persisted; a realtime outage must not
            // turn a successful support submission into a failed response.
            Log::error('Failed to broadcast newly created support request.', [
                'support_request_id' => $supportRequest->id,
                'exception' => get_class($exception),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Your question has been sent to a human assistant.',
            'support_request_id' => (int) $supportRequest->id,
        ], 201);
    }

    /**
     * Resolve the response version that should be shown for a selected FAQ.
     *
     * The FAQ itself remains the retrieval source. Version resolution happens
     * only after a match is selected so a broken/missing publication pointer
     * cannot turn an otherwise valid chatbot match into a 503.
     *
     * Preference order:
     * 1. The FAQ's explicit current_version_id/publication pointer.
     * 2. The newest non-superseded version when no valid pointer exists.
     * 3. The newest version as a final historical recovery path.
     *
     * Returning null is intentional: faqResponsePayload() can use the FAQ row
     * as a legacy/current-response fallback when versioning data is unavailable.
     */
    private function resolvePublishedFaqVersion(Faq $faq): ?FaqVersion
    {
        try {
            /*
             * The explicit current_version_id is the publication pointer and
             * therefore the authoritative response version. Using it first
             * prevents a stray non-superseded historical row from being shown.
             */
            if ($faq->relationLoaded('currentVersion')) {
                $current = $faq->currentVersion;

                if ($current) {
                    return $current;
                }
            } elseif ($faq->current_version_id) {
                $current = FaqVersion::query()
                    ->whereKey((int) $faq->current_version_id)
                    ->where('faq_id', $faq->id)
                    ->first();

                if ($current) {
                    return $current;
                }
            }

            $published = FaqVersion::query()
                ->where('faq_id', $faq->id)
                ->whereNull('superseded_at')
                ->orderByDesc('version_number')
                ->first();

            if ($published) {
                return $published;
            }

            return FaqVersion::query()
                ->where('faq_id', $faq->id)
                ->orderByDesc('version_number')
                ->first();
        } catch (\Throwable $e) {
            // Versioning must never make a valid FAQ unavailable. The caller
            // will fall back to the response stored on the FAQ row.
            Log::warning('KNOWURLOCAL chatbot FAQ version resolution failed.', [
                'faq_id' => $faq->id,
                'current_version_id' => $faq->current_version_id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Main AI FAQ retrieval endpoint.
     *
     * The complete flow is intentionally simple:
     *
     * browser question
     *     -> AI scores/ranks the eligible FAQ catalogue
     *     -> highest AI score
     *     -> validated FAQ ID
     *     -> database FAQ
     *     -> stored response
     *
     * The AI never writes the answer. It performs the semantic FAQ matching;
     * Laravel only validates the winning FAQ ID and returns its stored answer.
     */
    /**
     * Return a chatbot response with a lightweight version header. This makes
     * it immediately obvious in DevTools whether the browser is talking to
     * the current chatbot backend after a deployment/cache clear.
     */
    private function chatbotJson(array $payload, int $status = 200, ?string $matchMethod = null)
    {
        $response = response()
            ->json($payload, $status)
            ->header('X-KNOWURLOCAL-Chatbot', 'v15-ai');

        if ($matchMethod !== null) {
            $response->header('X-KNOWURLOCAL-Chatbot-Match', $matchMethod);
        }

        return $response;
    }

    public function ask(Request $request)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
            'agency_id' => ['nullable', 'integer', 'exists:agencies,id'],
        ]);

        $question = trim($validated['message']);
        $agencyId = isset($validated['agency_id'])
            ? (int) $validated['agency_id']
            : null;

        /*
         * Avoid an AI request for clearly incomplete, subject-less questions.
         * If the user has just discussed an FAQ in this session, reuse that
         * context instead of asking them to repeat it. Context is short-lived
         * and scoped to the selected agency to avoid stale/cross-scope matches.
         */
        $clarificationPrompt = $this->clarificationDetector->promptFor($question);
        $lastContext = session('chatbot.last_faq_context');
        $contextFaq = null;

        if (
            $clarificationPrompt !== null
            && is_array($lastContext)
            && isset($lastContext['faq_id'], $lastContext['created_at'])
            && (int) $lastContext['created_at'] >= now()->subMinutes(10)->timestamp
            && ($lastContext['agency_id'] ?? null) === $agencyId
        ) {
            $contextFaq = Faq::query()->find((int) $lastContext['faq_id']);
        }

        if ($clarificationPrompt !== null && !$contextFaq) {
            $this->logChat(
                $question,
                $clarificationPrompt,
                'clarification',
                'clarification',
                $agencyId
            );

            return $this->chatbotJson([
                'choices' => [[
                    'message' => [
                        'content' => $clarificationPrompt,
                        'attachments' => [],
                        'image' => null,
                        'clarification' => true,
                    ],
                ]],
            ], 200, 'clarification');
        }

        /*
         * Prefer exact, database-backed agency retrieval for clear agency
         * information questions. This path never invokes an AI provider.
         * If it cannot confidently identify an agency, continue through the
         * existing FAQ matcher without changing its behavior.
         */
        try {
            $agencyAnswer = $this->agencyChatbot->answer($question);
        } catch (\Throwable $e) {
            Log::warning('KNOWURLOCAL agency information retrieval failed; continuing to FAQ matching.', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'question' => $question,
            ]);
            $agencyAnswer = null;
        }

        if ($agencyAnswer !== null) {
            session()->forget('chatbot.last_faq_context');

            $chatLogId = $this->logChat(
                $question,
                (string) $agencyAnswer['content'],
                'answered',
                (string) ($agencyAnswer['match_method'] ?? 'agency_database'),
                isset($agencyAnswer['agency_id']) ? (int) $agencyAnswer['agency_id'] : null,
                null,
                null,
                100,
                'en'
            );

            return $this->chatbotJson([
                'choices' => [[
                    'message' => [
                        'content' => $agencyAnswer['content'],
                        'attachments' => $agencyAnswer['attachments'] ?? [],
                        'image' => $agencyAnswer['image'] ?? null,
                    ],
                ]],
            ], 200, (string) ($agencyAnswer['match_method'] ?? 'agency_database'));
        }

        /*
         * Prefer database-backed agency retrieval for clear agency questions.
         * This path never calls an AI provider. If it cannot confidently
         * identify a relevant agency, preserve the existing FAQ pipeline.
         */
        try {
            $agencyAnswer = $this->agencyChatbot->answer($question);
        } catch (\Throwable $e) {
            Log::warning('KNOWURLOCAL agency information retrieval failed; continuing to FAQ matching.', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'question' => $question,
            ]);
            $agencyAnswer = null;
        }

        if ($agencyAnswer !== null) {
            session()->forget('chatbot.last_faq_context');

            $chatLogId = $this->logChat(
                $question,
                (string) $agencyAnswer['content'],
                'answered',
                (string) ($agencyAnswer['match_method'] ?? 'agency_database'),
                isset($agencyAnswer['agency_id']) ? (int) $agencyAnswer['agency_id'] : null,
                null,
                null,
                100,
                'en'
            );

            return $this->chatbotJson([
                'choices' => [[
                    'message' => [
                        'content' => $agencyAnswer['content'],
                        'attachments' => $agencyAnswer['attachments'] ?? [],
                        'image' => $agencyAnswer['image'] ?? null,
                    ],
                ]],
            ], 200, (string) ($agencyAnswer['match_method'] ?? 'agency_database'));
        }

        // A generic follow-up can inherit the immediately preceding FAQ topic.
        $retrievalQuestion = $contextFaq
            ? $question . "\n\nConversation context: The user was just asking about this FAQ: "
                . (string) ($contextFaq->question ?? '')
            : $question;

        if ($clarificationPrompt === null) {
            // A complete new question supersedes the previous conversational topic.
            session()->forget('chatbot.last_faq_context');
        }

        /*
         * Retrieval and response rendering are intentionally separate failure
         * boundaries. A problem resolving historical FAQ versions must NEVER
         * turn a valid FAQ match into the generic "couldn't verify" message.
         */
        try {
            $match = $this->chatbot->findMatch($retrievalQuestion, $agencyId);
        } catch (\Throwable $e) {
            Log::error('KNOWURLOCAL chatbot retrieval failed.', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'question' => $question,
                'agency_id' => $agencyId,
            ]);

            $reply = 'The FAQ matching service is temporarily unavailable. Please try again or send a ticket.';

            $this->logChat(
                $question,
                $reply,
                'error',
                null,
                $agencyId
            );

            return $this->chatbotJson([
                'choices' => [[
                    'message' => [
                        'content' => $reply,
                        'fallback' => true,
                    ],
                ]],
            ], 200, 'error');
        }

        if ($match === null) {
            session()->forget('chatbot.last_faq_context');
            $reply = "I couldn’t find a matching FAQ for your question.";

            $this->logChat(
                $question,
                $reply,
                'fallback',
                null,
                $agencyId
            );

            return $this->chatbotJson([
                'choices' => [[
                    'message' => [
                        'content' => $reply,
                        'fallback' => true,
                    ],
                ]],
            ], 200, 'fallback');
        }

        /** @var Faq $faq */
        $faq = $match['faq'];
        session()->put('chatbot.last_faq_context', [
            'faq_id' => (int) $faq->id,
            'agency_id' => $agencyId,
            'created_at' => now()->timestamp,
        ]);
        $faqVersion = $this->resolvePublishedFaqVersion($faq);

        try {
            $payload = $this->faqResponsePayload(
                $faq,
                $faqVersion,
                $match['language'] ?? 'en'
            );
        } catch (\Throwable $e) {
            /*
             * The FAQ row itself is the canonical emergency response source.
             * Versioning/attachments are enhancements, not prerequisites for
             * returning the approved text stored on the FAQ.
             */
            Log::warning('KNOWURLOCAL chatbot response rendering degraded to FAQ row.', [
                'faq_id' => $faq->id,
                'faq_version_id' => $faqVersion?->id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            $language = ($match['language'] ?? 'en') === 'fil' ? 'fil' : 'en';
            $content = $language === 'fil'
                ? (string) ($faq->answer_fil ?? $faq->answer ?? '')
                : (string) ($faq->answer ?? $faq->answer_fil ?? '');

            $payload = [
                'content' => trim($content),
                'attachments' => [],
                'image' => null,
            ];
        }

        if (
            trim((string) $payload['content']) === ''
            && $payload['attachments'] === []
            && $payload['image'] === null
        ) {
            Log::warning('KNOWURLOCAL chatbot matched FAQ without usable response content.', [
                'faq_id' => $faq->id,
                'faq_version_id' => $faqVersion?->id,
            ]);

            $reply = 'This FAQ does not currently have a published response. Please send a ticket for human assistance.';

            $this->logChat(
                $question,
                $reply,
                'error',
                $match['method'] ?? 'semantic',
                $faq->agency_id,
                $faq->id,
                $faqVersion?->id,
                (int) round(((float) ($match['confidence'] ?? 0)) * 100),
                $match['language'] ?? 'en'
            );

            return $this->chatbotJson([
                'choices' => [[
                    'message' => [
                        'content' => $reply,
                        'fallback' => true,
                    ],
                ]],
            ], 200, 'matched-no-response');
        }

        $chatLogId = $this->logChat(
            $question,
            (string) $payload['content'],
            'answered',
            $match['method'] ?? 'semantic',
            $faq->agency_id,
            $faq->id,
            $faqVersion?->id,
            (int) round(((float) ($match['confidence'] ?? 0)) * 100),
            $match['language'] ?? 'en'
        );

        return $this->chatbotJson([
            'feedback_log_id' => $chatLogId,
            'choices' => [[
                'message' => [
                    'content' => $payload['content'],
                    'attachments' => $payload['attachments'],
                    'image' => $payload['image'],
                ],
            ]],
        ], 200, $match['method'] ?? 'semantic');
    }
}
