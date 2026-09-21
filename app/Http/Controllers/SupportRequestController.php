<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\SupportRequest;
use App\Models\UserLog;
use App\Services\FaqSimilarityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\SupportRequestResponseService;

class SupportRequestController extends Controller
{
    /**
     * =========================================================
     * 📋 DISPLAY SUPPORT REQUESTS
     * =========================================================
     *
     * Supports:
     *
     * - Active support requests
     * - Trashed support requests
     * - Status filtering
     * - Agency filtering
     * - Pagination
     *
     * Only Superadmins may access the recovery view.
     */
    public function index(Request $request)
    {
        /*
         * Determine which dataset should be displayed.
         *
         * Only "active" and "trashed" are accepted.
         *
         * Anything else safely falls back to "active".
         */
        $status = $request->input('status', 'active');

        if (!in_array($status, ['active', 'trashed'], true)) {
            $status = 'active';
        }


        /*
         * Start the Support Request query.
         *
         * Because SupportRequest uses SoftDeletes,
         * normal queries automatically exclude trashed records.
         */
        $query = SupportRequest::with([
            'user',
            'agency',
        ]);


        /*
         * =====================================================
         * 🔐 RECOVERY ACCESS
         * =====================================================
         *
         * Trashed Support Requests contain potentially sensitive
         * user-submitted questions and answers.
         *
         * Therefore only Superadmins may access them.
         */
        if ($status === 'trashed') {

            abort_unless(
                auth()->check() &&
                auth()->user()->role === 'superadmin',
                403
            );

            /*
             * onlyTrashed() returns exclusively soft-deleted
             * Support Requests.
             */
            $query->onlyTrashed();
        }


        /*
 * =====================================================
 * 🔍 STATUS FILTER
 * =====================================================
 *
 * Active Support Requests default to "pending".
 *
 * Trashed Support Requests default to showing all
 * historical statuses unless the administrator explicitly
 * chooses a status filter.
 */
$statusFilter = $request->has('status_filter')
    ? $request->input('status_filter')
    : 'pending';

$allowedStatusFilters = [
    '',
    'pending',
    'awaiting_confirmation',
    'needs_follow_up',
    'answered',
];

if (!in_array($statusFilter, $allowedStatusFilters, true)) {
    $statusFilter = '';
}

/*
 * Apply the status filter only when a specific status
 * has been selected.
 */
if ($statusFilter !== '') {
    $query->where(
        'status',
        $statusFilter
    );
}

/*
 * =====================================================
 * 🔎 QUESTION SEARCH
 * =====================================================
 *
 * Search only the Support Request question.
 */
$search = trim(
    $request->input('search', '')
);

if ($search !== '') {
    $query->where(
        'question',
        'like',
        '%' . $search . '%'
    );
}


        /*
         * =====================================================
         * 🏢 AGENCY FILTER
         * =====================================================
         */
        $agencyFilter = $request->input('agency', '');

        if ($agencyFilter !== '') {
            $query->where(
                'agency_id',
                $agencyFilter
            );
        }


        /*
|--------------------------------------------------------------------------
| 🔃 SORT ORDER
|--------------------------------------------------------------------------
|
| The administrator can choose whether Support Requests
| should be displayed from newest to oldest or oldest
| to newest.
|
| Only explicitly allowed values are accepted.
| This prevents arbitrary request input from reaching
| the database ORDER BY clause.
*/

$sort = $request->input('sort', 'newest');

$allowedSorts = [
    'newest',
    'oldest',
];

if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'newest';
}


/*
|--------------------------------------------------------------------------
| 📄 PAGINATION
|--------------------------------------------------------------------------
|
| The selected sort order is applied at the database level.
|
| This is important because the table is paginated.
| Sorting only the rows already loaded into the browser
| would produce incorrect ordering across multiple pages.
|
| withQueryString() preserves the active filters and
| sort order when the administrator changes pages.
*/

$requests = $query
    ->orderBy(
        'created_at',
        $sort === 'oldest' ? 'asc' : 'desc'
    )
    ->paginate(10)
    ->withQueryString();


