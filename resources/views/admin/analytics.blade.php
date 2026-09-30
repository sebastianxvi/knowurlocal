@extends('layouts.admin')

@section('title', 'KNOWURLOCAL | Analytics')
@section('page-title', 'Analytics')
@section('page-subtitle', 'Operational trends, service demand, response performance, and knowledge-base health')
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
>
    <section class="analytics-report-header">
        <div class="analytics-report-heading">
            <span class="eyebrow">Operational report</span>
            <h2>Analytics at a glance</h2>
            <p>
                A focused view of support demand, response performance, service demand,
                and knowledge-base health.
            </p>
        </div>

        <details class="analytics-export-menu">
            <summary class="analytics-export-button" aria-label="Export report">
                <i class="ph-light ph-export" aria-hidden="true"></i>
                <span>Export</span>
                <i class="ph-light ph-caret-down export-caret" aria-hidden="true"></i>
            </summary>
            <div class="analytics-export-dropdown">
                <a href="{{ route('admin.analytics.export', ['period' => $period, 'month' => $selectedMonth]) }}" target="_blank" rel="noopener">
                    <i class="ph-light ph-file-pdf"></i>
                    <span><strong>Analytics report</strong><small>{{ $periodLabel }} selected period</small></span>
                </a>
                <a href="{{ route('admin.report.full', ['period' => $period, 'month' => $selectedMonth]) }}" target="_blank" rel="noopener">
                    <i class="ph-light ph-files"></i>
                    <span><strong>Full administrative report</strong><small>Dashboard + {{ $periodLabel }} analytics</small></span>
                </a>
            </div>
        </details>
    </section>

    {{-- ==================== OVERVIEW ==================== --}}

    <section class="analytics-section">
        <div class="analytics-section-heading">
            <div>
                <span class="eyebrow">Current snapshot</span>
                <h2>Support request overview</h2>
                <p>Plain-language view of today’s workload, response performance, and what still needs attention.</p>
            </div>
        </div>

        <div class="analytics-metrics analytics-metrics-six">
            <article class="analytics-metric">
                <div class="analytics-metric-icon"><i class="ph-light ph-chat-circle-text"></i></div>
                <div class="analytics-metric-content">
                    <span>Support requests received</span>
                    <strong data-metric="total_inquiries">{{ number_format($totalInquiries) }}</strong>
                    <small>All active requests currently recorded</small>
                </div>
            </article>

            <article class="analytics-metric metric-attention">
                <div class="analytics-metric-icon"><i class="ph-light ph-stack"></i></div>
                <div class="analytics-metric-content">
                    <span>Requests needing action</span>
                    <strong data-metric="open_inquiries">{{ number_format($openInquiries) }}</strong>
                    <small>Still waiting for a response or next step</small>
                </div>
            </article>

            <article class="analytics-metric metric-success">
                <div class="analytics-metric-icon"><i class="ph-light ph-check-circle"></i></div>
                <div class="analytics-metric-content">
                    <span>Requests answered</span>
                    <strong data-metric="response_rate">{{ $responseRate }}%</strong>
                    <small data-metric-note="response_rate">{{ number_format($answeredInquiries) }} of {{ number_format($totalInquiries) }} active requests</small>
                </div>
            </article>

            <article class="analytics-metric">
                <div class="analytics-metric-icon"><i class="ph-light ph-timer"></i></div>
                <div class="analytics-metric-content">
                    <span>Average response time</span>
                    <strong data-metric="average_response_time">{{ $averageResponseTime ?? '—' }}</strong>
                    <small>Average time from submission to official answer</small>
                </div>
            </article>

            <article class="analytics-metric metric-success">
                <div class="analytics-metric-icon"><i class="ph-light ph-eye"></i></div>
                <div class="analytics-metric-content">
                    <span>Answers opened</span>
                    <strong data-metric="seen_answers">{{ number_format($seenAnswers) }}</strong>
                    <small>Citizens who have opened an official answer</small>
                </div>
            </article>

            <article class="analytics-metric metric-attention">
                <div class="analytics-metric-icon"><i class="ph-light ph-eye-slash"></i></div>
                <div class="analytics-metric-content">
                    <span>Answers not yet opened</span>
                    <strong data-metric="unseen_answers">{{ number_format($unseenAnswers) }}</strong>
                    <small>Official answers still waiting for citizen review</small>
                </div>
            </article>
        </div>

        <div class="analytics-grid-two">
            {{-- Trend --}}
            <article class="analytics-card analytics-trend-card">
                <div class="analytics-card-header analytics-trend-header">
                    <div>
                        <span class="eyebrow">Selected period</span>
                        <h3>Requests submitted vs. answered</h3>
                        <p data-trend-description>Daily activity for the selected period.</p>
                    </div>
                    <div class="analytics-trend-controls" aria-label="Trend range">
                        <div class="analytics-range-tabs" role="tablist" aria-label="Trend period">
                            <button type="button" class="analytics-range-tab {{ $period === '7d' ? 'is-active' : '' }}" data-analytics-period="7d" role="tab" aria-selected="{{ $period === '7d' ? 'true' : 'false' }}">7d</button>
                            <button type="button" class="analytics-range-tab {{ $period === '30d' ? 'is-active' : '' }}" data-analytics-period="30d" role="tab" aria-selected="{{ $period === '30d' ? 'true' : 'false' }}">1mo</button>
                            <button type="button" class="analytics-range-tab {{ $period === 'month' ? 'is-active' : '' }}" data-analytics-period="month" role="tab" aria-selected="{{ $period === 'month' ? 'true' : 'false' }}">Month</button>
                        </div>
                        <label class="analytics-month-picker {{ $period === 'month' ? 'is-visible' : '' }}">
                            <span class="sr-only">Select month</span>
                            <i class="ph-light ph-calendar-blank"></i>
                            <select data-analytics-month aria-label="Select calendar month">
                                @for($i = 0; $i < 25; $i++)
                                    @php $monthOption = now()->startOfMonth()->subMonths($i); @endphp
                                    <option value="{{ $monthOption->format('Y-m') }}" @selected($selectedMonth === $monthOption->format('Y-m'))>{{ $monthOption->format('M Y') }}</option>
                                @endfor
                            </select>
                        </label>
                    </div>
                    <div class="analytics-header-stat">
                        <strong data-metric="trend_answered">{{ number_format($trendAnswered) }}</strong>
                        <span data-trend-rate-label>{{ $periodLabel }} answered</span>
                    </div>
                </div>

                <div class="analytics-chart" data-trend-chart aria-label="Support request trend chart">
                    @php
                        $chartMaximum = max(collect($inquiryTrend)->flatMap(fn ($day) => [$day['submitted'], $day['answered']])->max() ?? 0, 1);
                    @endphp
                    <div class="analytics-chart-grid">
                        @foreach($inquiryTrend as $day)
                            @php
                                $submittedHeight = ($day['submitted'] / $chartMaximum) * 100;
                                $answeredHeight = ($day['answered'] / $chartMaximum) * 100;
                            @endphp
                            <div class="analytics-chart-day" data-chart-day="{{ $day['date'] }}">
                                <div class="analytics-chart-values">
                                    <span class="analytics-chart-value submitted-value">{{ $day['submitted'] }}</span>
                                    <span class="analytics-chart-value answered-value">{{ $day['answered'] }}</span>
                                </div>
                                <div class="analytics-bars">
                                    <div class="analytics-bar analytics-bar-submitted" data-series="submitted" style="height: {{ max($submittedHeight, 2) }}%"></div>
                                    <div class="analytics-bar analytics-bar-answered" data-series="answered" style="height: {{ max($answeredHeight, 2) }}%"></div>
                                </div>
                                <span class="analytics-day-label">{{ $day['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="analytics-legend">
                        <span><i class="legend-dot submitted"></i>Submitted</span>
                        <span><i class="legend-dot answered"></i>Answered</span>
                    </div>
                </div>
            </article>

            {{-- Status --}}
            <article class="analytics-card">
                <div class="analytics-card-header">
                    <div>
                        <span class="eyebrow">Current queue</span>
                        <h3>Request status</h3>
                        <p>Where active requests are in the response workflow.</p>
                    </div>
                    <i class="ph-light ph-funnel analytics-card-header-icon"></i>
                </div>

                <div class="status-breakdown">
                    <div class="status-row">
                        <div><span class="status-dot pending"></span><strong>Pending</strong><small>Needs an administrator response</small></div>
                        <b data-status="pending">{{ number_format($pendingInquiries) }}</b>
                    </div>
                    <div class="status-row">
                        <div><span class="status-dot awaiting"></span><strong>Awaiting confirmation</strong><small>Answer sent; citizen has not confirmed</small></div>
                        <b data-status="awaiting_confirmation">{{ number_format($awaitingConfirmation) }}</b>
                    </div>
                    <div class="status-row">
                        <div><span class="status-dot followup"></span><strong>Needs follow-up</strong><small>Citizen requested more help</small></div>
                        <b data-status="needs_follow_up">{{ number_format($needsFollowUp) }}</b>
                    </div>
                    <div class="status-row">
                        <div><span class="status-dot answered"></span><strong>Answered</strong><small>Request has reached a completed state</small></div>
                        <b data-status="answered">{{ number_format($answeredInquiries) }}</b>
                    </div>
                </div>
            </article>
        </div>

        <article class="analytics-card analytics-agency-card">
            <div class="analytics-card-header">
                <div>
                    <span class="eyebrow">Service demand</span>
                    <h3>Agencies receiving the most support requests</h3>
                    <p>Shows which agencies are handling the most citizen support requests. Higher volume means more incoming demand, not better or worse performance.</p>
                </div>
                <i class="ph-light ph-buildings analytics-card-header-icon"></i>
            </div>
            <div class="analytics-ranking-list" data-support-agencies>
                @forelse($topSupportAgencies as $item)
                    <div class="analytics-ranking-item">
                        <span class="ranking-index">{{ $loop->iteration }}</span>
                        <div><strong>{{ $item->agency?->agency_name ?? 'Unassigned agency' }}</strong><small>Support requests</small></div>
                        <b>{{ number_format($item->request_count) }}</b>
                    </div>
                @empty
                    <div class="analytics-empty">No agency-linked support requests have been recorded yet.</div>
                @endforelse
            </div>
        </article>
    </section>

    {{-- ==================== KNOWLEDGE BASE ==================== --}}
    <section class="analytics-section">
        <div class="analytics-section-heading">
            <div>
                <span class="eyebrow">Citizen information</span>
                <h2>Chatbot and FAQ performance</h2>
                <p>Understand how often citizens get answers from the knowledge base and where the content needs improvement.</p>
            </div>
        </div>

        <div class="analytics-metrics analytics-metrics-six">
            <article class="analytics-metric">
                <div class="analytics-metric-icon"><i class="ph-light ph-chats-circle"></i></div>
                <div class="analytics-metric-content"><span>Chatbot questions</span><strong data-metric="chatbot_interactions">{{ number_format($totalChatbotInteractions) }}</strong><small>Total citizen questions logged by the chatbot</small></div>
            </article>
            <article class="analytics-metric metric-success">
                <div class="analytics-metric-icon"><i class="ph-light ph-book-open-text"></i></div>
                <div class="analytics-metric-content"><span>Answered from FAQs</span><strong data-metric="faq_answer_rate">{{ $faqAnswerRate }}%</strong><small data-metric-note="faq_answer_rate">{{ number_format($faqAnswered) }} questions answered using an FAQ</small></div>
            </article>
            <article class="analytics-metric metric-attention">
                <div class="analytics-metric-icon"><i class="ph-light ph-warning-circle"></i></div>
                <div class="analytics-metric-content"><span>Questions without an FAQ match</span><strong data-metric="fallback_rate">{{ $fallbackRate }}%</strong><small data-metric-note="fallback_rate">{{ number_format($fallbackQuestions) }} questions needed another answer path</small></div>
            </article>
            <article class="analytics-metric">
                <div class="analytics-metric-icon"><i class="ph-light ph-chat-centered-dots"></i></div>
                <div class="analytics-metric-content"><span>Questions needing clarification</span><strong data-metric="clarification_questions">{{ number_format($clarificationQuestions) }}</strong><small>Citizen questions that needed more context before answering</small></div>
            </article>
            <article class="analytics-metric">
                <div class="analytics-metric-icon"><i class="ph-light ph-buildings"></i></div>
                <div class="analytics-metric-content"><span>Complete agency profiles</span><strong data-metric="complete_agencies">{{ number_format($completeAgencies) }}</strong><small data-metric-note="complete_agencies">of {{ number_format($totalAgencies) }} active agency records</small></div>
            </article>
            <article class="analytics-metric metric-attention">
                <div class="analytics-metric-icon"><i class="ph-light ph-file-text"></i></div>
                <div class="analytics-metric-content"><span>FAQs needing completion</span><strong data-metric="incomplete_faqs">{{ number_format($incompleteFaqs) }}</strong><small data-metric-note="incomplete_faqs">{{ number_format($completeFaqs) }} complete of {{ number_format($totalFaqs) }} bilingual FAQs</small></div>
            </article>
        </div>

        <div class="analytics-grid-two">
            <article class="analytics-card">
                <div class="analytics-card-header">
                    <div><span class="eyebrow">Knowledge usage</span><h3>FAQs used most often</h3><p>Shows which published FAQ answers are actually being used to answer citizen questions.</p></div>
                    <i class="ph-light ph-book-open analytics-card-header-icon"></i>
                </div>
                <div class="analytics-ranking-list" data-popular-faqs>
                    @forelse($popularFaqs as $item)
                        <div class="analytics-ranking-item">
                            <span class="ranking-index">{{ $loop->iteration }}</span>
                            <div><strong>{{ $item->faq?->question ?? 'FAQ no longer available' }}</strong><small>{{ $item->faq?->agency?->agency_name ?? 'No agency' }}</small></div>
                            <b>{{ number_format($item->usage_count) }}</b>
                        </div>
                    @empty
                        <div class="analytics-empty">No FAQ usage has been recorded yet.</div>
                    @endforelse
                </div>
            </article>

            <article class="analytics-card">
                <div class="analytics-card-header">
                    <div><span class="eyebrow">Citizen interest</span><h3>Agency topics asked about most</h3><p>Shows which agency topics citizens search for most through the chatbot.</p></div>
                    <i class="ph-light ph-magnifying-glass analytics-card-header-icon"></i>
                </div>
                <div class="analytics-ranking-list" data-popular-agencies>
                    @forelse($popularAgencies as $item)
                        <div class="analytics-ranking-item">
                            <span class="ranking-index">{{ $loop->iteration }}</span>
                            <div><strong>{{ $item->agency?->agency_name ?? 'Agency no longer available' }}</strong><small>Chatbot questions</small></div>
                            <b>{{ number_format($item->interaction_count) }}</b>
                        </div>
                    @empty
                        <div class="analytics-empty">No agency-related chatbot questions have been recorded yet.</div>
                    @endforelse
                </div>
            </article>
        </div>
    </section>

    {{-- ==================== FAQ ANSWER FEEDBACK ==================== --}}
    <section class="analytics-section">
        <div class="analytics-section-heading">
            <div>
                <span class="eyebrow">Continuous improvement</span>
                <h2>FAQ answer feedback</h2>
                <p>Ratings are aggregated per FAQ. Review a question from FAQ Management to inspect individual responses.</p>
            </div>
            <a class="analytics-export-button" href="{{ route('faqs.index') }}">
                <i class="ph-light ph-arrow-square-out" aria-hidden="true"></i>
                Manage FAQs
            </a>
        </div>

        <div class="analytics-metrics analytics-metrics-six">
            <article class="analytics-metric metric-success">
                <div class="analytics-metric-icon"><i class="ph-light ph-thumbs-up"></i></div>
                <div class="analytics-metric-content"><span>Total likes</span><strong data-metric="faq_feedback_helpful">{{ number_format($faqFeedbackHelpful) }}</strong><small>Helpful ratings across all FAQs</small></div>
            </article>
            <article class="analytics-metric metric-attention">
                <div class="analytics-metric-icon"><i class="ph-light ph-thumbs-down"></i></div>
                <div class="analytics-metric-content"><span>Total dislikes</span><strong data-metric="faq_feedback_not_helpful">{{ number_format($faqFeedbackNotHelpful) }}</strong><small>Negative ratings across all FAQs</small></div>
            </article>
            <a class="analytics-metric metric-attention" href="{{ route('faqs.index', ['feedback' => 'needs_review']) }}">
                <div class="analytics-metric-icon"><i class="ph-light ph-warning-circle"></i></div>
                <div class="analytics-metric-content"><span>FAQs needing review</span><strong data-metric="faq_feedback_needs_review">{{ number_format($faqFeedbackNeedsReview) }}</strong><small>Enough ratings and dislikes exceed likes</small></div>
            </a>
            <article class="analytics-metric">
                <div class="analytics-metric-icon"><i class="ph-light ph-chat-circle-dots"></i></div>
                <div class="analytics-metric-content"><span>Total ratings</span><strong data-metric="faq_feedback_total">{{ number_format($faqFeedbackTotal) }}</strong><small>All feedback responses recorded</small></div>
            </article>
        </div>
    </section>

    {{-- ==================== TEAM + DATA HEALTH ==================== --}}
    <section class="analytics-section analytics-section-last">
        <div class="analytics-section-heading">
            <div>
                <span class="eyebrow">Data & team health</span>
                <h2>Information quality and team workload</h2>
                <p>Quick checks for incomplete public information and administrator-to-administrator work that still needs attention.</p>
            </div>
        </div>

        <div class="analytics-health-grid">
            <article class="analytics-health-card">
                <div class="health-icon"><i class="ph-light ph-buildings"></i></div>
                <div><span>Agency profiles</span><strong><span data-metric="complete_agencies">{{ number_format($completeAgencies) }}</span> / {{ number_format($totalAgencies) }}</strong><small>profiles with all required public information</small></div>
                <b class="health-badge" data-health="agencies">{{ $totalAgencies > 0 ? round(($completeAgencies / $totalAgencies) * 100) : 0 }}%</b>
            </article>
            <article class="analytics-health-card">
                <div class="health-icon"><i class="ph-light ph-files"></i></div>
                <div><span>FAQ content</span><strong><span data-metric="complete_faqs">{{ number_format($completeFaqs) }}</span> / {{ number_format($totalFaqs) }}</strong><small>FAQs complete in English and Filipino</small></div>
                <b class="health-badge" data-health="faqs">{{ $totalFaqs > 0 ? round(($completeFaqs / $totalFaqs) * 100) : 0 }}%</b>
            </article>
            <article class="analytics-health-card">
                <div class="health-icon"><i class="ph-light ph-users-three"></i></div>
                <div><span>Team tasks still open</span><strong data-metric="collaboration_open">{{ number_format($collaborationOpen) }}</strong><small>handoffs or review work not finished yet</small></div>
                <b class="health-badge" data-health="collaboration">{{ number_format($collaborationOverdue) }} overdue</b>
            </article>
            <article class="analytics-health-card">
                <div class="health-icon"><i class="ph-light ph-check-square"></i></div>
                <div><span>Team tasks completed</span><strong data-metric="collaboration_completed">{{ number_format($collaborationCompleted) }}</strong><small>completed during the selected trend period</small></div>
                <b class="health-badge">Selected period</b>
            </article>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('jsfiles/admin/analytics.js') }}"></script>
@endpush
