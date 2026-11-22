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
                KNOWURLOCAL <span aria-hidden="true">/</span> {{ auth()->user()->role === 'agency_admin' ? 'Agency Workspace' : 'Admin Workspace' }}
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
                    @if(($adminNotificationSupportCount + $adminNotificationCollaborationCount + $adminNotificationInterventionCount) < 1) hidden @endif
                    aria-live="polite"
                >{{ min(99, $adminNotificationSupportCount + $adminNotificationCollaborationCount + $adminNotificationInterventionCount) }}</span>
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

                                <div class="admin-notification-tabs" role="tablist" aria-label="Notification categories">
                    <button type="button" class="admin-notification-tab is-active" role="tab"
                        id="admin-notification-tab-support" aria-controls="admin-notification-pane-support"
                        aria-selected="true" data-notification-tab="support">
                        <span>Support</span><span class="admin-notification-tab-count" data-support-count>{{ $adminNotificationSupportCount }}</span>
                    </button>
                    <button type="button" class="admin-notification-tab" role="tab"
                        id="admin-notification-tab-collaboration" aria-controls="admin-notification-pane-collaboration"
                        aria-selected="false" tabindex="-1" data-notification-tab="collaboration">
                        <span>Collaboration</span><span class="admin-notification-tab-count" data-collaboration-count>{{ $adminNotificationCollaborationCount }}</span>
                    </button>
                    @if(auth()->user()->role === 'agency_admin')
                        <button type="button" class="admin-notification-tab" role="tab"
                            id="admin-notification-tab-intervention" aria-controls="admin-notification-pane-intervention"
                            aria-selected="false" tabindex="-1" data-notification-tab="intervention">
                            <span>Updates</span><span class="admin-notification-tab-count" data-intervention-count>{{ $adminNotificationInterventionCount }}</span>
                        </button>
                    @endif
                </div>

                <div class="admin-notification-list" data-notification-list>
                    <section class="admin-notification-pane is-active" id="admin-notification-pane-support"
                        role="tabpanel" aria-labelledby="admin-notification-tab-support" data-notification-pane="support">
                        <div class="admin-notification-section-label">Support requests</div>
                        
                        @foreach($adminNotificationSupportRequests as $request)
                            <a
                                class="admin-notification-item"
                                href="{{ auth()->user()->role === 'agency_admin'
                                    ? route('agency-admin.tickets', ['status' => $request->status])
                                    : route('admin.support.requests', ['status' => 'active', 'status_filter' => $request->status]) }}"
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

                        @if($adminNotificationSupportRequests->isEmpty())
                            <div class="admin-notification-empty" data-empty-kind="support">
                                <i class="ph-light ph-check-circle" aria-hidden="true"></i>
                                <strong>No support notifications</strong>
                                <span>You're all caught up with support requests.</span>
                            </div>
                        @endif
                    </section>

                    <section class="admin-notification-pane" id="admin-notification-pane-collaboration"
                        role="tabpanel" aria-labelledby="admin-notification-tab-collaboration" data-notification-pane="collaboration" hidden>
                        <div class="admin-notification-section-label">Collaboration</div>
                        
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
                                href="{{ auth()->user()->role === 'agency_admin' ? route('agency-admin.dashboard') : route('admin.dashboard') }}#collaboration-task-{{ $task->id }}"
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

                        @if($adminNotificationCollaborationTasks->isEmpty())
                            <div class="admin-notification-empty" data-empty-kind="collaboration">
                                <i class="ph-light ph-check-circle" aria-hidden="true"></i>
                                <strong>No collaboration notifications</strong>
                                <span>You have no open collaboration tasks.</span>
                            </div>
                        @endif
                    </section>
                    @if(auth()->user()->role === 'agency_admin')
                    <section class="admin-notification-pane" id="admin-notification-pane-intervention"
                        role="tabpanel" aria-labelledby="admin-notification-tab-intervention" data-notification-pane="intervention" hidden>
                        <div class="admin-notification-section-label">Agency and FAQ updates</div>
                        
                        @foreach($adminNotificationInterventions as $intervention)
                            <a
                                class="admin-notification-item"
                                href="{{ route('agency-admin.notifications.open', $intervention->id) }}"
                                data-notification-kind="intervention"
                                data-notification-id="{{ $intervention->id }}"
                            >
                                <span class="admin-notification-item-icon is-collaboration"><i class="ph-light ph-shield-check" aria-hidden="true"></i></span>
                                <span class="admin-notification-item-copy">
                                    <strong>{{ $intervention->title }}</strong>
                                    <small>{{ \Illuminate\Support\Str::limit((string) ($intervention->data['reason'] ?? $intervention->message), 100) }}</small>
                                </span>
                                <i class="ph-light ph-arrow-up-right admin-notification-item-arrow" aria-hidden="true"></i>
                            </a>
                        @endforeach

                        @if(!isset($adminNotificationInterventions) || $adminNotificationInterventions->isEmpty())
                            <div class="admin-notification-empty" data-empty-kind="intervention">
                                <i class="ph-light ph-check-circle" aria-hidden="true"></i>
                                <strong>No agency updates</strong>
                                <span>There are no pending agency or FAQ interventions.</span>
                            </div>
                        @endif
                    </section>
                    @endif
                </div>

                <div class="admin-notification-panel-footer">
                    @if(auth()->user()->role === 'agency_admin')
                        <a data-notification-footer-link data-footer-kind="support" href="{{ route('agency-admin.tickets') }}">View support tickets <i class="ph-light ph-arrow-right" aria-hidden="true"></i></a>
                        <a data-notification-footer-link data-footer-kind="collaboration" href="{{ route('agency-admin.dashboard') }}#agency-collaboration" hidden>View collaboration <i class="ph-light ph-arrow-right" aria-hidden="true"></i></a>
                        <a data-notification-footer-link data-footer-kind="intervention" href="/agency-admin/agency" hidden>View agency updates <i class="ph-light ph-arrow-right" aria-hidden="true"></i></a>
                    @else
                        <a data-notification-footer-link data-footer-kind="support" href="{{ route('admin.support.requests') }}">View support requests <i class="ph-light ph-arrow-right" aria-hidden="true"></i></a>
                        <a data-notification-footer-link data-footer-kind="collaboration" href="{{ route('admin.dashboard') }}#team-collaboration" hidden>View collaboration <i class="ph-light ph-arrow-right" aria-hidden="true"></i></a>
                    @endif
                </div>
            </section>
        </div>
    </div>
</header>
