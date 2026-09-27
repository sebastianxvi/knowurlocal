<header class="topbar">
    <div class="header-left">
        <button
            type="button"
            class="admin-mobile-menu"
            data-admin-shell-toggle
            aria-label="Open navigation"
            aria-expanded="false"
        >
            <i class="ph-light ph-list" aria-hidden="true"></i>
        </button>

        <div class="header-heading">
            <div class="header-context">
                KNOWURLOCAL <span aria-hidden="true">/</span> Admin Workspace
            </div>

            <h1 class="page-title">@yield('page-title')</h1>

            @hasSection('page-subtitle')
                <p class="page-subtitle">@yield('page-subtitle')</p>
            @endif
        </div>
    </div>

    <div class="header-right">
        <div class="admin-notification-center" data-admin-notifications>
            <button
                type="button"
                class="header-quick-action admin-notification-trigger"
                id="admin-notification-trigger"
                aria-label="Open notifications"
                aria-haspopup="dialog"
                aria-expanded="false"
                title="Notifications"
            >
                <i class="ph-light ph-bell" aria-hidden="true"></i>
                <span
                    class="header-quick-badge admin-notification-badge"
                    data-notification-count
                    @if(($adminNotificationSupportCount + $adminNotificationCollaborationCount) < 1) hidden @endif
                    aria-live="polite"
                >{{ min(99, $adminNotificationSupportCount + $adminNotificationCollaborationCount) }}</span>
            </button>

            <section
                class="admin-notification-panel"
                data-notification-panel
                hidden
                aria-labelledby="admin-notification-title"
            >
                <div class="admin-notification-panel-header">
                    <div>
                        <span class="admin-notification-eyebrow">Workspace</span>
                        <h2 id="admin-notification-title">Notifications</h2>
                    </div>
                </div>

                <div class="admin-notification-summary">
                    <span><strong data-support-count>{{ $adminNotificationSupportCount }}</strong> support</span>
                    <span><strong data-collaboration-count>{{ $adminNotificationCollaborationCount }}</strong> collaboration</span>
                </div>

                <div class="admin-notification-list" data-notification-list>
                    @if($adminNotificationSupportRequests->isNotEmpty())
                        <div class="admin-notification-section-label" data-section="support">Support requests</div>
                        @foreach($adminNotificationSupportRequests as $request)
                            <a
                                class="admin-notification-item"
                                href="{{ route('admin.support.requests', [
                                    'status' => 'active',
                                    'status_filter' => $request->status,
                                ]) }}"
                                data-notification-kind="support"
                                data-notification-id="{{ $request->id }}"
                                data-notification-status="{{ $request->status }}"
                            >
                                <span class="admin-notification-item-icon is-support"><i class="ph-light ph-chat-circle-text" aria-hidden="true"></i></span>
                                <span class="admin-notification-item-copy">
                                    <strong>{{ \Illuminate\Support\Str::limit($request->question, 72) }}</strong>
                                    <small>
                                        {{ $request->user?->first_name ? trim($request->user->first_name . ' ' . $request->user->last_name) : 'Citizen' }}
                                        @if($request->agency) · {{ \Illuminate\Support\Str::limit($request->agency->agency_name, 34) }} @endif
                                    </small>
                                </span>
                                <i class="ph-light ph-arrow-up-right admin-notification-item-arrow" aria-hidden="true"></i>
                            </a>
                        @endforeach
                    @endif

                    @if($adminNotificationCollaborationTasks->isNotEmpty())
                        <div class="admin-notification-section-label" data-section="collaboration">Collaboration</div>
                        @foreach($adminNotificationCollaborationTasks as $task)
                            @php
                                $taskForMe = $task->assigned_to_id === auth()->id();
                                $taskIcon = match ($task->task_type) {
                                    'review' => 'ph-check-square',
                                    'assist' => 'ph-handshake',
                                    default => 'ph-arrow-bend-up-right',
                                };
                            @endphp
                            <a
                                class="admin-notification-item"
                                href="{{ route('admin.dashboard') }}#collaboration-task-{{ $task->id }}"
                                data-notification-kind="collaboration"
                                data-notification-id="{{ $task->id }}"
                            >
                                <span class="admin-notification-item-icon is-collaboration"><i class="ph-light {{ $taskIcon }}" aria-hidden="true"></i></span>
                                <span class="admin-notification-item-copy">
                                    <strong>{{ \Illuminate\Support\Str::limit($task->title, 72) }}</strong>
                                    <small>{{ $taskForMe ? 'Assigned to you' : 'You assigned it' }} · {{ $task->status_label }}</small>
                                </span>
                                <i class="ph-light ph-arrow-up-right admin-notification-item-arrow" aria-hidden="true"></i>
                            </a>
                        @endforeach
                    @endif

                    @if($adminNotificationSupportRequests->isEmpty() && $adminNotificationCollaborationTasks->isEmpty())
                        <div class="admin-notification-empty">
                            <i class="ph-light ph-check-circle" aria-hidden="true"></i>
                            <strong>You're all caught up.</strong>
                            <span>No pending support requests or collaboration work needs your attention.</span>
                        </div>
                    @endif
                </div>

                <div class="admin-notification-panel-footer">
                    <a href="{{ route('admin.support.requests') }}">Support requests <i class="ph-light ph-arrow-right" aria-hidden="true"></i></a>
                    <a href="{{ route('admin.dashboard') }}#team-collaboration">Collaboration <i class="ph-light ph-arrow-right" aria-hidden="true"></i></a>
                </div>
            </section>
        </div>
    </div>
</header>
