<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Faq;
use App\Models\Agency;
use App\Models\UserLog;

class FaqController extends Controller
{
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

        return view('admin.faqs', compact(
            'faqs',
            'agencies',
            'availableDates'
        ));
    }

    /**
     * ➕ STORE FAQ
     */
    public function store(Request $request)
    {
        $request->validate([
            'agency_id' => 'required|exists:agencies,id',
            'question'  => 'required|string|max:255',
            'answer'    => 'required|string',
        ]);

        $faq = Faq::create([
            'agency_id' => $request->agency_id,
            'question'  => $request->question,
            'answer'    => $request->answer,
        ]);

        /**
         * 🔥 LOGGING (FIXED)
         */
        $this->logAction(
            auth()->user()->role ?? 'admin',
            auth()->id(),
            $faq->agency_id,
            'create_faq',
            'admin_faq',
            null,
            [
                'question' => $faq->question,
                'answer'   => $faq->answer,
            ],
            'Created FAQ: ' . $faq->question
        );

        return redirect()->back()->with('success', 'FAQ created successfully.');
    }

    /**
     * ✏️ UPDATE FAQ
     */
    public function update(Request $request, $id)
    {
        $faq = Faq::findOrFail($id);

        $request->validate([
            'agency_id' => 'required|exists:agencies,id',
            'question'  => 'required|string|max:255',
            'answer'    => 'required|string',
        ]);

        /**
         * 🔥 CAPTURE OLD DATA
         */
        $oldData = [
            'question' => $faq->question,
            'answer'   => $faq->answer,
        ];

        $faq->update([
            'agency_id' => $request->agency_id,
            'question'  => $request->question,
            'answer'    => $request->answer,
        ]);

        /**
         * 🔥 LOGGING
         */
        $this->logAction(
            auth()->user()->role ?? 'admin',
            auth()->id(),
            $faq->agency_id,
            'update_faq',
            'admin_faq',
            $oldData,
            [
                'question' => $faq->question,
                'answer'   => $faq->answer,
            ],
            'Updated FAQ: ' . $faq->question
        );

        return redirect()->back()->with('success', 'FAQ updated successfully.');
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
            'question' => $faq->question,
            'answer'   => $faq->answer,
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
            'Deleted FAQ: ' . $oldData['question']
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
        $targetUserId = null
    ) {
        try {
            UserLog::create([
                'user_id' => $userId,
                'target_user_id' => $targetUserId,
                'agency_id' => $agencyId,

                'action' => $action,
                'page'   => $page,
                'role'   => $role,

                // 🔐 SECURITY
                'ip_address' => request()->ip(),
                'device'     => substr(request()->userAgent(), 0, 255),

                /**
                 * 🔥 JSON AUDIT TRAIL
                 */
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,

                /**
                 * ⚠️ BACKWARD COMPAT (for your current Blade)
                 */
                'old_value' => $oldValues ? json_encode($oldValues) : null,
                'new_value' => $newValues ? json_encode($newValues) : null,

                /**
                 * 🧠 HUMAN READABLE
                 */
                'description' => $description,
            ]);
        } catch (\Exception $e) {
            \Log::error('FAQ log failed: ' . $e->getMessage());
        }
    }
}