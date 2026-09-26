<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\SupportRequest;
use App\Models\UserLog;
use App\Services\FaqSimilarityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\SupportRequestResponseService;
use App\Events\SupportRequestResponseCreated;
use App\Events\SupportRequestAssigned;
use App\Models\User;

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
        // Opening the support queue acknowledges the notifications
        // for this administrator's current browser session.
        session(['admin_support_seen_at' => now()]);

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
            'assignedAdmin',
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
 *
 * Only apply the agency condition when the administrator
 * actually selected an agency.
 *
 * Laravel's filled() treats null and empty strings as
 * empty, preventing an accidental:
 *
 *     where('agency_id', null)
 *
 * which becomes:
 *
 *     agency_id IS NULL
 */
$agencyFilter = $request->input('agency');

if ($request->filled('agency')) {
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
    /** Return active administrators for collaboration controls. */
    public function collaborators()
    {
        return response()->json([
            'admins' => User::query()
                ->whereIn('role', ['admin', 'superadmin'])
                ->where(function ($query) {
                    $query->whereNull('status')
                        ->orWhere('status', 'active');
                })
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'role'])
                ->map(fn ($admin) => [
                    'id' => $admin->id,
                    'name' => trim($admin->first_name . ' ' . $admin->last_name),
                    'role' => $admin->role,
                ])
                ->values(),
        ]);
    }

    /** Assign or unassign a support request to an administrator. */
    public function assign(Request $request, int $id)
    {
        $validated = $request->validate([
            'admin_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $admin = null;
        if (!empty($validated['admin_id'])) {
            $admin = User::query()
                ->whereKey($validated['admin_id'])
                ->whereIn('role', ['admin', 'superadmin'])
                ->first();

            abort_unless($admin, 422, 'Selected collaborator is not an administrator.');
        }

        $supportRequest = SupportRequest::query()->findOrFail($id);
        $supportRequest->update([
            'assigned_admin_id' => $admin?->id,
            'assigned_at' => $admin ? now() : null,
        ]);

        broadcast(new SupportRequestAssigned(
            id: $supportRequest->id,
            adminId: $admin?->id,
            adminName: $admin ? trim($admin->first_name . ' ' . $admin->last_name) : null,
            assignedAt: $supportRequest->assigned_at?->toIso8601String() ?? now()->toIso8601String(),
        ));

        return response()->json([
            'success' => true,
            'assigned_admin' => $admin ? [
                'id' => $admin->id,
                'name' => trim($admin->first_name . ' ' . $admin->last_name),
            ] : null,
        ]);
    }

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

    /*
    * The response service has successfully completed its
    * database transaction at this point.
    *
    * The support request now has an official response
    * waiting for citizen confirmation.
    *
    * Only now do we notify the citizen through Reverb.
    */
    broadcast(new SupportRequestResponseCreated(
        userId: (int) $supportRequest->user_id,
        supportRequestId: (int) $supportRequest->id,
        responseId: (int) $response->id,
        status: 'awaiting_confirmation',
    ));

    return response()->json([
        'success' => true,
        'message' => 'Official response forwarded successfully.',
        'response_id' => $response->id,
    ]);
}

/**
 * Return paginated official response history for one support request.
 *
 * Historical responses are read-only records. They are never returned
 * as editable response-builder data.
 */
public function responseHistory(Request $request, int $id)
{
    $supportRequest = SupportRequest::query()
        ->findOrFail($id);

    $perPage = 5;

    $responses = $supportRequest->responses()
        ->where('status', '!=', 'draft')
        ->with([
            'admin:id,first_name,last_name',
            'components' => function ($query) {
                $query->orderBy('sort_order');
            },
        ])
        ->latest('id')
        ->paginate($perPage);

    $responses->getCollection()->transform(
        function ($response) use ($supportRequest) {

            $response->components->each(
                function ($component) use ($supportRequest, $response) {

                    if (
                        in_array(
                            $component->type,
                            ['image', 'file'],
                            true
                        )
                    ) {
                        $component->attachment_url = route(
                            'admin.support.response-attachment',
                            [
                                'supportRequestId' => $supportRequest->id,
                                'responseId' => $response->id,
                                'componentId' => $component->id,
                            ]
                        );
                    } else {
                        $component->attachment_url = null;
                    }
                }
            );

            return [
                'id' => $response->id,
                'status' => $response->status,
                'forwarded_at' => $response->forwarded_at,
                'responded_at' => $response->responded_at,
                'follow_up_reason' => $response->follow_up_reason,
                'admin' => $response->admin
                    ? [
                        'first_name' => $response->admin->first_name,
                        'last_name' => $response->admin->last_name,
                    ]
                    : null,
                'components' => $response->components,
            ];
        }
    );

    return response()->json([
        'success' => true,
        'responses' => $responses->items(),
        'pagination' => [
            'current_page' => $responses->currentPage(),
            'last_page' => $responses->lastPage(),
            'per_page' => $responses->perPage(),
            'total' => $responses->total(),
        ],
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
 * 👤 VIEW CITIZEN SUPPORT RESPONSE ATTACHMENT
 * =========================================================
 *
 * Serves an image or document belonging to the
 * authenticated citizen's own Support Request.
 *
 * Files remain on the private filesystem and are never
 * exposed through /storage.
 */
public function viewCitizenResponseAttachment(
    $supportRequestId,
    $responseId,
    $componentId
) {
    /*
     * The route is already protected by authentication,
     * but we still verify the authenticated user here.
     *
     * This is defense in depth.
     */
    abort_unless(
        auth()->check(),
        403
    );

    /*
     * Locate the component while verifying the complete
     * ownership chain:
     *
     * component
     *     ↓
     * response
     *     ↓
     * support request
     *     ↓
     * authenticated citizen
     *
     * This prevents a user from changing IDs in the URL
     * to access another citizen's attachment.
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
                $query
                    ->where('id', $supportRequestId)
                    ->where(
                        'user_id',
                        auth()->id()
                    );
            }
        )
        ->firstOrFail();

    /*
     * Only image and file components are allowed through
     * this endpoint.
     *
     * Text, links, and QR destinations are not storage files.
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
     * Verify that a path exists and that the corresponding
     * private file actually exists.
     */
    abort_unless(
        $path &&
        Storage::disk('private')->exists($path),
        404
    );

    /*
     * Stream the private file through Laravel.
     *
     * The actual storage location is never exposed
     * to the browser.
     */
    return Storage::disk('private')->response(
        $path,
        basename($path),
        [
            /*
             * Allow supported files such as images to be
             * displayed directly in the browser.
             */
            'Content-Disposition' =>
                'inline; filename="' .
                addslashes(basename($path)) .
                '"',

            /*
             * Prevent browsers from MIME-sniffing the file.
             */
            'X-Content-Type-Options' =>
                'nosniff',
        ]
    );
}


/**
 * Return one authenticated citizen inquiry with its
 * latest official response.
 *
 * Realtime notifications use this endpoint to retrieve
 * authoritative database state after Reverb reports
 * that something changed.
 */
public function userInquiry($id)
{
    $supportRequest = SupportRequest::query()
        ->where('id', $id)
        ->where('user_id', auth()->id())
        ->with([
            'agency',
            'latestResponse.components',
        ])
        ->firstOrFail();

    /*
     * Build secure attachment URLs.
     *
     * Private storage paths are never returned directly
     * to the browser.
     */
    if ($supportRequest->latestResponse) {

        $supportRequest->latestResponse
            ->components
            ->each(function ($component) use ($supportRequest) {

                $component->attachment_url =
                    in_array(
                        $component->type,
                        ['image', 'file'],
                        true
                    )
                        ? route(
                            'user.inquiries.response-attachment',
                            [
                                'supportRequestId' =>
                                    $supportRequest->id,

                                'responseId' =>
                                    $supportRequest->latestResponse->id,

                                'componentId' =>
                                    $component->id,
                            ]
                        )
                        : null;
            });
    }

    return response()->json([
        'success' => true,
        'inquiry' => $supportRequest,
    ]);
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
        $requests = SupportRequest::query()
            ->where('user_id', auth()->id())
            ->with([
                'agency',
                'latestResponse.components',
            ])
            ->latest()
            ->get();

        /*
        * Build secure citizen-side attachment URLs.
        *
        * We never expose the private storage path itself.
        */
        $requests->each(function ($supportRequest) {

            if (!$supportRequest->latestResponse) {
                return;
            }

            $supportRequest->latestResponse->components->each(
                function ($component) use ($supportRequest) {

                    $component->attachment_url =
                        in_array(
                            $component->type,
                            ['image', 'file'],
                            true
                        )
                            ? route(
                                'user.inquiries.response-attachment',
                                [
                                    'supportRequestId' =>
                                        $supportRequest->id,

                                    'responseId' =>
                                        $supportRequest->latestResponse->id,

                                    'componentId' =>
                                        $component->id,
                                ]
                            )
                            : null;
                }
            );
        });

        return view(
            'public_user.my-inquiries.index',
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
 * ✅ CONFIRM OFFICIAL RESPONSE
 * =========================================================
 *
 * Allows the authenticated citizen to confirm that the
 * latest official response resolved their concern.
 */
public function confirmResponse($id)
{
    /*
     * Retrieve only a Support Request belonging to the
     * currently authenticated citizen.
     *
     * The user ID comes from the authenticated session,
     * never from browser input.
     */
    $supportRequest = SupportRequest::query()
        ->where('id', $id)
        ->where('user_id', auth()->id())
        ->firstOrFail();

    /*
     * A response can only be confirmed while the ticket
     * is waiting for citizen confirmation.
     *
     * This prevents duplicate or out-of-order confirmations.
     */
    abort_unless(
        $supportRequest->status === 'awaiting_confirmation',
        409
    );

    /*
     * Retrieve the latest official response.
     *
     * The response belongs to this ticket because it is
     * retrieved through the Support Request relationship.
     */
    $response = $supportRequest
        ->latestResponse()
        ->firstOrFail();

    /*
     * The response itself must also be in the forwarded state.
     *
     * This provides a second workflow check instead of
     * trusting only the Support Request status.
     */
    abort_unless(
        $response->status === 'forwarded',
        409
    );

    /*
     * Mark this response as accepted by the citizen.
     *
     * responded_at records when the citizen completed
     * the confirmation step.
     */
    $response->update([
        'status' => 'accepted',
        'responded_at' => now(),
    ]);

    /*
     * "answered" now means the citizen has confirmed that
     * the official response resolved the concern.
     */
    $supportRequest->update([
        'status' => 'answered',
        'answered_at' => now(),
        'answer_seen_at' => now(),
    ]);

    return response()->json([
        'success' => true,
        'status' => 'answered',
        'message' => 'Your inquiry has been marked as resolved.',
    ]);
}

/**
 * =========================================================
 * 🔄 REQUEST FOLLOW-UP
 * =========================================================
 *
 * Allows the authenticated citizen to indicate that the
 * latest official response did not resolve their concern.
 */
public function requestFollowUp(Request $request, $id)
{
    /*
     * Validate the optional explanation.
     *
     * The explanation is useful to the administrator when
     * preparing the next official response.
     */
    $validated = $request->validate([
        'reason' => [
            'nullable',
            'string',
            'max:2000',
        ],
    ]);

    /*
     * Retrieve only a Support Request belonging to the
     * authenticated citizen.
     */
    $supportRequest = SupportRequest::query()
        ->where('id', $id)
        ->where('user_id', auth()->id())
        ->firstOrFail();

    /*
     * Follow-up is only valid while the ticket is waiting
     * for the citizen's confirmation.
     */
    abort_unless(
        $supportRequest->status === 'awaiting_confirmation',
        409
    );

    /*
     * Retrieve the latest official response.
     */
    $response = $supportRequest
        ->latestResponse()
        ->firstOrFail();

    /*
     * The response must still be awaiting the citizen's
     * decision.
     */
    abort_unless(
        $response->status === 'forwarded',
        409
    );

    /*
     * Record that the citizen did not accept this response.
     *
     * The reason is stored on the response attempt itself,
     * preserving the history of what happened.
     */
    $response->update([
        'status' => 'needs_follow_up',
        'responded_at' => now(),
        'follow_up_reason' => $validated['reason'] ?? null,
    ]);

    /*
     * Move the ticket back into the administrator's
     * follow-up queue.
     */
    $supportRequest->update([
        'status' => 'needs_follow_up',
    ]);

    return response()->json([
        'success' => true,
        'status' => 'needs_follow_up',
        'message' => 'Your inquiry has been returned for follow-up.',
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
        if ($support->status !== 'answered') {

            return response()->json([
                'success' => false,

                'message' =>
                    'Only completed/answered support requests can be used for FAQ conversion.',
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
        if ($support->status !== 'answered') {

            return back()->with(
                'error',
                'Only completed/answered support requests can be added to FAQs.'
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