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
     * 📄 DISPLAY SUPPORT REQUESTS LIST (ADMIN)
     */
    public function index(Request $request)
    {
        $status = $request->get('status');
        $agency = $request->get('agency');

        $query = SupportRequest::with(['user', 'agency'])
                    ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        if ($agency) {
            $query->where('agency_id', $agency);
        }

        $requests = $query
            ->paginate(10)
            ->withQueryString();
        $agencies = Agency::select('id', 'agency_name')->get();

        return view('admin.support_requests', compact('requests', 'agencies'));
    }

    /**
     * 📩 ADMIN REPLY
     */
    public function reply(Request $request)
    {
        $validated = $request->validate([
            'request_id' => 'required|exists:support_requests,id',

            // 🔥 NEW: validate agency
            'agency_id' => 'required|exists:agencies,id',

            'reply' => 'required|string|max:1000'
        ]);

        $support = SupportRequest::findOrFail($validated['request_id']);

        $support->update([
    'answer' => $validated['reply'],
    'agency_id' => $validated['agency_id'],
    'status' => 'answered',

    // Record when the administrator provided the answer.
    'answered_at' => now(),

    // The new answer has not been seen by the user yet.
    'answer_seen_at' => null,
]);

        return redirect()->back()->with('success', 'Reply sent successfully');
    }

    /**
     * 👤 USER VIEW (MY INQUIRIES)
     */
    public function userIndex()
    {
        // 🔒 SECURITY: only logged-in user's data
        $requests = SupportRequest::where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('public_user.inquiries', compact('requests'));
    }


    /**
 * 👁️ MARK AN ANSWER AS SEEN
 *
 * Records that the authenticated user has opened
 * an answered support request.
 */
public function markAnswerSeen($id)
{
    /*
     * 🔒 Retrieve the request belonging specifically
     * to the currently authenticated user.
     *
     * This is important: we do NOT retrieve by ID alone,
     * because a user must never be able to mark another
     * user's inquiry as seen.
     */
    $support = SupportRequest::where('id', $id)
        ->where('user_id', auth()->id())
        ->where('status', 'answered')
        ->firstOrFail();

    /*
     * Only update the timestamp if the answer has not
     * already been seen.
     *
     * This avoids unnecessary database writes every time
     * the user opens the same inquiry.
     */
    if (is_null($support->answer_seen_at)) {

        $support->update([
            'answer_seen_at' => now(),
        ]);
    }

    /*
     * Return JSON because the inquiry page will call
     * this endpoint asynchronously with JavaScript.
     */
    return response()->json([
        'success' => true,
    ]);
}

    public function update(Request $request, $id)
{
    // 🔒 Validate input
    $validated = $request->validate([
        'reply' => 'required|string|max:1000'
    ]);

    // 🔍 Find record
    $support = SupportRequest::findOrFail($id);

    // 🔄 Update safely
    $support->update([
    'answer' => $validated['reply'],
    'status' => 'answered',

    // Keep the original answer timestamp if one already exists.
    'answered_at' => $support->answered_at ?? now(),

    // The answer was modified, so the user should be notified again.
    'answer_seen_at' => null,
]);

    return back()->with('success', 'Answer updated successfully.');
}

/**
 * ❌ DELETE SUPPORT REQUEST
 *
 * Permanently removes the Support Request from normal
 * application visibility and records the deletion in
 * the administrator audit log.
 */
