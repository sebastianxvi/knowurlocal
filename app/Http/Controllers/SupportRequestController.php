<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\SupportRequest;
use App\Models\UserLog;
use App\Services\FaqSimilarityService;
use Illuminate\Http\Request;

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
         * Status is only meaningful for normal active requests.
         *
         * Trashed records retain their historical status, so the
         * same filter can still technically be applied to them.
         */
        if (
            $request->filled('status_filter') &&
            in_array(
                $request->status_filter,
                ['pending', 'answered'],
                true
            )
        ) {

            $query->where(
                'status',
                $request->status_filter
            );
        }


        /*
         * =====================================================
         * 🏢 AGENCY FILTER
         * =====================================================
         */
        if ($request->filled('agency')) {

            $query->where(
                'agency_id',
                $request->agency
            );
        }


        /*
         * =====================================================
         * 📄 PAGINATION
         * =====================================================
         *
         * withQueryString() keeps the current filters when the
         * administrator changes pages.
         */
        $requests = $query
            ->latest()
            ->paginate(10)
            ->withQueryString();


        /*
         * Retrieve agencies for the filter dropdown.
         *
         * Only the required columns are selected.
         */
        $agencies = Agency::select(
            'id',
            'agency_name'
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
            'admin.support_requests',
            compact(
                'requests',
                'agencies',
                'status',
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


        /*
         * Update the answer lifecycle.
         */
        $support->update([

            'answer' =>
                $validated['reply'],

            'agency_id' =>
                $validated['agency_id'],

            'status' =>
                'answered',

            /*
             * Record when the answer was provided.
             */
            'answered_at' =>
                now(),

            /*
             * The new answer has not been viewed yet.
             */
            'answer_seen_at' =>
                null,
        ]);


        return back()->with(
            'success',
            'Reply sent successfully.'
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
         * Validate the new answer.
         */
        $validated = $request->validate([
            'reply' =>
                'required|string|max:1000',
        ]);


        /*
         * Only active Support Requests may be edited.
         */
        $support =
            SupportRequest::findOrFail($id);


        /*
         * Update the answer.
         */
        $support->update([

            'answer' =>
                $validated['reply'],

            'status' =>
                'answered',

            /*
             * Preserve the original answer timestamp when
             * possible.
             */
            'answered_at' =>
                $support->answered_at ?? now(),

            /*
             * Since the answer changed, the user must be
             * notified that a new answer is available.
             */
            'answer_seen_at' =>
                null,
        ]);


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


        /*
         * Permanently remove the record.
         */
        $support->forceDelete();


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