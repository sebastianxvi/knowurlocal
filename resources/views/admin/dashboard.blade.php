@extends('layouts.admin')

@section('title', 'KNOWURLOCAL | ' . ucfirst(auth()->user()->role) . ' Module')

@section('page-title', 'Dashboard')
@section('page-subtitle', 'System overview')

@section('content')

@push('styles')
<link
    rel="stylesheet"
    href="{{ asset('cssfiles/admin/dashboard.css') }}"
>
@endpush


<div class="dashboard-content">

    {{-- =====================================================
         OVERVIEW
         ===================================================== --}}

    <section class="dashboard-section">

        <div class="section-heading">

            <div class="section-heading-main">

                <div class="section-heading-icon">
                    <i class="ph-light ph-squares-four"></i>
                </div>

                <div class="section-heading-copy">

                    <span class="eyebrow">
                        Overview
                    </span>

                    <h2>
                        System at a glance
                    </h2>

                    <p>
                        Key information for managing KNOWURLOCAL.
                    </p>

                </div>

            </div>


            {{-- =================================================
                EXPORT REPORT
                ================================================= --}}

            <a
                href="{{ route('admin.dashboard.export') }}"
                class="dashboard-export-button"
                target="_blank"
                rel="noopener"
            >

                <i
                    class="ph-light ph-file-pdf"
                    aria-hidden="true"
                ></i>

                <span>
                    Export PDF
                </span>

            </a>

        </div>


        <div class="overview-grid">

            {{-- AGENCIES --}}
            <article class="overview-card overview-agencies">

                <div class="overview-icon">
                    <i class="ph-light ph-buildings"></i>
                </div>

                <div class="overview-info">

                    <span class="overview-label">
                        Agencies
                    </span>

                    <strong>
                        {{ number_format($totalAgencies) }}
                    </strong>

                    <span class="overview-meta">
                        Directory records
                    </span>

                </div>

            </article>


            {{-- FAQs --}}
            <article class="overview-card overview-faqs">

                <div class="overview-icon">
                    <i class="ph-light ph-book-open-text"></i>
                </div>

                <div class="overview-info">

                    <span class="overview-label">
                        FAQs
                    </span>

                    <strong>
                        {{ number_format($totalFaqs) }}
                    </strong>

                    <span class="overview-meta">
                        Knowledge base records
                    </span>

                </div>

            </article>


            {{-- USERS --}}
            <article class="overview-card overview-users">

                <div class="overview-icon">
                    <i class="ph-light ph-users"></i>
                </div>

                <div class="overview-info">

                    <span class="overview-label">
                        Public Users
                    </span>

                    <strong>
                        {{ number_format($totalUsers) }}
                    </strong>

                    <span class="overview-meta">
                        Registered accounts
                    </span>

                </div>

            </article>


            {{-- PENDING INQUIRIES --}}
            <article
                class="overview-card overview-pending {{ $pendingInquiries > 0 ? 'has-attention' : '' }}"
            >

                <div class="overview-icon">
                    <i class="ph-light ph-chat-circle-text"></i>
                </div>

                <div class="overview-info">

                    <span class="overview-label">
                        Pending Inquiries
                    </span>

                    <strong>
                        {{ number_format($pendingInquiries) }}
                    </strong>

                    <span class="overview-meta">
                        Awaiting response
                    </span>

                </div>

            </article>

        </div>

    </section>


    {{-- =====================================================
         NEEDS ATTENTION
         ===================================================== --}}

    <section class="dashboard-section">

        <div class="section-heading">

            <div class="section-heading-main">

                <div class="section-heading-icon attention">
                    <i class="ph-light ph-warning-circle"></i>
                </div>

                <div class="section-heading-copy">

                    <span class="eyebrow">
                        Attention
                    </span>

                    <h2>
                        Needs attention
                    </h2>

                    <p>
                        Items that may require administrative action.
                    </p>

                </div>

            </div>

        </div>


        <div class="attention-list">

            {{-- PENDING INQUIRIES --}}
            @if($pendingInquiries > 0)

                <a
                    href="{{ route('admin.support.requests', ['status' => 'pending']) }}"
                    class="attention-item attention-warning"
                >

                    <span class="attention-icon">
                        <i class="ph-light ph-clock"></i>
                    </span>

                    <span class="attention-content">

                        <strong>
                            {{ $pendingInquiries }}
                            {{ $pendingInquiries === 1 ? 'inquiry' : 'inquiries' }}
                            awaiting response
                        </strong>

                        <span>
                            Users are waiting for an administrator's response.
                        </span>

                    </span>

                    <i class="ph-light ph-arrow-right attention-arrow"></i>

                </a>

            @endif


            {{-- INCOMPLETE AGENCIES --}}
            @if($incompleteAgencies > 0)

                            <a
                                href="{{ route('admin.nga', ['filter' => 'incomplete']) }}"
                                class="attention-item attention-info"
                            >

                                <span class="attention-icon">
                                    <i class="ph-light ph-buildings"></i>
                                </span>

                                <span class="attention-content">

                                    <strong>
                                        {{ $incompleteAgencies }}
                                        {{ $incompleteAgencies === 1 ? 'agency record needs' : 'agency records need' }}
                                        attention
                                    </strong>

                                    <span>
                                        Some required directory information is missing.
                                    </span>

                                </span>

                                <i class="ph-light ph-arrow-right attention-arrow"></i>

                            </a>

                        @endif

                        @if($incompleteFaqs > 0)

                <a
                    href="{{ route('faqs.index', ['filter' => 'missing_translation']) }}"
                    class="attention-item attention-warning"
                >

                    <div class="attention-icon">
                        <i class="ph-light ph-translate"></i>
                    </div>

                    <div class="attention-content">

                        <strong>
                            {{ $incompleteFaqs }}
                            {{ $incompleteFaqs === 1 ? 'FAQ needs' : 'FAQs need' }}
                            translation
                        </strong>

                        <span>
                            Filipino/Taglish content is incomplete.
                        </span>

                    </div>

                    <i class="ph-light ph-arrow-right attention-arrow"></i>

                </a>

            @endif


            {{-- NOTHING REQUIRES ATTENTION --}}
            @if(
                $pendingInquiries === 0 &&
                $incompleteAgencies === 0 &&
                $incompleteFaqs === 0
            )

                <div class="attention-item attention-success">

                    <span class="attention-icon">
                        <i class="ph-light ph-check-circle"></i>
                    </span>

                    <span class="attention-content">

                        <strong>
                            Everything looks good
                        </strong>

                        <span>
                            No immediate administrative issues were detected.
                        </span>

                    </span>

                </div>

            @endif

        </div>

    </section>


    {{-- =====================================================
         DIRECTORY + KNOWLEDGE BASE
         ===================================================== --}}

    <section class="dashboard-section">

        <div class="section-heading">

            <div class="section-heading-main">

                <div class="section-heading-icon">
                    <i class="ph-light ph-database"></i>
                </div>

                <div class="section-heading-copy">

                    <span class="eyebrow">
                        Data management
                    </span>

                    <h2>
                        Directory & knowledge base
                    </h2>

                    <p>
                        Monitor the completeness of information used by citizens.
                    </p>

                </div>

            </div>

        </div>

        <div class="management-grid">


            {{-- AGENCY DIRECTORY --}}
            <article class="management-card">

                <div class="management-header">

                    <div class="management-icon">
                        <i class="ph-light ph-buildings"></i>
                    </div>

                    <div>

                        <span class="eyebrow">
                            Directory
                        </span>

                        <h2>
                            Agency data
                        </h2>

                    </div>

                </div>


                <div class="management-stats">

                    <div class="management-stat management-complete">

                        <strong>
                            {{ number_format($completeAgencies) }}
                        </strong>

                        <span>
                            Complete
                        </span>

                    </div>

                    <div class="management-stat management-attention">

                        <strong>
                            {{ number_format($incompleteAgencies) }}
                        </strong>

                        <span>
                            Needs attention
                        </span>

                    </div>

                </div>


                <div class="management-footer">

                    <span>
                        {{ number_format($totalAgencies) }}
                        total agency records
                    </span>

                    <a href="{{ route('admin.nga') }}">
                        Manage agencies
                        <i class="ph-light ph-arrow-right"></i>
                    </a>

                </div>

            </article>


            {{-- KNOWLEDGE BASE --}}
            <article class="management-card">

                <div class="management-header">

                    <div class="management-icon">
                        <i class="ph-light ph-book-open-text"></i>
                    </div>

                    <div>

                        <span class="eyebrow">
                            Knowledge Base
                        </span>

                        <h2>
                            FAQ data
                        </h2>

                    </div>

                </div>


                <div class="management-stats">

                    <div class="management-stat management-complete">

                        <strong>
                            {{ number_format($completeFaqs) }}
                        </strong>

                        <span>
                            Complete
                        </span>

                    </div>

                    <div class="management-stat management-attention">

                        <strong>
                            {{ number_format($incompleteFaqs) }}
                        </strong>

                        <span>
                            Needs attention
                        </span>

                    </div>

                </div>


                <div class="management-footer">

                    <span>
                        {{ number_format($totalFaqs) }}
                        total FAQ records
                    </span>

                    <a href="{{ route('faqs.index') }}">
                        Manage FAQs
                        <i class="ph-light ph-arrow-right"></i>
                    </a>

                </div>

            </article>

        </div>

    </section>


    {{-- =====================================================
     USER INQUIRIES
     ===================================================== --}}

    <section class="dashboard-section">

        <div class="section-heading">

            <div class="section-heading-main">

                <div class="section-heading-icon">
                    <i class="ph-light ph-chats"></i>
                </div>

                <div class="section-heading-copy">

                    <span class="eyebrow">
                        Support
                    </span>

                    <h2>
                        User inquiries
                    </h2>

                    <p>
                        Monitor questions submitted by citizens.
                    </p>

                </div>

            </div>


            <a
                href="{{ route('admin.support.requests') }}"
                class="section-action"
            >
                View inquiries
                <i class="ph-light ph-arrow-right"></i>
            </a>

        </div>


        <div class="inquiry-overview">

            {{-- ================= TOTAL ================= --}}

            <div class="inquiry-stat inquiry-total">

                <span class="inquiry-stat-label">
                    Total
                </span>

                <strong class="inquiry-stat-value">
                    {{ $totalInquiries }}
                </strong>

                <span class="inquiry-stat-description">
                    Submitted questions
                </span>

            </div>


            {{-- ================= PENDING ================= --}}

            <a
                href="{{ route('admin.support.requests', ['status' => 'pending']) }}"
                class="inquiry-stat inquiry-stat-action inquiry-pending"
            >

                <span class="inquiry-stat-label">
                    Pending
                </span>

                <strong class="inquiry-stat-value">
                    {{ $pendingInquiries }}
                </strong>

                <span class="inquiry-stat-description">
                    Awaiting response
                </span>

                <i
                    class="ph-light ph-arrow-up-right inquiry-stat-arrow"
                    aria-hidden="true"
                ></i>

            </a>


            {{-- ================= ANSWERED ================= --}}

            <div class="inquiry-stat inquiry-answered">

                <span class="inquiry-stat-label">
                    Answered
                </span>

                <strong class="inquiry-stat-value">
                    {{ $answeredInquiries }}
                </strong>

                <span class="inquiry-stat-description">
                    Responses provided
                </span>

            </div>

        </div>


        {{-- ================= RESPONSE INSIGHT ================= --}}

        <div class="inquiry-insight {{ $pendingInquiries > 0 ? 'is-warning' : 'is-success' }}">

            <div class="inquiry-insight-icon">
                <i class="ph-light ph-chart-donut"></i>
            </div>

            <div class="inquiry-insight-copy">

                @if($totalInquiries === 0)

                    <strong>
                        No inquiries yet
                    </strong>

                    <span>
                        There are currently no citizen questions to manage.
                    </span>

                @elseif($pendingInquiries === 0)

                    <strong>
                        All inquiries have been answered
                    </strong>

                    <span>
                        There are currently no questions waiting for a response.
                    </span>

                @else

                    <strong>
                        {{ $pendingInquiryPercentage }}% of inquiries need attention
                    </strong>

                    <span>
                        {{ $pendingInquiries }}
                        {{ $pendingInquiries === 1 ? 'inquiry is' : 'inquiries are' }}
                        currently awaiting an administrator's response.
                    </span>

                @endif

            </div>

        </div>

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







    {{-- =====================================================
     RECENT ACTIVITY
     ===================================================== --}}