        /*
 * Retrieve agencies for the filter dropdown
 * and searchable agency selector.
 *
 * agency_abbreviation must be included because
 * the Blade view uses it for abbreviation searching.
 */
$agencies = Agency::select(
    'id',
    'agency_name',
    'agency_abbreviation'
)
->orderBy('agency_name')
->get();


        /*
         * Count active and trashed records separately.
         *
         * Normal count() excludes soft-deleted records.
         */
        $activeCount =
            SupportRequest::count();

        $trashedCount =
            SupportRequest::onlyTrashed()->count();


        return view(
            'admin.support-requests.index',
            compact(
                'requests',
                'agencies',
                'status',
                'statusFilter',
                'agencyFilter',
                'search',
                'sort',
                'activeCount',
                'trashedCount'
            )
        );
    }


    /**
     * =========================================================
     * 📩 ADMIN REPLY
     * =========================================================
     *
     * Answers an active Support Request.
     */
    public function reply(Request $request)
    {
        /*
         * Validate all browser-supplied values.
         */
        $validated = $request->validate([
    'request_id' =>
        'required|exists:support_requests,id',

    'agency_id' =>
        'required|exists:agencies,id',

    'reply' =>
        'required|string|max:1000',

    'answer_image' => [
        'nullable',
        'image',
        'mimes:jpg,jpeg,png,webp',
        'max:5120',
    ],
]);


        /*
         * Retrieve the authoritative record.
         *
         * Normal findOrFail() intentionally excludes trashed
         * requests because administrators should not reply to
         * requests currently in the recovery area.
         */
        $support =
            SupportRequest::findOrFail(
                $validated['request_id']
            );

            $imagePath = null;

if ($request->hasFile('answer_image')) {
    $imagePath = $request
        ->file('answer_image')
        ->store('support-answers', 'public');
}


        /*
         * Update the answer lifecycle.
         */
        $support->update([
    'answer' =>
        $validated['reply'],

    'agency_id' =>
        $validated['agency_id'],

    'answer_image' =>
        $imagePath,

    'status' =>
        'answered',

    'answered_at' =>
        now(),

    'answer_seen_at' =>
        null,
]);


        return back()->with(
            'success',
            'Reply sent successfully.'
        );
    }


    /**
 * Create and forward an official response to a citizen.
 */
public function forwardResponse(
    Request $request,
    SupportRequestResponseService $responseService
) {
    $validated = $request->validate([
        'request_id' => [
            'required',
            'integer',
            'exists:support_requests,id',
        ],

        'agency_id' => [
            'required',
            'integer',
            'exists:agencies,id',
        ],

        'components' => [
            'required',
            'array',
            'min:1',
            'max:10',
        ],

        'components.*.type' => [
            'required',
            'string',
            'in:text,image,file,link,qr_code',
        ],

        'components.*.content' => [
            'nullable',
            'string',
            'max:5000',
        ],

        'components.*.label' => [
            'nullable',
            'string',
            'max:255',
        ],

        'components.*.file' => [
            'nullable',
            'file',
            'max:5120',
            'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx',
        ],
    ]);

    /*
     * Retrieve the ticket without including soft-deleted
     * records. This prevents a deleted ticket from receiving
     * a new official response.
     */
    $supportRequest = SupportRequest::findOrFail(
        $validated['request_id']
    );

    /*
 * Prevent a new official response from being created while
 * the previous response is awaiting citizen confirmation
 * or has already been accepted.
 *
 * This is a server-side authorization/workflow check.
 * The frontend disabled button is not considered a security
 * boundary because browser controls can be modified.
 */
if (
    in_array(
        $supportRequest->status,
        [
            'awaiting_confirmation',
            'answered',
        ],
        true
    )
) {
    return response()->json([
        'success' => false,
        'message' =>
            'This ticket is not currently available for a new official response.',
    ], 409);
}

    /*
     * Build a clean component array for the service.
     *
     * We do not pass the entire Request object into the
     * service. This keeps the service independent from HTTP.
     */
    $components = [];

    foreach ($validated['components'] as $index => $component) {
        $type = $component['type'];

        /*
         * Text components require actual text.
         */
        if ($type === 'text') {
            if (
                !isset($component['content'])
                || trim($component['content']) === ''
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Text responses cannot be empty.',
                ], 422);
            }
        }

        /*
         * Links and QR codes must contain valid HTTP/HTTPS URLs.
         */
        if (in_array($type, ['link', 'qr_code'], true)) {
            if (
                empty($component['content'])
                || !filter_var(
                    $component['content'],
                    FILTER_VALIDATE_URL
                )
                || !in_array(
                    strtolower(
                        parse_url(
                            $component['content'],
                            PHP_URL_SCHEME
                        ) ?? ''
                    ),
                    ['http', 'https'],
                    true
                )
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Links and QR codes must use a valid HTTP or HTTPS URL.',
                ], 422);
            }
        }

        /*
         * Uploaded components must actually contain an upload.
         */
        if (in_array($type, ['image', 'file'], true)) {
            if (!isset($component['file'])) {
                return response()->json([
                    'success' => false,
                    'message' => ucfirst($type) . ' components require a file.',
                ], 422);
            }
        }

        $components[] = [
            'type' => $type,
            'content' => $component['content'] ?? null,
            'label' => $component['label'] ?? null,
            'file' => $component['file'] ?? null,
        ];
    }

    /*
     * Create the official response and move the ticket
     * into the citizen-confirmation stage.
     */
    $response = $responseService->createAndForward(
        $supportRequest,
        auth()->id(),
        $validated['agency_id'],
        $components
    );

    return response()->json([
        'success' => true,
        'message' => 'Official response forwarded successfully.',
        'response_id' => $response->id,
    ]);
}

