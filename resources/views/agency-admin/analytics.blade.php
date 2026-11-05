@extends('layouts.admin')
@section('title', 'KNOWURLOCAL | Agency Analytics')
@section('page-title', 'Agency Analytics')
@section('page-subtitle', 'Understand service demand and the requests your agency handles')
@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/admin/analytics.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/agency-admin/analytics.css') }}">
@endpush
@section('content')
<div class="analytics-page agency-analytics-page"
     data-analytics-root
     data-analytics-url="{{ route('agency-admin.analytics.data') }}"
     data-analytics-current-period="{{ $period }}"
     data-analytics-current-month="{{ $selectedMonth }}"
     data-analytics-initial-trend='@json($trend)'
     data-analytics-initial-status='@json($statusCounts)'
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
        <article class="analytics-kpi is-warm" data-tooltip="Requests waiting for your agency to respond or follow up. Requests awaiting citizen confirmation are excluded."><div class="analytics-kpi-icon"><i class="ph-light ph-hourglass-medium"></i></div><div><span>Needs attention</span><strong data-metric="needs_attention">{{ number_format($openCount) }}</strong><small>Current agency workload</small></div></article>
        <article class="analytics-kpi is-good" data-tooltip="Requests currently marked answered after citizen confirmation."><div class="analytics-kpi-icon"><i class="ph-light ph-check-circle"></i></div><div><span>Resolution share</span><strong data-metric="response_rate">{{ $responseRate }}%</strong><small data-metric-note="resolved">{{ number_format((int)($statusCounts['answered'] ?? 0)) }} of {{ number_format($total) }} requests</small></div></article>
        <article class="analytics-kpi" data-tooltip="FAQs published for this agency and available to citizens."><div class="analytics-kpi-icon"><i class="ph-light ph-book-open-text"></i></div><div><span>Published FAQs</span><strong data-metric="faq_count">{{ number_format($faqCount) }}</strong><small>Public information coverage</small></div></article>
    </section>

    <section class="analytics-panel analytics-trend-panel">
        <div class="analytics-panel-head">
            <div><span class="analytics-eyebrow">Citizen demand</span><h3>How many requests are coming in?</h3><p data-trend-description>Daily requests submitted and answered during {{ $periodLabel }}.</p></div>
            <div class="analytics-period-controls">
                <div class="analytics-range-tabs" role="tablist" aria-label="Trend period">
                    <button type="button" class="analytics-range-tab {{ $period === '7d' ? 'is-active' : '' }}" data-analytics-period="7d" role="tab" aria-selected="{{ $period === '7d' ? 'true' : 'false' }}">7 days</button>
                    <button type="button" class="analytics-range-tab {{ $period === '30d' ? 'is-active' : '' }}" data-analytics-period="30d" role="tab" aria-selected="{{ $period === '30d' ? 'true' : 'false' }}">30 days</button>
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
        <div class="analytics-chart-footer"><div class="analytics-legend"><span><i class="legend-dot submitted"></i> Submitted</span><span><i class="legend-dot answered"></i> Answered</span></div><span class="analytics-chart-hint"><i class="ph-light ph-cursor-click" aria-hidden="true"></i> Hover a point to see the daily count</span></div>
    </section>

    <section class="analytics-grid analytics-grid-2 agency-analytics-lower">
        <article class="analytics-panel">
            <div class="analytics-panel-head compact"><div><span class="analytics-eyebrow">Current workload</span><h3>Where requests stand</h3><p>Distribution of this agency's active, non-deleted requests.</p></div><span class="analytics-info" data-tooltip="Statuses reflect the current state of agency-owned support requests."><i class="ph-light ph-info"></i></span></div>
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
<script src="{{ asset('jsfiles/agency-admin/analytics.js') }}" defer></script>
@endpush
