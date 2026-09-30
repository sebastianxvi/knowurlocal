@extends('layouts.admin')

@section('title', 'KNOWURLOCAL | ' . ucfirst(auth()->user()->role) . ' Module')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'A brief operational report for KNOWURLOCAL')

@section('content')

@push('styles')
<link
    rel="stylesheet"
    href="{{ asset('cssfiles/admin/dashboard.css') }}"
>
@endpush

<div class="dashboard-content" data-collaboration-realtime="true" data-admin-id="{{ auth()->id() }}">

    {{-- =====================================================
         REPORT HEADER
         ===================================================== --}}
    <section class="dashboard-report-header">
        <div class="dashboard-report-heading">
            <span class="eyebrow">Operational report</span>
            <h2>System at a glance</h2>
            <p>
                A concise view of the directory, knowledge base, citizens,
                and work that needs attention.
            </p>
        </div>

        <details class="admin-export-menu">
            <summary class="dashboard-export-button" aria-label="Export report">
                <i class="ph-light ph-export" aria-hidden="true"></i>
                <span>Export</span>
                <i class="ph-light ph-caret-down export-caret" aria-hidden="true"></i>
            </summary>
            <div class="admin-export-dropdown">
                <a href="{{ route('admin.dashboard.export') }}" target="_blank" rel="noopener">
                    <i class="ph-light ph-file-pdf"></i>
                    <span><strong>Dashboard summary</strong><small>Current operational snapshot</small></span>
                </a>
                <a href="{{ route('admin.report.full') }}" target="_blank" rel="noopener">
                    <i class="ph-light ph-files"></i>
                    <span><strong>Full administrative report</strong><small>Dashboard + 7-day analytics</small></span>
                </a>
            </div>
        </details>
    </section>


    {{-- =====================================================
         CORE SNAPSHOT
         ===================================================== --}}
    <section class="dashboard-section dashboard-snapshot-section">
        <div class="dashboard-snapshot-grid">

            <a
                href="{{ route('admin.nga') }}"
                class="dashboard-metric dashboard-metric-agencies"
            >
                <span class="dashboard-metric-icon">
                    <i class="ph-light ph-buildings" aria-hidden="true"></i>
                </span>

                <span class="dashboard-metric-copy">
                    <span class="dashboard-metric-label">Agencies</span>
                    <strong>{{ number_format($totalAgencies) }}</strong>
                    <small>
                        {{ number_format($totalNGA) }} NGA ·
                        {{ number_format($totalNGO) }} NGO
                    </small>
                </span>

                <i class="ph-light ph-arrow-up-right dashboard-metric-arrow" aria-hidden="true"></i>
            </a>


            <a
                href="{{ route('faqs.index') }}"
                class="dashboard-metric dashboard-metric-faqs"
            >
                <span class="dashboard-metric-icon">
                    <i class="ph-light ph-book-open-text" aria-hidden="true"></i>
                </span>

                <span class="dashboard-metric-copy">
                    <span class="dashboard-metric-label">FAQs</span>
                    <strong>{{ number_format($totalFaqs) }}</strong>
                    <small>
                        {{ number_format($completeFaqs) }} complete ·
                        {{ number_format($incompleteFaqs) }} need attention
                    </small>
                </span>

                <i class="ph-light ph-arrow-up-right dashboard-metric-arrow" aria-hidden="true"></i>
            </a>


            <a
                href="{{ route('admin.users') }}"
                class="dashboard-metric dashboard-metric-users"
            >
                <span class="dashboard-metric-icon">
                    <i class="ph-light ph-users" aria-hidden="true"></i>
                </span>

                <span class="dashboard-metric-copy">
                    <span class="dashboard-metric-label">Public users</span>
                    <strong>{{ number_format($totalUsers) }}</strong>
                    <small>Registered citizen accounts</small>
                </span>

                <i class="ph-light ph-arrow-up-right dashboard-metric-arrow" aria-hidden="true"></i>
            </a>


            <a
                href="{{ route('admin.support.requests', ['status' => 'pending']) }}"
                class="dashboard-metric dashboard-metric-support {{ $pendingInquiries > 0 ? 'is-attention' : '' }}"
            >
                <span class="dashboard-metric-icon">
                    <i class="ph-light ph-chat-circle-text" aria-hidden="true"></i>
                </span>

                <span class="dashboard-metric-copy">
                    <span class="dashboard-metric-label">Pending inquiries</span>
                    <strong>{{ number_format($pendingInquiries) }}</strong>
                    <small>
                        {{ number_format($answeredInquiries) }} answered
                    </small>
                </span>

                <i class="ph-light ph-arrow-up-right dashboard-metric-arrow" aria-hidden="true"></i>
            </a>

        </div>
    </section>


    {{-- =====================================================
         NEEDS ATTENTION
         ===================================================== --}}
    <section class="dashboard-section">

        <div class="section-heading">
            <div class="section-heading-main">
                <div class="section-heading-icon attention">
                    <i class="ph-light ph-warning-circle" aria-hidden="true"></i>
                </div>

                <div class="section-heading-copy">
                    <span class="eyebrow">Attention</span>
                    <h2>Needs attention</h2>
                    <p>Items that may require an administrator's action.</p>
                </div>
            </div>

            <span class="dashboard-section-count">
                {{ number_format($totalNeedsAttention) }} items
            </span>
        </div>


        <div class="dashboard-attention-list">

            @if($pendingInquiries > 0)
                <a
                    href="{{ route('admin.support.requests', ['status' => 'pending']) }}"
                    class="dashboard-attention-item is-warning"
                >
                    <span class="dashboard-attention-icon">
                        <i class="ph-light ph-clock" aria-hidden="true"></i>
                    </span>

                    <span class="dashboard-attention-copy">
                        <strong>
                            {{ number_format($pendingInquiries) }}
                            {{ $pendingInquiries === 1 ? 'inquiry' : 'inquiries' }}
                            awaiting response
                        </strong>
                        <small>Citizens are waiting for an administrator.</small>
                    </span>

                    <i class="ph-light ph-arrow-right" aria-hidden="true"></i>
                </a>
            @endif


            @if($incompleteAgencies > 0)
                <a
                    href="{{ route('admin.nga', ['filter' => 'incomplete']) }}"
                    class="dashboard-attention-item is-info"
                >
                    <span class="dashboard-attention-icon">
                        <i class="ph-light ph-buildings" aria-hidden="true"></i>
                    </span>

                    <span class="dashboard-attention-copy">
                        <strong>
                            {{ number_format($incompleteAgencies) }}
                            {{ $incompleteAgencies === 1 ? 'agency record' : 'agency records' }}
                            need attention
                        </strong>
                        <small>Required directory information is incomplete.</small>
                    </span>

                    <i class="ph-light ph-arrow-right" aria-hidden="true"></i>
                </a>
            @endif


            @if($faqFeedbackOutstanding > 0)
                <a
                    href="{{ route('faqs.index', ['feedback' => 'needs_review']) }}"
                    class="dashboard-attention-item is-warning"
                >
                    <span class="dashboard-attention-icon">
                        <i class="ph-light ph-thumbs-down" aria-hidden="true"></i>
                    </span>
                    <span class="dashboard-attention-copy">
                        <strong>{{ number_format($faqFeedbackOutstanding) }} {{ $faqFeedbackOutstanding === 1 ? 'FAQ needs' : 'FAQs need' }} review</strong>
                        <small>Ratings are aggregated per FAQ; individual ratings do not create separate tasks.</small>
                    </span>
                    <i class="ph-light ph-arrow-right" aria-hidden="true"></i>
                </a>
            @endif

            @if($incompleteFaqs > 0)
                <a
                    href="{{ route('faqs.index', ['filter' => 'missing_translation']) }}"
                    class="dashboard-attention-item is-warning"
                >
                    <span class="dashboard-attention-icon">
                        <i class="ph-light ph-translate" aria-hidden="true"></i>
                    </span>

                    <span class="dashboard-attention-copy">
                        <strong>
                            {{ number_format($incompleteFaqs) }}
                            {{ $incompleteFaqs === 1 ? 'FAQ needs' : 'FAQs need' }}
                            translation
                        </strong>
                        <small>Filipino or Taglish content is incomplete.</small>
                    </span>

                    <i class="ph-light ph-arrow-right" aria-hidden="true"></i>
                </a>
            @endif


            @if($totalNeedsAttention === 0)
                <div class="dashboard-attention-item is-success">
                    <span class="dashboard-attention-icon">
                        <i class="ph-light ph-check-circle" aria-hidden="true"></i>
                    </span>

                    <span class="dashboard-attention-copy">
                        <strong>Everything looks good</strong>
                        <small>No immediate data-management issues were detected.</small>
                    </span>
                </div>
            @endif

        </div>

        <a href="{{ route('faqs.index') }}" class="dashboard-feedback-summary">
            <span class="dashboard-feedback-summary-icon"><i class="ph-light ph-chat-centered-text" aria-hidden="true"></i></span>
            <span class="dashboard-feedback-summary-copy">
                <strong>FAQ answer feedback</strong>
                <small>{{ number_format($faqFeedbackHelpful) }} likes · {{ number_format($faqFeedbackNotHelpful) }} dislikes · {{ number_format($faqFeedbackNeedsReview) }} FAQs need review</small>
            </span>
            <span class="dashboard-feedback-summary-action">Manage FAQs <i class="ph-light ph-arrow-right" aria-hidden="true"></i></span>
        </a>
    </section>


    {{-- =====================================================
         TEAM COLLABORATION
         ===================================================== --}}
    <section class="dashboard-section" id="team-collaboration">

        <div class="section-heading">
            <div class="section-heading-main">
                <div class="section-heading-icon">
                    <i class="ph-light ph-users-three" aria-hidden="true"></i>
                </div>

                <div class="section-heading-copy">
                    <span class="eyebrow">Team collaboration</span>
                    <h2>Shared work</h2>
                    <p>Handoffs and review requests between administrators.</p>
                </div>
            </div>

            <button
                type="button"
                class="dashboard-collaboration-add"
                id="open-collaboration-modal"
                title="Create collaboration task"
            >
                <i class="ph-light ph-plus" aria-hidden="true"></i>
                <span>New task</span>
            </button>
        </div>

        <div class="dashboard-collaboration-summary">
            <div class="dashboard-collaboration-stat">
                <strong>{{ number_format($collaborationOpenCount) }}</strong>
                <span>open</span>
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
                            {{ $task->task_type_label }} ·
                            {{ $creatorName }} → {{ $assigneeName }}
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
         ANALYTICS LINK
         ===================================================== --}}
    <section class="dashboard-section dashboard-analytics-section">

        <a
            href="{{ route('admin.analytics') }}"
            class="dashboard-analytics-link"
        >
            <span class="dashboard-analytics-icon">
                <i class="ph-light ph-chart-line-up" aria-hidden="true"></i>
            </span>

            <span class="dashboard-analytics-copy">
                <span class="eyebrow">Detailed analytics</span>
                <strong>Open the full operational report</strong>
                <small>
                    Trends, response performance, chatbot metrics, and knowledge gaps.
                </small>
            </span>

            <i class="ph-light ph-arrow-right" aria-hidden="true"></i>
        </a>

    </section>

</div>

@push('scripts')
<script src="{{ asset('jsfiles/admin/dashboard.js') }}"></script>
@endpush

@endsection