/**
 * =========================================================
 * 📄 GET LATEST OFFICIAL RESPONSE
 * =========================================================
 *
 * Returns the latest response for a Support Request.
 *
 * This endpoint is read-only.
 * It does not modify the ticket or response.
 */
public function latestResponse($id)
{

abort_unless(
    auth()->check() &&
    in_array(
        auth()->user()->role,
        ['admin', 'superadmin'],
        true
    ),
    403
);
    /*
     * Retrieve only active Support Requests.
     *
     * Soft-deleted tickets must not be exposed through
     * the normal Manage modal.
     */
    $supportRequest = SupportRequest::findOrFail($id);

    /*
     * Load the latest official response together with
     * its ordered components.
     *
     * The relationship already defines the component
     * ordering through sort_order.
     */
    $response = $supportRequest
        ->latestResponse()
        ->with('components')
        ->first();

    /*
     * A pending ticket may legitimately have no response yet.
     *
     * Returning null instead of a 404 allows the frontend
     * to distinguish "no response yet" from "ticket missing".
     */
    return response()->json([
        'success' => true,

        'support_request_id' =>
            $supportRequest->id,

        'status' =>
            $supportRequest->status,

        'response' =>
            $response
                ? [
                    'id' => $response->id,

                    'status' =>
                        $response->status,

                    'forwarded_at' =>
                        $response->forwarded_at?->toISOString(),

                    'responded_at' =>
                        $response->responded_at?->toISOString(),

                    'follow_up_reason' =>
                        $response->follow_up_reason,

                     'components' =>
                        $response->components->map(
                            function ($component) use (
                                $supportRequest,
                                $response
                            ) {
                                return [
                                    'id' =>
                                        $component->id,

                                    'type' =>
                                        $component->type,

                                    'content' =>
                                        $component->content,

                                    'label' =>
                                        $component->label,

                                    'sort_order' =>
                                        $component->sort_order,

                                    /*
                                    * Never expose the private storage path.
                                    *
                                    * The frontend receives an authenticated Laravel
                                    * endpoint instead.
                                    */
                                    'attachment_url' =>
                                        in_array(
                                            $component->type,
                                            ['image', 'file'],
                                            true
                                        )
                                            ? route(
                                                'admin.support.response-attachment',
                                                [
                                                    'supportRequestId' =>
                                                        $supportRequest->id,

                                                    'responseId' =>
                                                        $response->id,

                                                    'componentId' =>
                                                        $component->id,
                                                ]
                                            )
                                            : null,
                                ];
                            }
                        )->values(),
                ]
                : null,
    ]);
}

/**
 * =========================================================
 * 🔐 VIEW SUPPORT RESPONSE ATTACHMENT
 * =========================================================
 *
 * Serves an image or document from the private disk.
 *
 * The file is never exposed through /storage.
 * Access is restricted to authenticated administrators.
 */
