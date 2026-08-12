<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Faq;
use App\Models\Agency;
use App\Models\UserLog;
use App\Models\SupportRequest;
use App\Services\FaqTranslationService;
use Illuminate\Support\Facades\Storage;

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
    $validated['answer'],
    $request->input('keywords', '')
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
                    'keyword_suggestions' =>
                        $translation['keyword_suggestions'],
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
         * original user's question and generates both:
         *
         * - English version
         * - Filipino/Taglish version
         *
         * It also generates search keyword suggestions.
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

                'keyword_suggestions' =>
                    $draft['keyword_suggestions'],
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
     */
    public function index(Request $request)
    {
        /**
         * 🔧 BASE QUERY
         */
        $query = Faq::with('agency');

        /**
         * 🔍 SEARCH
         */
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('question', 'LIKE', "%{$search}%")
                  ->orWhere('answer', 'LIKE', "%{$search}%");
            });
        }

        /**
         * 🔍 FILTER: AGENCY
         */
        if ($request->filled('agency')) {
            $query->where('agency_id', $request->agency);
        }

        /**
         * 🔍 FILTER: DATE
         */
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        /**
         * 🔒 SORT (SAFE)
         */
        if ($request->sort === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        /**
         * ✅ PAGINATION
         */
        $faqs = $query->paginate(10)->withQueryString();

        /**
         * 📅 AVAILABLE DATES (FIXES YOUR ERROR)
         */
        $availableDates = Faq::selectRaw('DATE(created_at) as date')
            ->distinct()
            ->orderBy('date', 'desc')
            ->limit(15)
            ->pluck('date');

        /**
 * 🏢 AGENCIES
 */
$agencies = Agency::all();

/**
 * 📝 SUPPORT REQUEST → FAQ CONVERSION
 *
 * Retrieve temporary conversion context from the session.
 *
 * Only the Support Request ID and agency ID are stored here.
 * The actual question and answer remain in the database.
 */
$conversionSupport = session('conversionSupport');

return view('admin.faqs', compact(
    'faqs',
    'agencies',
    'availableDates',
    'conversionSupport'
));
    }

    /**
     * ➕ STORE FAQ
     */
    public function store(Request $request)
    {
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
    'mimes:jpg,jpeg,png',
    'max:2048',
],
        ]);

        $imagePath = null;

if ($request->hasFile('image')) {
    $imagePath = $request->file('image')->store(
        'faqs',
        'public'
    );
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
    'mimes:jpg,jpeg,png',
    'max:2048',
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
];


        $imageChanged = $request->hasFile('image');

$imagePath = $faq->image;

if ($imageChanged) {

    /*
     * Remove the previous image only when the administrator
     * actually uploads a replacement.
     */
    if ($faq->image) {
        Storage::disk('public')->delete($faq->image);
    }

    /*
     * Store the replacement using Laravel's public disk.
     * Laravel generates the stored filename instead of
     * trusting the user's original filename.
     */
    $imagePath = $request->file('image')->store(
        'faqs',
        'public'
    );
}
        

        $faq->update([
    'agency_id'    => $request->agency_id,

    'question'     => $request->question,
    'answer'       => $request->answer,

    'question_fil' => $request->question_fil,
    'answer_fil'   => $request->answer_fil,

    'keywords'     => $request->keywords,

    'image'        => $imagePath,
]);

$newData = [
    'agency_id'    => $faq->agency_id,
    'question'     => $faq->question,
    'answer'       => $faq->answer,
    'question_fil' => $faq->question_fil,
    'answer_fil'   => $faq->answer_fil,
    'keywords'     => $faq->keywords,
    'image'        => $faq->image,
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