<section class="dashboard-section">

    <div class="section-heading">

        <div class="section-heading-main">

            <div class="section-heading-icon">
                <i class="ph-light ph-activity"></i>
            </div>

            <div class="section-heading-copy">

                <span class="eyebrow">
                    Activity
                </span>

                <h2>
                    Recent system activity
                </h2>

                <p>
                    The latest actions recorded by KNOWURLOCAL.
                </p>

            </div>

        </div>

        <a
            href="{{ route('admin.logs') }}"
            class="section-action"
        >
            View activity logs
            <i class="ph-light ph-arrow-right"></i>
        </a>

    </div>


    <div class="activity-list">

        @forelse($recentActivity as $log)

            <article class="activity-item">


                {{-- ================= ACTIVITY ICON ================= --}}

                @php
                    $activityIcon = match ($log->action) {

                        'view_agency' =>
                            'ph-eye',

                        'search_agency' =>
                            'ph-magnifying-glass',

                        'get_directions' =>
                            'ph-navigation-arrow',

                        'contact_agency' =>
                            'ph-phone',

                        'create_agency' =>
                            'ph-buildings',

                        'update_agency' =>
                            'ph-pencil-simple',

                        'delete_agency' =>
                            'ph-trash',

                        'create_faq' =>
                            'ph-book-open-text',

                        'update_faq' =>
                            'ph-pencil-simple',

                        'delete_faq' =>
                            'ph-trash',

                        default =>
                            'ph-activity',
                    };
                @endphp

                <div class="activity-icon">

                    <i class="ph-light {{ $activityIcon }}"></i>

                </div>


                {{-- ================= ACTIVITY CONTENT ================= --}}

                <div class="activity-content">

                    <div class="activity-actor">

                        <strong>
                            {{ $log->actor_name }}
                        </strong>

                        @if($log->role)

                            <span class="activity-role">
                                {{ ucfirst($log->role) }}
                            </span>

                        @endif

                    </div>


                    <span class="activity-description">

                        {{ $log->description ?: $log->action_label }}

                        @if($log->agency)

                            @php
                                $descriptionContainsAgency =
                                    $log->description &&
                                    str_contains(
                                        strtolower($log->description),
                                        strtolower($log->agency->agency_name)
                                    );
                            @endphp

                            @if(!$descriptionContainsAgency)

                                <span class="activity-target">
                                    · {{ $log->agency->agency_name }}
                                </span>

                            @endif

                        @elseif($log->category)

                            @php
                                $descriptionContainsCategory =
                                    $log->description &&
                                    str_contains(
                                        strtolower($log->description),
                                        strtolower($log->category->category_name)
                                    );
                            @endphp

                            @if(!$descriptionContainsCategory)

                                <span class="activity-target">
                                    · {{ $log->category->category_name }}
                                </span>

                            @endif

                        @endif

                    </span>

                </div>


                {{-- ================= TIME ================= --}}

                <time
                    datetime="{{ $log->created_at->toIso8601String() }}"
                    class="activity-time"
                >
                    {{ $log->created_at->diffForHumans() }}
                </time>

            </article>

        @empty

            <div class="activity-empty">

                <i class="ph-light ph-clock"></i>

                <span>
                    No system activity has been recorded yet.
                </span>

            </div>

        @endforelse

    </div>

</section>

</div>

@endsection