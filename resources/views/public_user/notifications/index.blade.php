<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f8faf8">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa-icon-192.png') }}">
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
    <link rel="stylesheet" href="{{ asset('cssfiles/components/modal.css') }}?v={{ filemtime(public_path('cssfiles/components/modal.css')) }}">
    <link rel="stylesheet" href="{{ asset('cssfiles/public_user/notifications.css') }}?v={{ filemtime(public_path('cssfiles/public_user/notifications.css')) }}">
    @vite('resources/js/echo.js')
    <script src="{{ asset('jsfiles/public_user/navbar.js') }}" defer></script>
    <script type="module" src="{{ asset('jsfiles/public_user/navbar-realtime.js') }}?v={{ md5_file(public_path('jsfiles/public_user/navbar-realtime.js')) }}"></script>
    <script type="module" src="{{ asset('jsfiles/public_user/notifications.js') }}?v={{ filemtime(public_path('jsfiles/public_user/notifications.js')) }}"></script>
    <link rel="stylesheet" href="{{ asset('cssfiles/public_user/typography.css') }}?v=20261104-field-focus-reset&build={{ file_exists(public_path('cssfiles/public_user/typography.css')) ? filemtime(public_path('cssfiles/public_user/typography.css')) : time() }}">
</head>
<body class="user-notifications-page">
    <x-public.navbar :hideSearch="true" />
    <main class="notifications-shell">
        <header class="notifications-heading">
            <div class="notifications-heading-copy">
                <h1>Notifications</h1>
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
                        @php
                            $isEvaluationNotice = str_starts_with($notification->type, 'evaluation_')
                                && $notification->type !== 'evaluation_response';
                            $notificationIcon = match ($notification->type) {
                                'ticket_trashed', 'ticket_permanently_deleted', 'evaluation_removed' => 'ph-trash',
                                'ticket_restored' => 'ph-arrow-counter-clockwise',
                                'evaluation_hidden' => 'ph-eye-slash',
                                'evaluation_restored' => 'ph-eye',
                                'evaluation_response' => 'ph-chat-circle-text',
                                default => 'ph-chat-circle-text',
                            };
                            $notificationTone = in_array($notification->type, ['ticket_trashed', 'ticket_permanently_deleted', 'evaluation_removed'], true)
                                ? 'is-trash'
                                : (($isEvaluationNotice || $notification->type === 'evaluation_response') ? 'is-evaluation' : 'is-response');
                        @endphp
                        <span class="user-notification-icon {{ $notificationTone }}">
                            <i class="ph-light {{ $notificationIcon }}" aria-hidden="true"></i>
                        </span>
                        <span class="user-notification-copy">
                            <strong>{{ $notification->title }}</strong>
                            <span>{{ $notification->message }}</span>
                            <small>{{ $notification->created_at?->timezone(config('app.timezone'))->format('M j · g:i A') }}</small>
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
                        <p>New ticket updates, official responses, and evaluation notices will appear here.</p>
                    </div>
                @endforelse
            </div>
            @if($notifications->hasPages())
                <nav class="notifications-pagination" aria-label="Notification pages">
                    <p class="notifications-pagination-summary">
                        Showing
                        <strong>{{ $notifications->firstItem() }}</strong>
                        to
                        <strong>{{ $notifications->lastItem() }}</strong>
                        of
                        <strong>{{ $notifications->total() }}</strong>
                        results
                    </p>

                    <div class="notifications-pagination-controls">
                        @if(!$notifications->onFirstPage())
                            <a class="notifications-page-link notifications-page-link-direction"
                               href="{{ $notifications->previousPageUrl() }}"
                               rel="prev"
                               aria-label="Go to previous page">
                                <i class="ph-light ph-caret-left" aria-hidden="true"></i>
                                <span>Previous</span>
                            </a>
                        @else
                            <span class="notifications-page-link notifications-page-link-direction is-disabled" aria-disabled="true">
                                <i class="ph-light ph-caret-left" aria-hidden="true"></i>
                                <span>Previous</span>
                            </span>
                        @endif

                        <div class="notifications-page-numbers" aria-label="Page numbers">
                            @if($notifications->currentPage() > 2)
                                <a class="notifications-page-link" href="{{ $notifications->url(1) }}">1</a>
                                @if($notifications->currentPage() > 3)
                                    <span class="notifications-page-ellipsis" aria-hidden="true">…</span>
                                @endif
                            @endif

                            @for($page = max(1, $notifications->currentPage() - 1); $page <= min($notifications->lastPage(), $notifications->currentPage() + 1); $page++)
                                @if($page === $notifications->currentPage())
                                    <span class="notifications-page-link is-current" aria-current="page">{{ $page }}</span>
                                @else
                                    <a class="notifications-page-link" href="{{ $notifications->url($page) }}">{{ $page }}</a>
                                @endif
                            @endfor

                            @if($notifications->currentPage() < $notifications->lastPage() - 1)
                                @if($notifications->currentPage() < $notifications->lastPage() - 2)
                                    <span class="notifications-page-ellipsis" aria-hidden="true">…</span>
                                @endif
                                <a class="notifications-page-link" href="{{ $notifications->url($notifications->lastPage()) }}">{{ $notifications->lastPage() }}</a>
                            @endif
                        </div>

                        @if($notifications->hasMorePages())
                            <a class="notifications-page-link notifications-page-link-direction"
                               href="{{ $notifications->nextPageUrl() }}"
                               rel="next"
                               aria-label="Go to next page">
                                <span>Next</span>
                                <i class="ph-light ph-caret-right" aria-hidden="true"></i>
                            </a>
                        @else
                            <span class="notifications-page-link notifications-page-link-direction is-disabled" aria-disabled="true">
                                <span>Next</span>
                                <i class="ph-light ph-caret-right" aria-hidden="true"></i>
                            </span>
                        @endif
                    </div>
                </nav>
            @endif
        </section>
    </main>
</body>
</html>
