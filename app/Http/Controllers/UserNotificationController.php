<?php

namespace App\Http\Controllers;

use App\Models\SupportRequest;
use App\Models\UserNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        $unreadNotificationCount = UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->count();

        return view('public_user.notifications.index', compact('notifications', 'unreadNotificationCount'));
    }

    public function open(Request $request, int $notificationId): RedirectResponse
    {
        $notification = UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($notificationId);

        if ($notification->read_at === null) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        $ticketId = (int) ($notification->support_request_id ?? 0);
        if ($ticketId > 0) {
            $ticket = SupportRequest::withTrashed()
                ->where('user_id', $request->user()->id)
                ->whereNull('user_deleted_at')
                ->find($ticketId);

            if ($ticket) {
                return redirect()->route('user.inquiries', ['ticket' => $ticket->id]);
            }
        }

        return redirect()->route('user.inquiries')->with('info', 'That ticket is no longer available in your inquiry list.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        UserNotification::query()
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);

        return redirect()->route('user.notifications.index')->with('success', 'All notifications marked as read.');
    }
}
