@extends('layouts.admin')

@section('title', 'KNOWURLOCAL | Analytics')
@section('page-title', 'Analytics')
@section('page-subtitle', 'Understand service demand, response activity, and knowledge-base performance')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/admin/dashboard.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/analytics.css') }}">
@endpush

@section('content')
<div class="analytics-page">
    <section class="analytics-hero">
        <div>
            <span class="eyebrow">Decision support</span>
            <h2>What the system is telling your team</h2>
            <p>Use these signals to see where citizens need help, how quickly requests are handled, and where the knowledge base needs improvement.</p>
        </div>
        <a class="analytics-back" href="{{ route('admin.dashboard') }}">
            <i class="ph-light ph-arrow-left"></i> Dashboard
        </a>
    </section>

    {{-- =====================================================
     ANALYTICS
     ===================================================== --}}

<section class="dashboard-section">

    <div class="section-heading">

        <div class="section-heading-main">

            <div class="section-heading-icon analytics-heading-icon">
                <i class="ph-light ph-chart-line-up"></i>
            </div>

            <div class="section-heading-copy">

                <span class="eyebrow">
                    Analytics
                </span>

                <h2>
                    Inquiry performance
                </h2>

                <p>
                    Monitor citizen inquiries and administrator response activity.
                </p>

            </div>

        </div>

    </div>


    {{-- =================================================
         ANALYTICS METRICS
         ================================================= --}}

    <div class="analytics-metrics">


        {{-- RESPONSE RATE --}}

        <div class="analytics-metric analytics-response">

            <div class="analytics-metric-icon">
                <i class="ph-light ph-chart-donut"></i>
            </div>

            <div class="analytics-metric-content">

                <span>
                    Response rate
                </span>

                <strong>
                    {{ $responseRate }}%
                </strong>

                <small>
                    {{ $answeredInquiries }}
                    of
                    {{ $totalInquiries }}
                    inquiries answered
                </small>

            </div>

        </div>


        {{-- AVERAGE RESPONSE TIME --}}

        <div class="analytics-metric analytics-time">

            <div class="analytics-metric-icon">
                <i class="ph-light ph-timer"></i>
            </div>

            <div class="analytics-metric-content">

                <span>
                    Average response
                </span>

                <strong>
                    {{ $averageResponseTime ?? '—' }}
                </strong>

                <small>
                    Time from submission to answer
                </small>

            </div>

        </div>


        {{-- ANSWERS SEEN --}}

        <div class="analytics-metric analytics-seen">

            <div class="analytics-metric-icon">
                <i class="ph-light ph-eye"></i>
            </div>

            <div class="analytics-metric-content">

                <span>
                    Answers seen
                </span>

                <strong>
                    {{ $seenAnswers }}
                </strong>

                <small>
                    Citizens who viewed their answers
                </small>

            </div>

        </div>


        {{-- UNSEEN ANSWERS --}}

        <div class="analytics-metric analytics-unseen">

            <div class="analytics-metric-icon">
                <i class="ph-light ph-envelope"></i>
            </div>

            <div class="analytics-metric-content">

                <span>
                    Awaiting view
                </span>

                <strong>
                    {{ $unseenAnswers }}
                </strong>

                <small>
                    Answered but not yet viewed
                </small>

            </div>

        </div>

    </div>


    {{-- =================================================
         INQUIRY TREND
         ================================================= --}}

    <div class="analytics-chart-card">

        <div class="analytics-chart-header">

            <div>

                <span class="eyebrow">
                    Activity trend
                </span>

                <h3>
                    Inquiry activity
                </h3>

            </div>

            <span class="analytics-period">
                Last 7 days
            </span>

        </div>


        <div class="analytics-chart">

            @php

                /*
                 * Find the highest value across both
                 * submitted and answered inquiries.
                 *
                 * This value is used to scale the
                 * chart bars consistently.
                 */
                $chartMaximum = collect($inquiryTrend)
                    ->flatMap(function ($day) {
                        return [
                            $day['submitted'],
                            $day['answered'],
                        ];
                    })
                    ->max();

                /*
                 * Prevent division by zero when there
                 * has been no activity during the period.
                 */
                $chartMaximum = max(
                    $chartMaximum ?? 0,
                    1
                );

            @endphp


            <div class="analytics-chart-grid">

                @foreach($inquiryTrend as $day)

                    @php

                        /*
                         * Convert the raw inquiry counts
                         * into percentages for the chart.
                         */
                        $submittedHeight =
                            ($day['submitted'] / $chartMaximum) * 100;

                        $answeredHeight =
                            ($day['answered'] / $chartMaximum) * 100;

                    @endphp


                    <div class="analytics-chart-day">

                        <div class="analytics-bars">

                            {{-- SUBMITTED --}}

                            <div
                                class="analytics-bar analytics-bar-submitted"
                                style="height: {{ max($submittedHeight, 3) }}%;"
                                title="{{ $day['submitted'] }} submitted"
                            ></div>


                            {{-- ANSWERED --}}

                            <div
                                class="analytics-bar analytics-bar-answered"
                                style="height: {{ max($answeredHeight, 3) }}%;"
                                title="{{ $day['answered'] }} answered"
                            ></div>

                        </div>


                        <span class="analytics-day-label">
                            {{ $day['label'] }}
                        </span>

                    </div>

                @endforeach

            </div>


            {{-- =================================================
                 CHART LEGEND
                 ================================================= --}}

            <div class="analytics-legend">

                <span>
                    <i class="analytics-legend-dot submitted"></i>
                    Submitted
                </span>

                <span>
                    <i class="analytics-legend-dot answered"></i>
                    Answered
                </span>

            </div>

        </div>

    </div>

