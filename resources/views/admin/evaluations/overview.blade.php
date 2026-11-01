@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/theme.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/form-system.css') }}">
@endpush

@section('title', 'Agency Satisfaction')
@section('page-title', 'Agency Satisfaction Overview')
@section('page-subtitle', 'Monitor public service satisfaction across all registered agencies.')

@section('content')
<div class="logs-page evaluation-admin-page">
    <div class="admin-stats-grid">
        <div class="admin-stat-card"><span>Published evaluations</span><strong>{{ number_format($summary->total) }}</strong></div>
        <div class="admin-stat-card"><span>Overall satisfaction</span><strong>{{ number_format((float) $summary->average, 1) }} <small>/ 5</small></strong></div>
        <div class="admin-stat-card"><span>Awaiting official response</span><strong>{{ number_format($unanswered) }}</strong></div>
        @if(auth()->user()->role === 'superadmin' || (auth()->user()->role === 'admin' && auth()->user()->can_moderate_evaluations))
            <a class="admin-stat-card evaluation-pending-reports-link" href="{{ route('admin.evaluations.moderation') }}">
                <span>Pending reports</span><strong>{{ number_format($pendingReports) }}</strong>
                <small>Open moderation queue <i class="ph-light ph-arrow-up-right" aria-hidden="true"></i></small>
            </a>
        @else
            <div class="admin-stat-card">
                <span>Pending reports</span><strong>{{ number_format($pendingReports) }}</strong>
                <small>Managed by authorized moderators</small>
            </div>
        @endif
    </div>

    <div class="section-heading evaluation-overview-heading">
        <div>
            <h2>Agency performance</h2>
            <p>Select an agency to inspect its individual evaluations and, where permitted, respond.</p>
        </div>
    </div>

    {{-- Use the same filter toolbar used by NGA & NGO Management and other admin list pages. --}}
    <div class="admin-data-controls">
        <section class="support-filter-toolbar admin-filter-toolbar" aria-label="Agency search">
            <form method="GET" action="{{ route('admin.evaluations.overview') }}" class="support-filter-form" role="search">
                <div class="support-filter-field support-search-field">
                    <label for="evaluation-agency-search" class="sr-only">Search agencies</label>
                    <i class="ph-light ph-magnifying-glass" aria-hidden="true"></i>
                    <input
                        type="search"
                        id="evaluation-agency-search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search agency name or abbreviation..."
                        autocomplete="off"
                    >
                </div>
                <div class="support-filter-field evaluation-rating-filter">
                    <label for="evaluation-rating-filter" class="sr-only">Filter agencies by average rating</label>
                    <i class="ph-light ph-funnel" aria-hidden="true"></i>
                    <select id="evaluation-rating-filter" name="rating_filter" aria-label="Filter agencies by average rating">
                        <option value="all" @selected($ratingFilter === 'all')>All rating levels</option>
                        <option value="below_3" @selected($ratingFilter === 'below_3')>Below 3 stars</option>
                        <option value="three_to_four" @selected($ratingFilter === 'three_to_four')>3 to below 4 stars</option>
                        <option value="four_plus" @selected($ratingFilter === 'four_plus')>4 stars and above</option>
                        <option value="no_evaluations" @selected($ratingFilter === 'no_evaluations')>No evaluations</option>
                    </select>
                </div>
                <button type="submit" class="support-filter-submit" aria-label="Apply agency filters" title="Apply agency filters">
                    <i class="ph-light ph-magnifying-glass" aria-hidden="true"></i>
                    <span>Search</span>
                </button>
                @if($search !== '' || $ratingFilter !== 'all')
                    <a class="support-filter-clear" href="{{ route('admin.evaluations.overview') }}">
                        <i class="ph-light ph-x" aria-hidden="true"></i><span>Clear</span>
                    </a>
                @endif
            </form>
        </section>
    </div>

    {{-- Shared result count used by the other admin list modules. --}}
    @include('admin.components.list-result-meta', [
        'count' => $agencies->total(),
        'label' => 'agency',
    ])

    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Agency</th>
                    <th>Assigned administrator</th>
                    <th>Average rating</th>
                    <th>Evaluations</th>
                    <th>Awaiting response</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($agencies as $agency)
                    <tr>
                        <td>
                            <strong>{{ $agency->agency_name }}</strong>
                            <small class="evaluation-agency-abbreviation">{{ $agency->agency_abbreviation }}</small>
                        </td>
                        <td>
                            @forelse($agency->administrators as $administrator)
                                <span>{{ trim($administrator->first_name . ' ' . $administrator->last_name) }}</span>
                            @empty
                                <span class="badge">No assigned admin</span>
                            @endforelse
                        </td>
                        <td>{{ $agency->evaluation_count ? number_format((float) $agency->average_rating, 1) . ' / 5' : '—' }}</td>
                        <td>{{ number_format($agency->evaluation_count) }}</td>
                        <td>{{ number_format($agency->unanswered_count) }}</td>
                        <td>
                            <a class="btn btn-secondary evaluation-view-link" href="{{ route('admin.evaluations.agency', ['agency' => $agency->id] + request()->only(['search', 'rating_filter'])) }}">
                                View evaluations
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty">
                            @if($search !== '')
                                No agencies matched “{{ $search }}”. Try the full agency name or its abbreviation.
                            @else
                                No registered agencies found.
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Match the exact footer and pagination structure used by NGA & NGO Management. --}}
    <div class="footer">
        <span class="result-info">
            Showing {{ $agencies->firstItem() ?? 0 }}
            to {{ $agencies->lastItem() ?? 0 }}
            of {{ $agencies->total() }} results
        </span>

        <div class="pagination-modern" aria-label="Agency results pagination">
            @if ($agencies->onFirstPage())
                <span class="arrow disabled" aria-disabled="true">
                    <i class="ph-light ph-caret-left" aria-hidden="true"></i>
                </span>
            @else
                <a href="{{ $agencies->previousPageUrl() }}" class="arrow" aria-label="Previous page">
                    <i class="ph-light ph-caret-left" aria-hidden="true"></i>
                </a>
            @endif

            <span class="page-indicator" aria-current="page">Page {{ $agencies->currentPage() }}</span>

            @if ($agencies->hasMorePages())
                <a href="{{ $agencies->nextPageUrl() }}" class="arrow" aria-label="Next page">
                    <i class="ph-light ph-caret-right" aria-hidden="true"></i>
                </a>
            @else
                <span class="arrow disabled" aria-disabled="true">
                    <i class="ph-light ph-caret-right" aria-hidden="true"></i>
                </span>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.evaluation-admin-page{display:grid;gap:16px;min-width:0}
