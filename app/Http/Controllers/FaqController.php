<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Faq;
use App\Models\FaqVersion;
use App\Models\Agency;
use App\Models\UserLog;
use App\Models\SupportRequest;
use App\Models\SupportResponseComponent;
use App\Services\FaqTranslationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Support\PrivateStorageDiagnostics;


class FaqController extends Controller
{
    /**
     * 🤖 GENERATE FILIPINO / TAGLISH FAQ TRANSLATION
     *
     * This endpoint only generates a translation draft.
     * It does NOT modify the FAQ in the database.
     */
    public function translate(
        Request $request,
        FaqTranslationService $translator
    ) {
        /*
         * Validate the English source content before
         * sending anything to the external AI service.
         */
        $validated = $request->validate([
            'question' => [
                'required',
                'string',
                'max:255',
            ],

            'answer' => [
                'required',
                'string',
                'max:10000',
            ],
        ]);

        try {

            /*
             * Send the validated English FAQ to
             * our translation service.
             */
            $translation = $translator->translate(
                $validated['question'],
                $validated['answer']
            );

            /*
             * Return only the generated translation.
             *
             * Nothing has been saved to the database yet.
             */
            return response()->json([
                'success' => true,

                'translation' => [
                    'question_fil' => $translation['question_fil'],
                    'answer_fil' => $translation['answer_fil'],
                ],
            ]);

        } catch (\Throwable $e) {

            /*
             * Log the technical error server-side.
             *
             * We deliberately do NOT send the exception
             * message to the browser.
             */
            \Log::error('FAQ translation failed.', [
                'user_id' => auth()->id(),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            /*
             * Give the frontend a generic error.
             */
            return response()->json([
                'success' => false,
                'message' => 'Unable to generate a translation right now.',
            ], 503);
        }
    }

    /**
 * 🤖 PREPARE FAQ FROM SUPPORT REQUEST
 *
 * Retrieves an answered Support Request and generates
 * a bilingual FAQ draft for administrator review.
 *
 * IMPORTANT:
 * This method DOES NOT create an FAQ.
 * It only prepares a draft.
 */
public function prepareFromSupport(
    Request $request,
    $id,
    FaqTranslationService $translator
) {
    /*
     * Defense-in-depth authorization.
     */
    abort_unless(
        auth()->check() &&
        auth()->user()->role === 'superadmin',
        403,
        'Unauthorized action.'
    );

    /*
     * Only a resolved Support Request may be converted.
     * "answered" is deliberately stricter than merely having
     * an answer string because awaiting_confirmation is not a
     * completed ticket yet.
     */
    $support = SupportRequest::query()
        ->with('latestResponse.components')
        ->findOrFail($id);

    if ($support->status !== 'answered') {
        return response()->json([
            'success' => false,
            'message' =>
                'Only completed/answered support requests can be added to FAQs.',
        ], 422);
    }

    if (!$support->agency_id) {
        return response()->json([
            'success' => false,
            'message' =>
                'This support request does not have an agency assigned.',
        ], 422);
    }

    try {
        /*
         * Keep the existing bilingual question preparation so the
         * FAQ modal receives both language fields, but do NOT use
         * the AI-generated answer fields as the FAQ response source.
         * The authoritative response components come from the
         * completed Support Request itself.
         */
        $latestResponse = $support->latestResponse;
        $responseComponents = [];

        $responseAnswer = trim((string) ($support->answer ?? ''));

        if ($responseAnswer === '' && $latestResponse) {
            $responseAnswer = $latestResponse->components
                ->where('type', 'text')
                ->pluck('content')
                ->filter(fn ($content) => trim((string) $content) !== '')
                ->implode("\n\n");
        }

        /*
         * A completed Support Request can legitimately contain an
         * attachment-only response. Do not require a text answer here:
         * the response components are the authoritative FAQ response,
         * and the FAQ response builder can publish image/file/link/QR
         * components without an accompanying text block.
         *
         * The final empty-response guard is performed after all components
         * have been built below, so legacy answer_image records are also
         * supported.
         */

        /*
         * Translate the entire conversion in one batch.
         *
         * This is the key reliability change: a support request can contain
         * several response blocks, so making one remote AI request per block
         * made the whole conversion vulnerable to latency and transient
         * provider failures.
         */
        $translationInputs = [
            'question' => (string) $support->question,
        ];

        if (!$latestResponse) {
            $translationInputs['answer'] = $responseAnswer;
        }

        if ($latestResponse) {
            foreach ($latestResponse->components as $component) {
                if ($component->type === 'text' && trim((string) $component->content) !== '') {
                    $translationInputs['component_' . $component->id] = (string) $component->content;
                }
            }
        }

        $translated = $translator->prepareTextPairs($translationInputs);

        $questionPair = $translated['question'] ?? [
            'language' => 'en',
            'en' => trim((string) $support->question),
            'fil' => trim((string) $support->question),
        ];

        $questionDraft = [
            'detected_language' => $questionPair['language'],
            'question' => $questionPair['en'],
            'question_fil' => $questionPair['fil'],
            'answer' => '',
            'answer_fil' => '',
        ];

        if (!$latestResponse) {
            $answerPair = $translated['answer'] ?? [
                'en' => $responseAnswer,
                'fil' => $responseAnswer,
            ];

            $questionDraft['answer'] = $answerPair['en'];
            $questionDraft['answer_fil'] = $answerPair['fil'];
        }

        /*
         * Build bilingual response blocks from the authoritative structured
         * response. Attachments are copied without sending their URLs or file
         * contents through the translation model.
         */
        if ($latestResponse) {
            foreach ($latestResponse->components as $component) {
                $type = $component->type;

                if ($type === 'text') {
                    $text = trim((string) $component->content);
                    if ($text === '') {
                        continue;
                    }

                    $pair = $translated['component_' . $component->id] ?? [
                        'en' => $text,
                        'fil' => $text,
                    ];

                    $responseComponents[] = [
                        'type' => 'text',
                        'language' => 'en',
                        'content' => $pair['en'],
                        'label' => $component->label,
                    ];

                    $responseComponents[] = [
                        'type' => 'text',
                        'language' => 'fil',
                        'content' => $pair['fil'],
                        'label' => $component->label,
                    ];

                    continue;
                }

                if (in_array($type, ['link', 'qr_code'], true)) {
                    $responseComponents[] = [
                        'type' => $type,
                        'language' => 'attachment',
                        'content' => (string) $component->content,
                        'label' => $component->label,
                    ];
                    continue;
                }

                if (in_array($type, ['image', 'file'], true)) {
                    $responseComponents[] = [
                        'type' => $type,
                        'language' => 'attachment',
                        'content' => '',
                        'label' => $component->label,
                        'attachment_url' => route(
                            'admin.support.response-attachment',
                            [
                                'supportRequestId' => $support->id,
                                'responseId' => $latestResponse->id,
                                'componentId' => $component->id,
                            ]
                        ),
                        'source_support_request_id' => $support->id,
                        'source_response_id' => $latestResponse->id,
                        'source_component_id' => $component->id,
                    ];
                }
            }
        }

        /*
         * Legacy tickets may not have structured response components.
         * Only synthesize text blocks when a legacy text answer actually
         * exists. Attachment-only tickets must remain attachment-only.
         */
        if (
            $responseComponents === []
            && trim($responseAnswer) !== ''
        ) {
            $responseComponents[] = [
                'type' => 'text',
                'language' => 'en',
                'content' => $questionDraft['answer'],
                'label' => null,
            ];

            $responseComponents[] = [
                'type' => 'text',
                'language' => 'fil',
                'content' => $questionDraft['answer_fil'],
                'label' => null,
            ];
        }

        /*
         * Legacy support-request answers used a separate public
         * answer_image column. Keep that image available in the
         * same FAQ attachment section when there is no equivalent
         * structured image component.
         */
        $hasStructuredImage = collect($responseComponents)
            ->contains(fn ($component) => ($component['type'] ?? null) === 'image');

        if (!$hasStructuredImage && $support->answer_image) {
            $responseComponents[] = [
                'type' => 'image',
                'language' => 'attachment',
                'content' => '',
                'label' => 'Support Request image',
                'source_legacy_support_request_id' => $support->id,
                'attachment_url' => Storage::disk('public')->exists($support->answer_image)
                    ? Storage::disk('public')->url(ltrim($support->answer_image, '/'))
                    : null,
            ];
        }

        /*
         * Never prepare an empty FAQ. A response is valid when it contains
         * either text or at least one attachment/link/QR component.
         */
        if ($responseComponents === []) {
            return response()->json([
                'success' => false,
                'message' =>
                    'The completed support request does not contain a response or attachment that can be added to an FAQ.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'support_request_id' => $support->id,
            'agency_id' => $support->agency_id,
            'draft' => [
                'detected_language' => $questionDraft['detected_language'],
                'question' => $questionDraft['question'],
                'question_fil' => $questionDraft['question_fil'],
                'response_components' => $responseComponents,
            ],
        ]);
    } catch (\Throwable $e) {
        \Log::error(
            'Support Request FAQ preparation failed.',
            [
                'support_request_id' => $support->id,
                'user_id' => auth()->id(),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]
        );

        return response()->json([
            'success' => false,
            'message' =>
                'Unable to prepare the FAQ draft right now.',
        ], 503);
    }
}


    /**
 * 📄 DISPLAY FAQ LIST
 *
 * Handles both:
 * - Active FAQs
 * - Soft-deleted FAQs
 *
 * The same FAQ management page is used for both states.
 *
 * Only Superadmins are allowed to view trashed FAQs.
 */
/**
 * Serve a private FAQ response attachment to an authenticated administrator.
 *
 * The stored path is never accepted directly from the browser. The FAQ
 * and component index are verified before the private file is streamed.
 */
public function responseAttachment(int $faqId, int $componentIndex)
{
    $faq = Faq::findOrFail($faqId);
    $components = $faq->response_components ?? [];

    if (
        !is_array($components) ||
        !array_key_exists($componentIndex, $components)
    ) {
        abort(404);
    }

    $component = $components[$componentIndex];

    if (
        !is_array($component) ||
        !in_array($component['type'] ?? null, ['image', 'file'], true)
    ) {
        abort(404);
    }

    $path = $component['content'] ?? null;

    if (
        !is_string($path) ||
        !str_starts_with($path, 'faqs/responses/')
    ) {
        abort(404);
    }

    $disk = Storage::disk('private');

    if (!$disk->exists($path)) {
        abort(404);
    }

    $mime = $disk->mimeType($path) ?: 'application/octet-stream';
    $filename = basename($path);

    return response()->stream(
        static function () use ($disk, $path): void {
            $stream = $disk->readStream($path);

            if ($stream === false) {
                return;
            }

            fpassthru($stream);
            fclose($stream);
        },
        200,
        [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . addslashes($filename) . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=300',
        ]
    );
}

public function index(Request $request)
{
    /*
     * =========================================================
     * DETERMINE FAQ STATUS
     * =========================================================
     *
     * The status comes from the URL:
     *
     * /faqs?status=active
     * /faqs?status=trashed
     *
     * Active is the safe default.
     */
    $status = $request->query(
        'status',
        'active'
    );


    /*
     * =========================================================
     * BUILD THE BASE QUERY
     * =========================================================
     *
     * IMPORTANT:
     *
     * We must NOT create another Faq::with('agency') query
     * later in this method.
     *
     * The query created here is the query that all filters
     * and pagination will use.
     */

    if (
        $status === 'trashed' &&
        auth()->user()->role === 'superadmin'
    ) {

        /*
         * onlyTrashed() returns ONLY FAQs whose
         * deleted_at column is not null.
         */
        $query = Faq::onlyTrashed()
            ->with('agency');

    } else {

        /*
         * Normal Faq queries automatically exclude
         * soft-deleted records because the Faq model
         * uses Laravel's SoftDeletes trait.
         *
         * If somebody manually requests:
         *
         * /faqs?status=trashed
         *
         * but is not a Superadmin, we force the page
         * back to the active dataset.
         */
        $status = 'active';

        $query = Faq::with('agency');
    }


    /*
     * =========================================================
     * SEARCH
     * =========================================================
     *
     * Search active or trashed FAQs depending on the
     * currently selected status.
     */
    if ($request->filled('search')) {

        $search = trim((string) $request->input('search'));

        if ($search !== '') {
            $like = '%' . $search . '%';

            $query->where(function ($q) use ($like) {

                $q->where('question', 'LIKE', $like)
                    ->orWhere('answer', 'LIKE', $like)
                    ->orWhere('keywords', 'LIKE', $like)
                    ->orWhereHas('agency', function ($agencyQuery) use ($like) {
                        $agencyQuery
                            ->where('agency_name', 'LIKE', $like)
                            ->orWhere('agency_abbreviation', 'LIKE', $like);
                    });
            });
        }
    }


    /*
     * =========================================================
     * FILTER: AGENCY
     * =========================================================
     */
    if ($request->filled('agency')) {

        $query->where(
            'agency_id',
            $request->input('agency')
        );
    }


    /*
     * =========================================================
     * FILTER: DATE
     * =========================================================
     *
     * Active FAQs are filtered using created_at.
     * Trashed FAQs are more useful when filtered by the
     * date they were actually deleted.
     */
    if ($request->filled('date')) {

        if ($status === 'trashed') {

            $query->whereDate(
                'deleted_at',
                $request->input('date')
            );

        } else {

            $query->whereDate(
                'updated_at',
                $request->input('date')
            );
        }
    }


    /*
     * =========================================================
     * SORT
     * =========================================================
     *
     * Only two known values are accepted:
     *
     * latest
     * oldest
     *
     * Anything else falls back to latest.
     */
    $sortDirection =
        $request->input('sort') === 'oldest'
            ? 'asc'
            : 'desc';


    if ($status === 'trashed') {

        /*
         * Deleted FAQs are sorted according to
         * when they entered the recycle bin.
         */
        $query->orderBy(
            'deleted_at',
            $sortDirection
        );

    } else {

        /*
         * Active FAQs are sorted according to
         * when they were created.
         */
        $query->orderBy(
            'updated_at',
            $sortDirection
        );
    }


    /*
     * =========================================================
     * FILTER: MISSING FILIPINO / TAGLISH TRANSLATION
     * =========================================================
     *
     * This filter is still supported for the active FAQ
     * management page.
     */
    if (
        $request->query('filter')
        === 'missing_translation'
    ) {

        $query->where(function ($q) {

            $q->whereNull('question_fil')

                ->orWhere(
                    'question_fil',
                    ''
                )

                ->orWhereNull('answer_fil')

                ->orWhere(
                    'answer_fil',
                    ''
                );
        });
    }


    /*
     * =========================================================
     * PAGINATION
     * =========================================================
     *
     * Never load every FAQ into memory.
     *
     * Pagination keeps the page efficient as the
     * database grows.
     */
    // Keep the table focused on FAQ-level feedback aggregates instead of
    // loading every individual rating into the main list.
    // The main FAQ list represents the currently published response only.
    // Historical versions remain available from the feedback workspace but
    // must never make a newly published answer look like it already has
    // ratings.
    $query->withCount([
        'currentFeedback as feedback_likes_count' => fn ($feedbackQuery) =>
            $feedbackQuery->where('rating', 'helpful'),
        'currentFeedback as feedback_dislikes_count' => fn ($feedbackQuery) =>
            $feedbackQuery->where('rating', 'not_helpful'),
    ]);

    if ($status === 'active' && $request->query('feedback') === 'needs_review') {
        $minimumRatings = (int) config('faq_feedback.minimum_ratings_for_review', 5);
        $needsReviewFaqIds = \App\Models\FaqFeedback::query()
            ->select('faq_id')
            ->whereColumn('faq_version_id', 'faqs.current_version_id')
            ->whereNotNull('faq_id')
            ->groupBy('faq_id')
            ->havingRaw('COUNT(*) >= ?', [$minimumRatings])
            ->havingRaw(
                "SUM(CASE WHEN rating = 'not_helpful' THEN 1 ELSE 0 END) > SUM(CASE WHEN rating = 'helpful' THEN 1 ELSE 0 END)"
            );
        $query->whereIn('id', $needsReviewFaqIds);
    }

    $faqs = $query
        ->paginate(10)
        ->withQueryString();


    /*
 * =========================================================
 * AVAILABLE DATES
 * =========================================================
 *
 * Active FAQs:
 *     updated_at
 *
 * Trashed FAQs:
 *     deleted_at
 *
 * The dropdown therefore represents the currently
 * selected dataset.
 */

if ($status === 'trashed') {

    $availableDates = Faq::onlyTrashed()
        ->selectRaw('DATE(deleted_at) as date')
        ->distinct()
        ->orderBy('date', 'desc')
        ->limit(15)
        ->pluck('date');

} else {

    $availableDates = Faq::selectRaw('DATE(updated_at) as date')
        ->distinct()
        ->orderBy('date', 'desc')
        ->limit(15)
        ->pluck('date');
}


    /*
     * =========================================================
     * AGENCIES
     * =========================================================
     *
     * Used by the agency filter and FAQ form.
     */
    $agencies = Agency::orderBy(
        'agency_name'
    )->get();


    /*
     * =========================================================
     * FAQ COUNTS
     * =========================================================
     *
     * These power the Active / Trashed tabs.
     */
    $activeCount = Faq::count();

    $trashedCount = Faq::onlyTrashed()->count();


    /*
     * =========================================================
     * SUPPORT REQUEST → FAQ CONVERSION
     * =========================================================
     *
     * Retrieve temporary conversion context from the session.
     *
     * Only the Support Request ID and agency ID are stored
     * in the session.
     */
    $conversionSupport = session(
        'conversionSupport'
    );


    /*
     * =========================================================
     * RETURN FAQ MANAGEMENT PAGE
     * =========================================================
     */
    return view(
        'admin.faqs',
        compact(
            'faqs',
            'agencies',
            'availableDates',
            'conversionSupport',
            'status',
            'activeCount',
            'trashedCount'
        )
    );
}



    /**
 * ➕ STORE FAQ
 */
public function store(Request $request)
{
    /*
    |--------------------------------------------------------------------------
    | VALIDATE BASIC INPUT FIRST
    |--------------------------------------------------------------------------
    |
    | We validate the incoming data before using it for
    | duplicate detection or database operations.
    |
    */

    $this->mergeResponseAnswersIntoRequest($request);

    $validated = $request->validate([
        'agency_id' => [
            'required',
            'integer',
            'exists:agencies,id',
        ],

        'support_request_id' => [
            'nullable',
            'integer',
            'exists:support_requests,id',
        ],

        // English is required.
        'question' => [
            'required',
            'string',
            'max:255',
        ],

        'answer' => [
            'nullable',
            'string',
            function ($attribute, $value, $fail) use ($request) {
                if (
                    trim((string) $value) === ''
                    && !$this->requestContainsResponseContent($request)
                ) {
                    $fail(
                        'Add a text response or at least one attachment, link, or QR code to the FAQ.'
                    );
                }
            },
        ],

        // Filipino / Taglish is optional.
        'question_fil' => [
            'nullable',
            'string',
            'max:255',
        ],

        'answer_fil' => [
            'nullable',
            'string',
        ],

        // Search keywords are optional.
        'keywords' => [
            'nullable',
            'string',
            'max:1000',
        ],

        'image' => [
            'nullable',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:5120',
        ],

        'remove_image' => [
            'nullable',
            'boolean',
        ],

        'response_components' => ['nullable', 'array', 'max:10'],
        'response_components.*.type' => ['required_with:response_components', 'in:text,image,file,link,qr_code'],
        'response_components.*.language' => ['nullable', 'string', 'in:en,fil,attachment'],
        'response_components.*.content' => ['nullable', 'string', 'max:5000'],
        'response_components.*.label' => ['nullable', 'string', 'max:255'],
        'response_components.*.existing_content' => ['nullable', 'string', 'max:5000'],
        'response_components.*.attachment_url' => ['nullable', 'url', 'max:2048'],
        'response_components.*.source_support_request_id' => ['nullable', 'integer', 'exists:support_requests,id'],
        'response_components.*.source_response_id' => ['nullable', 'integer', 'exists:support_request_responses,id'],
        'response_components.*.source_component_id' => ['nullable', 'integer', 'exists:support_response_components,id'],
        'response_components.*.source_legacy_support_request_id' => ['nullable', 'integer', 'exists:support_requests,id'],
        'response_components.*.file' => [
            'nullable',
            'file',
            'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx',
            'max:5120',
        ],
    ]);

    /*
    |--------------------------------------------------------------------------
    | NORMALIZE VALUES FOR DUPLICATE DETECTION
    |--------------------------------------------------------------------------
    |
    | trim() removes unnecessary spaces.
    | mb_strtolower() makes the comparison case-insensitive.
    |
    */

    $normalizedQuestion = mb_strtolower(
        trim($validated['question'])
    );

    $normalizedAnswer = mb_strtolower(
        trim($validated['answer'])
    );

    $normalizedKeywords = mb_strtolower(
        trim($validated['keywords'] ?? '')
    );

    /*
    |--------------------------------------------------------------------------
    | CHECK FOR AN EXISTING FAQ
    |--------------------------------------------------------------------------
    |
    | withTrashed() also checks soft-deleted FAQs.
    | This prevents an administrator from creating a duplicate
    | while the original FAQ is still in the recycle bin.
    |
    */

    $duplicateFaq = Faq::withTrashed()
        ->where(
            'agency_id',
            $validated['agency_id']
        )
        ->get()
        ->first(function ($faq) use (
            $normalizedQuestion,
            $normalizedAnswer,
            $normalizedKeywords
        ) {
            return mb_strtolower(
                trim($faq->question)
            ) === $normalizedQuestion

            && mb_strtolower(
                trim($faq->answer)
            ) === $normalizedAnswer

            && mb_strtolower(
                trim($faq->keywords ?? '')
            ) === $normalizedKeywords;
        });

    /*
    |--------------------------------------------------------------------------
    | STOP DUPLICATE CREATION
    |--------------------------------------------------------------------------
    */

    if ($duplicateFaq) {
        return redirect()
            ->back()
            ->withInput()
            ->withErrors([
                'question' =>
                    'This FAQ already exists for the selected agency.',
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | FAQ IMAGE HANDLING
    |--------------------------------------------------------------------------
    */

    $imagePath = null;

/*
|--------------------------------------------------------------------------
| CASE 1: ADMINISTRATOR UPLOADED A NEW IMAGE
|--------------------------------------------------------------------------
|
| A manually uploaded image always takes priority over
| the image attached to the Support Request.
|
*/

if ($request->hasFile('image')) {

    $imagePath = $request->file('image')->store(
        'faqs',
        'public'
    );
}

/*
|--------------------------------------------------------------------------
| CASE 2: COPY IMAGE FROM SUPPORT REQUEST
|--------------------------------------------------------------------------
|
| This is used when the FAQ is being created from an
| answered Support Request and no replacement image
| was manually uploaded.
|
*/

elseif ($request->filled('support_request_id')) {

    /*
     * Retrieve the source Support Request from the database.
     *
     * We do not trust the question, answer, or image path
     * sent by the browser.
     */
    $support = SupportRequest::findOrFail(
        $request->input('support_request_id')
    );

    /*
     * Only answered Support Requests may become FAQs.
     */
    abort_unless(
        $support->status === 'answered',
        422,
        'Only completed/answered support requests can become FAQs.'
    );

    /*
     * Make sure the selected agency belongs to the
     * original Support Request.
     *
     * This prevents an administrator from associating
     * the copied content with an unrelated agency.
     */
    abort_unless(
        (int) $support->agency_id ===
        (int) $request->input('agency_id'),
        403,
        'The selected agency does not match the support request.'
    );

    /*
     * Only copy the image if the Support Request actually
     * contains an image and the file exists on storage.
     */
    if (
        $support->answer_image &&
        Storage::disk('public')->exists(
            $support->answer_image
        )
    ) {

        /*
         * Preserve the original file extension.
         */
        $extension = pathinfo(
            $support->answer_image,
            PATHINFO_EXTENSION
        );

        /*
         * Generate a unique destination filename.
         *
         * The copied file is stored separately from the
         * Support Request image so the FAQ does not depend
         * on the original file remaining unchanged.
         */
        $newImagePath =
            'faqs/faq-' .
            Str::uuid() .
            '.' .
            strtolower($extension);

        /*
         * Copy the original Support Request image into
         * the FAQ storage directory.
         */
        Storage::disk('public')->copy(
            $support->answer_image,
            $newImagePath
        );

        /*
         * Save the copied FAQ image path in the FAQ record.
         */
        $imagePath = $newImagePath;
    }
}

$faq = Faq::create([
    'agency_id'    => $request->agency_id,

    'question'     => $request->question,
    'answer'       => (string) ($request->input('answer') ?? ''),

    'question_fil' => $request->question_fil,
    'answer_fil'   => $request->answer_fil,

    'keywords'     => $this->normalizeKeywords($request->keywords),

    'image'        => $imagePath,
]);

    $faq->response_components = $this->storeResponseComponents(
        $request->input('response_components', []),
        $request->file('response_components', [])
    );

    $faq->save();

    // Every FAQ starts with an immutable Version 1 snapshot. Feedback is
    // always attached to a version rather than directly to the mutable FAQ.
    $this->createFaqVersion($faq, auth()->id());

        $this->logAction(
            auth()->user()->role ?? 'admin',
            auth()->id(),
            $faq->agency_id,
            'create_faq',
            'admin_faq',
            null,
            [
    'question'     => $faq->question,
    'answer'       => $faq->answer,
    'question_fil' => $faq->question_fil,
    'answer_fil'   => $faq->answer_fil,
    'keywords'     => $faq->keywords,
    'image'        => $faq->image,
    'response_components' => $faq->response_components,
],
            'Created FAQ: ' . $faq->question,
null,
$faq->id
);
    

        return redirect()
            ->back()
            ->with('success', 'FAQ created successfully.');
    }



    /**
     * ✏️ UPDATE FAQ
     */
    public function update(Request $request, $id)
    {
        $faq = Faq::findOrFail($id);

        $this->mergeResponseAnswersIntoRequest($request);

        $request->validate([
            'agency_id' => 'required|exists:agencies,id',
            'question' => 'required|string|max:255',
            'answer' => [
                'nullable',
                'string',
                function ($attribute, $value, $fail) use ($request) {
                    if (
                        trim((string) $value) === ''
                        && !$this->requestContainsResponseContent($request)
                    ) {
                        $fail(
                            'Add a text response or at least one attachment, link, or QR code to the FAQ.'
                        );
                    }
                },
            ],
            'question_fil' => 'nullable|string|max:255',
            'answer_fil' => 'nullable|string',
            'keywords' => 'nullable|string|max:1000',
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'remove_image' => ['nullable', 'boolean'],
            'response_components' => ['nullable', 'array', 'max:10'],
            'response_components.*.type' => ['required_with:response_components', 'in:text,image,file,link,qr_code'],
            'response_components.*.language' => ['nullable', 'string', 'in:en,fil,attachment'],
            'response_components.*.content' => ['nullable', 'string', 'max:5000'],
            'response_components.*.label' => ['nullable', 'string', 'max:255'],
            'response_components.*.existing_content' => ['nullable', 'string', 'max:5000'],
            'response_components.*.attachment_url' => ['nullable', 'url', 'max:2048'],
            'response_components.*.source_support_request_id' => ['nullable', 'integer', 'exists:support_requests,id'],
            'response_components.*.source_response_id' => ['nullable', 'integer', 'exists:support_request_responses,id'],
            'response_components.*.source_component_id' => ['nullable', 'integer', 'exists:support_response_components,id'],
            'response_components.*.source_legacy_support_request_id' => ['nullable', 'integer', 'exists:support_requests,id'],
            'response_components.*.file' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx',
                'max:5120',
            ],
        ]);

        $oldData = [
            'agency_id' => $faq->agency_id,
            'question' => $faq->question,
            'answer' => $faq->answer,
            'question_fil' => $faq->question_fil,
            'answer_fil' => $faq->answer_fil,
            'keywords' => $faq->keywords,
            'image' => $faq->image,
            'response_components' => $faq->response_components,
        ];

        $imageChanged = $request->hasFile('image');
        $removeImage = $request->boolean('remove_image');
        $imagePath = $faq->image;

        if ($removeImage && $faq->image) {
            // Keep the old file because the previous response version may
            // still reference it. Permanent FAQ deletion cleans it up later.
            $imagePath = null;
        }

        if ($imageChanged) {
            // The replacement is stored alongside the historical file. The
            // old path remains part of the previous immutable version.
            $imagePath = $request->file('image')->store('faqs', 'public');
        }

        $oldResponseComponents = $faq->response_components ?? [];
        $newResponseComponents = $this->storeResponseComponents(
            $request->input('response_components', []),
            $request->file('response_components', []),
            $oldResponseComponents
        );

        $newData = [
            'agency_id' => $request->agency_id,
            'question' => $request->question,
            'answer' => $request->answer,
            'question_fil' => $request->question_fil,
            'answer_fil' => $request->answer_fil,
            'keywords' => $this->normalizeKeywords($request->keywords),
            'image' => $imagePath,
            'response_components' => $newResponseComponents,
        ];

        $changes = $this->getChangedValues($oldData, $newData);

        if (empty($changes['old']) && empty($changes['new'])) {
            return redirect()
                ->back()
                ->with('success', 'No changes were made.');
        }

        // Compare the proposed response with the currently published
        // response, not merely with mutable FAQ metadata. This prevents
        // agency/question/keyword edits (or harmless component form
        // metadata) from creating a duplicate response version.
        $publishedResponse = $faq->currentVersion;
        $responseBaseline = $publishedResponse
            ? $this->responseSnapshot($publishedResponse)
            : $oldData;

        $responseChanged = $this->faqResponseChanged($responseBaseline, $newData);

        DB::transaction(function () use (
            $faq,
            $newData,
            $responseChanged,
            $changes
        ) {
            /** @var Faq $lockedFaq */
            $lockedFaq = Faq::query()
                ->whereKey($faq->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedFaq->update($newData);

            if ($responseChanged) {
                $this->createNextFaqVersion(
                    $lockedFaq,
                    $newData,
                    auth()->id()
                );
            }

            $this->logAction(
                auth()->user()->role ?? 'admin',
                auth()->id(),
                $lockedFaq->agency_id,
                'update_faq',
                'admin_faq',
                $changes['old'],
                $changes['new'],
                'Updated FAQ: ' . $lockedFaq->question,
                null,
                $lockedFaq->id
            );
        });

        return redirect()
            ->back()
            ->with('success', $responseChanged
                ? 'FAQ updated successfully. A new response version is now collecting fresh feedback.'
                : 'FAQ updated successfully.');
    }

    /**
     * ❌ DELETE FAQ
     */
    public function destroy(Request $request, $id)
    {
        $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $reason = trim($request->input('reason'));
        $faq = Faq::findOrFail($id);

        /**
         * 🔥 CAPTURE OLD DATA
         */
        $oldData = [
    'question'     => $faq->question,
    'answer'       => $faq->answer,
    'question_fil' => $faq->question_fil,
    'answer_fil'   => $faq->answer_fil,
    'keywords'     => $faq->keywords,
    'image'        => $faq->image,
];

        $agencyId = $faq->agency_id;
        

        $faq->trash_reason = $reason;
        $faq->save();
        $faq->delete();

        /**
         * 🔥 LOGGING
         */
        $this->logAction(
            auth()->user()->role ?? 'admin',
            auth()->id(),
            $agencyId,
            'delete_faq',
            'admin_faq',
            $oldData,
            ['trash_reason' => $reason],
            'Moved FAQ to Trash: ' . $oldData['question'] . ' | Reason: ' . $reason,
null,
$faq->id
);
        return redirect()->back()->with('success', 'FAQ deleted successfully.');
    }


/**
 * =========================================================
 * RESTORE FAQ
 * =========================================================
 *
 * Restores a soft-deleted FAQ back into the active dataset.
 */
public function restore($id)
{
    /*
     * onlyTrashed() guarantees that we are operating on
     * something currently inside the recycle bin.
     */
    $faq = Faq::onlyTrashed()
        ->findOrFail($id);

    /*
     * Remember the agency before restoration so the audit
     * log remains associated with the correct organization.
     */
    $agencyId = $faq->agency_id;

    /*
     * Restore the FAQ by clearing deleted_at.
     */
    $faq->restore();

    /*
     * Record the recovery action.
     */
    $this->logAction(
        auth()->user()->role ?? 'superadmin',
        auth()->id(),
        $agencyId,
        'restore_faq',
        'admin_faq',
        null,
        [
            'question' => $faq->question,
            'answer' => $faq->answer,
            'question_fil' => $faq->question_fil,
            'answer_fil' => $faq->answer_fil,
            'keywords' => $faq->keywords,
            'image' => $faq->image,
            'response_components' => $faq->response_components,
        ],
        'Restored FAQ: ' . $faq->question,
        null,
        $faq->id
    );

    return redirect()
        ->back()
        ->with(
            'success',
            'FAQ restored successfully.'
        );
}

/**
 * =========================================================
 * PERMANENTLY DELETE FAQ
 * =========================================================
 *
 * This operation cannot be undone.
 */
public function forceDestroy($id)
{
    /*
     * Only retrieve FAQs that are already in the recycle bin.
     *
     * This prevents an active FAQ from accidentally being
     * permanently deleted through this endpoint.
     */
    $faq = Faq::onlyTrashed()
        ->findOrFail($id);


    /*
     * Capture the FAQ's important values BEFORE deletion.
     *
     * The FAQ will no longer exist after forceDelete(),
     * so the audit log must preserve the information it needs
     * before that happens.
     */
    $oldData = [
        'faq_id'       => $faq->id,
        'agency_id'    => $faq->agency_id,
        'question'     => $faq->question,
        'answer'       => $faq->answer,
        'question_fil' => $faq->question_fil,
        'answer_fil'   => $faq->answer_fil,
        'keywords'     => $faq->keywords,
        'image'        => $faq->image,
        'response_components' => $faq->response_components,
    ];


    /*
     * Preserve the agency ID separately.
     *
     * The FAQ object will disappear after forceDelete().
     */
    $agencyId = $faq->agency_id;


    /*
     * Delete files owned by every response version, not just the current FAQ
     * row. Historical snapshots intentionally keep their old attachment paths
     * alive while the FAQ exists. Permanent deletion is the lifecycle point
     * where all of those files can safely be removed.
     */
    $versions = $faq->versions()->get();
    $publicImagePaths = collect([$faq->image])
        ->merge($versions->pluck('image'))
        ->filter()
        ->unique()
        ->values();

    foreach ($publicImagePaths as $imagePath) {
        Storage::disk('public')->delete($imagePath);
    }

    $privateComponentPaths = $versions
        ->pluck('response_components')
        ->push($faq->response_components)
        ->filter(fn ($components) => is_array($components))
        ->flatMap(fn (array $components) => collect($components)->map(
            fn ($component) => is_array($component) ? ($component['content'] ?? null) : null
        ))
        ->filter(fn ($path) => is_string($path) && str_starts_with($path, 'faqs/responses/'))
        ->unique()
        ->values();

    foreach ($privateComponentPaths as $path) {
        Storage::disk('private')->delete($path);
    }


    /*
     * Record the permanent deletion BEFORE removing
     * the FAQ from the database.
     *
     * faq_id is intentionally NULL because the referenced
     * FAQ is about to cease to exist.
     *
     * The actual FAQ ID is preserved inside old_values.
     */
    $this->logAction(
        auth()->user()->role ?? 'superadmin',
        auth()->id(),
        $agencyId,
        'force_delete_faq',
        'admin_faq',
        $oldData,
        null,
        'Permanently deleted FAQ: ' . $oldData['question'],
        null,
        $faq->id
    );


    /*
     * Permanently remove the FAQ from the database.
     *
     * This happens only after the audit record has been
     * successfully created.
     */
    $faq->forceDelete();


    return redirect()
        ->back()
        ->with(
            'success',
            'FAQ permanently deleted.'
        );
}

    /**
     * Build the immutable snapshot used to determine whether the published
     * response itself changed.
     *
     * FAQ metadata is intentionally excluded here. Changing the agency,
     * English/Filipino question, or keywords changes how the FAQ is indexed
     * and displayed, but it does not create a new answer generation and must
     * not invalidate feedback already collected for the current response.
     */
    private function responseSnapshot(array|Faq|FaqVersion $source): array
    {
        if ($source instanceof Faq || $source instanceof FaqVersion) {
            $answer = $source->answer;
            $answerFil = $source->answer_fil;
            $image = $source->image;
            $components = $source->response_components ?? [];
        } else {
            $answer = $source['answer'] ?? '';
            $answerFil = $source['answer_fil'] ?? null;
            $image = $source['image'] ?? null;
            $components = $source['response_components'] ?? [];
        }

        return [
            'answer' => $this->normalizeResponseText($answer),
            'answer_fil' => $this->normalizeResponseText($answerFil),
            'image' => $image !== null ? trim((string) $image) : null,
            'response_components' => $this->normalizeResponseComponentsForComparison($components),
        ];
    }

    /**
     * Normalize response text only for equality checks. Formatting-only
     * differences such as line endings or surrounding whitespace should not
     * reset feedback when the user-visible response is unchanged.
     */
    private function normalizeResponseText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return trim(str_replace(["\r\n", "\r"], "\n", $value));
    }

    /**
     * Compare only user-facing response component data. Browser/helper
     * metadata such as existing_content, source IDs, attachment URLs and
     * generated sort_order values are deliberately ignored because they do
     * not change the published response itself.
     */
    private function normalizeResponseComponentsForComparison(mixed $components): array
    {
        if (!is_array($components)) {
            return [];
        }

        $normalized = [];

        foreach ($components as $index => $component) {
            if (!is_array($component)) {
                continue;
            }

            $type = (string) ($component['type'] ?? '');
            if ($type === '') {
                continue;
            }

            $language = (string) ($component['language'] ?? '');
            $content = $component['content'] ?? '';
            $label = $component['label'] ?? null;

            $normalized[] = [
                'type' => $type,
                'language' => $language,
                'content' => is_string($content)
                    ? trim(str_replace(["\r\n", "\r"], "\n", $content))
                    : $content,
                'label' => $label !== null
                    ? trim((string) $label)
                    : null,
                '_order' => isset($component['sort_order'])
                    ? (int) $component['sort_order']
                    : $index,
            ];
        }

        usort($normalized, static function (array $a, array $b): int {
            return $a['_order'] <=> $b['_order'];
        });

        foreach ($normalized as &$component) {
            unset($component['_order']);
        }
        unset($component);

        return array_values($normalized);
    }

    /**
     * Determine whether an FAQ edit represents a new user-facing response.
     * Only answer text or response attachments/components reset feedback by
     * publishing a new immutable response version. FAQ metadata changes such
     * as agency, question, and keywords deliberately do not.
     */
    private function faqResponseChanged(array|Faq|FaqVersion $oldData, array $newData): bool
    {
        $old = $this->responseSnapshot($oldData);
        $new = $this->responseSnapshot($newData);

        return json_encode($old, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            !== json_encode($new, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Create Version 1 for a newly created FAQ.
     */
    private function createFaqVersion(Faq $faq, ?int $changedBy): FaqVersion
    {
        $version = FaqVersion::create([
            'faq_id' => $faq->id,
            'version_number' => 1,
            'agency_id' => $faq->agency_id,
            'question' => $faq->question,
            'answer' => $faq->answer,
            'question_fil' => $faq->question_fil,
            'answer_fil' => $faq->answer_fil,
            'keywords' => $faq->keywords,
            'image' => $faq->image,
            'response_components' => $faq->response_components ?? [],
            'changed_by' => $changedBy,
        ]);

        $faq->forceFill(['current_version_id' => $version->id])->save();

        return $version;
    }

    /**
     * Publish a new immutable response version while the FAQ row is locked.
     */
    private function createNextFaqVersion(
        Faq $faq,
        array $snapshot,
        ?int $changedBy
    ): FaqVersion {
        $current = $faq->current_version_id
            ? FaqVersion::query()
                ->whereKey($faq->current_version_id)
                ->lockForUpdate()
                ->first()
            : null;

        if ($current) {
            // Final invariant: never create a duplicate version when the
            // proposed response is byte/structure-equivalent to the already
            // published response. This protects against form normalization,
            // stale metadata, and future callers that accidentally request a
            // version for a metadata-only edit.
            if (!$this->faqResponseChanged($current, $snapshot)) {
                return $current;
            }

            $current->update(['superseded_at' => now()]);
        }

        $nextNumber = ((int) FaqVersion::query()
            ->where('faq_id', $faq->id)
            ->max('version_number')) + 1;

        $version = FaqVersion::create([
            'faq_id' => $faq->id,
            'version_number' => $nextNumber,
            'agency_id' => $snapshot['agency_id'] ?? null,
            'question' => $snapshot['question'] ?? '',
            'answer' => $snapshot['answer'] ?? '',
            'question_fil' => $snapshot['question_fil'] ?? null,
            'answer_fil' => $snapshot['answer_fil'] ?? null,
            'keywords' => $faq->keywords,
            'image' => $snapshot['image'] ?? null,
            'response_components' => $snapshot['response_components'] ?? [],
            'changed_by' => $changedBy,
        ]);

        $faq->forceFill(['current_version_id' => $version->id])->save();

        return $version;
    }

    /**
     * Determine whether the FAQ request contains any usable response
     * content even when there is no text answer.
     *
     * Attachment-only FAQs are valid because the response_components
     * payload is the source of truth for images, files, links and QR codes.
     */
    private function requestContainsResponseContent(Request $request): bool
    {
        $components = $request->input('response_components', []);

        if (!is_array($components)) {
            return false;
        }

        foreach ($components as $index => $component) {
            if (!is_array($component)) {
                continue;
            }

            $type = $component['type'] ?? null;
            $content = trim((string) ($component['content'] ?? ''));

            if ($type === 'text' && $content !== '') {
                return true;
            }

            if (in_array($type, ['link', 'qr_code'], true) && $content !== '') {
                return true;
            }

            if (in_array($type, ['image', 'file'], true)) {
                $uploaded = $request->file("response_components.$index.file");

                if (
                    $content !== ''
                    || trim((string) ($component['existing_content'] ?? '')) !== ''
                    || !empty($component['source_component_id'])
                    || ($uploaded && method_exists($uploaded, 'isValid') && $uploaded->isValid())
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Keep the legacy FAQ answer columns synchronized with the new
     * language-specific response text components.
     *
     * The response builder is now the source of truth for answers.
     * The existing answer / answer_fil columns remain populated so
     * duplicate detection, search, logs, and older public code continue
     * to work without a breaking database migration.
     */
    private function mergeResponseAnswersIntoRequest(Request $request): void
    {
        $components = $request->input('response_components', []);

        if (!is_array($components)) {
            return;
        }

        $english = [];
        $filipino = [];

        foreach ($components as $component) {
            if (!is_array($component) || ($component['type'] ?? null) !== 'text') {
                continue;
            }

            $content = trim((string) ($component['content'] ?? ''));

            if ($content === '') {
                continue;
            }

            if (($component['language'] ?? 'en') === 'fil') {
                $filipino[] = $content;
            } else {
                $english[] = $content;
            }
        }

        if ($english !== []) {
            $request->merge([
                'answer' => implode("\n\n", $english),
            ]);
        }

        if ($filipino !== []) {
            $request->merge([
                'answer_fil' => implode("\n\n", $filipino),
            ]);
        }
    }

    /**
     * File paths are generated server-side; browser values are
     * never trusted as storage paths.
     */
    private function storeResponseComponents(
        array $components,
        array $files = [],
        array $existing = []
    ): array {
        $normalized = [];

        foreach ($components as $index => $component) {
            $type = $component['type'] ?? null;

            if (!in_array($type, ['text', 'image', 'file', 'link', 'qr_code'], true)) {
                continue;
            }

            $content = trim((string) ($component['content'] ?? ''));
            $label = trim((string) ($component['label'] ?? ''));

            /*
             * Text components belong to one language. Attachments are
             * explicitly marked as attachment so they never get mistaken
             * for response text by the public FAQ renderer.
             */
            $language = $component['language'] ?? (
                $type === 'text' ? 'en' : 'attachment'
            );

            if ($type === 'text' && !in_array($language, ['en', 'fil'], true)) {
                continue;
            }

            if ($type !== 'text' && $language !== 'attachment') {
                $language = 'attachment';
            }

            if (in_array($type, ['link', 'qr_code'], true)) {
                if (!filter_var($content, FILTER_VALIDATE_URL)) {
                    continue;
                }

                $scheme = strtolower((string) parse_url($content, PHP_URL_SCHEME));
                if (!in_array($scheme, ['http', 'https'], true)) {
                    continue;
                }
            }

            if ($type === 'text' && $content === '') {
                continue;
            }

            if (in_array($type, ['image', 'file'], true)) {
                // New browser upload takes priority.
                $uploaded = request()->file("response_components.$index.file");

                if ($uploaded && $uploaded->isValid()) {
                    $diagnostic = PrivateStorageDiagnostics::context() + [
                        'feature' => 'faq_response_attachment',
                        'component_index' => $index,
                        'file_size_bytes' => $uploaded->getSize(),
                        'file_mime_type' => $uploaded->getMimeType(),
                    ];

                    Log::info('Private storage upload starting.', $diagnostic);

                    try {
                        $storedPath = $uploaded->store('faqs/responses', 'private');
                    } catch (\Throwable $exception) {
                        Log::error('Private storage upload failed.', $diagnostic + [
                            'exception_class' => get_class($exception),
                            'exception_message' => mb_substr($exception->getMessage(), 0, 700),
                        ]);

                        throw $exception;
                    }

                    if (!$storedPath) {
                        continue;
                    }

                    $content = $storedPath;
                }
                // Existing FAQ attachment during edit.
                elseif (
                    !empty($component['existing_content'])
                    && is_string($component['existing_content'])
                    && in_array($component['existing_content'], array_column($existing, 'content'), true)
                ) {
                    $content = $component['existing_content'];
                }
                // Attachment forwarded from a completed Support Request.
                elseif (!empty($component['source_support_request_id'])
                    && !empty($component['source_response_id'])
                    && !empty($component['source_component_id'])
                ) {
                    $source = SupportResponseComponent::query()
                        ->where('id', (int) $component['source_component_id'])
                        ->where('support_request_response_id', (int) $component['source_response_id'])
                        ->where('type', $type)
                        ->whereHas('response.supportRequest', function ($query) use ($component) {
                            $query->where('id', (int) $component['source_support_request_id'])
                                ->where('status', 'answered')
                                ->where('agency_id', (int) request()->input('agency_id'));
                        })
                        ->first();

                    abort_unless(
                        $source && $source->content
                        && Storage::disk('private')->exists($source->content),
                        422,
                        'The selected Support Request attachment is no longer available.'
                    );

                    $extension = pathinfo($source->content, PATHINFO_EXTENSION);
                    $destination = 'faqs/responses/faq-response-' . Str::uuid()
                        . ($extension ? '.' . strtolower($extension) : '');

                    abort_unless(
                        Storage::disk('private')->copy($source->content, $destination),
                        422,
                        'The Support Request attachment could not be copied into the FAQ.'
                    );

                    $content = $destination;
                }
                // Legacy Support Request answer image.
                elseif (!empty($component['source_legacy_support_request_id']) && $type === 'image') {
                    $support = SupportRequest::findOrFail(
                        (int) $component['source_legacy_support_request_id']
                    );

                    abort_unless(
                        $support->status === 'answered'
                        && (int) $support->agency_id === (int) request()->input('agency_id')
                        && $support->answer_image
                        && Storage::disk('public')->exists($support->answer_image),
                        422,
                        'The Support Request image is no longer available.'
                    );

                    $extension = pathinfo($support->answer_image, PATHINFO_EXTENSION);
                    $destination = 'faqs/responses/faq-response-' . Str::uuid()
                        . ($extension ? '.' . strtolower($extension) : '');

                    $contents = Storage::disk('public')->get($support->answer_image);

                    abort_unless(
                        Storage::disk('private')->put($destination, $contents),
                        422,
                        'The Support Request image could not be copied into the FAQ.'
                    );

                    $content = $destination;
                } else {
                    continue;
                }
            }

            $normalized[] = [
                'type' => $type,
                'language' => $language,
                'content' => $content,
                'label' => $label !== '' ? $label : null,
                'sort_order' => count($normalized),
            ];
        }

        return $normalized;
    }

    /**
     * Serve a private FAQ response attachment only to authenticated admins.
     *
     * The database stores the private storage path, not a public URL.
     * This endpoint validates the FAQ/component relationship before
     * exposing the file, preventing arbitrary file-path access.
     */
    public function viewResponseAttachment($faqId, $componentIndex)
    {
        abort_unless(
            auth()->check() && auth()->user()->role === 'superadmin',
            403
        );

        $faq = Faq::withTrashed()->findOrFail($faqId);
        $components = $faq->response_components ?? [];

        if (!is_array($components) || !array_key_exists((int) $componentIndex, $components)) {
            abort(404);
        }

        $component = $components[(int) $componentIndex];
        $type = $component['type'] ?? null;
        $path = $component['content'] ?? null;

        abort_unless(
            in_array($type, ['image', 'file'], true)
            && is_string($path)
            && str_starts_with($path, 'faqs/responses/'),
            404
        );

        $disk = Storage::disk('private');
        abort_unless($disk->exists($path), 404);

        $mime = $disk->mimeType($path) ?: 'application/octet-stream';
        $filename = basename($path);

        return $disk->response($path, $filename, [
            'Content-Type' => $mime,
            'Content-Disposition' => str_starts_with($mime, 'image/')
                ? 'inline; filename="' . addslashes($filename) . '"'
                : 'attachment; filename="' . addslashes($filename) . '"',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Remove only private files that are no longer referenced by the FAQ.
     */
    /**
     * Normalize comma/newline-separated FAQ keywords.
     *
     * A word may only appear once across the entire keyword field,
     * case-insensitively. This prevents entries such as
     * "police clearance, police, clearance" from being stored.
     */
    private function normalizeKeywords(?string $keywords): string
    {
        $seen = [];
        $normalized = [];

        foreach (preg_split('/[,\n]+/', (string) $keywords, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $keyword) {
            $words = preg_split('/\s+/u', trim($keyword), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $uniqueWords = [];

            foreach ($words as $word) {
                $word = trim($word, " \t\r\n.,;:!?()[]{}\"'");
                if ($word === '') {
                    continue;
                }

                $key = mb_strtolower($word, 'UTF-8');
                if (isset($seen[$key])) {
                    continue;
                }

                $seen[$key] = true;
                $uniqueWords[] = $word;
            }

            if ($uniqueWords !== []) {
                $normalized[] = implode(' ', $uniqueWords);
            }
        }

        return implode(', ', $normalized);
    }

    /**
     * 🔒 CENTRALIZED LOGGING (PRODUCTION STYLE)
     */
    private function logAction(
    $role,
    $userId,
    $agencyId,
    $action,
    $page,
    $oldValues = null,
    $newValues = null,
    $description = null,
    $targetUserId = null,
    $faqId = null
) {
        try {
            UserLog::create([
                'user_id' => $userId,
                'target_user_id' => $targetUserId,
                'agency_id' => $agencyId,
                'faq_id' => $faqId,
                'target_type' => $faqId ? 'faq' : ($agencyId ? 'agency' : ($targetUserId ? 'user' : null)),
                'target_id' => $faqId ?: ($agencyId ?: $targetUserId),

                'action' => $action,
                'page'   => $page,
                'role'   => $role,

                // 🔐 SECURITY
                'ip_address' => request()->ip(),
                'device'     => substr(request()->userAgent(), 0, 255),

                /**
                 * 🔥 JSON AUDIT TRAIL
                 */
                'old_values' => $oldValues,
                'new_values' => $newValues,

                /**
                 * 🧠 HUMAN READABLE
                 */
                'description' => $description,
            ]);
        } catch (\Exception $e) {
            \Log::error('FAQ log failed: ' . $e->getMessage());
        }
    }

    /**
 * Compare the previous and current FAQ data.
 *
 * Only fields whose values actually changed are returned.
 */
private function getChangedValues(
    array $oldData,
    array $newData
): array {
    $oldChanged = [];
    $newChanged = [];

    foreach ($newData as $field => $newValue) {

        $oldValue = $oldData[$field] ?? null;

        if ($oldValue !== $newValue) {
            $oldChanged[$field] = $oldValue;
            $newChanged[$field] = $newValue;
        }
    }

    return [
        'old' => $oldChanged,
        'new' => $newChanged,
    ];
}
}