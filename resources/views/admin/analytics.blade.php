@extends('layouts.admin')

@section('title', 'KNOWURLOCAL | Analytics')
@section('page-title', 'Analytics')
@section('page-subtitle', 'A clearer view of service demand, response performance, chatbot coverage, and data quality')
@section('admin-page', 'analytics')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/admin/analytics.css') }}">
@endpush

@section('content')
<div
    class="analytics-page"
    data-analytics-root
    data-analytics-url="{{ route('admin.analytics.data') }}"
    data-analytics-current-period="{{ $period }}"
    data-analytics-current-month="{{ $selectedMonth }}"
    data-analytics-initial-trend='@json($inquiryTrend)'
    data-analytics-initial-status='@json($queueStatusCounts)'
    data-analytics-initial-chatbot-methods='@json($chatbotMatchMethods)'
    data-analytics-initial-feedback='@json($feedbackBreakdown)'
>
    <header class="analytics-header">
        <div class="analytics-header-copy">
            <span class="analytics-eyebrow">Operations intelligence</span>
            <h2>Know what needs attention.</h2>
            <p>Track citizen demand, how quickly requests move forward, how well the chatbot finds answers, and where your knowledge base needs work.</p>
        </div>
        <div class="analytics-header-actions">
            <span class="analytics-updated" data-last-updated>
                <i class="ph-light ph-clock-counter-clockwise" aria-hidden="true"></i>
                Snapshot loaded just now
            </span>
            <details class="analytics-export-menu">
                <summary class="analytics-export-button" aria-label="Export report">
                    <i class="ph-light ph-export" aria-hidden="true"></i><span>Export</span><i class="ph-light ph-caret-down export-caret" aria-hidden="true"></i>
                </summary>
                <div class="analytics-export-dropdown">
                    <a href="{{ route('admin.analytics.export', ['period' => $period, 'month' => $selectedMonth]) }}" target="_blank" rel="noopener">
                        <i class="ph-light ph-file-pdf"></i><span><strong>Analytics PDF</strong><small>{{ $periodLabel }} selected period</small></span>
                    </a>
                    <a href="{{ route('admin.report.full', ['period' => $period, 'month' => $selectedMonth]) }}" target="_blank" rel="noopener">
                        <i class="ph-light ph-files"></i><span><strong>Full PDF report</strong><small>Dashboard + {{ $periodLabel }} analytics</small></span>
                    </a>
                </div>
            </details>
        </div>
    </header>



    <section class="analytics-kpi-grid">
        <article class="analytics-kpi" data-tooltip="The total number of citizen support requests recorded so far. This is the overall workload, not just the period shown in the chart.">
            <div class="analytics-kpi-icon"><i class="ph-light ph-chat-circle-text"></i></div>
            <div><span>Support requests</span><strong data-metric="total_inquiries">{{ number_format($totalInquiries) }}</strong><small>Total requests recorded</small></div>
        </article>
        <article class="analytics-kpi is-warm" data-tooltip="Requests that are not finished yet. These may still need an admin response, a citizen confirmation, or a follow-up.">
            <div class="analytics-kpi-icon"><i class="ph-light ph-hourglass-medium"></i></div>
            <div><span>Needs attention</span><strong data-metric="open_inquiries">{{ number_format($openInquiries) }}</strong><small>Not finished yet</small></div>
        </article>
        <article class="analytics-kpi is-good" data-tooltip="The share of support requests that have reached the completed "Answered" status.">
            <div class="analytics-kpi-icon"><i class="ph-light ph-check-circle"></i></div>
            <div><span>Answered</span><strong data-metric="response_rate">{{ $responseRate }}%</strong><small data-metric-note="response_rate">{{ number_format($answeredInquiries) }} of {{ number_format($totalInquiries) }}</small></div>
        </article>
        <article class="analytics-kpi" data-tooltip="The average time from a citizen submitting a request to an official answer being recorded.">
            <div class="analytics-kpi-icon"><i class="ph-light ph-timer"></i></div>
            <div><span>Average response time</span><strong data-metric="average_response_time">{{ $averageResponseTime ?? '—' }}</strong><small>From request received to answer sent</small></div>
        </article>
    </section>

    <section class="analytics-panel analytics-trend-panel">
        <div class="analytics-panel-head">
            <div>
                <span class="analytics-eyebrow">Citizen demand</span>
                <h3>How many requests are coming in?</h3>
                <p data-trend-description>Shows how many requests came in and how many were answered each day during {{ $periodLabel }}.</p>
            </div>
            <div class="analytics-period-controls">
                <div class="analytics-range-tabs" role="tablist" aria-label="Trend period">
                    <button type="button" class="analytics-range-tab {{ $period === '7d' ? 'is-active' : '' }}" data-analytics-period="7d" role="tab" aria-selected="{{ $period === '7d' ? 'true' : 'false' }}">7 days</button>
                    <button type="button" class="analytics-range-tab {{ $period === '30d' ? 'is-active' : '' }}" data-analytics-period="30d" role="tab" aria-selected="{{ $period === '30d' ? 'true' : 'false' }}">30 days</button>
                    <button type="button" class="analytics-range-tab {{ $period === 'month' ? 'is-active' : '' }}" data-analytics-period="month" role="tab" aria-selected="{{ $period === 'month' ? 'true' : 'false' }}">Month</button>
                </div>
                <label class="analytics-month-picker {{ $period === 'month' ? 'is-visible' : '' }}">
                    <i class="ph-light ph-calendar-blank" aria-hidden="true"></i>
                    <select data-analytics-month aria-label="Select calendar month">
                        @for($i = 0; $i < 25; $i++)
                            @php $monthOption = now()->startOfMonth()->subMonths($i); @endphp
                            <option value="{{ $monthOption->format('Y-m') }}" @selected($selectedMonth === $monthOption->format('Y-m'))>{{ $monthOption->format('M Y') }}</option>
                        @endfor
                    </select>
                </label>
            </div>
            <div class="analytics-period-total" data-tooltip="The number of requests that received an official answer during the selected period.">
                <strong data-metric="trend_answered">{{ number_format($trendAnswered) }}</strong><span>answered during period</span>
            </div>
        </div>
        <div class="analytics-line-chart" data-trend-chart aria-label="Support request trend chart"></div>
        <div class="analytics-chart-footer">
            <div class="analytics-legend"><span><i class="legend-dot submitted"></i> Submitted</span><span><i class="legend-dot answered"></i> Answered</span></div>
            <span class="analytics-chart-hint"><i class="ph-light ph-cursor-click" aria-hidden="true"></i> Hover a point to see what happened that day</span>
        </div>
    </section>

    <section class="analytics-grid analytics-grid-2">
        <article class="analytics-panel analytics-donut-panel">
            <div class="analytics-panel-head compact">
                <div><span class="analytics-eyebrow">Current workload</span><h3>What still needs action?</h3><p>See which requests are still in progress and which ones are finished.</p></div>
                <span class="analytics-info" data-tooltip="A request can stay open while waiting for an admin response, a citizen confirmation, or a follow-up. "Answered" means the request is complete."><i class="ph-light ph-info"></i></span>
            </div>
            <div class="analytics-donut-layout">
                <div class="analytics-donut" data-status-chart></div>
                <div class="analytics-donut-legend" data-status-legend>
                    @foreach([
                        ['key'=>'pending','label'=>'Pending','class'=>'pending'],
                        ['key'=>'awaiting_confirmation','label'=>'Waiting for confirmation','class'=>'confirmation'],
                        ['key'=>'needs_follow_up','label'=>'Needs follow-up','class'=>'followup'],
                        ['key'=>'answered','label'=>'Answered','class'=>'answered'],
                    ] as $item)
                        <div class="analytics-legend-row" data-status-row="{{ $item['key'] }}" data-tooltip="{{ $item['label'] }} requests in the current system. Hover to see how many are in this stage.">
                            <span><i class="legend-dot {{ $item['class'] }}"></i>{{ $item['label'] }}</span><strong data-status="{{ $item['key'] }}">{{ number_format($queueStatusCounts[$item['key']] ?? 0) }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>

        <article class="analytics-panel analytics-attention-panel">
            <div class="analytics-panel-head compact">
                <div><span class="analytics-eyebrow">Have citizens seen their answers?</span><h3>Have citizens seen their answers?</h3><p>Shows whether citizens have opened the official answers sent to them.</p></div>
            </div>
            <div class="analytics-visibility">
                <div class="visibility-main">
                    <div class="visibility-ring" data-visibility-ring style="--value: {{ $answeredInquiries > 0 ? round(($seenAnswers / $answeredInquiries) * 100) : 0 }}%"><span data-visibility-rate>{{ $answeredInquiries > 0 ? round(($seenAnswers / $answeredInquiries) * 100) : 0 }}%</span></div>
                    <div><strong>Answers opened by citizens</strong><p data-visibility-note>{{ number_format($seenAnswers) }} opened · {{ number_format($unseenAnswers) }} still waiting</p></div>
                </div>
                <div class="visibility-bars">
                    <div class="visibility-bar"><span>Opened</span><b data-metric="seen_answers">{{ number_format($seenAnswers) }}</b><i><em data-visibility-opened style="width:{{ $answeredInquiries > 0 ? min(100, ($seenAnswers / $answeredInquiries) * 100) : 0 }}%"></em></i></div>
                    <div class="visibility-bar"><span>Not opened</span><b data-metric="unseen_answers">{{ number_format($unseenAnswers) }}</b><i><em data-visibility-unopened style="width:{{ $answeredInquiries > 0 ? min(100, ($unseenAnswers / $answeredInquiries) * 100) : 0 }}%"></em></i></div>
                </div>
            </div>
        </article>
    </section>

    <section class="analytics-panel">
        <div class="analytics-panel-head">
            <div><span class="analytics-eyebrow">Where is demand coming from?</span><h3>Which agencies receive the most requests?</h3><p>This shows where citizens are asking for the most help. A higher number means more demand, not better or worse agency performance.</p></div>
            <span class="analytics-info" data-tooltip="This is a demand list, not a performance ranking. It helps admins see which agencies may be handling more citizen questions."><i class="ph-light ph-info"></i></span>
        </div>
        <div class="analytics-ranking-bars" data-support-agencies>
            @forelse($topSupportAgencies as $item)
                <div class="analytics-ranking-bar" data-tooltip="{{ number_format($item->request_count) }} support requests linked to {{ $item->agency?->agency_name ?? 'Unassigned agency' }}.">
                    <div class="rank-label"><span>{{ $loop->iteration }}</span><strong>{{ $item->agency?->agency_name ?? 'Unassigned agency' }}</strong><b>{{ number_format($item->request_count) }}</b></div>
                    <i><em style="width:{{ $topSupportAgencies->max('request_count') ? ($item->request_count / $topSupportAgencies->max('request_count')) * 100 : 0 }}%"></em></i>
                </div>
            @empty
                <div class="analytics-empty">No agency-linked support requests have been recorded yet.</div>
            @endforelse
        </div>
    </section>

    <section class="analytics-section-heading standalone-heading">
        <div><span class="analytics-eyebrow">Chatbot & FAQ health</span><h2>Is the chatbot finding the right answers?</h2><p>These numbers show how often the chatbot finds an existing FAQ and where the FAQ information may need improvement.</p></div>
    </section>

    <section class="analytics-kpi-grid analytics-kpi-grid-3">
        <article class="analytics-kpi" data-tooltip="The total number of questions and chatbot conversations recorded."><div class="analytics-kpi-icon"><i class="ph-light ph-chats-circle"></i></div><div><span>Chatbot interactions</span><strong data-metric="chatbot_interactions">{{ number_format($totalChatbotInteractions) }}</strong><small>Total conversations recorded</small></div></article>
        <article class="analytics-kpi is-good" data-tooltip="The share of questions where the chatbot found a published FAQ and used its saved answer."><div class="analytics-kpi-icon"><i class="ph-light ph-sparkle"></i></div><div><span>FAQ answer coverage</span><strong data-metric="faq_answer_rate">{{ $faqAnswerRate }}%</strong><small data-metric-note="faq_answer_rate">{{ number_format($faqAnswered) }} Questions answered from an FAQ</small></div></article>
        <article class="analytics-kpi is-warm" data-tooltip="The share of questions for which the chatbot could not find a suitable published FAQ to use."><div class="analytics-kpi-icon"><i class="ph-light ph-warning-circle"></i></div><div><span>Questions without a matching FAQ</span><strong data-metric="fallback_rate">{{ $fallbackRate }}%</strong><small data-metric-note="fallback_rate">{{ number_format($fallbackQuestions) }} Questions without a matching FAQ</small></div></article>
        <article class="analytics-kpi" data-tooltip="Questions where the chatbot needs more information before it can give a useful answer."><div class="analytics-kpi-icon"><i class="ph-light ph-chat-centered-dots"></i></div><div><span>Questions needing more detail</span><strong data-metric="clarification_questions">{{ number_format($clarificationQuestions) }}</strong><small>May need a clearer or more detailed question</small></div></article>
        <article class="analytics-kpi" data-tooltip="Active agencies whose public information is complete enough to be shown properly to citizens."><div class="analytics-kpi-icon"><i class="ph-light ph-buildings"></i></div><div><span>Complete agency profiles</span><strong data-metric="complete_agencies">{{ number_format($completeAgencies) }}</strong><small data-metric-note="complete_agencies">of {{ number_format($totalAgencies) }} active agencies</small></div></article>
        <article class="analytics-kpi is-warm" data-tooltip="FAQs that are missing required information, such as English/Filipino content or an agency assignment."><div class="analytics-kpi-icon"><i class="ph-light ph-file-text"></i></div><div><span>FAQs needing completion</span><strong data-metric="incomplete_faqs">{{ number_format($incompleteFaqs) }}</strong><small data-metric-note="incomplete_faqs">{{ number_format($completeFaqs) }} complete of {{ number_format($totalFaqs) }}</small></div></article>
    </section>

    <section class="analytics-grid analytics-grid-2">
        <article class="analytics-panel">
            <div class="analytics-panel-head compact"><div><span class="analytics-eyebrow">How the chatbot finds answers</span><h3>How did the chatbot find the FAQ?</h3><p>This shows how an existing FAQ was found. "Meaning-based" means the question and FAQ have the same meaning even when the wording is different.</p></div></div>
            <div class="analytics-method-chart" data-chatbot-method-chart></div>
            <div class="analytics-method-note">Hover a method to see how often it was used and what it means.</div>
        </article>
        <article class="analytics-panel">
            <div class="analytics-panel-head compact"><div><span class="analytics-eyebrow">Citizen feedback</span><h3>How do citizens rate the answers?</h3><p>Helpful ratings show what is working; negative ratings point to answers that may need review.</p></div><a class="analytics-inline-link" href="{{ route('faqs.index', ['feedback' => 'needs_review']) }}">Review flagged FAQs <i class="ph-light ph-arrow-up-right"></i></a></div>
            <div class="analytics-feedback-layout">
                <div class="analytics-donut feedback" data-feedback-chart></div>
                <div class="analytics-donut-legend" data-feedback-legend>
                    <div class="analytics-legend-row" data-tooltip="Helpful ratings submitted by citizens."><span><i class="legend-dot helpful"></i>Helpful</span><strong data-metric="faq_feedback_helpful">{{ number_format($faqFeedbackHelpful) }}</strong></div>
                    <div class="analytics-legend-row" data-tooltip="Negative ratings submitted by citizens."><span><i class="legend-dot not-helpful"></i>Not helpful</span><strong data-metric="faq_feedback_not_helpful">{{ number_format($faqFeedbackNotHelpful) }}</strong></div>
                    <div class="analytics-feedback-summary"><strong data-metric="faq_feedback_total">{{ number_format($faqFeedbackTotal) }}</strong><span>total ratings</span></div>
                    <a class="analytics-review-badge" href="{{ route('faqs.index', ['feedback' => 'needs_review']) }}" data-tooltip="FAQs where the feedback threshold indicates that administrator review is warranted."><i class="ph-light ph-warning-circle"></i><span>Needs review</span><b data-metric="faq_feedback_needs_review">{{ number_format($faqFeedbackNeedsReview) }}</b></a>
                </div>
            </div>
        </article>
    </section>

    <section class="analytics-grid analytics-grid-2">
        <article class="analytics-panel">
            <div class="analytics-panel-head compact"><div><span class="analytics-eyebrow">Frequently used answers</span><h3>Which FAQs are used most often?</h3><p>These are answers the chatbot uses often. They are good places to review wording and keywords.</p></div></div>
            <div class="analytics-ranking-list" data-popular-faqs>
                @forelse($popularFaqs as $item)
                    <div class="analytics-list-item" data-tooltip="Selected {{ number_format($item->usage_count) }} times as the source of an answered chatbot response."><span class="list-rank">{{ $loop->iteration }}</span><div><strong>{{ $item->faq?->question ?? 'FAQ no longer available' }}</strong><small>{{ $item->faq?->agency?->agency_name ?? 'No agency' }}</small></div><b>{{ number_format($item->usage_count) }}</b></div>
                @empty <div class="analytics-empty">No FAQ usage has been recorded yet.</div> @endforelse
            </div>
        </article>
        <article class="analytics-panel">
            <div class="analytics-panel-head compact"><div><span class="analytics-eyebrow">What are citizens asking about?</span><h3>Which agency topics come up most?</h3><p>Shows which agencies are mentioned most often in chatbot questions.</p></div></div>
            <div class="analytics-ranking-list" data-popular-agencies>
                @forelse($popularAgencies as $item)
                    <div class="analytics-list-item" data-tooltip="{{ number_format($item->interaction_count) }} chatbot questions were associated with this agency."><span class="list-rank">{{ $loop->iteration }}</span><div><strong>{{ $item->agency?->agency_name ?? 'Agency no longer available' }}</strong><small>Chatbot questions</small></div><b>{{ number_format($item->interaction_count) }}</b></div>
                @empty <div class="analytics-empty">No agency-related chatbot questions have been recorded yet.</div> @endforelse
            </div>
        </article>
    </section>

    <section class="analytics-panel analytics-health-panel">
        <div class="analytics-panel-head">
            <div><span class="analytics-eyebrow">Keeping information up to date</span><h3>What may need attention next?</h3><p>These are simple checks for keeping agency information, FAQs, and admin work up to date.</p></div>
        </div>
        <div class="analytics-health-grid">
            <article class="analytics-health-card" data-tooltip="The share of active agency profiles that have the information citizens need, such as location, services, and hours."><i class="ph-light ph-buildings"></i><div><span>Agency information</span><strong><span data-metric="complete_agencies">{{ number_format($completeAgencies) }}</span> / {{ number_format($totalAgencies) }}</strong><small>complete public information</small></div><b data-health="agencies">{{ $totalAgencies ? round(($completeAgencies / $totalAgencies) * 100) : 0 }}%</b></article>
            <article class="analytics-health-card" data-tooltip="The share of FAQs with the required English and Filipino information and a linked agency."><i class="ph-light ph-files"></i><div><span>FAQ information</span><strong><span data-metric="complete_faqs">{{ number_format($completeFaqs) }}</span> / {{ number_format($totalFaqs) }}</strong><small>complete English + Filipino information</small></div><b data-health="faqs">{{ $totalFaqs ? round(($completeFaqs / $totalFaqs) * 100) : 0 }}%</b></article>
            <article class="analytics-health-card" data-tooltip="Open pieces of work assigned between administrators. Overdue work is shown separately."><i class="ph-light ph-users-three"></i><div><span>Open team tasks</span><strong data-metric="collaboration_open">{{ number_format($collaborationOpen) }}</strong><small>work assigned between admins</small></div><b data-health="collaboration">{{ number_format($collaborationOverdue) }} overdue</b></article>
            <article class="analytics-health-card" data-tooltip="Team tasks that were completed during the selected period."><i class="ph-light ph-check-square"></i><div><span>Finished team tasks</span><strong data-metric="collaboration_completed">{{ number_format($collaborationCompleted) }}</strong><small>finished during selected period</small></div><b>Period</b></article>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('jsfiles/admin/analytics.js') }}"></script>
@endpush
