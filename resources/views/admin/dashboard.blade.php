@extends('layouts.admin')

@section('title', 'KNOWURLOCAL | ' . ucfirst(auth()->user()->role) . ' Module')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'A clearer view of what is happening across KNOWURLOCAL')

@section('content')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/admin/dashboard.css') }}">
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

    {{-- =====================================================
         ATTENTION + HEALTH
         ===================================================== --}}
    <section class="dashboard-section dashboard-priority-section">

        <div class="dashboard-section-heading">
            <div>
                <span class="eyebrow">WHAT NEEDS YOUR ATTENTION?</span>
                <h2>Priority items</h2>
                <p>Start here when you want to know what may need action.</p>
            </div>
            <span class="dashboard-count-badge">
                {{ number_format($totalNeedsAttention) }} {{ $totalNeedsAttention === 1 ? 'item' : 'items' }}
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
                        <a href="{{ route('admin.support.requests', ['status' => 'pending']) }}" class="dashboard-attention-item is-warning">
                            <span class="dashboard-attention-icon"><i class="ph-light ph-clock" aria-hidden="true"></i></span>
                            <span class="dashboard-attention-copy">
                                <strong>{{ number_format($pendingInquiries) }} {{ $pendingInquiries === 1 ? 'citizen request is' : 'citizen requests are' }} waiting</strong>
                                <small>Someone has submitted a request and has not received an answer yet.</small>
                            </span>
                            <i class="ph-light ph-arrow-right" aria-hidden="true"></i>
                        </a>
                    @endif

                    @if($incompleteAgencies > 0)
                        <a href="{{ route('admin.nga', ['filter' => 'incomplete']) }}" class="dashboard-attention-item is-info">
                            <span class="dashboard-attention-icon"><i class="ph-light ph-buildings" aria-hidden="true"></i></span>
                            <span class="dashboard-attention-copy">
                                <strong>{{ number_format($incompleteAgencies) }} {{ $incompleteAgencies === 1 ? 'agency profile is' : 'agency profiles are' }} incomplete</strong>
                                <small>Some directory information is missing or not yet filled in.</small>
                            </span>
                            <i class="ph-light ph-arrow-right" aria-hidden="true"></i>
                        </a>
                    @endif

                    @if($faqFeedbackOutstanding > 0)
                        <a href="{{ route('faqs.index', ['feedback' => 'needs_review']) }}" class="dashboard-attention-item is-warning">
                            <span class="dashboard-attention-icon"><i class="ph-light ph-thumbs-down" aria-hidden="true"></i></span>
                            <span class="dashboard-attention-copy">
                                <strong>{{ number_format($faqFeedbackOutstanding) }} {{ $faqFeedbackOutstanding === 1 ? 'FAQ needs' : 'FAQs need' }} review</strong>
                                <small>Citizen feedback suggests these answers may need another look.</small>
                            </span>
                            <i class="ph-light ph-arrow-right" aria-hidden="true"></i>
                        </a>
                    @endif

                    @if($incompleteFaqs > 0)
                        <a href="{{ route('faqs.index', ['filter' => 'missing_translation']) }}" class="dashboard-attention-item is-info">
                            <span class="dashboard-attention-icon"><i class="ph-light ph-translate" aria-hidden="true"></i></span>
                            <span class="dashboard-attention-copy">
                                <strong>{{ number_format($incompleteFaqs) }} {{ $incompleteFaqs === 1 ? 'FAQ needs' : 'FAQs need' }} completion</strong>
                                <small>Some required answer or Filipino/Taglish content is missing.</small>
                            </span>
                            <i class="ph-light ph-arrow-right" aria-hidden="true"></i>
                        </a>
                    @endif

                    @if($totalNeedsAttention === 0)
                        <div class="dashboard-attention-empty">
                            <span class="dashboard-attention-icon is-success"><i class="ph-light ph-check-circle" aria-hidden="true"></i></span>
                            <div>
                                <strong>Nothing urgent right now</strong>
                                <small>No immediate support or data-quality issues were detected.</small>
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
                        <p>How complete the information is behind the public service.</p>
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
            <div class="dashboard-collaboration-stat">
                <strong>{{ number_format($collaborationOpenCount) }}</strong>
                <span>open tasks</span>
            </div>
            <div class="dashboard-collaboration-stat is-primary">
                <strong>{{ number_format($collaborationYourActionCount) }}</strong>
                <span>assigned to you</span>
            </div>
            <div class="dashboard-collaboration-stat">
                <strong>{{ number_format($collaborationReviewCount) }}</strong>
                <span>reviews for you</span>
            </div>
            <div class="dashboard-collaboration-stat">
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
                            @if($task->created_by_id === auth()->id() || auth()->user()->role === 'superadmin')
                                <option value="cancelled" {{ $task->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            @endif
                            <option value="open" {{ $task->status === 'open' ? 'selected' : '' }}>Open</option>
                            <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>In progress</option>
                            <option value="completed" {{ $task->status === 'completed' ? 'selected' : '' }}>Completed</option>
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

                <div class="dashboard-form-grid">
                    <label>
                        <span>Work type</span>
                        <select name="task_type" required>
                            <option value="handoff">Handoff</option>
                            <option value="review">Review</option>
                            <option value="assist">Ask for assistance</option>
                        </select>
                    </label>

                    <label>
                        <span>Assign to</span>
                        <select name="assigned_to_id" required>
                            <option value="">Choose administrator</option>
                            @foreach(\App\Models\User::query()->whereIn('role', ['admin', 'superadmin'])->where('status', 'active')->where('id', '<>', auth()->id())->orderBy('first_name')->orderBy('last_name')->get() as $admin)
                                <option value="{{ $admin->id }}">{{ trim($admin->first_name . ' ' . $admin->last_name) ?: $admin->email }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <label>
                    <span>Related data</span>
                    <div class="dashboard-target-row">
                        <select name="target_type" id="collaboration-target-type">
                            <option value="">No specific record</option>
                            <option value="agency">Agency</option>
                            <option value="faq">FAQ</option>
                            <option value="category">Category</option>
                            <option value="support_request">Support request</option>
                            <option value="user">Account</option>
                        </select>
                        <select name="target_id" id="collaboration-target-id" disabled>
                            <option value="">Choose a record</option>
                        </select>
                    </div>
                </label>

                <label>
                    <span>Title</span>
                    <input type="text" name="title" maxlength="140" placeholder="e.g. Review the new FAQ draft" required>
                </label>

                <label>
                    <span>Note <em>optional</em></span>
                    <textarea name="description" rows="3" maxlength="1000" placeholder="Add context for the administrator taking this over..."></textarea>
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
<script src="{{ asset('jsfiles/admin/dashboard.js') }}"></script>
@endpush

@endsection