public function destroy($id)
{
    /*
     * 🔒 Defense-in-depth authorization.
     *
     * The route should already be protected by the appropriate
     * middleware, but destructive operations must also verify
     * the authenticated user's role here.
     */
    if (
        !auth()->check() ||
        auth()->user()->role !== 'superadmin'
    ) {
        abort(403, 'Unauthorized action.');
    }

    /*
     * 🔍 Retrieve the authoritative Support Request.
     *
     * We retrieve the record from the database instead of
     * trusting information supplied by the browser.
     */
    $support = SupportRequest::findOrFail($id);

    /*
     * 🔥 Capture the important values BEFORE deletion.
     *
     * Once the record is deleted, these values may no longer
     * be available through a normal query.
     *
     * This becomes the "before" snapshot in the audit trail.
     */
    $oldData = [
        'support_request_id' => $support->id,
        'user_id'            => $support->user_id,
        'agency_id'          => $support->agency_id,
        'question'           => $support->question,
        'answer'             => $support->answer,
        'status'             => $support->status,
        'created_at'         => $support->created_at?->toDateTimeString(),
    ];

    /*
     * Preserve the agency ID separately because the Support
     * Request itself may no longer be available after deletion.
     */
    $agencyId = $support->agency_id;

    /*
     * 🔥 Perform the actual deletion.
     *
     * If SupportRequest uses SoftDeletes, Laravel will mark
     * the record as deleted instead of physically removing it.
     */
    $support->delete();

    /*
     * 📝 Create the audit record.
     *
     * We use the same UserLog table already used by the FAQ
     * management system.
     */
    try {

        UserLog::create([
            'user_id' => auth()->id(),

            /*
             * Support Requests currently do not appear to have
             * a dedicated target_support_request_id field in the
             * logging structure, so the ID is preserved inside
             * old_values instead.
             */
            'target_user_id' => null,

            'agency_id' => $agencyId,

            /*
             * This is not an FAQ action, so faq_id remains null.
             */
            'faq_id' => null,

            'action' => 'delete_support_request',

            'page' => 'admin_support_requests',

            'role' => auth()->user()->role ?? 'admin',

            /*
             * 🔐 Security information.
             *
             * These values follow the same pattern as your FAQ audit.
             */
            'ip_address' => request()->ip(),

            'device' => substr(
                request()->userAgent() ?? '',
                0,
                255
            ),

            /*
             * Store the deleted Support Request snapshot.
             *
             * This gives administrators a historical record of
             * what was deleted.
             */
            'old_values' => $oldData,

            /*
             * Deletion has no "new" state.
             */
            'new_values' => null,

            /*
             * Human-readable description for the Logs page.
             */
            'description' =>
                'Deleted Support Request #' . $support->id,
        ]);

    } catch (\Exception $e) {

        /*
         * The deletion itself has already happened.
         *
         * We therefore do not undo the deletion merely because
         * audit logging failed.
         *
         * Instead, record the technical logging failure in
         * Laravel's application log for investigation.
         */
        \Log::error(
            'Support Request audit log failed.',
            [
                'support_request_id' => $support->id,
                'user_id' => auth()->id(),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]
        );
    }

    return back()->with(
        'success',
        'Request deleted successfully.'
    );
}   

/**
 * 🔎 FIND SIMILAR FAQs
 *
 * Checks whether an answered Support Request has
 * potentially similar FAQs already stored in the database.
 *
 * IMPORTANT:
 * This method is read-only.
 *
 * It does NOT:
 * - create an FAQ
 * - update an FAQ
 * - delete an FAQ
 * - modify the Support Request
 */
public function findSimilarFaqs(
    Request $request,
    $id,
    FaqSimilarityService $similarityService
) {
    /*
     * 🔒 Defense-in-depth authorization.
     *
     * The route should already be protected by the
     * superadmin middleware, but sensitive administrative
     * operations should still verify authorization here.
     */
    if (
        !auth()->check() ||
        auth()->user()->role !== 'superadmin'
    ) {
        abort(403, 'Unauthorized action.');
    }

    /*
     * 🔍 Retrieve the authoritative Support Request
     * directly from the database.
     *
     * We intentionally do NOT accept the question or
     * agency ID from the browser.
     */
    $support = SupportRequest::findOrFail($id);

    /*
     * An unanswered Support Request cannot meaningfully
     * become an FAQ.
     *
     * We stop here rather than attempting similarity
     * checking against incomplete information.
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
     * The Support Request's agency is used as a relevance
     * signal by FaqSimilarityService.
     */
    if (!$support->agency_id) {
        return response()->json([
            'success' => false,
            'message' =>
                'This support request does not have an agency assigned.',
        ], 422);
    }

    /*
     * Ask the dedicated similarity service to compare
     * the user's original question against existing FAQs.
     *
     * The service performs the actual matching logic.
     */
    $matches = $similarityService->findSimilar(
        $support->question,
        $support->agency_id,
        5
    );

    /*
     * Return a deliberately small JSON structure.
     *
     * We do not expose unnecessary database columns.
     */
    return response()->json([
        'success' => true,

        'support_request_id' =>
            $support->id,

        'matches' => $matches,
    ]);
}

/**
 * 📝 OPEN SUPPORT REQUEST FOR FAQ CONVERSION
 *
 * This method does NOT create an FAQ.
 *
 * It only validates the Support Request and redirects
 * the administrator to the FAQ management page with
 * the Support Request ID attached to the session.
 */
public function toFaq($id)
{
    /*
     * 🔒 Defense-in-depth authorization.
     *
     * The route is already protected by superadmin.only,
     * but sensitive operations should still verify the
     * user's role inside the controller.
     */
    if (
        !auth()->check() ||
        auth()->user()->role !== 'superadmin'
    ) {
        abort(403, 'Unauthorized action.');
    }

    /*
     * Retrieve the actual Support Request from the database.
     *
     * We do not trust IDs or content supplied by the browser
     * beyond the route parameter itself.
     */
    $support = SupportRequest::findOrFail($id);

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
     * Redirect to the normal FAQ management page.
     *
     * We only store the Support Request ID in the session.
     *
     * The question and answer are intentionally NOT passed
     * through the browser or session because the FAQ preparation
     * endpoint will retrieve the authoritative values directly
     * from the database.
     */
    return redirect()
        ->route('faqs.index')
        ->with('conversionSupport', [
            'id' => $support->id,
            'agency_id' => $support->agency_id,
        ]);
}


}