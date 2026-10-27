<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">
    <meta name="broadcast-auth-endpoint" content="{{ url('/broadcasting/auth') }}">
    <meta name="realtime-driver" content="{{ config('broadcasting.default') }}">
    <meta name="reverb-app-key" content="{{ config('broadcasting.connections.reverb.key') }}">
    <meta name="reverb-host" content="{{ config('broadcasting.connections.reverb.options.host') }}">
    <meta name="reverb-port" content="{{ config('broadcasting.connections.reverb.options.port') }}">
    <meta name="reverb-scheme" content="{{ config('broadcasting.connections.reverb.options.scheme') }}">
    <title>KNOWURLOCAL | Notifications</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/knowurlocal-kyl-logo.svg') }}?v=3">
    <script src="https://unpkg.com/phosphor-icons@1.4.2"></script>
    <link rel="stylesheet" href="{{ asset('cssfiles/public_user/navbar.css') }}?v={{ filemtime(public_path('cssfiles/public_user/navbar.css')) }}">
    <link rel="stylesheet" href="{{ asset('cssfiles/public_user/notifications.css') }}?v={{ filemtime(public_path('cssfiles/public_user/notifications.css')) }}">
    @vite('resources/js/echo.js')
    <script src="{{ asset('jsfiles/public_user/navbar.js') }}" defer></script>
    <script type="module" src="{{ asset('jsfiles/public_user/navbar-realtime.js') }}?v={{ filemtime(public_path('jsfiles/public_user/navbar-realtime.js')) }}"></script>
    <script type="module" src="{{ asset('jsfiles/public_user/notifications.js') }}?v={{ filemtime(public_path('jsfiles/public_user/notifications.js')) }}"></script>
</head>
<body class="user-notifications-page">
    <x-public.navbar :hideSearch="true" />
    <main class="notifications-shell">
        <header class="notifications-heading">
            <div>
                <span class="notifications-eyebrow">YOUR ACCOUNT</span>
                <h1>Notifications</h1>
                <p>Updates about your support tickets and official agency responses.</p>
            </div>
            <form method="POST" action="{{ route('user.notifications.read-all') }}">
                @csrf
                <button
                    class="notifications-read-all"
                    type="submit"
                    data-notifications-read-all
                    @if($unreadNotificationCount <= 0) hidden @endif
                >
                    <i class="ph-light ph-checks" aria-hidden="true"></i>
                    Mark all as read
                </button>
            </form>
        </header>

        <section class="notifications-card" aria-label="Your notifications">
            <div class="notifications-card-header">
                <span>Recent activity</span>
                <span class="notifications-unread-summary"><span data-notifications-unread-count>{{ $unreadNotificationCount }}</span> unread</span>
            </div>
            <div class="notifications-list" data-notifications-list>
                @forelse($notifications as $notification)
                    <a class="user-notification-item {{ $notification->read_at ? '' : 'is-unread' }}"
                       href="{{ route('user.notifications.open', $notification->id) }}"
                       data-user-notification-item
                       data-notification-id="{{ $notification->id }}"
                       data-read="{{ $notification->read_at ? '1' : '0' }}">
                        <span class="user-notification-icon {{ $notification->type === 'ticket_trashed' ? 'is-trash' : 'is-response' }}">
                            <i class="ph-light {{ $notification->type === 'ticket_trashed' ? 'ph-trash' : 'ph-chat-circle-text' }}" aria-hidden="true"></i>
                        </span>
                        <span class="user-notification-copy">
                            <strong>{{ $notification->title }}</strong>
                            <span>{{ $notification->message }}</span>
                            <small>{{ $notification->created_at?->timezone(config('app.timezone'))->format('M j, Y · g:i A') }}</small>
                        </span>
                        @if(!$notification->read_at)
                            <span class="user-notification-unread-dot" aria-label="Unread"></span>
                        @endif
                        <i class="ph-light ph-arrow-up-right user-notification-arrow" aria-hidden="true"></i>
                    </a>
                @empty
                    <div class="notifications-empty" data-notifications-empty>
                        <span class="notifications-empty-icon"><i class="ph-light ph-bell-slash" aria-hidden="true"></i></span>
                        <h2>You're all caught up</h2>
                        <p>New ticket updates and official responses will appear here.</p>
                    </div>
                @endforelse
            </div>
            @if($notifications->hasPages())
                <div class="notifications-pagination">{{ $notifications->links() }}</div>
            @endif
        </section>
    </main>
</body>
</html>
