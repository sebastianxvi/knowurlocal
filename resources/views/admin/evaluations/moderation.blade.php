@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/theme.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/form-system.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/components/action-menu.css') }}">
@endpush

@section('title', 'Evaluation Moderation')
@section('page-title', 'Evaluation Moderation')
@section('page-subtitle', 'Review public evaluation reports and record consistent moderation decisions.')

@section('content')
<div class="logs-page moderation-page">
    <section class="moderation-overview-grid" aria-label="Moderation summary">
        <a class="moderation-stat-card is-primary" href="{{ route('admin.evaluations.moderation', ['status' => 'pending']) }}">
            <span>Pending reports</span>
            <strong>{{ number_format($pendingCount) }}</strong>
            <small>Awaiting review</small>
        </a>
        <a class="moderation-stat-card" href="{{ route('admin.evaluations.moderation', ['status' => 'all']) }}">
            <span>Reported evaluations</span>
            <strong>{{ number_format($reportedEvaluationCount) }}</strong>
            <small>Unique evaluations reported</small>
        </a>
        <a class="moderation-stat-card" href="{{ route('admin.evaluations.moderation', ['status' => 'resolved']) }}">
            <span>Resolved reports</span>
            <strong>{{ number_format($resolvedCount) }}</strong>
            <small>Dismissed or actioned</small>
        </a>
        <div class="moderation-stat-card">
            <span>Hidden evaluations</span>
            <strong>{{ number_format($hiddenCount) }}</strong>
            <small>Currently unavailable publicly</small>
        </div>
    </section>

    <section class="admin-card moderation-queue-card">
        <div class="admin-card-heading moderation-heading">
            <div>
                <h2>Reported evaluations</h2>
                <p>Reports for the same evaluation are grouped together. Reporter identities remain private.</p>
            </div>
            <span class="moderation-queue-total">{{ number_format($reportedEvaluations->total()) }} results</span>
        </div>

        <div class="moderation-tabs" role="tablist" aria-label="Filter reported evaluations">
            <a href="{{ route('admin.evaluations.moderation', request()->except('page', 'status') + ['status' => 'pending']) }}"
               class="{{ $statusFilter === 'pending' ? 'is-active' : '' }}">
                Pending <span>{{ number_format($pendingCount) }}</span>
            </a>
            <a href="{{ route('admin.evaluations.moderation', request()->except('page', 'status') + ['status' => 'resolved']) }}"
               class="{{ $statusFilter === 'resolved' ? 'is-active' : '' }}">
                Resolved <span>{{ number_format($resolvedCount) }}</span>
            </a>
            <a href="{{ route('admin.evaluations.moderation', request()->except('page', 'status') + ['status' => 'all']) }}"
               class="{{ $statusFilter === 'all' ? 'is-active' : '' }}">
                All reports
            </a>
        </div>

        <div class="admin-data-controls moderation-filter-controls">
            <section class="support-filter-toolbar admin-filter-toolbar" aria-label="Search reported evaluations">
                <form method="GET" action="{{ route('admin.evaluations.moderation') }}" class="support-filter-form" role="search">
                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                    <div class="support-filter-field support-search-field">
                        <label for="moderation-search" class="sr-only">Search reported evaluations</label>
                        <i class="ph-light ph-magnifying-glass" aria-hidden="true"></i>
                        <input type="text" id="moderation-search" name="search" value="{{ $search }}"
                               placeholder="Search agency, evaluation, or report reason..." autocomplete="off">
                    </div>
                    <button type="submit" class="support-filter-submit">
                        <i class="ph-light ph-magnifying-glass" aria-hidden="true"></i>
                        <span>Search</span>
                    </button>
                    @if($search !== '')
                        <a class="support-filter-clear" href="{{ route('admin.evaluations.moderation', ['status' => $statusFilter]) }}">
                            <i class="ph-light ph-x" aria-hidden="true"></i><span>Clear</span>
                        </a>
                    @endif
                </form>
            </section>
        </div>

        @include('admin.components.list-result-meta', [
            'count' => $reportedEvaluations->total(),
            'label' => 'reported evaluation',
        ])

        <div class="moderation-table-wrap">
            <table class="table moderation-table">
                <thead>
                    <tr>
                        <th>Agency / evaluation</th>
                        <th>Public feedback</th>
                        <th>Rating</th>
                        <th>Reports</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportedEvaluations as $evaluation)
                        @php
                            $hasPending = (int) $evaluation->pending_reports_count > 0;
                            $isHidden = $evaluation->status === 'hidden';
                            $isDeleted = $evaluation->trashed();
                            $severityCount = (int) $evaluation->pending_reports_count;
                            $priorityThreshold = (int) config('faq_feedback.priority_minimum_ratings', 10);
                            $reviewThreshold = (int) config('faq_feedback.minimum_ratings_for_review', 5);
                            $reportSeverity = $severityCount >= $priorityThreshold
                                ? ['priority', 'Priority review']
                                : ($severityCount >= $reviewThreshold
                                    ? ['attention', 'Needs attention']
                                    : ['reported', 'Reported']);
                        @endphp
                        <tr class="moderation-table-row">
                            <td class="moderation-agency-cell">
                                <strong>{{ $evaluation->agency?->agency_name ?? 'Agency unavailable' }}</strong>
                                <small>Evaluation #{{ $evaluation->id }} · {{ $evaluation->created_at->format('M d, Y · g:i A') }}</small>
                            </td>
                            <td class="moderation-feedback-cell">
                                <span>{{ \Illuminate\Support\Str::limit($evaluation->comment ?: 'No additional comment provided.', 105) }}</span>
                            </td>
                            <td>
                                <span class="moderation-table-rating">{{ $evaluation->rating }} / 5</span>
                                <span class="moderation-table-stars" aria-label="{{ $evaluation->rating }} out of 5 stars">{{ str_repeat('★', (int) $evaluation->rating) }}{{ str_repeat('☆', 5 - (int) $evaluation->rating) }}</span>
                            </td>
                            <td>
                                <button type="button"
                                        class="moderation-compact-reports"
                                        data-open-reports
                                        data-reports-url="{{ route('admin.evaluations.moderation.reports', $evaluation) }}"
                                        data-total="{{ $evaluation->reports_count }}"
                                        data-agency="{{ $evaluation->agency?->agency_name ?? 'Agency unavailable' }}"
                                        data-evaluation="Evaluation #{{ $evaluation->id }}"
                                        aria-haspopup="dialog">
                                    <i class="ph-light ph-flag" aria-hidden="true"></i>
                                    <span class="moderation-report-copy">
                                        <strong>{{ number_format($evaluation->reports_count) }} {{ \Illuminate\Support\Str::plural('report', (int) $evaluation->reports_count) }}</strong>
                                        @if((int) $evaluation->pending_reports_count > 0)
                                            <small class="moderation-severity is-{{ $reportSeverity[0] }}">{{ $reportSeverity[1] }}</small>
                                        @else
                                            <small>View report details</small>
                                        @endif
                                    </span>
                                </button>
                            </td>
                            <td>
                                @if($isDeleted)
                                    <span class="moderation-pill is-hidden"><i class="ph-light ph-trash"></i> Removed</span>
                                @elseif($isHidden)
                                    <span class="moderation-pill is-hidden"><i class="ph-light ph-eye-slash"></i> Under review</span>
                                @elseif($hasPending)
                                    <span class="moderation-pill is-pending"><i class="ph-light ph-clock"></i> Needs review</span>
                                @else
                                    <span class="moderation-pill is-resolved"><i class="ph-light ph-check-circle"></i> Reviewed</span>
                                @endif
                            </td>
                            <td class="moderation-actions-cell">
                                @if(!$isDeleted && ($hasPending || $isHidden))
                                    <div class="admin-row-actions moderation-row-actions">
                                        @if($isHidden)
                                            <form method="POST" action="{{ route('admin.evaluations.moderate', $evaluation) }}" class="moderation-action-form">
                                                @csrf
                                                <input type="hidden" name="action" value="restore">
                                                <button type="submit" class="admin-action-primary"
                                                        data-confirm-title="Restore evaluation and dismiss reports?"
                                                        data-confirm-message="The evaluation will become public again and its pending reports will be marked as reviewed."
                                                        data-confirm-action="Restore evaluation"
                                                        data-confirm-icon="ph-light ph-eye"
                                                        data-confirm-variant="success">
                                                    <i class="ph-light ph-arrow-counter-clockwise" aria-hidden="true"></i><span>Restore</span>
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.evaluations.moderate', $evaluation) }}" class="moderation-action-form">
                                                @csrf
                                                <input type="hidden" name="action" value="dismiss">
                                                <button type="submit" class="admin-action-primary"
                                                        data-confirm-title="Dismiss all pending reports?"
                                                        data-confirm-message="The reports will be marked as reviewed, and the evaluation will remain publicly visible."
                                                        data-confirm-action="Dismiss reports"
                                                        data-confirm-icon="ph-light ph-check-circle"
                                                        data-confirm-variant="success">
                                                    <i class="ph-light ph-check-circle" aria-hidden="true"></i><span>Dismiss reports</span>
                                                </button>
                                            </form>
                                        @endif

                                        <div class="admin-action-menu">
                                            <button type="button" class="admin-action-menu-trigger"
                                                    aria-label="More moderation actions for evaluation #{{ $evaluation->id }}"
                                                    aria-expanded="false" aria-haspopup="menu">
                                                <i class="ph-light ph-dots-three-vertical" aria-hidden="true"></i>
                                            </button>
                                            <div class="admin-action-menu-content" role="menu">
                                                @if($isHidden)
                                                    <form method="POST" action="{{ route('admin.evaluations.moderate', $evaluation) }}" class="moderation-delete-form">
                                                        @csrf
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="reason" value="">
                                                        <button type="button" class="admin-menu-action admin-menu-danger" role="menuitem" data-open-removal-modal>
                                                            <i class="ph-light ph-trash" aria-hidden="true"></i><span>Remove permanently</span>
                                                        </button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('admin.evaluations.moderate', $evaluation) }}" class="moderation-action-form">
                                                        @csrf
                                                        <input type="hidden" name="action" value="hide">
                                                        <button type="submit" class="admin-menu-action admin-menu-danger" role="menuitem"
                                                                data-confirm-title="Temporarily hide this evaluation?"
                                                                data-confirm-message="The evaluation will be hidden while you investigate. Its reports will remain pending. You can restore it or permanently remove it later."
                                                                data-confirm-action="Hide for review"
                                                                data-confirm-icon="ph-light ph-eye-slash"
                                                                data-confirm-variant="warning">
                                                            <i class="ph-light ph-eye-slash" aria-hidden="true"></i><span>Hide for review</span>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="moderation-no-action">{{ $isDeleted ? 'Removed' : 'No action needed' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="moderation-empty-state">
                                    <span class="moderation-empty-icon"><i class="ph-light ph-shield-check" aria-hidden="true"></i></span>
                                    <h3>{{ $search !== '' ? 'No matching reports' : ($statusFilter === 'pending' ? 'No pending reports' : 'No reports found') }}</h3>
                                    <p>
                                        @if($search !== '')
                                            Try another agency name, evaluation comment, or report reason.
                                        @elseif($statusFilter === 'pending')
                                            You're all caught up. New public reports will appear here for review.
                                        @else
                                            There are no reports in this view yet.
                                        @endif
                                    </p>
                                    @if($search !== '' || $statusFilter !== 'pending')
                                        <a class="btn btn-secondary" href="{{ route('admin.evaluations.moderation') }}">Clear filters</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-pagination">{{ $reportedEvaluations->links() }}</div>
    </section>

</div>

<div class="moderation-reports-backdrop" id="moderation-reports-modal" aria-hidden="true">
    <section class="moderation-reports-dialog" role="dialog" aria-modal="true"
             aria-labelledby="moderation-reports-title" tabindex="-1">
        <header class="moderation-reports-modal-header">
            <div>
                <span class="moderation-eyebrow">Evaluation reports</span>
                <h2 id="moderation-reports-title">Reports</h2>
                <p id="moderation-reports-subtitle"></p>
            </div>
            <button type="button" class="moderation-modal-close" data-close-reports aria-label="Close reports">
                <i class="ph-light ph-x" aria-hidden="true"></i>
            </button>
        </header>
        <div class="moderation-reports-modal-body">
            <div class="moderation-reports-modal-meta" id="moderation-reports-count"></div>
            <div class="moderation-reports-modal-list" id="moderation-reports-modal-list" aria-live="polite"></div>
            <div class="moderation-reports-modal-pagination" id="moderation-reports-pagination" hidden>
                <button type="button" class="btn btn-secondary" id="moderation-reports-prev" disabled>Previous</button>
                <span id="moderation-reports-page-label"></span>
                <button type="button" class="btn btn-secondary" id="moderation-reports-next" disabled>Next</button>
            </div>
        </div>
    </section>
</div>
@endsection

@push('styles')
<style>
.moderation-page{display:grid;gap:18px;min-width:0}
.moderation-overview-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
.moderation-stat-card{display:flex;flex-direction:column;min-width:0;min-height:116px;padding:17px 18px;border:1px solid var(--border-color,#e5eaf1);border-radius:13px;background:var(--surface,#fff);color:var(--text-primary,#172033);text-decoration:none;transition:border-color .16s ease,box-shadow .16s ease}
.moderation-stat-card:hover{border-color:#b9c9e0;box-shadow:0 5px 18px rgba(15,23,42,.06)}
.moderation-stat-card.is-primary{border-color:#c8d9ff;background:linear-gradient(145deg,#fff,#f6f9ff)}
.moderation-stat-card>span{color:var(--text-muted,#64748b);font-size:13px}
.moderation-stat-card strong{margin-top:8px;font-size:27px;line-height:1.1;font-weight:650}
.moderation-stat-card small{margin-top:auto;padding-top:9px;color:var(--text-muted,#64748b);font-size:11px}
.admin-card{min-width:0;padding:18px;border:1px solid var(--border-color,#e5eaf1);border-radius:14px;background:var(--surface,#fff)}
.moderation-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:14px}
.admin-card-heading h2{margin:0;font-size:17px;font-weight:650}
.admin-card-heading p{margin:5px 0 0;color:var(--text-muted,#64748b);font-size:13px}
.moderation-queue-total{flex:0 0 auto;padding:5px 10px;border-radius:999px;background:#f1f5f9;color:#475569;font-size:12px;font-weight:600}
.moderation-tabs{display:flex;gap:8px;max-width:100%;margin:18px 0 14px;overflow-x:auto;padding-bottom:2px}
.moderation-tabs a{display:inline-flex;align-items:center;gap:8px;flex:0 0 auto;min-height:36px;padding:7px 12px;border:1px solid #dce4ee;border-radius:999px;background:#fff;color:#475569;text-decoration:none;font-size:12px;font-weight:550}
.moderation-tabs a.is-active{border-color:#b8d0ff;background:#eff6ff;color:#1d4ed8}
.moderation-tabs a span{color:inherit;opacity:.75}
.moderation-filter-controls{margin:0 0 12px}
.moderation-filter-controls .support-filter-form{display:flex;align-items:center;gap:10px;width:100%;min-width:0}
.moderation-filter-controls .support-search-field{flex:1 1 420px}
.moderation-filter-controls .support-filter-submit,.moderation-filter-controls .support-filter-clear{flex:0 0 auto;min-width:94px}
.moderation-page .admin-list-result-meta{margin:10px 0 12px}
.moderation-list{display:grid;gap:13px}
.moderation-evaluation-card{min-width:0;padding:17px 0 18px;border-bottom:1px solid #e9eef5}
.moderation-evaluation-card:first-child{padding-top:4px}
.moderation-evaluation-header{display:flex;align-items:flex-start;justify-content:space-between;gap:14px}
.moderation-agency{min-width:0}
.moderation-eyebrow,.moderation-panel-label{display:block;color:#8492a6;font-size:10px;font-weight:650;letter-spacing:.07em;text-transform:uppercase}
.moderation-agency h3{margin:4px 0;color:var(--text-primary,#172033);font-size:15px;font-weight:650;overflow-wrap:anywhere}
.moderation-evaluation-meta{display:block;color:#7b8798;font-size:11px}
.moderation-pill,.moderation-report-status{display:inline-flex;align-items:center;gap:5px;padding:5px 9px;border-radius:999px;font-size:11px;font-weight:600;white-space:nowrap}
.moderation-pill.is-pending{background:#fff6e5;color:#9a5b00}
.moderation-pill.is-hidden{background:#fff0f0;color:#b42318}
.moderation-pill.is-resolved{background:#edf8f1;color:#187245}
.moderation-evaluation-body{display:grid;grid-template-columns:minmax(0,1fr) 190px;gap:12px;margin:14px 0}
.moderation-review-panel,.moderation-report-summary{min-width:0;padding:13px 14px;border:1px solid #e8edf4;border-radius:10px;background:#f8fafc}
.moderation-panel-heading{display:flex;justify-content:space-between;gap:12px;color:#64748b;font-size:11px;font-weight:600}
.moderation-stars{color:#e9a52e;letter-spacing:1px;font-size:14px}
.moderation-review-panel p{margin:9px 0;color:#334155;font-size:13px;line-height:1.55;white-space:pre-wrap;overflow-wrap:anywhere}
.moderation-review-panel>small{color:#7b8798;font-size:11px}
.moderation-report-summary{display:flex;flex-direction:column;justify-content:center}
.moderation-report-summary>strong{margin:7px 0 5px;font-size:23px;line-height:1.1}
.moderation-report-counts{display:flex;flex-wrap:wrap;gap:5px;color:#64748b;font-size:11px}
.moderation-report-counts span+span:before{content:"·";margin-right:5px}
.moderation-report-details{border:1px solid #e6ebf2;border-radius:10px;background:#fff}
.moderation-report-details summary{display:flex;align-items:center;justify-content:space-between;gap:12px;min-height:43px;padding:10px 13px;cursor:pointer;list-style:none;color:#334155;font-size:12px;font-weight:600}
.moderation-report-details summary::-webkit-details-marker{display:none}
.moderation-report-details summary>span{display:inline-flex;align-items:center;gap:7px}
.moderation-report-details summary>span:last-child{color:#7b8798;font-weight:500}
.moderation-report-details[open] summary{border-bottom:1px solid #edf0f4}
.moderation-report-details[open] summary>span:last-child .ph-caret-down{transform:rotate(180deg)}
.moderation-report-details summary i{font-size:16px}
.moderation-report-list{padding:0 13px}
.moderation-report-row{padding:12px 0;border-bottom:1px solid #edf0f4}
.moderation-report-row:last-child{border-bottom:0}
.moderation-report-row-head{display:flex;align-items:center;justify-content:space-between;gap:12px}
.moderation-report-row-head strong{display:block;color:#334155;font-size:12px}
.moderation-report-row-head small{display:block;margin-top:3px;color:#8793a5;font-size:10px}
.moderation-report-status{padding:4px 8px;background:#f1f5f9;color:#64748b;font-size:10px}
.moderation-report-status.pending{background:#fff6e5;color:#9a5b00}
.moderation-severity{display:inline-flex!important;width:max-content;max-width:100%;margin-top:3px;padding:3px 7px;border-radius:999px;font-size:9px!important;font-weight:700;line-height:1.2}
.moderation-severity.is-reported{background:#f1f5f9;color:#64748b}
.moderation-severity.is-attention{background:#fff6e5;color:#9a5b00}
.moderation-severity.is-priority{background:#fff0f0;color:#b42318}
.moderation-report-status.dismissed,.moderation-report-status.actioned{background:#edf8f1;color:#187245}
.moderation-report-row>p{margin:8px 0 0;color:#596579;font-size:12px;line-height:1.5;white-space:pre-wrap;overflow-wrap:anywhere}
.moderation-resolution-note{display:flex;gap:7px;margin-top:8px;padding:8px 9px;border-radius:7px;background:#f5f8fc;color:#64748b;font-size:11px;line-height:1.45}
.moderation-resolution-note i{flex:0 0 auto;font-size:15px}
.moderation-decision-form{display:grid;gap:9px;margin-top:13px;padding:13px;border:1px solid #e6ebf2;border-radius:10px;background:#fbfcfe}
.moderation-decision-form label{color:#334155;font-size:12px;font-weight:600}
.moderation-decision-form label span{color:#8793a5;font-weight:400}
.moderation-decision-form textarea{box-sizing:border-box;width:100%;min-height:62px;padding:10px 11px;border:1px solid #d9e1eb;border-radius:8px;background:#fff;color:#334155;font:inherit;font-size:12px;resize:vertical}
.moderation-decision-form textarea:focus{outline:none;border-color:#7ba5ed;box-shadow:0 0 0 3px rgba(37,99,235,.09)}
.moderation-note-help{color:#8793a5;font-size:10px;line-height:1.4}
.moderation-note-field.is-removal-reason{padding:10px;border:1px solid #fecaca;border-radius:9px;background:#fffafa}
.moderation-note-field.is-removal-reason label{color:#b42318}
.moderation-note-field.is-removal-reason textarea{border-color:#f3b4b4}
.moderation-note-field.is-removal-reason .moderation-note-help{color:#b42318}
.moderation-decision-actions{display:flex;justify-content:flex-end;flex-wrap:wrap;gap:8px}
.moderation-decision-actions .btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:38px;padding:8px 13px;border-radius:8px;font-size:12px;font-weight:600}
.moderation-decision-actions .btn i{font-size:16px}
.moderation-empty-state{display:grid;justify-items:center;padding:44px 18px;text-align:center}
.moderation-empty-icon{display:grid;place-items:center;width:48px;height:48px;border-radius:14px;background:#eff6ff;color:#2563eb;font-size:24px}
.moderation-empty-state h3{margin:13px 0 5px;color:#243247;font-size:15px}
.moderation-empty-state p{max-width:390px;margin:0 0 14px;color:#718096;font-size:12px;line-height:1.5}
.admin-pagination{margin-top:16px}
.moderation-row-actions{justify-content:flex-end;gap:6px}
.moderation-row-actions .admin-action-primary{min-height:34px;padding:0 10px;font-size:12px}
.moderation-row-actions .admin-action-menu{flex:0 0 34px}
.moderation-action-form{margin:0}
@media(max-width:900px){.moderation-overview-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:700px){.moderation-heading{align-items:flex-start;flex-direction:column}.moderation-filter-controls .support-filter-form{display:grid;grid-template-columns:minmax(0,1fr)}.moderation-filter-controls .support-filter-form>*{width:100%;min-width:0}.moderation-evaluation-body{grid-template-columns:1fr}.moderation-report-summary{min-height:90px}.moderation-decision-actions{justify-content:stretch}.moderation-decision-actions .btn{flex:1 1 100%}.moderation-summary{display:block}}
@media(max-width:480px){.moderation-overview-grid{gap:9px}.moderation-stat-card{min-height:100px;padding:13px}.moderation-stat-card strong{font-size:23px}.admin-card{padding:14px}.moderation-evaluation-header{align-items:flex-start}.moderation-pill{font-size:10px}.moderation-row-actions{justify-content:flex-end}.moderation-row-actions .admin-action-primary{min-height:32px;padding:0 8px;font-size:11px}}
@media(prefers-reduced-motion:reduce){.moderation-stat-card{transition:none}}

/* Compact moderation cards: reports and decisions are available on demand. */
.moderation-evaluation-body{grid-template-columns:minmax(0,1fr);margin:13px 0 12px}
.moderation-report-summary{display:none!important}
.moderation-card-footer{display:grid;gap:10px}
.moderation-view-reports{display:flex;align-items:center;gap:10px;width:100%;min-height:46px;padding:9px 12px;border:1px solid #e1e8f1;border-radius:10px;background:#fff;color:#243247;text-align:left;cursor:pointer;transition:background .16s,border-color .16s}
.moderation-view-reports:hover{border-color:#b9cbed;background:#f8fbff}
.moderation-view-reports-icon{display:grid;place-items:center;flex:0 0 30px;width:30px;height:30px;border-radius:9px;background:#eff5ff;color:#2860bd;font-size:16px}
.moderation-view-reports>span:nth-child(2){font-size:12px;font-weight:650}
.moderation-view-reports-count{margin-left:auto;color:#64748b;font-size:11px;font-weight:500}
.moderation-view-reports>i:last-child{color:#8a97a9;font-size:15px}
.moderation-action-disclosure{display:flex;justify-content:flex-end;margin-top:11px}
.moderation-decision-form[hidden]{display:none!important}
.moderation-decision-form{margin-top:10px}
.moderation-reports-backdrop{position:fixed;inset:0;z-index:1450;display:grid;place-items:center;padding:18px;background:rgba(15,23,42,.48);backdrop-filter:blur(5px);opacity:0;visibility:hidden;transition:opacity .18s,visibility .18s}
.moderation-reports-backdrop.is-open{opacity:1;visibility:visible}
.moderation-reports-dialog{display:flex;flex-direction:column;width:min(100%,650px);max-height:min(92vh, 780px);min-height:220px;overflow:hidden;border:1px solid #e4eaf2;border-radius:17px;background:#fff;box-shadow:0 24px 70px rgba(15,23,42,.22);outline:none;transform:translateY(8px);transition:transform .18s}
.moderation-reports-backdrop.is-open .moderation-reports-dialog{transform:translateY(0)}
.moderation-reports-modal-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:20px 22px 16px;border-bottom:1px solid #edf1f5}
.moderation-reports-modal-header h2{margin:5px 0 3px;color:#1e293b;font-size:18px;font-weight:650}
.moderation-reports-modal-header p{margin:0;color:#718096;font-size:12px;line-height:1.5;overflow-wrap:anywhere}
.moderation-modal-close{display:grid;place-items:center;flex:0 0 36px;width:36px;height:36px;border:1px solid #e1e8f0;border-radius:10px;background:#fff;color:#64748b;font-size:18px;cursor:pointer}
.moderation-modal-close:hover{background:#f8fafc;color:#1e293b}
.moderation-reports-modal-body{min-height:0;overflow:auto;padding:15px 22px}
.moderation-reports-modal-meta{margin-bottom:11px;color:#64748b;font-size:11px;font-weight:600}
.moderation-reports-modal-list{display:grid;gap:10px}
.moderation-report-modal-item{padding:13px 14px;border:1px solid #e7edf4;border-radius:11px;background:#fbfcfe}
.moderation-report-modal-item .moderation-report-row-head{align-items:flex-start}
.moderation-report-modal-item .moderation-report-row-head strong{font-size:13px}
.moderation-report-modal-item .moderation-report-row-head small{font-size:11px}
.moderation-report-modal-item>p{margin:10px 0 0;color:#536176;font-size:12px;line-height:1.55;white-space:pre-wrap;overflow-wrap:anywhere}
body.moderation-reports-open{overflow:hidden}
@media(max-width:600px){
 .moderation-reports-backdrop{align-items:end;padding:0}
 .moderation-reports-dialog{width:100%;max-height:88dvh;border-radius:17px 17px 0 0}
 .moderation-reports-modal-header{padding:17px 16px 13px}
 .moderation-reports-modal-body{padding:13px 16px}
 .moderation-action-disclosure{justify-content:stretch}
}
@media(prefers-reduced-motion:reduce){
}
/* Dense, scalable moderation queue: one compact row per reported evaluation. */
.moderation-table-wrap{width:100%;max-width:100%;overflow-x:auto;border:1px solid #e5eaf1;border-radius:11px;background:#fff}
.moderation-table{width:100%;min-width:920px;table-layout:fixed}
.moderation-table th{padding:10px 12px;font-size:10px;letter-spacing:.045em;text-transform:uppercase}
.moderation-table td{padding:10px 12px;font-size:12px;line-height:1.4}
.moderation-table th:nth-child(1){width:23%;text-align:left}
.moderation-table th:nth-child(2){width:25%;text-align:left}
.moderation-table th:nth-child(3){width:10%}
.moderation-table th:nth-child(4){width:13%}
.moderation-table th:nth-child(5){width:13%}
.moderation-table th:nth-child(6){width:16%}
.moderation-table td.moderation-agency-cell,.moderation-table td.moderation-feedback-cell{text-align:left}
.moderation-agency-cell strong{display:block;color:#26364d;font-size:12px;font-weight:650;line-height:1.4}
.moderation-agency-cell small{display:block;margin-top:4px;color:#8793a5;font-size:10px}
.moderation-feedback-cell span{display:-webkit-box;overflow:hidden;-webkit-box-orient:vertical;-webkit-line-clamp:2;color:#536176;font-size:11px;line-height:1.45}
.moderation-table-rating{display:block;color:#334155;font-size:11px;font-weight:600;white-space:nowrap}
.moderation-table-stars{display:block;margin-top:3px;color:#e9a52e;font-size:10px;letter-spacing:.5px;white-space:nowrap}
.moderation-compact-reports{display:flex;align-items:center;justify-content:flex-start;gap:8px;width:100%;min-width:0;min-height:42px;padding:6px 9px;border:1px solid #dce5f0;border-radius:9px;background:#fff;color:#334155;text-align:left;cursor:pointer;transition:background .16s ease,border-color .16s ease,box-shadow .16s ease}
.moderation-compact-reports:hover{border-color:#a9c3e8;background:#f7faff;box-shadow:0 2px 7px rgba(37,99,235,.07)}
.moderation-compact-reports:focus-visible{outline:3px solid rgba(37,99,235,.18);outline-offset:2px}
.moderation-compact-reports>i{display:grid;place-items:center;flex:0 0 28px;width:28px;height:28px;border-radius:8px;background:#eff5ff;color:#2860bd;font-size:15px}
.moderation-report-copy{display:grid;gap:2px;min-width:0}
.moderation-report-copy strong{color:#334155;font-size:11px;font-weight:650;line-height:1.2;white-space:nowrap}
.moderation-report-copy small{color:#8793a5;font-size:10px;line-height:1.2;white-space:nowrap}
.moderation-compact-reports[data-total="0"]{opacity:.7}
.moderation-actions-cell{white-space:nowrap}
.moderation-no-action{color:#98a2b3;font-size:10px}
.moderation-decision-row[hidden]{display:none!important}
.moderation-decision-row>td{padding:0 12px 12px!important;background:#fbfcfe;text-align:left!important}
.moderation-decision-form{display:flex;align-items:flex-end;gap:12px;margin:0;padding:12px;border:1px solid #e6ebf2;border-radius:9px;background:#fbfcfe}
.moderation-note-field{flex:1 1 auto;min-width:0;display:grid;gap:6px}
.moderation-note-field textarea{min-height:54px}
.moderation-decision-actions{flex:0 0 auto}
.moderation-decision-actions .btn{min-height:34px;padding:7px 10px;font-size:11px}
.moderation-table tbody tr.moderation-decision-row:hover{background:transparent}
.moderation-report-empty{padding:16px;border:1px dashed #dbe3ed;border-radius:9px;color:#718096;font-size:12px;text-align:center}
.moderation-reports-modal-pagination{display:flex;align-items:center;justify-content:center;gap:12px;margin-top:13px;padding-top:12px;border-top:1px solid #edf1f5}
.moderation-reports-modal-pagination[hidden]{display:none!important}
.moderation-reports-modal-pagination .btn{min-height:32px;padding:6px 11px;font-size:11px}
.moderation-reports-modal-pagination .btn:disabled{opacity:.45;cursor:not-allowed}
.moderation-reports-modal-pagination span{color:#718096;font-size:11px;font-variant-numeric:tabular-nums}
@media(max-width:700px){
 .moderation-table{min-width:860px}
 .moderation-decision-form{align-items:stretch;flex-direction:column}
 .moderation-decision-actions{justify-content:flex-end}
}
</style>

@endpush

@push('scripts')
<script src="{{ asset('jsfiles/admin/components/action-menu.js') }}"></script>
<script>
(() => {
    const reportModal = document.getElementById('moderation-reports-modal');
    const reportDialog = reportModal?.querySelector('.moderation-reports-dialog');
    const reportTitle = document.getElementById('moderation-reports-title');
    const reportSubtitle = document.getElementById('moderation-reports-subtitle');
    const reportCount = document.getElementById('moderation-reports-count');
    const reportList = document.getElementById('moderation-reports-modal-list');
    let previousReportFocus = null;
    let activeReportsUrl = null;
    let currentReportPage = 1;
    let lastReportPage = 1;
    const reportPagination = document.getElementById('moderation-reports-pagination');
    const reportPrev = document.getElementById('moderation-reports-prev');
    const reportNext = document.getElementById('moderation-reports-next');
    const reportPageLabel = document.getElementById('moderation-reports-page-label');
    let reportFetchController = null;

    const appendText = (parent, tag, className, value) => {
        const element = document.createElement(tag);
        if (className) element.className = className;
        element.textContent = value ?? '';
        parent.appendChild(element);
        return element;
    };

    const renderReportRows = (reports) => {
        reportList.replaceChildren();
        if (!reports.length) {
            appendText(reportList, 'p', 'moderation-report-empty', 'No reports found for this evaluation.');
            return;
        }

        reports.forEach((report) => {
            const item = document.createElement('article');
            item.className = 'moderation-report-modal-item';

            const head = document.createElement('div');
            head.className = 'moderation-report-row-head';
            const copy = document.createElement('div');
            appendText(copy, 'strong', '', report.reason || 'Unspecified reason');
            appendText(copy, 'small', '', `Submitted ${report.created_at || 'date unavailable'}`);
            head.appendChild(copy);

            const status = appendText(head, 'span', 'moderation-report-status', (report.status || 'unknown').replaceAll('_', ' '));
            if (['pending', 'dismissed', 'actioned'].includes(report.status)) status.classList.add(report.status);
            item.appendChild(head);
            appendText(item, 'p', '', report.details || 'No additional details provided.');

            if (report.resolution_note) {
                const note = document.createElement('div');
                note.className = 'moderation-resolution-note';
                appendText(note, 'strong', '', 'Internal resolution note:');
                appendText(note, 'span', '', report.resolution_note);
                item.appendChild(note);
            }
            reportList.appendChild(item);
        });
    };

    const loadReportPage = async (page) => {
        if (!activeReportsUrl) return;
        reportFetchController?.abort();
        reportFetchController = new AbortController();
        reportCount.textContent = 'Loading reports…';
        reportList.replaceChildren();

        try {
            const separator = activeReportsUrl.includes('?') ? '&' : '?';
            const response = await fetch(`${activeReportsUrl}${separator}page=${page}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: reportFetchController.signal,
            });
            if (!response.ok) throw new Error('Unable to load reports.');
            const payload = await response.json();
            const meta = payload.meta || {};
            currentReportPage = Number(meta.current_page || page);
            lastReportPage = Number(meta.last_page || 1);
            reportCount.textContent = `${Number(meta.total || 0).toLocaleString()} ${Number(meta.total || 0) === 1 ? 'report' : 'reports'} submitted for this evaluation`;
            renderReportRows(Array.isArray(payload.data) ? payload.data : []);
            reportPagination.hidden = lastReportPage <= 1;
            reportPrev.disabled = currentReportPage <= 1;
            reportNext.disabled = currentReportPage >= lastReportPage;
            reportPageLabel.textContent = `Page ${currentReportPage} of ${lastReportPage}`;
        } catch (error) {
            if (error.name === 'AbortError') return;
            reportCount.textContent = 'Reports could not be loaded.';
            reportList.replaceChildren();
            appendText(reportList, 'p', 'moderation-report-empty', 'Please close this window and try again.');
        }
    };

    if (reportPrev) reportPrev.addEventListener('click', () => {
        if (currentReportPage > 1) loadReportPage(currentReportPage - 1);
    });
    if (reportNext) reportNext.addEventListener('click', () => {
        if (currentReportPage < lastReportPage) loadReportPage(currentReportPage + 1);
    });

    if (reportModal && reportDialog && reportList) {
        if (reportModal.parentElement !== document.body) document.body.appendChild(reportModal);

        const closeReports = () => {
            reportModal.classList.remove('is-open');
            reportModal.setAttribute('aria-hidden', 'true');
            reportFetchController?.abort();
            document.body.classList.remove('moderation-reports-open');
            if (previousReportFocus?.focus) previousReportFocus.focus();
        };

        document.addEventListener('click', (event) => {
            const openButton = event.target.closest('[data-open-reports]');
            if (openButton) {
                previousReportFocus = openButton;
                reportTitle.textContent = 'Evaluation reports';
                reportSubtitle.textContent = `${openButton.dataset.agency || 'Agency unavailable'} · ${openButton.dataset.evaluation || ''}`;
                activeReportsUrl = openButton.dataset.reportsUrl;
                currentReportPage = 1;
                lastReportPage = 1;
                reportList.replaceChildren();
                reportCount.textContent = 'Loading reports…';
                reportPagination.hidden = true;
                reportModal.classList.add('is-open');
                reportModal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('moderation-reports-open');
                reportDialog.focus();
                loadReportPage(1);
                return;
            }

            if (event.target.closest('[data-close-reports]')) closeReports();

        });

        reportModal.addEventListener('click', (event) => {
            if (event.target === reportModal) closeReports();
        });

        document.addEventListener('keydown', (event) => {
            if (!reportModal.classList.contains('is-open')) return;
            if (event.key === 'Escape') {
                closeReports();
                return;
            }
            if (event.key === 'Tab') {
                const focusable = Array.from(reportDialog.querySelectorAll(
                    'button:not([disabled]), a[href], input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
                )).filter((element) => element.offsetParent !== null);
                if (!focusable.length) {
                    event.preventDefault();
                    reportDialog.focus();
                    return;
                }
                const first = focusable[0];
                const last = focusable[focusable.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        });
    }

    // Use the application-wide alert modal for every consequential action.
    // This keeps confirmations and the required removal-reason input identical
    // to the other admin modules instead of creating a page-specific modal.
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('.moderation-action-form');
        if (!form) return;

        const submitter = event.submitter;
        if (!submitter?.dataset.confirmTitle) return;
        event.preventDefault();

        if (typeof window.showAlertModal !== 'function') {
            console.error('The shared admin confirmation modal is unavailable.');
            return;
        }

        window.showAlertModal({
            title: submitter.dataset.confirmTitle,
            text: submitter.dataset.confirmMessage || 'Please confirm this moderation decision.',
            icon: submitter.dataset.confirmIcon || 'ph-light ph-shield-warning',
            variant: submitter.dataset.confirmVariant || 'warning',
            confirmText: submitter.dataset.confirmAction || 'Confirm',
            cancelText: 'Cancel',
            showCancel: true,
            onConfirm: () => {
                window.closeAlertModal?.();
                submitter.disabled = true;
                form.submit();
            },
        });
    });

    document.addEventListener('click', (event) => {
        const openRemoval = event.target.closest('[data-open-removal-modal]');
        if (!openRemoval) return;

        const form = openRemoval.closest('.moderation-delete-form');
        const reasonField = form?.querySelector('input[name="reason"]');
        if (!form || !reasonField) return;

        // Close the shared portalled action menu before opening the global modal.
        const ownerMenuId = openRemoval.closest('.admin-action-menu-content')?.dataset.ownerMenu;
        const ownerTrigger = ownerMenuId
            ? document.querySelector(`.admin-action-menu[data-menu-id="${ownerMenuId}"] .admin-action-menu-trigger`)
            : null;
        ownerTrigger?.click();

        if (typeof window.showAlertModal !== 'function') {
            console.error('The shared admin removal modal is unavailable.');
            return;
        }

        window.showAlertModal({
            title: 'Remove evaluation permanently?',
            text: 'This evaluation will be removed from public view. Its original content and moderation records will be retained. The author will receive the reason you provide.',
            icon: 'ph-light ph-trash',
            variant: 'danger',
            confirmText: 'Remove evaluation',
            cancelText: 'Cancel',
            showCancel: true,
            input: {
                label: 'Reason for removal (required)',
                placeholder: 'Explain why this evaluation is being removed…',
                required: true,
                minLength: 5,
                maxLength: 1000,
                validationMessage: 'Please provide a reason of at least 5 characters.',
            },
            onConfirm: (reason) => {
                reasonField.value = String(reason || '').trim();
                window.closeAlertModal?.();
                openRemoval.disabled = true;
                form.submit();
            },
        });
    });
})();
</script>
@endpush
