<?php

namespace App\Http\Controllers;


use App\Models\SupportRequest;
use Illuminate\Http\Request;
use App\Models\Faq;

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

        $requests = $query->paginate(10);

        return view('admin.support_requests', compact('requests'));
    }

    /**
     * 📩 ADMIN REPLY
     */
    public function reply(Request $request)
    {
        $validated = $request->validate([
            'request_id' => 'required|exists:support_requests,id',
            'reply' => 'required|string|max:1000'
        ]);

        $support = SupportRequest::findOrFail($validated['request_id']);

        $support->update([
            'answer' => $validated['reply'],
            'status' => 'answered'
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
        'status' => 'answered'
    ]);

    return back()->with('success', 'Answer updated successfully.');
}

public function destroy($id)
{
    // 🔒 Defense-in-depth (never rely on middleware alone)
    if (!auth()->user() || auth()->user()->role !== 'superadmin') {
        abort(403, 'Unauthorized action.');
    }

    $support = SupportRequest::findOrFail($id);

    $support->delete();

    return back()->with('success', 'Request deleted successfully.');
}

public function toFaq($id)
{
    if (!auth()->user() || auth()->user()->role !== 'superadmin') {
        abort(403, 'Unauthorized action.');
    }

    $support = SupportRequest::findOrFail($id);

    if (!$support->answer) {
        return back()->with('error', 'Cannot add unanswered request to FAQ.');
    }

    if (!$support->agency_id) {
        return back()->with('error', 'Cannot add to FAQ without agency.');
    }

    Faq::updateOrCreate(
        ['question' => $support->question],
        [
            'answer' => $support->answer,
            'agency_id' => $support->agency_id
        ]
    );

    return back()->with('success', 'FAQ saved successfully.');
}
}