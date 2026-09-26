@extends('layouts.admin')

@section('title', 'KNOWURLOCAL | Analytics')
@section('page-title', 'Analytics')
@section('page-subtitle', 'Operational insights for inquiries, knowledge and collaboration')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/admin/dashboard.css') }}">
@endpush

@section('content')
<div class="dashboard-content analytics-workspace">
    <section class="dashboard-section">
        <div class="section-heading">
            <div class="section-heading-main">
                <div class="section-heading-icon analytics-heading-icon">
                    <i class="ph-light ph-chart-line-up"></i>
                </div>
                <div class="section-heading-copy">
                    <span class="eyebrow">Analytics workspace</span>
                    <h2>System performance</h2>
                    <p>Use the same server-calculated metrics as the dashboard, with more room for analysis.</p>
                </div>
            </div>
            <a class="section-action" href="{{ route('admin.dashboard') }}">
                Back to dashboard <i class="ph-light ph-arrow-right"></i>
            </a>
        </div>

        <div class="analytics-metrics">
            <div class="analytics-metric analytics-response"><div class="analytics-metric-icon"><i class="ph-light ph-chart-donut"></i></div><div class="analytics-metric-content"><span>Response rate</span><strong>{{ $responseRate }}%</strong><small>{{ number_format($answeredInquiries) }} of {{ number_format($totalInquiries) }} inquiries answered</small></div></div>
            <div class="analytics-metric analytics-time"><div class="analytics-metric-icon"><i class="ph-light ph-timer"></i></div><div class="analytics-metric-content"><span>Average response</span><strong>{{ $averageResponseTime ?? '—' }}</strong><small>Submission to official answer</small></div></div>
            <div class="analytics-metric chatbot-answer-rate"><div class="analytics-metric-icon"><i class="ph-light ph-book-open-text"></i></div><div class="analytics-metric-content"><span>FAQ answer rate</span><strong>{{ $faqAnswerRate }}%</strong><small>{{ number_format($faqAnswered) }} FAQ-backed answers</small></div></div>
            <div class="analytics-metric chatbot-fallback-rate"><div class="analytics-metric-icon"><i class="ph-light ph-warning-circle"></i></div><div class="analytics-metric-content"><span>Fallback rate</span><strong>{{ $fallbackRate }}%</strong><small>{{ number_format($fallbackQuestions) }} knowledge gaps</small></div></div>
        </div>
    </section>

    <section class="dashboard-section">
        <div class="analytics-chart-card">
            <div class="analytics-chart-header"><div><span class="eyebrow">Last 7 days</span><h3>Inquiry activity</h3></div><span class="analytics-period">Submitted vs answered</span></div>
            @php
                $chartMaximum = max(collect($inquiryTrend)->flatMap(fn ($day) => [$day['submitted'], $day['answered']])->max() ?? 0, 1);
            @endphp
            <div class="analytics-chart">
                <div class="analytics-chart-grid">
                    @foreach($inquiryTrend as $day)
                        @php
                            $submittedHeight = ($day['submitted'] / $chartMaximum) * 100;
                            $answeredHeight = ($day['answered'] / $chartMaximum) * 100;
                        @endphp
                        <div class="analytics-chart-day">
                            <div class="analytics-bars">
                                <div class="analytics-bar analytics-bar-submitted" style="height:{{ max($submittedHeight, 3) }}%" title="{{ $day['submitted'] }} submitted"></div>
                                <div class="analytics-bar analytics-bar-answered" style="height:{{ max($answeredHeight, 3) }}%" title="{{ $day['answered'] }} answered"></div>
                            </div>
                            <span class="analytics-day-label">{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="analytics-legend"><span><i class="analytics-legend-dot submitted"></i>Submitted</span><span><i class="analytics-legend-dot answered"></i>Answered</span></div>
            </div>
        </div>
    </section>

    <section class="dashboard-section">
        <div class="chatbot-analytics-grid">
            <article class="chatbot-analytics-card">
                <div class="chatbot-card-header"><div><span class="eyebrow">Knowledge base</span><h3>Most used FAQs</h3></div><div class="chatbot-card-icon"><i class="ph-light ph-book-open-text"></i></div></div>
                <div class="chatbot-ranking-list">
                    @forelse($popularFaqs as $item)
                        <div class="chatbot-ranking-item"><div class="chatbot-ranking-main"><strong>{{ $item->faq?->question ?? 'FAQ no longer available' }}</strong><span>{{ $item->faq?->agency?->agency_name ?? 'Unassigned agency' }}</span></div><span class="chatbot-ranking-count">{{ number_format($item->usage_count) }}</span></div>
                    @empty
                        <div class="chatbot-analytics-empty"><i class="ph-light ph-book-open"></i><span>No FAQ usage has been recorded yet.</span></div>
                    @endforelse
                </div>
            </article>

            <article class="chatbot-analytics-card">
                <div class="chatbot-card-header"><div><span class="eyebrow">Agency demand</span><h3>Most requested agencies</h3></div><div class="chatbot-card-icon"><i class="ph-light ph-buildings"></i></div></div>
                <div class="chatbot-ranking-list">
                    @forelse($popularAgencies as $item)
                        <div class="chatbot-ranking-item"><div class="chatbot-ranking-main"><strong>{{ $item->agency?->agency_name ?? 'Agency no longer available' }}</strong><span>Chatbot interactions</span></div><span class="chatbot-ranking-count">{{ number_format($item->interaction_count) }}</span></div>
                    @empty
                        <div class="chatbot-analytics-empty"><i class="ph-light ph-buildings"></i><span>No agency demand has been recorded yet.</span></div>
                    @endforelse
                </div>
            </article>
        </div>
    </section>

    <section class="dashboard-section">
        <div class="section-heading"><div class="section-heading-main"><div class="section-heading-icon"><i class="ph-light ph-users-three"></i></div><div class="section-heading-copy"><span class="eyebrow">Collaboration</span><h2>Support workload</h2><p>See where pending citizen requests are currently assigned.</p></div></div></div>
        <div class="analytics-metrics">
            <div class="analytics-metric"><div class="analytics-metric-icon"><i class="ph-light ph-user-plus"></i></div><div class="analytics-metric-content"><span>Unassigned</span><strong>{{ number_format($unassignedPending) }}</strong><small>Pending requests without an owner</small></div></div>
            <div class="analytics-metric"><div class="analytics-metric-icon"><i class="ph-light ph-users-three"></i></div><div class="analytics-metric-content"><span>Assigned</span><strong>{{ number_format($assignedPending) }}</strong><small>Pending requests in collaboration</small></div></div>
            <div class="analytics-metric"><div class="analytics-metric-icon"><i class="ph-light ph-user-circle"></i></div><div class="analytics-metric-content"><span>My queue</span><strong>{{ number_format($myAssignedPending) }}</strong><small>Pending requests assigned to you</small></div></div>
            <div class="analytics-metric"><div class="analytics-metric-icon"><i class="ph-light ph-users"></i></div><div class="analytics-metric-content"><span>Active admins</span><strong>{{ number_format($activeAdmins->count()) }}</strong><small>Available collaboration members</small></div></div>
        </div>
    </section>
</div>
@endsection