public function viewResponseAttachment(
    $supportRequestId,
    $responseId,
    $componentId
) {
    /*
     * Defense-in-depth authorization.
     *
     * Even if the route is already protected by admin middleware,
     * the controller independently verifies the user's role.
     */
    abort_unless(
        auth()->check() &&
        in_array(
            auth()->user()->role,
            ['admin', 'superadmin'],
            true
        ),
        403
    );

    /*
     * Locate the component while simultaneously verifying
     * the entire ownership chain:
     *
     * component
     *     ↓
     * response
     *     ↓
     * support request
     *
     * This prevents an administrator from changing IDs in the
     * URL to access an attachment belonging to another ticket.
     */
    $component = \App\Models\SupportResponseComponent::query()
        ->where('id', $componentId)
        ->where(
            'support_request_response_id',
            $responseId
        )
        ->whereHas(
            'response.supportRequest',
            function ($query) use ($supportRequestId) {
                $query->where(
                    'id',
                    $supportRequestId
                );
            }
        )
        ->firstOrFail();

    /*
     * Only image and file components are allowed to use
     * this endpoint.
     *
     * Text, links, and QR destination URLs must never be
     * interpreted as storage paths.
     */
    abort_unless(
        in_array(
            $component->type,
            ['image', 'file'],
            true
        ),
        404
    );

    /*
     * The database contains the private storage path.
     */
    $path = $component->content;

    /*
     * Confirm that a path exists and that the file actually
     * exists on the private filesystem.
     */
    abort_unless(
        $path &&
        Storage::disk('private')->exists($path),
        404
    );

    /*
     * Stream the private file through Laravel.
     *
     * The actual storage location remains hidden from the browser.
     */
    return Storage::disk('private')->response(
        $path,
        basename($path),
        [
            /*
             * "inline" allows browsers to display supported
             * content such as images directly.
             *
             * Unsupported documents may still be downloaded
             * by the browser.
             */
            'Content-Disposition' =>
                'inline; filename="' .
                addslashes(basename($path)) .
                '"',

            /*
             * Prevent MIME-sniffing.
             */
            'X-Content-Type-Options' =>
                'nosniff',
        ]
    );
}


    /**
     * =========================================================
     * 👤 USER VIEW
     * =========================================================
     *
     * Displays only the authenticated user's own inquiries.
     */
    public function userIndex()
    {
        /*
         * The user ID comes from the authenticated session,
         * never from browser input.
         */
        $requests =
            SupportRequest::where(
                'user_id',
                auth()->id()
            )
            ->latest()
            ->get();


        return view(
            'public_user.inquiries',
            compact('requests')
        );
    }


    /**
     * =========================================================
     * 👁️ MARK ANSWER AS SEEN
     * =========================================================
     */
    public function markAnswerSeen($id)
    {
        /*
         * Retrieve only an answered request belonging to the
         * currently authenticated user.
         *
         * This prevents IDOR-style access where one user could
         * modify another user's inquiry.
         */
        $support =
            SupportRequest::where('id', $id)
            ->where(
                'user_id',
                auth()->id()
            )
            ->where(
                'status',
                'answered'
            )
            ->firstOrFail();


        /*
         * Avoid unnecessary database writes if the answer
         * has already been seen.
         */
        if (
            is_null(
                $support->answer_seen_at
            )
        ) {

            $support->update([
                'answer_seen_at' =>
                    now(),
            ]);
        }


        return response()->json([
            'success' => true,
        ]);
    }


    /**
 * =========================================================
 * 📝 UPDATE ANSWER
 * =========================================================
 */