</section>





{{-- =====================================================
     KNOWLEDGE BASE & CHATBOT ANALYTICS
     ===================================================== --}}

<section class="dashboard-section">

    <div class="section-heading">

        <div class="section-heading-main">

            <div class="section-heading-icon chatbot-heading-icon">
                <i class="ph-light ph-chat-circle-dots"></i>
            </div>

            <div class="section-heading-copy">

                <span class="eyebrow">
                    Knowledge Base
                </span>

                <h2>
                    Chatbot performance
                </h2>

                <p>
                    Monitor how effectively KNOWURLOCAL answers citizen questions automatically.
                </p>

            </div>

        </div>

    </div>


    {{-- =================================================
         CHATBOT METRICS
         ================================================= --}}

    <div class="analytics-metrics chatbot-metrics">


        {{-- FAQ ANSWER RATE --}}

        <div class="analytics-metric chatbot-answer-rate">

            <div class="analytics-metric-icon">
                <i class="ph-light ph-book-open-text"></i>
            </div>

            <div class="analytics-metric-content">

                <span>
                    FAQ answer rate
                </span>

                <strong>
                    {{ $faqAnswerRate }}%
                </strong>

                <small>
                    {{ number_format($faqAnswered) }}
                    of
                    {{ number_format($knowledgeQuestions) }}
                    knowledge questions answered
                </small>

            </div>

        </div>


        {{-- FALLBACK RATE --}}

        <div class="analytics-metric chatbot-fallback-rate">

            <div class="analytics-metric-icon">
                <i class="ph-light ph-arrow-u-down-left"></i>
            </div>

            <div class="analytics-metric-content">

                <span>
                    Fallback rate
                </span>

                <strong>
                    {{ $fallbackRate }}%
                </strong>

                <small>
                    {{ number_format($fallbackQuestions) }}
                    questions could not use an FAQ
                </small>

            </div>

        </div>


        {{-- FAQ ANSWERS --}}

        <div class="analytics-metric chatbot-faq-answers">

            <div class="analytics-metric-icon">
                <i class="ph-light ph-check-circle"></i>
            </div>

            <div class="analytics-metric-content">

                <span>
                    FAQ answers
                </span>

                <strong>
                    {{ number_format($faqAnswered) }}
                </strong>

                <small>
                    Answers provided from the knowledge base
                </small>

            </div>

        </div>


        {{-- TOTAL CHATBOT QUESTIONS --}}

        <div class="analytics-metric chatbot-total">

            <div class="analytics-metric-icon">
                <i class="ph-light ph-chats-circle"></i>
            </div>

            <div class="analytics-metric-content">

                <span>
                    Chatbot questions
                </span>

                <strong>
                    {{ number_format($knowledgeQuestions) }}
                </strong>

                <small>
                    Information-seeking interactions
                </small>

            </div>

        </div>

    </div>


    {{-- =================================================
         KNOWLEDGE BASE BREAKDOWN
         ================================================= --}}

    <div class="chatbot-analytics-grid">


        {{-- =================================================
             MOST USED FAQs
             ================================================= --}}

        <article class="chatbot-analytics-card">

            <div class="chatbot-card-header">

                <div>

                    <span class="eyebrow">
                        Knowledge Base
                    </span>

                    <h3>
                        Most used FAQs
                    </h3>

                </div>

                <div class="chatbot-card-icon">
                    <i class="ph-light ph-book-open-text"></i>
                </div>

            </div>


            <div class="chatbot-ranking-list">

                @forelse($popularFaqs as $item)

                    <div class="chatbot-ranking-item">

                        <div class="chatbot-ranking-main">

                            <strong>
                                {{ $item->faq?->question ?? 'FAQ no longer available' }}
                            </strong>

                            @if($item->faq?->agency)

                                <span>
                                    {{ $item->faq->agency->agency_name }}
                                </span>

                            @endif

                        </div>

                        <span class="chatbot-ranking-count">
                            {{ number_format($item->usage_count) }}
                        </span>

                    </div>

                @empty

                    <div class="chatbot-analytics-empty">

                        <i class="ph-light ph-book-open"></i>

                        <span>
                            No FAQ usage has been recorded yet.
                        </span>

                    </div>

                @endforelse

            </div>

        </article>


        {{-- =================================================
             KNOWLEDGE GAPS
             ================================================= --}}

        <article class="chatbot-analytics-card">

            <div class="chatbot-card-header">

                <div>

                    <span class="eyebrow">
                        Attention
                    </span>

                    <h3>
                        Knowledge gaps
                    </h3>

                </div>

                <div class="chatbot-card-icon chatbot-warning-icon">
                    <i class="ph-light ph-warning-circle"></i>
                </div>

            </div>


            <div class="chatbot-gap-list">


                {{-- FALLBACK QUESTIONS --}}

                <div class="chatbot-gap-item">

                    <div class="chatbot-gap-icon chatbot-gap-warning">
                        <i class="ph-light ph-arrow-u-down-left"></i>
                    </div>

                    <div class="chatbot-gap-content">

                        <strong>
                            Fallback questions
                        </strong>

                        <span>
                            Questions that could not be answered using an FAQ.
                        </span>

                    </div>

                    <strong class="chatbot-gap-count">
                        {{ number_format($fallbackQuestions) }}
                    </strong>

                </div>


                {{-- CLARIFICATION QUESTIONS --}}

                <div class="chatbot-gap-item">

                    <div class="chatbot-gap-icon chatbot-gap-info">
                        <i class="ph-light ph-chat-circle-dots"></i>
                    </div>

                    <div class="chatbot-gap-content">

                        <strong>
                            Clarifications
                        </strong>

                        <span>
                            Questions that required additional information.
                        </span>

                    </div>

                    <strong class="chatbot-gap-count">
                        {{ number_format($clarificationQuestions) }}
                    </strong>

                </div>


                {{-- MATCHING METHODS --}}

                <div class="chatbot-gap-item">

                    <div class="chatbot-gap-icon chatbot-gap-success">
                        <i class="ph-light ph-git-branch"></i>
                    </div>

                    <div class="chatbot-gap-content">

                        <strong>
                            Matching methods
                        </strong>

                        <span>
                            Rule-based and semantic FAQ matches recorded.
                        </span>

                    </div>

                    <div class="chatbot-match-counts">

                        <span>
                            {{ number_format($ruleMatches) }}
                            rule
                        </span>

                        <span>
                            {{ number_format($semanticMatches) }}
                            semantic
                        </span>

                    </div>

                </div>

            </div>

        </article>

    </div>


    {{-- =================================================
         MOST REQUESTED AGENCIES
         ================================================= --}}

    <article class="chatbot-analytics-card chatbot-agency-card">

        <div class="chatbot-card-header">

            <div>

                <span class="eyebrow">
                    Citizen Interest
                </span>

                <h3>
                    Most requested agencies
                </h3>

            </div>

            <div class="chatbot-card-icon">
                <i class="ph-light ph-buildings"></i>
            </div>

        </div>


        <div class="chatbot-agency-list">

            @forelse($popularAgencies as $item)

                <div class="chatbot-agency-item">

                    <div class="chatbot-agency-rank">
                        {{ $loop->iteration }}
                    </div>

                    <div class="chatbot-agency-info">

                        <strong>
                            {{ $item->agency?->agency_name ?? 'Agency no longer available' }}
                        </strong>

                        <span>
                            Chatbot interactions
                        </span>

                    </div>

                    <strong class="chatbot-agency-count">
                        {{ number_format($item->interaction_count) }}
                    </strong>

                </div>

            @empty

                <div class="chatbot-analytics-empty">

                    <i class="ph-light ph-buildings"></i>

                    <span>
                        No agency-related chatbot interactions have been recorded yet.
                    </span>

                </div>

            @endforelse

        </div>

    </article>

</section>








</div>
@endsection
