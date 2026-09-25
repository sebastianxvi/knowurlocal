<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Faq;
use App\Models\Agency;
use App\Models\UserLog;
use App\Models\SupportRequest;
use App\Services\FaqTranslationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


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
     * 🔒 Defense-in-depth authorization.
     *
     * The route should also be protected by the
     * superadmin middleware, but sensitive operations
     * should not rely on middleware alone.
     */
    if (
        !auth()->check() ||
        auth()->user()->role !== 'superadmin'
    ) {
        abort(403, 'Unauthorized action.');
    }

    /*
     * 🔍 Retrieve the actual Support Request.
     *
     * We intentionally retrieve the question and answer
     * from the database instead of trusting browser data.
     */
    $support = SupportRequest::findOrFail($id);

    /*
     * Only answered requests can become FAQs.
     *
     * An unanswered request does not contain enough
     * information to create a useful FAQ.
     */
    if (
        !$support->answer ||
        trim($support->answer) === ''
    ) {
        return response()->json([
            'success' => false,
            'message' =>
                'Cannot create an FAQ from an unanswered request.',
        ], 422);
    }

    /*
     * Every FAQ must belong to an agency.
     */
    if (!$support->agency_id) {
        return response()->json([
            'success' => false,
            'message' =>
                'This support request does not have an agency assigned.',
        ], 422);
    }

    try {

        /*
         * Generate the bilingual FAQ draft.
         *
         * This method determines the language of the
         * original user's question and generates both
         * English and Filipino/Taglish versions.
         */
        $draft = $translator->prepareSupportRequestFaq(
            $support->question,
            $support->answer
        );

        /*
         * Return ONLY the information needed by the
         * FAQ creation interface.
         *
         * Nothing is written to the FAQ database here.
         */
        return response()->json([
    'success' => true,

    'support_request_id' => $support->id,

    'agency_id' => $support->agency_id,

    'support_image' => $support->answer_image,

    'draft' => [
        'detected_language' =>
            $draft['detected_language'],

        'question' =>
            $draft['question'],

        'answer' =>
            $draft['answer'],

        'question_fil' =>
            $draft['question_fil'],

        'answer_fil' =>
            $draft['answer_fil'],
    ],
]);

    } catch (\Throwable $e) {

        /*
         * 🔒 Never expose the actual AI/API exception
         * to the browser.
         *
         * Technical details remain in the Laravel log.
         */
        \Log::error(
            'Support Request FAQ preparation failed.',
            [
                'support_request_id' =>
                    $support->id,

                'user_id' =>
                    auth()->id(),

                'exception' =>
                    get_class($e),

                'message' =>
                    $e->getMessage(),
            ]
        );

        /*
         * Give the frontend a generic failure response.
         */
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

        $search = $request->input('search');

        $query->where(function ($q) use ($search) {

            $q->where(
                'question',
                'LIKE',
                "%{$search}%"
            )

            ->orWhere(
                'answer',
                'LIKE',
                "%{$search}%"
            );
        });
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
                'created_at',
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
            'created_at',
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
    $faqs = $query
        ->paginate(10)
        ->withQueryString();


    /*
 * =========================================================
 * AVAILABLE DATES
 * =========================================================
 *
 * Active FAQs:
 *     created_at
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

    $availableDates = Faq::selectRaw('DATE(created_at) as date')
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
            'required',
            'string',
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
        filled($support->answer),
        422,
        'Only answered support requests can become FAQs.'
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
    'answer'       => $request->answer,

    'question_fil' => $request->question_fil,
    'answer_fil'   => $request->answer_fil,

    'keywords'     => $request->keywords,

    'image'        => $imagePath,
]);

    $faq->response_components = $this->storeResponseComponents(
        $request->input('response_components', []),
        $request->file('response_components', [])
    );

    $faq->save();

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

        $request->validate([
            'agency_id'    => 'required|exists:agencies,id',

            // English is required.
            'question'     => 'required|string|max:255',
            'answer'       => 'required|string',

            // Filipino / Taglish is optional.
            'question_fil' => 'nullable|string|max:255',
            'answer_fil'   => 'nullable|string',

            // Search keywords are optional.
            'keywords'     => 'nullable|string|max:1000',
            'image' => [
    'nullable',
    'image',
    'mimes:jpg,jpeg,png,webp',
    'max:5120',
],

            'remove_image' => ['nullable', 'boolean'],

            'response_components' => ['nullable', 'array', 'max:10'],
            'response_components.*.type' => ['required_with:response_components', 'in:text,image,file,link,qr_code'],
            'response_components.*.content' => ['nullable', 'string', 'max:5000'],
            'response_components.*.label' => ['nullable', 'string', 'max:255'],
            'response_components.*.file' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx',
                'max:5120',
            ],
        ]);

        /**
         * Capture the existing values before updating.
         * This gives the audit log an accurate before/after record.
         */
        $oldData = [
    'agency_id'    => $faq->agency_id,
    'question'     => $faq->question,
    'answer'       => $faq->answer,
    'question_fil' => $faq->question_fil,
    'answer_fil'   => $faq->answer_fil,
    'keywords'     => $faq->keywords,
    'image'        => $faq->image,
    'response_components' => $faq->response_components,
];


        $imageChanged = $request->hasFile('image');

$removeImage = $request->boolean('remove_image');

$imagePath = $faq->image;

/*
 * CASE 1:
 * The administrator clicked X and removed the existing image.
 */
if ($removeImage && $faq->image) {

    Storage::disk('public')->delete(
        $faq->image
    );

    $imagePath = null;
}

/*
 * CASE 2:
 * The administrator uploaded a replacement image.
 *
 * The replacement takes priority over remove_image.
 */
if ($imageChanged) {

    /*
     * Delete the previous image from storage.
     */
    if ($faq->image) {

        Storage::disk('public')->delete(
            $faq->image
        );
    }

    /*
     * Store the new image.
     */
    $imagePath = $request->file('image')->store(
        'faqs',
        'public'
    );
}
        

        $oldResponseComponents = $faq->response_components ?? [];

        $newResponseComponents = $this->storeResponseComponents(
            $request->input('response_components', []),
            $request->file('response_components', []),
            $oldResponseComponents
        );

        $faq->update([
    'agency_id'    => $request->agency_id,

    'question'     => $request->question,
    'answer'       => $request->answer,

    'question_fil' => $request->question_fil,
    'answer_fil'   => $request->answer_fil,

    'keywords'     => $request->keywords,

    'image'        => $imagePath,

    'response_components' => $newResponseComponents,
]);

$newData = [
    'agency_id'    => $faq->agency_id,
    'question'     => $faq->question,
    'answer'       => $faq->answer,
    'question_fil' => $faq->question_fil,
    'answer_fil'   => $faq->answer_fil,
    'keywords'     => $faq->keywords,
    'image'        => $faq->image,
    'response_components' => $faq->response_components,
];

$changes = $this->getChangedValues(
    $oldData,
    $newData
);

if (empty($changes['old']) && empty($changes['new'])) {
    return redirect()
        ->back()
        ->with('success', 'No changes were made.');
}

$this->logAction(
    auth()->user()->role ?? 'admin',
    auth()->id(),
    $faq->agency_id,
    'update_faq',
    'admin_faq',
    $changes['old'],
    $changes['new'],
    'Updated FAQ: ' . $faq->question,
    null,
    $faq->id
);

        

        return redirect()
            ->back()
            ->with('success', 'FAQ updated successfully.');
    }

    /**
     * ❌ DELETE FAQ
     */
    public function destroy($id)
    {
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
            null,
            'Deleted FAQ: ' . $oldData['question'],
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
     * Delete the uploaded image.
     *
     * The path comes from the trusted database record,
     * not directly from browser input.
     */
    if ($faq->image) {

        Storage::disk('public')->delete(
            $faq->image
        );
    }

    $this->deleteResponseComponentFiles(
        $faq->response_components ?? []
    );


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
        null
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
     * Store FAQ response components in their submitted order.
     * File paths are generated server-side; browser values are
     * never trusted as storage paths.
     */
    private function storeResponseComponents(
        array $components,
        array $files = [],
        array $existing = []
    ): array {
        $normalized = [];

        foreach (array_values($components) as $index => $component) {
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
                $uploaded = $files[$index]['file'] ?? null;

                if ($uploaded) {
                    $content = $uploaded->store('faqs/responses', 'private');
                } elseif (
                    !empty($component['existing_content'])
                    && is_string($component['existing_content'])
                    && in_array($component['existing_content'], array_column($existing, 'content'), true)
                ) {
                    $content = $component['existing_content'];
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
     * Remove private files belonging to FAQ response components.
     */
    private function deleteResponseComponentFiles(array $components): void
    {
        foreach ($components as $component) {
            if (
                in_array($component['type'] ?? null, ['image', 'file'], true)
                && !empty($component['content'])
                && is_string($component['content'])
            ) {
                Storage::disk('private')->delete($component['content']);
            }
        }
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