public function update(
    Request $request,
    $id
) {
    /*
     * Validate all submitted values.
     */
    $validated = $request->validate([
        'reply' => [
            'required',
            'string',
            'max:1000',
        ],

        'answer_image' => [
            'nullable',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:5120',
        ],

        /*
         * This field is sent as "1" when the administrator
         * clicks the X button.
         */
        'remove_answer_image' => [
            'nullable',
            'boolean',
        ],
    ]);

    /*
     * Retrieve only an active Support Request.
     */
    $support = SupportRequest::findOrFail($id);

    /*
     * Preserve the existing image path before changing
     * the Support Request.
     */
    $oldImagePath = $support->answer_image;

    /*
     * Start with the existing image.
     *
     * This means the old image is preserved unless the
     * administrator removes it or uploads a replacement.
     */
    $newImagePath = $oldImagePath;

    /*
     * =====================================================
     * REMOVE EXISTING IMAGE
     * =====================================================
     *
     * This runs when the hidden input contains "1".
     */
    if ($request->boolean('remove_answer_image')) {
        $newImagePath = null;
    }

    /*
     * =====================================================
     * UPLOAD NEW IMAGE
     * =====================================================
     *
     * A newly uploaded image takes priority over the
     * removal flag.
     */
    if ($request->hasFile('answer_image')) {
        $newImagePath = $request
            ->file('answer_image')
            ->store('support-answers', 'public');
    }

    /*
     * Update the Support Request record.
     */
    $support->update([
        'answer' => $validated['reply'],

        'answer_image' => $newImagePath,

        'status' => 'answered',

        /*
         * Preserve the original answer timestamp when possible.
         */
        'answered_at' => $support->answered_at ?? now(),

        /*
         * Notify the user that the answer was changed.
         */
        'answer_seen_at' => null,
    ]);

    /*
     * =====================================================
     * DELETE THE OLD IMAGE FILE
     * =====================================================
     *
     * Delete the old physical file only when:
     *
     * - An old image exists; and
     * - The image was removed or replaced.
     *
     * The old file is not deleted when the administrator
     * simply edits the answer text.
     */
    $imageWasRemoved =
        $request->boolean('remove_answer_image');

    $imageWasReplaced =
        $request->hasFile('answer_image');

    if (
        $oldImagePath &&
        ($imageWasRemoved || $imageWasReplaced) &&
        Storage::disk('public')->exists($oldImagePath)
    ) {
        Storage::disk('public')->delete($oldImagePath);
    }

    return back()->with(
        'success',
        'Answer updated successfully.'
    );
}


    /**
     * =========================================================
     * 🗑️ MOVE SUPPORT REQUEST TO TRASH
     * =========================================================
     *
     * This is a SOFT DELETE.
     *
     * The record remains in the database and can be restored
     * by a Superadmin.
     */
    public function destroy($id)
    {
        /*
         * Defense-in-depth authorization.
         *
         * Even if the route is protected by middleware,
         * destructive operations should verify authorization
         * inside the controller as well.
         */
        abort_unless(
            auth()->check() &&
            auth()->user()->role === 'superadmin',
            403
        );


        /*
         * Retrieve only an active Support Request.
         *
         * A request already in the trash cannot be "deleted"
         * again through this endpoint.
         */
        $support =
            SupportRequest::findOrFail($id);


        /*
         * Capture the historical state BEFORE deletion.
         */
        $oldData =
            $this->buildAuditSnapshot(
                $support
            );


        /*
         * Preserve the agency ID for the audit record.
         */
        $agencyId =
            $support->agency_id;


        /*
         * Preserve the Support Request ID.
         */
        $supportId =
            $support->id;


        /*
         * Preserve a short description for the audit log.
         */
        $description =
            'Moved Support Request #' .
            $supportId .
            ' to Trash';


        /*
         * Perform the soft delete.
         */
        $support->delete();


        /*
         * Record the lifecycle event AFTER the database
         * operation succeeds.
         */
        $this->logAction(
            'delete_support_request',
            $supportId,
            $agencyId,
            $oldData,
            null,
            $description
        );


        return back()->with(
            'success',
            'Support Request moved to trash successfully.'
        );
    }


    /**
     * =========================================================
     * ♻️ RESTORE SUPPORT REQUEST
     * =========================================================
     *
     * Only Superadmins may restore a Support Request.
     */
    public function restore($id)
    {
        /*
         * Defense-in-depth authorization.
         */
        abort_unless(
            auth()->check() &&
            auth()->user()->role === 'superadmin',
            403
        );


        /*
         * onlyTrashed() guarantees that we are restoring
         * an actually deleted record.
         */
        $support =
            SupportRequest::onlyTrashed()
            ->findOrFail($id);


        /*
         * Capture the state while the record is still in trash.
         */
        $oldData =
            $this->buildAuditSnapshot(
                $support
            );


        /*
         * Preserve important identifiers.
         */
        $supportId =
            $support->id;

        $agencyId =
            $support->agency_id;


        /*
         * Restore the Support Request.
         */
        $support->restore();


        /*
         * The restored state is represented by the same
         * database information, but the deletion timestamp
         * has been removed.
         */
        $newData =
            $this->buildAuditSnapshot(
                $support->fresh()
            );


        /*
         * Record the restoration.
         */
        $this->logAction(
            'restore_support_request',
            $supportId,
            $agencyId,
            $oldData,
            $newData,
            'Restored Support Request #' .
                $supportId
        );


        return back()->with(
            'success',
            'Support Request restored successfully.'
        );
    }


    /**
     * =========================================================
     * 🔥 PERMANENTLY DELETE SUPPORT REQUEST
     * =========================================================
     *
     * This is irreversible.
     *
     * Only Superadmins may perform this operation.
     */
    public function forceDestroy($id)
    {
        /*
         * Defense-in-depth authorization.
         */
        abort_unless(
            auth()->check() &&
            auth()->user()->role === 'superadmin',
            403
        );


        /*
         * Only records already in the trash may be permanently
         * deleted.
         */
        $support =
            SupportRequest::onlyTrashed()
            ->findOrFail($id);


        /*
         * Capture ALL important information BEFORE the
         * database row disappears.
         */
        $oldData =
            $this->buildAuditSnapshot(
                $support
            );


        /*
         * Preserve identifiers independently because the model
         * will no longer exist after forceDelete().
         */
        $supportId =
            $support->id;

        $agencyId =
            $support->agency_id;

            $imagePath =
    $support->answer_image;


        /*
         * Permanently remove the record.
         */
        $support->forceDelete();

if (
    $imagePath &&
    Storage::disk('public')->exists($imagePath)
) {
    Storage::disk('public')->delete($imagePath);
}


        /*
         * The audit record is created AFTER successful
         * permanent deletion.
         *
         * old_values therefore becomes the historical source
         * of truth.
         */
        $this->logAction(
            'force_delete_support_request',
            $supportId,
            $agencyId,
            $oldData,
            null,
            'Permanently Deleted Support Request #' .
                $supportId
        );


        return back()->with(
            'success',
            'Support Request permanently deleted.'
        );
    }


    /**
     * =========================================================
     * 🔎 FIND SIMILAR FAQs
     * =========================================================
     *
     * Read-only similarity checking.
     */
    public function findSimilarFaqs(
        Request $request,
        $id,
        FaqSimilarityService $similarityService
    ) {
        /*
         * Only Superadmins may use this administrative
         * operation.
         */
        abort_unless(
            auth()->check() &&
            auth()->user()->role === 'superadmin',
            403
        );


        /*
         * Retrieve an active Support Request.
         */
        $support =
            SupportRequest::findOrFail($id);


        /*
         * Similarity checking requires an answer.
         */
        if (
            !$support->answer ||
            trim($support->answer) === ''
        ) {

            return response()->json([
                'success' => false,

                'message' =>
                    'Cannot check FAQ similarity for an unanswered request.',
            ], 422);
        }


        /*
         * Similarity checking requires an agency.
         */
        if (!$support->agency_id) {

            return response()->json([
                'success' => false,

                'message' =>
                    'This support request does not have an agency assigned.',
            ], 422);
        }


        /*
         * Ask the dedicated similarity service to locate
         * potentially related FAQs.
         */
        $matches =
            $similarityService->findSimilar(
                $support->question,
                $support->agency_id,
                5
            );


        return response()->json([
            'success' => true,

            'support_request_id' =>
                $support->id,

            'matches' =>
                $matches,
        ]);
    }


    /**
     * =========================================================
     * 📝 PREPARE SUPPORT REQUEST FOR FAQ CONVERSION
     * =========================================================
     *
     * This does NOT create the FAQ.
     *
     * It simply prepares the FAQ management page.
     */
    public function toFaq($id)
    {
        /*
         * Only Superadmins may convert Support Requests into FAQs.
         */
        abort_unless(
            auth()->check() &&
            auth()->user()->role === 'superadmin',
            403
        );


        /*
         * Only active Support Requests may be converted.
         */
        $support =
            SupportRequest::findOrFail($id);


        /*
         * An unanswered request cannot become an FAQ.
         */
        if (
            !$support->answer ||
            trim($support->answer) === ''
        ) {

            return back()->with(
                'error',
                'Cannot create an FAQ from an unanswered request.'
            );
        }


        /*
         * An FAQ must have an agency association.
         */
        if (!$support->agency_id) {

            return back()->with(
                'error',
                'Cannot create an FAQ without an agency.'
            );
        }


        /*
         * Pass only the Support Request ID and agency ID.
         *
         * The FAQ page can retrieve authoritative information
         * directly from the database.
         */
        return redirect()
            ->route('faqs.index')
            ->with(
                'conversionSupport',
                [
                    'id' =>
                        $support->id,

                    'agency_id' =>
                        $support->agency_id,
                ]
            );
    }


    /**
     * =========================================================
     * 🧾 BUILD AUDIT SNAPSHOT
     * =========================================================
     *
     * Creates a complete historical representation of a
     * Support Request.
     *
     * This is particularly important because a Support Request
     * can later be permanently deleted.
     */
    private function buildAuditSnapshot(
        SupportRequest $support
    ): array {
        return [

            'support_request_id' =>
                $support->id,

            'user_id' =>
                $support->user_id,

            'agency_id' =>
                $support->agency_id,

            'question' =>
                $support->question,

            'answer' =>
                $support->answer,

            'answer_image' =>
    $support->answer_image,

            'status' =>
                $support->status,

            'answered_at' =>
                $support->answered_at?->toDateTimeString(),

            'answer_seen_at' =>
                $support->answer_seen_at?->toDateTimeString(),

            'created_at' =>
                $support->created_at?->toDateTimeString(),

            'updated_at' =>
                $support->updated_at?->toDateTimeString(),
        ];
    }


    /**
     * =========================================================
     * 📝 CREATE AUDIT LOG
     * =========================================================
     */
    private function logAction(
        string $action,
        int $supportRequestId,
        ?int $agencyId,
        ?array $oldValues,
        ?array $newValues,
        string $description
    ): void {
        try {

            UserLog::create([

                /*
                 * Administrator performing the operation.
                 */
                'user_id' =>
                    auth()->id(),

                /*
                 * Support Requests are not user-management
                 * actions, so no target user is required.
                 */
                'target_user_id' =>
                    null,

                /*
                 * Preserve the related agency when available.
                 *
                 * UserLog does not currently have a dedicated
                 * Support Request foreign key, so the Support
                 * Request ID is stored inside old_values.
                 */
                'agency_id' =>
                    $agencyId,

                /*
                 * Not an FAQ action.
                 */
                'faq_id' =>
                    null,

                /*
                 * Categories are unrelated.
                 */
                'category_id' =>
                    null,

                /*
                 * Stable machine-readable action.
                 */
                'action' =>
                    $action,

                /*
                 * Page where the action occurred.
                 */
                'page' =>
                    'admin_support_requests',

                /*
                 * Preserve the actor's role.
                 */
                'role' =>
                    auth()->user()->role ?? 'admin',

                /*
                 * Security metadata.
                 */
                'ip_address' =>
                    request()->ip(),

                'device' =>
                    substr(
                        request()->userAgent() ?? 'Unknown',
                        0,
                        255
                    ),

                /*
                 * Historical before/after snapshots.
                 */
                'old_values' =>
                    $oldValues,

                'new_values' =>
                    $newValues,

                /*
                 * Human-readable audit description.
                 */
                'description' =>
                    $description,
            ]);

        } catch (\Throwable $e) {

            /*
             * Audit failure must not cause the already-completed
             * Support Request operation to fail.
             *
             * The technical error is still recorded in Laravel's
             * application log for investigation.
             */
            \Log::error(
                'Support Request audit logging failed.',
                [
                    'action' =>
                        $action,

                    'support_request_id' =>
                        $supportRequestId,

                    'error' =>
                        $e->getMessage(),
                ]
            );
        }
    }
}