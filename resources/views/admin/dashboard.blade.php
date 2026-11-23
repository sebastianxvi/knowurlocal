@extends('layouts.admin')

@section('title', 'KNOWURLOCAL | ' . ucfirst(auth()->user()->role) . ' Module')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'A clearer view of what is happening across KNOWURLOCAL')

@section('content')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/admin/dashboard.918a184166bd.css') }}">
@endpush

@php
    $agencyCompleteness = $totalAgencies > 0 ? round(($completeAgencies / $totalAgencies) * 100) : 0;
    $faqCompleteness = $totalFaqs > 0 ? round(($completeFaqs / $totalFaqs) * 100) : 0;
    $feedbackTotal = $faqFeedbackHelpful + $faqFeedbackNotHelpful;
    $feedbackPositiveRate = $feedbackTotal > 0 ? round(($faqFeedbackHelpful / $feedbackTotal) * 100) : 0;
@endphp

<div class="dashboard-content" data-collaboration-realtime="true" data-admin-id="{{ auth()->id() }}">

    {{-- =====================================================
         WELCOME / SNAPSHOT
         ===================================================== --}}
    <section class="dashboard-hero">
        <div class="dashboard-hero-copy">
            <span class="eyebrow">ADMIN WORKSPACE</span>
            <h2>Know what needs attention.</h2>
            <p>
                A simple snapshot of your directory, FAQs, citizen requests, and shared team work.
                Use the cards below to jump straight to the area that needs you.
            </p>
        </div>

        <details class="admin-export-menu">
            <summary class="dashboard-export-button" aria-label="Export dashboard report">
                <i class="ph-light ph-export" aria-hidden="true"></i>
                <span>Export</span>
                <i class="ph-light ph-caret-down export-caret" aria-hidden="true"></i>
            </summary>
            <div class="admin-export-dropdown">
                <a href="{{ route('admin.dashboard.export') }}" target="_blank" rel="noopener">
                    <i class="ph-light ph-file-pdf" aria-hidden="true"></i>
                    <span><strong>Dashboard summary</strong><small>Current operational snapshot</small></span>
                </a>
                <a href="{{ route('admin.report.full') }}" target="_blank" rel="noopener">
                    <i class="ph-light ph-files" aria-hidden="true"></i>
                    <span><strong>Full PDF report</strong><small>Dashboard + analytics</small></span>
                </a>
            </div>
        </details>
    </section>

    {{-- =====================================================
         CORE METRICS
         ===================================================== --}}
    <section class="dashboard-kpi-grid" aria-label="System overview">

        <a href="{{ route('admin.support.requests', ['status' => 'pending']) }}"
           class="dashboard-kpi dashboard-kpi-support {{ $pendingInquiries > 0 ? 'is-attention' : '' }}"
           title="Open support requests to see which citizens are still waiting for a response.">
            <span class="dashboard-kpi-icon"><i class="ph-light ph-chat-circle-text" aria-hidden="true"></i></span>
            <span class="dashboard-kpi-copy">
                <span class="dashboard-kpi-label">Citizen requests</span>
                <strong>{{ number_format($totalInquiries) }}</strong>
                <small>
                    @if($pendingInquiries > 0)
                        {{ number_format($pendingInquiries) }} still waiting for a response
                    @else
                        No requests currently waiting for a response
                    @endif
                </small>
            </span>
            <span class="dashboard-kpi-action"><i class="ph-light ph-arrow-up-right" aria-hidden="true"></i></span>
        </a>

        <a href="{{ route('admin.nga') }}"
           class="dashboard-kpi"
           title="Open agency management to review the directory and agency information.">
            <span class="dashboard-kpi-icon is-blue"><i class="ph-light ph-buildings" aria-hidden="true"></i></span>
            <span class="dashboard-kpi-copy">
                <span class="dashboard-kpi-label">Agencies</span>
                <strong>{{ number_format($totalAgencies) }}</strong>
                <small>{{ number_format($agencyCompleteness) }}% of agency profiles are complete</small>
            </span>
            <span class="dashboard-kpi-action"><i class="ph-light ph-arrow-up-right" aria-hidden="true"></i></span>
        </a>

        <a href="{{ route('faqs.index') }}"
           class="dashboard-kpi"
           title="Open FAQs to maintain answers used by citizens and the chatbot.">
            <span class="dashboard-kpi-icon is-violet"><i class="ph-light ph-book-open-text" aria-hidden="true"></i></span>
            <span class="dashboard-kpi-copy">
                <span class="dashboard-kpi-label">FAQs</span>
                <strong>{{ number_format($totalFaqs) }}</strong>
                <small>{{ number_format($faqCompleteness) }}% complete · {{ number_format($incompleteFaqs) }} need attention</small>
            </span>
            <span class="dashboard-kpi-action"><i class="ph-light ph-arrow-up-right" aria-hidden="true"></i></span>
        </a>

        <a href="{{ route('admin.users') }}"
           class="dashboard-kpi"
           title="Open user management to view registered citizen accounts.">
            <span class="dashboard-kpi-icon is-teal"><i class="ph-light ph-users" aria-hidden="true"></i></span>
            <span class="dashboard-kpi-copy">
                <span class="dashboard-kpi-label">Citizen accounts</span>
                <strong>{{ number_format($totalUsers) }}</strong>
                <small>Registered public users</small>
            </span>
            <span class="dashboard-kpi-action"><i class="ph-light ph-arrow-up-right" aria-hidden="true"></i></span>
        </a>

    </section>

    @if($canModerateEvaluations)
        <section class="dashboard-section dashboard-evaluation-moderation" aria-labelledby="dashboard-evaluation-moderation-title">
            <div class="dashboard-section-heading">
                <div>
                    <span class="eyebrow">PUBLIC FEEDBACK</span>
                    <h2 id="dashboard-evaluation-moderation-title">Evaluation moderation</h2>
                    <p title="A summary of reports submitted by the public and the evaluations currently under moderation.">System-wide report activity and the current moderation workload.</p>
                </div>
                <a class="dashboard-section-action"
                   href="{{ route('admin.evaluations.moderation') }}"
                   title="Open the moderation workspace to review reports, hide evaluations, restore them, or dismiss reports."
                   aria-label="Open evaluation moderation">
                    Open moderation <i class="ph-light ph-arrow-up-right" aria-hidden="true"></i>
                </a>
            </div>
            <div class="dashboard-kpi-grid">
                <a href="{{ route('admin.evaluations.moderation', ['status' => 'pending']) }}" class="dashboard-kpi {{ $evaluationPendingReports > 0 ? 'is-attention' : '' }}"
                    title="Reports that have not yet received a moderation decision.">
                    <span class="dashboard-kpi-icon"><i class="ph-light ph-flag" aria-hidden="true"></i></span>
                    <span class="dashboard-kpi-copy">
                        <span class="dashboard-kpi-label">Pending reports</span>
                        <strong>{{ number_format($evaluationPendingReports) }}</strong>
                        <small>Awaiting moderation review</small>
                    </span>
                    <span class="dashboard-kpi-action"><i class="ph-light ph-arrow-up-right" aria-hidden="true"></i></span>
                </a>
                <a href="{{ route('admin.evaluations.moderation', ['status' => 'all']) }}" class="dashboard-kpi"
                    title="The number of distinct public evaluations that have at least one report.">
                    <span class="dashboard-kpi-icon is-blue"><i class="ph-light ph-chat-circle-text" aria-hidden="true"></i></span>
                    <span class="dashboard-kpi-copy">
                        <span class="dashboard-kpi-label">Reported evaluations</span>
                        <strong>{{ number_format($reportedEvaluationCount) }}</strong>
                        <small>Unique evaluations reported</small>
                    </span>
                    <span class="dashboard-kpi-action"><i class="ph-light ph-arrow-up-right" aria-hidden="true"></i></span>
                </a>
                <a href="{{ route('admin.evaluations.moderation', ['status' => 'resolved']) }}" class="dashboard-kpi"
                    title="Reports that moderators have already resolved by dismissing or taking action.">
                    <span class="dashboard-kpi-icon is-teal"><i class="ph-light ph-check-circle" aria-hidden="true"></i></span>
                    <span class="dashboard-kpi-copy">
                        <span class="dashboard-kpi-label">Resolved reports</span>
                        <strong>{{ number_format($evaluationResolvedReports) }}</strong>
                        <small>Dismissed or actioned</small>
                    </span>
                    <span class="dashboard-kpi-action"><i class="ph-light ph-arrow-up-right" aria-hidden="true"></i></span>
                </a>
                <a href="{{ route('admin.evaluations.moderation', ['status' => 'pending']) }}" class="dashboard-kpi"
                    title="Reported evaluations temporarily hidden from public view while they are reviewed.">
                    <span class="dashboard-kpi-icon is-violet"><i class="ph-light ph-eye-slash" aria-hidden="true"></i></span>
                    <span class="dashboard-kpi-copy">
                        <span class="dashboard-kpi-label">Hidden evaluations</span>
                        <strong>{{ number_format($hiddenReportedEvaluations) }}</strong>
                        <small>Temporarily unavailable publicly</small>
                    </span>
                    <span class="dashboard-kpi-action"><i class="ph-light ph-arrow-up-right" aria-hidden="true"></i></span>
                </a>
            </div>
        </section>
    @endif

    {{-- =====================================================
         ATTENTION + HEALTH
         ===================================================== --}}
    <section class="dashboard-section dashboard-priority-section">

        <div class="dashboard-section-heading">
            <div>
                <span class="eyebrow">WHAT NEEDS YOUR ATTENTION?</span>
                <h2>Priority items</h2>
                <p>Start here to review outstanding work across the system.</p>
                <p class="dashboard-priority-note">Counts are attention flags, not unique records; one record may appear in more than one category.</p>
            </div>
            <span class="dashboard-count-badge"
                  title="Counts outstanding work categories. A single record may count more than once if it needs attention in multiple ways.">
                {{ number_format($totalNeedsAttention) }} {{ $totalNeedsAttention === 1 ? 'attention flag' : 'attention flags' }}
            </span>
        </div>

        <div class="dashboard-priority-layout">

            <div class="dashboard-priority-card">
                <div class="dashboard-card-heading">
                    <div class="dashboard-card-heading-icon is-warning">
                        <i class="ph-light ph-warning-circle" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h3>Needs action</h3>
                        <p>These items may be waiting for an administrator.</p>
                    </div>
                </div>

                <div class="dashboard-attention-list">
                    @if($pendingInquiries > 0)
                        <a href="{{ route('admin.support.requests', ['status' => 'pending']) }}" class="dashboard-attention-item is-warning" title="Citizen requests that are still waiting for an administrator to respond.">
                            <span class="dashboard-attention-icon"><i class="ph-light ph-clock" aria-hidden="true"></i></span>
                            <span class="dashboard-attention-copy">
                                <strong>{{ number_format($pendingInquiries) }} {{ $pendingInquiries === 1 ? 'citizen request is' : 'citizen requests are' }} waiting</strong>
                                <small>Someone has submitted a request and has not received an answer yet.</small>
                            </span>
                            <i class="ph-light ph-arrow-right" aria-hidden="true"></i>
                        </a>
                    @endif

                    @if($canModerateEvaluations && $evaluationPendingReports > 0)
                        <a href="{{ route('admin.evaluations.moderation', ['status' => 'pending']) }}" class="dashboard-attention-item is-warning" title="Public evaluations reported by users and waiting for a moderation decision.">
                            <span class="dashboard-attention-icon"><i class="ph-light ph-flag" aria-hidden="true"></i></span>
                            <span class="dashboard-attention-copy">
                                <strong>{{ number_format($evaluationPendingReports) }} {{ $evaluationPendingReports === 1 ? 'evaluation report is' : 'evaluation reports are' }} awaiting review</strong>
                                <small>Reported public evaluations may need a moderation decision.</small>
                            </span>
                            <i class="ph-light ph-arrow-right" aria-hidden="true"></i>
                        </a>
                    @endif

                    @if($incompleteAgencies > 0)
                        <a href="{{ route('admin.nga', ['filter' => 'incomplete']) }}" class="dashboard-attention-item is-info" title="Agency directory profiles missing one or more required information fields.">
                            <span class="dashboard-attention-icon"><i class="ph-light ph-buildings" aria-hidden="true"></i></span>
                            <span class="dashboard-attention-copy">
                                <strong>{{ number_format($incompleteAgencies) }} {{ $incompleteAgencies === 1 ? 'agency profile is' : 'agency profiles are' }} incomplete</strong>
                                <small>Some directory information is missing or not yet filled in.</small>
                            </span>
                            <i class="ph-light ph-arrow-right" aria-hidden="true"></i>
                        </a>
                    @endif

                    @if($faqFeedbackOutstanding > 0)
                        <a href="{{ route('faqs.index', ['feedback' => 'needs_review']) }}" class="dashboard-attention-item is-warning" title="FAQs whose citizen feedback indicates that an answer may need review.">
                            <span class="dashboard-attention-icon"><i class="ph-light ph-thumbs-down" aria-hidden="true"></i></span>
                            <span class="dashboard-attention-copy">
                                <strong>{{ number_format($faqFeedbackOutstanding) }} {{ $faqFeedbackOutstanding === 1 ? 'FAQ needs' : 'FAQs need' }} review</strong>
                                <small>Citizen feedback suggests these answers may need another look.</small>
                            </span>
                            <i class="ph-light ph-arrow-right" aria-hidden="true"></i>
                        </a>
                    @endif

                    @if($incompleteFaqs > 0)
                        <a href="{{ route('faqs.index', ['filter' => 'missing_translation']) }}" class="dashboard-attention-item is-info" title="FAQs missing required answer or Filipino/Taglish content.">
                            <span class="dashboard-attention-icon"><i class="ph-light ph-translate" aria-hidden="true"></i></span>
                            <span class="dashboard-attention-copy">
                                <strong>{{ number_format($incompleteFaqs) }} {{ $incompleteFaqs === 1 ? 'FAQ needs' : 'FAQs need' }} completion</strong>
                                <small>Some required answer or Filipino/Taglish content is missing.</small>
                            </span>
                            <i class="ph-light ph-arrow-right" aria-hidden="true"></i>
                        </a>
                    @endif

                    @if($canManageAdministratorAccounts && $pendingAdminApprovals > 0)
                        <a href="{{ route('admin.admins', ['status' => 'pending']) }}" class="dashboard-attention-item is-warning" title="Central administrator accounts that have requested access and still need approval.">
                            <span class="dashboard-attention-icon"><i class="ph-light ph-user-check" aria-hidden="true"></i></span>
                            <span class="dashboard-attention-copy">
                                <strong>{{ number_format($pendingAdminApprovals) }} {{ $pendingAdminApprovals === 1 ? 'administrator account is' : 'administrator accounts are' }} awaiting approval</strong>
                                <small>Review pending central administrator access requests.</small>
                            </span>
                            <i class="ph-light ph-arrow-right" aria-hidden="true"></i>
                        </a>
                    @endif

                    @if($canManageAdministratorAccounts && $pendingAgencyAdminInvitations > 0)
                        <a href="{{ route('admin.agency-admins', ['status' => 'pending']) }}" class="dashboard-attention-item is-info" title="Invitations sent to Agency Admins that have not yet been accepted.">
                            <span class="dashboard-attention-icon"><i class="ph-light ph-envelope-simple" aria-hidden="true"></i></span>
                            <span class="dashboard-attention-copy">
                                <strong>{{ number_format($pendingAgencyAdminInvitations) }} {{ $pendingAgencyAdminInvitations === 1 ? 'Agency Admin invitation is' : 'Agency Admin invitations are' }} pending</strong>
                                <small>Check invitations that have not yet been accepted.</small>
                            </span>
                            <i class="ph-light ph-arrow-right" aria-hidden="true"></i>
                        </a>
                    @endif

                    @if($totalNeedsAttention === 0)
                        <div class="dashboard-attention-empty">
                            <span class="dashboard-attention-icon is-success"><i class="ph-light ph-check-circle" aria-hidden="true"></i></span>
                            <div>
                                <strong>Nothing urgent right now</strong>
                                <small>No outstanding support, moderation, account, or information-quality issues were detected.</small>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="dashboard-health-card">
                <div class="dashboard-card-heading">
                    <div class="dashboard-card-heading-icon is-blue">
                        <i class="ph-light ph-chart-donut" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h3>Information health</h3>
                        <p title="These measures indicate whether important agency and FAQ information is complete and useful to citizens.">How complete the information is behind the public service.</p>
                    </div>
                </div>

                <div class="dashboard-health-list">
                    <a href="{{ route('admin.nga') }}" class="dashboard-health-row" title="Agency profiles that contain the required directory information.">
                        <span class="dashboard-health-row-top">
                            <span>Agency profiles</span>
                            <strong>{{ number_format($agencyCompleteness) }}%</strong>
                        </span>
                        <span class="dashboard-progress"><span style="width: {{ $agencyCompleteness }}%"></span></span>
                        <small>{{ number_format($completeAgencies) }} complete · {{ number_format($incompleteAgencies) }} need attention</small>
                    </a>

                    <a href="{{ route('faqs.index') }}" class="dashboard-health-row" title="FAQs with the required question, answer, agency, and language information.">
                        <span class="dashboard-health-row-top">
                            <span>FAQ information</span>
                            <strong>{{ number_format($faqCompleteness) }}%</strong>
                        </span>
                        <span class="dashboard-progress is-violet"><span style="width: {{ $faqCompleteness }}%"></span></span>
                        <small>{{ number_format($completeFaqs) }} complete · {{ number_format($incompleteFaqs) }} need attention</small>
                    </a>

                    <a href="{{ route('faqs.index') }}" class="dashboard-health-row" title="How citizens have rated FAQ answers that received feedback.">
                        <span class="dashboard-health-row-top">
                            <span>Positive FAQ feedback</span>
                            <strong>{{ number_format($feedbackPositiveRate) }}%</strong>
                        </span>
                        <span class="dashboard-progress is-teal"><span style="width: {{ $feedbackPositiveRate }}%"></span></span>
                        <small>{{ number_format($faqFeedbackHelpful) }} helpful · {{ number_format($faqFeedbackNotHelpful) }} not helpful</small>
                    </a>
                </div>
            </div>

        </div>
    </section>

    {{-- =====================================================
         TEAM COLLABORATION
         ===================================================== --}}
    <section class="dashboard-section" id="team-collaboration">

        <div class="dashboard-section-heading">
            <div>
                <span class="eyebrow">TEAM WORK</span>
                <h2>What your team is working on</h2>
                <p>Handoffs, reviews, and assistance requests between administrators.</p>
            </div>

            <button type="button" class="dashboard-collaboration-add" id="open-collaboration-modal" title="Create a shared task">
                <i class="ph-light ph-plus" aria-hidden="true"></i>
                <span>New task</span>
            </button>
        </div>

        <div class="dashboard-collaboration-summary">
            <div class="dashboard-collaboration-stat" title="All shared tasks that are still open or in progress.">
                <strong>{{ number_format($collaborationOpenCount) }}</strong>
                <span>open tasks</span>
            </div>
            <div class="dashboard-collaboration-stat is-primary" title="Tasks assigned to your account that need your work or follow-up.">
                <strong>{{ number_format($collaborationYourActionCount) }}</strong>
                <span>assigned to you</span>
            </div>
            <div class="dashboard-collaboration-stat" title="Tasks where another administrator is asking you to review or check their work.">
                <strong>{{ number_format($collaborationReviewCount) }}</strong>
                <span>reviews for you</span>
            </div>
            <div class="dashboard-collaboration-stat" title="Tasks handed to you by another administrator for continued work.">
                <strong>{{ number_format($collaborationHandoffCount) }}</strong>
                <span>handoffs</span>
            </div>
        </div>

        <div class="dashboard-collaboration-card">
            @forelse($collaborationTasks as $task)
                @php
                    $creatorName = trim(($task->creator?->first_name ?? 'Admin') . ' ' . ($task->creator?->last_name ?? ''));
                    $assigneeName = trim(($task->assignee?->first_name ?? 'Admin') . ' ' . ($task->assignee?->last_name ?? ''));
                    $isAssignedToMe = $task->assigned_to_id === auth()->id();
                    $isOverdue = $task->due_at && $task->due_at->isPast();
                @endphp

                <article class="dashboard-collaboration-item {{ $isOverdue ? 'is-overdue' : '' }}" id="collaboration-task-{{ $task->id }}" data-collaboration-task data-task-id="{{ $task->id }}">
                    <div class="dashboard-collaboration-item-icon">
                        <i class="ph-light {{ $task->task_type === 'review' ? 'ph-check-square' : ($task->task_type === 'assist' ? 'ph-handshake' : 'ph-arrow-bend-up-right') }}" aria-hidden="true"></i>
                    </div>

                    <div class="dashboard-collaboration-item-copy">
                        <strong>{{ $task->title }}</strong>
                        <small>
                            {{ $task->task_type_label }} · {{ $creatorName }} → {{ $assigneeName }}
                            @if($task->target_label_snapshot)
                                · {{ $task->target_module_label }}: {{ $task->target_label }}
                            @endif
                        </small>
                    </div>

                    <div class="dashboard-collaboration-item-meta">
                        @if($task->due_at)
                            <span class="dashboard-collaboration-due {{ $isOverdue ? 'is-overdue' : '' }}">
                                <i class="ph-light ph-calendar-blank" aria-hidden="true"></i>
                                {{ $isOverdue ? 'Overdue' : $task->due_at->format('M j') }}
                            </span>
                        @endif

                        <select
                            class="dashboard-collaboration-status"
                            data-collaboration-status
                            data-task-id="{{ $task->id }}"
                            aria-label="Update collaboration task status"
                            {{ $isAssignedToMe || auth()->user()->role === 'superadmin' || $task->created_by_id === auth()->id() ? '' : 'disabled' }}
                        >
                            @if($isAssignedToMe || auth()->user()->role === 'superadmin')
                                @if($task->created_by_id === auth()->id() || auth()->user()->role === 'superadmin')
                                    <option value="cancelled" {{ $task->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                @endif
                                <option value="open" {{ $task->status === 'open' ? 'selected' : '' }}>Open</option>
                                <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>In progress</option>
                                <option value="completed" {{ $task->status === 'completed' ? 'selected' : '' }}>Completed</option>
                            @else
                                <option value="{{ $task->status }}" selected>{{ $task->status_label }}</option>
                                @if($task->created_by_id === auth()->id())
                                    <option value="cancelled">Cancel task</option>
                                @endif
                            @endif
                        </select>
                    </div>
                </article>
            @empty
                <div class="dashboard-collaboration-empty">
                    <i class="ph-light ph-users-three" aria-hidden="true"></i>
                    <div>
                        <strong>No shared work is waiting.</strong>
                        <span>Create a handoff, review, or assistance task when another administrator needs to take over.</span>
                    </div>
                </div>
            @endforelse
        </div>
    </section>

    {{-- Collaboration task modal --}}
    <div class="dashboard-collaboration-modal" id="collaboration-modal" hidden aria-hidden="true">
        <div class="dashboard-collaboration-modal-backdrop" data-close-collaboration-modal></div>
        <section class="dashboard-collaboration-modal-card" role="dialog" aria-modal="true" aria-labelledby="collaboration-modal-title">
            <div class="dashboard-collaboration-modal-header">
                <div>
                    <span class="eyebrow">Team collaboration</span>
                    <h3 id="collaboration-modal-title">Create shared work</h3>
                </div>
                <button type="button" class="dashboard-icon-button" data-close-collaboration-modal title="Close" aria-label="Close">
                    <i class="ph-light ph-x" aria-hidden="true"></i>
                </button>
            </div>

            <form
                id="collaboration-form"
                class="dashboard-collaboration-form"
                data-store-url="{{ route('admin.dashboard.collaboration.store') }}"
                data-targets-url="{{ route('admin.dashboard.collaboration.targets') }}"
                data-status-base-url="{{ url('/admin/dashboard/collaboration') }}"
            >
                @csrf
                <input type="hidden" name="bulk_assign_agency_admins" value="0" data-bulk-agency-flag>

                <div class="dashboard-form-grid">
                    <label>
                        <span>Work type</span>
                        <select name="task_type" required>
                            <option value="handoff">Handoff</option>
                            <option value="review">Review</option>
                            <option value="assist">Ask for assistance</option>
                        </select>
                    </label>

                    <div class="dashboard-recipient-field">
                        <label class="dashboard-field-label" for="collaboration-recipient-search">Assign to</label>
                        <div class="dashboard-recipient-picker" data-recipient-picker>
                            <input type="search" id="collaboration-recipient-search" class="dashboard-recipient-search-input" data-recipient-search
                                placeholder="Search administrator or agency" autocomplete="off" role="combobox" aria-autocomplete="list"
                                aria-expanded="false" aria-controls="collaboration-recipient-options">
                            <div class="dashboard-recipient-dropdown" data-recipient-dropdown hidden>
                                <div class="dashboard-recipient-list" id="collaboration-recipient-options" data-recipient-list role="listbox" aria-label="Administrators">
                                    @php
                                        $collaborationRecipients = \App\Models\User::query()
                                            ->with('agency')
                                            ->whereIn('role', ['admin', 'superadmin', 'agency_admin'])
                                            ->where('status', 'active')
                                            ->where(function ($query) {
                                                $query->whereIn('role', ['admin', 'superadmin'])
                                                    ->orWhere(function ($agencyQuery) {
                                                        $agencyQuery->where('role', 'agency_admin')->whereHas('agency');
                                                    });
                                            })
                                            ->where('id', '<>', auth()->id())
                                            ->orderByRaw("CASE role WHEN 'admin' THEN 1 WHEN 'superadmin' THEN 2 WHEN 'agency_admin' THEN 3 ELSE 4 END")
                                            ->orderBy('first_name')
                                            ->orderBy('last_name')
                                            ->get();
                                        $selectAllRendered = false;
                                    @endphp
                                    @foreach($collaborationRecipients as $admin)
                                        @if($admin->role === 'agency_admin' && !$selectAllRendered)
                                            <button type="button" class="dashboard-recipient-option dashboard-recipient-select-all" data-select-all-option role="option">
                                                <strong>Select all Agency Admins</strong><small>Assign an individual task to every active Agency Admin</small>
                                            </button>
                                            @php $selectAllRendered = true; @endphp
                                        @endif
                                        @php
                                            $recipientName = trim($admin->first_name . ' ' . $admin->last_name) ?: $admin->email;
                                            $agencyName = $admin->agency?->agency_name ?? '';
                                            $agencyAbbreviation = $admin->agency?->agency_abbreviation ?? '';
                                            $recipientSearch = \Illuminate\Support\Str::lower($recipientName . ' ' . $agencyName . ' ' . $agencyAbbreviation . ' ' . $admin->role);
                                        @endphp
                                        <button type="button" class="dashboard-recipient-option" data-recipient-option data-recipient-id="{{ $admin->id }}"
                                            data-role="{{ $admin->role }}" data-search="{{ $recipientSearch }}" role="option">
                                            <strong>{{ $recipientName }}</strong>
                                            <small>{{ $admin->role === 'agency_admin' ? trim(($agencyAbbreviation ? $agencyAbbreviation . ' · ' : '') . ($agencyName ?: 'Agency Admin')) : ucfirst($admin->role) }}</small>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            <div data-recipient-values></div>
                        </div>
                    </div>
                </div>

                <p class="dashboard-collaboration-guidance" data-collaboration-guidance role="note">Choose work that matches the recipient’s responsibilities and link the relevant record when applicable.</p>

                <label>
                    <span>Related record <em>required</em></span>
                    <div class="dashboard-target-row" data-target-picker>
                        <select name="target_type" id="collaboration-target-type" required>
                            <option value="">Choose record type</option>
                            <option value="agency">Agency</option>
                            <option value="faq">FAQ</option>
                            <option value="category">Category</option>
                            <option value="support_request">Support request</option>
                            <option value="user">Account</option>
                        </select>
                        <div class="dashboard-target-search-wrap">
                            <input type="search" id="collaboration-target-search" data-target-search placeholder="Choose or search a record" autocomplete="off"
                                role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="collaboration-target-results" disabled>
                            <div class="dashboard-target-results" id="collaboration-target-results" data-target-results role="listbox" aria-label="Matching records" hidden></div>
                            <input type="hidden" name="target_id" id="collaboration-target-id">
                        </div>
                    </div>
                </label>

                <label>
                    <span>Title</span>
                    <input type="text" name="title" maxlength="140" placeholder="e.g. Review the new FAQ draft" required>
                </label>

                <label>
                    <span>Instructions <em>required</em></span>
                    <textarea name="description" rows="3" maxlength="1000" required placeholder="Explain the specific action, expected result, and relevant context..."></textarea>
                </label>

                <label>
                    <span>Due date <em>optional</em></span>
                    <input type="datetime-local" name="due_at">
                </label>

                <div class="dashboard-collaboration-form-error" id="collaboration-form-error" role="alert" hidden></div>

                <div class="dashboard-collaboration-modal-actions">
                    <button type="button" class="dashboard-secondary-button" data-close-collaboration-modal>Cancel</button>
                    <button type="submit" class="dashboard-primary-button" id="collaboration-submit">
                        <i class="ph-light ph-paper-plane-tilt" aria-hidden="true"></i>
                        Create task
                    </button>
                </div>
            </form>
        </section>
    </div>

    {{-- =====================================================
         ANALYTICS
         ===================================================== --}}
    <section class="dashboard-section dashboard-analytics-section">
        <a href="{{ route('admin.analytics') }}" class="dashboard-analytics-link">
            <span class="dashboard-analytics-icon">
                <i class="ph-light ph-chart-line-up" aria-hidden="true"></i>
            </span>
            <span class="dashboard-analytics-copy">
                <span class="eyebrow">GO DEEPER</span>
                <strong>Explore detailed analytics</strong>
                <small>See trends, response performance, chatbot coverage, and where the knowledge base may need improvement.</small>
            </span>
            <span class="dashboard-analytics-action">
                Open analytics <i class="ph-light ph-arrow-right" aria-hidden="true"></i>
            </span>
        </a>
    </section>

</div>

@push('scripts')
<script src="{{ asset('jsfiles/admin/dashboard.ff02fcccceab.js') }}"></script>
@endpush

@endsection
