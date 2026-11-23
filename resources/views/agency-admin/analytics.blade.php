@extends('layouts.admin')
@section('title', 'KNOWURLOCAL | Agency Analytics')
@section('page-title', 'Agency Analytics')
@section('page-subtitle', 'Understand service demand and the requests your agency handles')
@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/admin/analytics.18b7d18f4406.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/agency-admin/analytics.dfe2d9192077.css') }}">
@endpush
@section('content')
<div class="analytics-page agency-analytics-page"
     data-analytics-root
     data-analytics-url="{{ route('agency-admin.analytics.data') }}"
     data-analytics-current-period="{{ $period }}"
     data-analytics-current-month="{{ $selectedMonth }}"
     data-analytics-initial-trend='@json($trend)'
     data-analytics-initial-status='@json($statusCounts)'
     data-analytics-initial-evaluation-ratings='@json($evaluationRatingDistribution)'
     data-analytics-initial-evaluation-trend='@json($evaluationTrend)'
     >
    <header class="analytics-header">
        <div class="analytics-header-copy">
            <span class="analytics-eyebrow">Agency operations</span>
            <h2>{{ $agency->name ?? 'Your agency' }} at a glance.</h2>
            <p>Monitor incoming citizen requests, track the current queue, and identify recurring questions that could be answered through public FAQs.</p>
        </div>
        <div class="analytics-header-actions">
            <span class="analytics-updated" data-last-updated><i class="ph-light ph-clock-counter-clockwise" aria-hidden="true"></i> Snapshot loaded just now</span>
            <details class="analytics-export-menu">
                <summary class="analytics-export-button" aria-label="Export agency analytics report">
                    <i class="ph-light ph-export" aria-hidden="true"></i><span>Export</span><i class="ph-light ph-caret-down export-caret" aria-hidden="true"></i>
                </summary>
                <div class="analytics-export-dropdown">
                    <a href="#" onclick="window.print(); return false;"><i class="ph-light ph-file-pdf" aria-hidden="true"></i><span><strong>Analytics report</strong><small>Print or save this agency report</small></span></a>
                </div>
            </details>
        </div>
    </header>

    <section class="analytics-kpi-grid agency-analytics-kpis" aria-label="Agency reporting metrics">
        <article class="analytics-kpi" data-tooltip="All non-deleted support requests recorded for this agency."><div class="analytics-kpi-icon"><i class="ph-light ph-chat-circle-text"></i></div><div><span>Support requests</span><strong data-metric="total">{{ number_format($total) }}</strong><small>All-time agency volume</small></div></article>
        <article class="analytics-kpi is-warm" data-tooltip="Requests waiting for your agency to respond or follow up. Requests awaiting citizen confirmation are excluded."><div class="analytics-kpi-icon"><i class="ph-light ph-hourglass-medium"></i></div><div><span>Needs attention</span><strong data-metric="needs_attention">{{ number_format($openCount) }}</strong><small>Current open workload</small></div></article>
        <article class="analytics-kpi is-good" data-tooltip="Requests currently marked answered after citizen confirmation."><div class="analytics-kpi-icon"><i class="ph-light ph-check-circle"></i></div><div><span>Resolution share</span><strong data-metric="response_rate">{{ $responseRate }}%</strong><small data-metric-note="resolved">{{ number_format((int)($statusCounts['answered'] ?? 0)) }} of {{ number_format($total) }} requests</small></div></article>
        <article class="analytics-kpi" data-tooltip="FAQs published for this agency and available to citizens."><div class="analytics-kpi-icon"><i class="ph-light ph-book-open-text"></i></div><div><span>Published FAQs</span><strong data-metric="faq_count">{{ number_format($faqCount) }}</strong><small>Public information coverage</small></div></article>
    </section>

    <section class="analytics-panel analytics-trend-panel">
        <div class="analytics-panel-head">
            <div><span class="analytics-eyebrow">Citizen demand</span><h3>How many requests are coming in?</h3><p data-trend-description>Requests submitted and answered during {{ $periodLabel }}. {{ $period === 'all' ? 'All-time activity is grouped by month.' : 'The chart shows daily activity.' }}</p></div>
            <div class="analytics-period-controls">
                <div class="analytics-range-tabs" role="tablist" aria-label="Trend period">
                    <button type="button" class="analytics-range-tab {{ $period === '7d' ? 'is-active' : '' }}" data-analytics-period="7d" role="tab" aria-selected="{{ $period === '7d' ? 'true' : 'false' }}">7 days</button>
                    <button type="button" class="analytics-range-tab {{ $period === '30d' ? 'is-active' : '' }}" data-analytics-period="30d" role="tab" aria-selected="{{ $period === '30d' ? 'true' : 'false' }}">30 days</button>
                    <button type="button" class="analytics-range-tab {{ $period === '90d' ? 'is-active' : '' }}" data-analytics-period="90d" role="tab" aria-selected="{{ $period === '90d' ? 'true' : 'false' }}">90 days</button>
                    <button type="button" class="analytics-range-tab {{ $period === '365d' ? 'is-active' : '' }}" data-analytics-period="365d" role="tab" aria-selected="{{ $period === '365d' ? 'true' : 'false' }}">12 months</button>
                    <button type="button" class="analytics-range-tab {{ $period === 'all' ? 'is-active' : '' }}" data-analytics-period="all" role="tab" aria-selected="{{ $period === 'all' ? 'true' : 'false' }}">All time</button>
                    <button type="button" class="analytics-range-tab {{ $period === 'month' ? 'is-active' : '' }}" data-analytics-period="month" role="tab" aria-selected="{{ $period === 'month' ? 'true' : 'false' }}">Month</button>
                </div>
                <label class="analytics-month-picker {{ $period === 'month' ? 'is-visible' : '' }}"><i class="ph-light ph-calendar-blank" aria-hidden="true"></i><select data-analytics-month aria-label="Select calendar month">
                    @for($i = 0; $i < 25; $i++)
                        @php($monthOption = now()->startOfMonth()->subMonths($i))
                        <option value="{{ $monthOption->format('Y-m') }}" @selected($selectedMonth === $monthOption->format('Y-m'))>{{ $monthOption->format('M Y') }}</option>
                    @endfor
                </select></label>
            </div>
            <div class="analytics-period-total" data-tooltip="Requests answered during the selected period."><strong data-metric="period_answered">{{ number_format(collect($trend)->sum('answered')) }}</strong><span>answered during period</span></div>
        </div>
        <div class="analytics-line-chart" data-trend-chart aria-label="Agency support request trend chart"></div>
        <div class="analytics-chart-footer"><div class="analytics-legend"><span><i class="legend-dot submitted"></i> Submitted</span><span><i class="legend-dot answered"></i> Answered</span></div><span class="analytics-chart-hint" data-trend-hint><i class="ph-light ph-cursor-click" aria-hidden="true"></i> {{ $period === "all" ? "Hover a point to see the monthly count" : "Hover a point to see the daily count" }}</span></div>
        <p class="analytics-scope-note">The selected period updates request activity and citizen satisfaction. The top summary cards show all-time or current agency totals.</p>
    </section>

    <section class="analytics-panel analytics-satisfaction-panel agency-satisfaction-panel">
        <div class="analytics-panel-head">
            <div>
                <span class="analytics-eyebrow">Citizen satisfaction</span>
                <h3>How are citizens rating your agency?</h3>
                <p>Published evaluations received during {{ $periodLabel }}. Only evaluations for your agency are included.</p>
            </div>
            <span class="analytics-info" data-tooltip="Only published, non-deleted evaluations for your agency are counted. The reporting period follows the date selector above."><i class="ph-light ph-info"></i></span>
        </div>
        <div class="analytics-satisfaction-grid">
            <article class="analytics-satisfaction-metric" data-tooltip="Published evaluations submitted to your agency during the selected period.">
                <span>Published evaluations</span>
                <strong data-metric="evaluation_period_total">{{ number_format($evaluationPeriodTotal) }}</strong>
                <small>Selected period</small>
            </article>
            <article class="analytics-satisfaction-metric" data-tooltip="The average star rating from published evaluations submitted to your agency during the selected period.">
                <span>Average rating</span>
                <strong data-metric="evaluation_period_average">{{ $evaluationPeriodAverage !== null ? number_format((float) $evaluationPeriodAverage, 1) . ' / 5' : '—' }}</strong>
                <small>Out of 5 stars</small>
            </article>
            <article class="analytics-satisfaction-metric" data-tooltip="The share of published evaluations in this period that have an official response from your agency.">
                <span>Response coverage</span>
                <strong data-metric="evaluation_period_response_rate">{{ $evaluationPeriodResponseRate }}%</strong>
                <small><span data-metric="evaluation_period_responded">{{ number_format($evaluationPeriodResponded) }}</span> responded</small>
            </article>
            <article class="analytics-satisfaction-metric is-warm" data-tooltip="Published evaluations from this period that do not yet have an official response from your agency.">
                <span>Awaiting response</span>
                <strong data-metric="evaluation_period_awaiting_response">{{ number_format($evaluationPeriodAwaitingResponse) }}</strong>
                <small>Needs your attention</small>
            </article>
        </div>
        <div class="analytics-rating-distribution">
            <div class="analytics-rating-distribution-head">
                <strong>Rating distribution</strong>
                <span>Published evaluations in selected period</span>
            </div>
            <div data-evaluation-rating-chart>
                @if($evaluationPeriodTotal > 0)
                @foreach(range(5, 1) as $rating)
                    @php($ratingCount = (int) ($evaluationRatingDistribution[$rating] ?? 0))
                    <div class="analytics-rating-row" data-rating-row="{{ $rating }}">
                        <span>{{ $rating }} <i class="ph-fill ph-star"></i></span>
                        <i class="analytics-rating-track"><em style="width:{{ $evaluationPeriodTotal ? ($ratingCount / $evaluationPeriodTotal) * 100 : 0 }}%"></em></i>
                        <b>{{ number_format($ratingCount) }}</b>
                    </div>
                @endforeach
                @else
                    <div class="analytics-empty">No published evaluations were received during this period.</div>
                @endif
            </div>
        </div>
        <div class="analytics-rating-trend">
            <div class="analytics-rating-distribution-head"><strong>Average rating trend</strong><span data-evaluation-trend-period-label>{{ $period === 'all' ? 'Monthly average' : 'Daily average' }} · published evaluations only</span></div>
            <div data-evaluation-trend-chart aria-label="Average rating trend for your agency"></div>
        </div>
    </section>

    <section class="analytics-grid analytics-grid-2 agency-analytics-lower">
        <article class="analytics-panel">
            <div class="analytics-panel-head compact"><div><span class="analytics-eyebrow">Current workload</span><h3>Where requests stand</h3><p>Current distribution of this agency's active, non-deleted requests.</p></div><span class="analytics-info" data-tooltip="Statuses reflect the current state of agency-owned support requests."><i class="ph-light ph-info"></i></span></div>
            <div class="agency-status-list">
                @foreach(['pending'=>['Needs response','pending'],'needs_follow_up'=>['Needs follow-up','followup'],'awaiting_confirmation'=>['Awaiting citizen','confirmation'],'answered'=>['Confirmed resolved','answered']] as $key=>$item)
                    @php($amount=(int)($statusCounts[$key] ?? 0))
                    <div class="agency-status-row"><div><span class="legend-dot {{ $item[1] }}"></span><span>{{ $item[0] }}</span></div><strong>{{ number_format($amount) }}</strong><span class="agency-status-track"><i style="width:{{ $total ? min(100,($amount/$total)*100) : 0 }}%"></i></span><small>{{ $total ? round(($amount/$total)*100) : 0 }}%</small></div>
                @endforeach
            </div>
        </article>
        <article class="analytics-panel">
            <div class="analytics-panel-head compact"><div><span class="analytics-eyebrow">Knowledge improvement</span><h3>FAQ opportunities</h3><p>Repeated question patterns over the last 90 days.</p></div><a class="analytics-text-link" href="{{ route('agency-admin.faqs') }}">Manage FAQs <i class="ph-light ph-arrow-up-right"></i></a></div>
            <p class="agency-analytics-note"><i class="ph-light ph-lightbulb" aria-hidden="true"></i>Review repeated questions and compare them with existing FAQs before publishing an answer.</p>
            @if($popularQuestions->isEmpty())
                <div class="analytics-empty">No repeated question patterns yet.</div>
            @else
                <div class="agency-faq-opportunities">
                    @foreach($popularQuestions as $question)
                        <div class="agency-faq-opportunity"><div><strong>{{ $question['question'] }}</strong><small>{{ $question['count'] }} related requests</small></div><span>{{ $question['count'] >= 3 ? 'High repeat' : 'Review' }}</span></div>
                    @endforeach
                </div>
            @endif
        </article>
    </section>
</div>
@endsection
@push('scripts')
<script src="{{ asset('jsfiles/agency-admin/analytics.ef4ff6c6ec48.js') }}" defer></script>
@endpush