.admin-stats-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
.admin-stat-card{min-width:0;padding:18px;background:var(--admin-surface,#fff);border:1px solid var(--admin-border,#e5eaf1);border-radius:14px;box-shadow:var(--admin-shadow-sm,0 2px 8px rgba(15,23,42,.04))}
.admin-stat-card span{display:block;color:var(--admin-muted,#64748b);font-size:13px}
.admin-stat-card strong{display:block;margin-top:8px;color:var(--admin-text,#172033);font-size:26px;font-weight:650;line-height:1.2}
.admin-stat-card small{color:var(--admin-muted,#64748b);font-size:13px;font-weight:500}
.evaluation-overview-heading{margin:0 0 2px}
.evaluation-overview-heading h2{margin:0;color:var(--admin-text,#172033);font-size:17px;font-weight:650}
.evaluation-overview-heading p{margin:5px 0 0;color:var(--admin-muted,#64748b);font-size:13px}
.evaluation-agency-abbreviation{display:block;margin-top:2px;color:var(--admin-muted,#64748b);font-size:12px}
.evaluation-view-link{display:inline-flex;align-items:center;justify-content:center;min-height:36px;padding:8px 14px;border-radius:8px;font-size:12px;white-space:nowrap;text-decoration:none}
.evaluation-rating-filter{flex:0 1 230px}
.evaluation-rating-filter select{padding-right:30px}
.evaluation-pending-reports-link{display:flex;flex-direction:column;text-decoration:none;color:inherit;transition:border-color .16s ease,box-shadow .16s ease}
.evaluation-pending-reports-link:hover{border-color:#b8c9e2;box-shadow:0 5px 18px rgba(15,23,42,.06)}
.evaluation-pending-reports-link small{display:flex;align-items:center;gap:5px;margin-top:auto;padding-top:8px;color:var(--admin-muted,#64748b);font-size:11px}
.evaluation-admin-page .support-filter-form{display:flex;align-items:center;gap:10px;min-width:0}
.evaluation-admin-page .support-filter-submit,.evaluation-admin-page .support-filter-clear{flex:0 0 auto;min-width:92px}
.evaluation-admin-page .support-search-field{flex:1 1 340px}
@media(max-width:700px){.evaluation-admin-page .support-filter-form{display:grid;grid-template-columns:minmax(0,1fr)}.evaluation-admin-page .support-filter-form>*{width:100%;min-width:0}.evaluation-admin-page .support-filter-form .support-filter-submit,.evaluation-admin-page .support-filter-form .support-filter-clear{min-height:42px}}

@media(max-width:700px){.evaluation-rating-filter{flex:1 1 100%}.support-filter-form{flex-wrap:wrap}.support-filter-form .support-search-field{flex:1 1 100%}.support-filter-form .support-filter-submit{width:100%}}
@media(max-width:1000px){.admin-stats-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:700px){.admin-stats-grid{gap:10px}.admin-stat-card{padding:14px}.evaluation-overview-heading{margin-bottom:0}}
@media(max-width:480px){.admin-stats-grid{grid-template-columns:1fr}}
</style>
@endpush
