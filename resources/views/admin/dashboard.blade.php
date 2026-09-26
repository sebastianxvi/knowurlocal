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

    {{-- =================================================
         AGENCIES
         ================================================= --}}

    <a
        href="{{ route('admin.nga') }}"
        class="overview-card overview-agencies"
    >

        <div class="overview-icon">

            <i
                class="ph-light ph-buildings"
                aria-hidden="true"
            ></i>

        </div>


        <div class="overview-info">

            <span class="overview-label">
                Agencies
            </span>

            <strong>
                {{ number_format($totalAgencies) }}
            </strong>


            <div class="overview-breakdown">

                <span>
                    NGA

                    <strong>
                        {{ number_format($totalNGA) }}
                    </strong>
                </span>


                <span>
                    NGO

                    <strong>
                        {{ number_format($totalNGO) }}
                    </strong>
                </span>

            </div>

        </div>


        <i
            class="ph-light ph-arrow-up-right overview-card-arrow"
            aria-hidden="true"
        ></i>

    </a>



    {{-- =================================================
         FAQs
         ================================================= --}}

    <a
        href="{{ route('faqs.index') }}"
        class="overview-card overview-faqs"
    >

        <div class="overview-icon">

            <i
                class="ph-light ph-book-open-text"
                aria-hidden="true"
            ></i>

        </div>


        <div class="overview-info">

            <span class="overview-label">
                FAQs
            </span>

            <strong>
                {{ number_format($totalFaqs) }}
            </strong>


            <div class="overview-meta overview-top-contributor">

                @if($topFaqCount > 0)

                    <span class="overview-meta-label">

                        {{ $topFaqContributorTieCount > 1
                            ? 'Top contributors'
                            : 'Top contributor'
                        }}

                    </span>


                    <span class="overview-meta-value">

                        @if($topFaqContributorTieCount > 1)

                            {{ $topFaqContributorTieCount }}
                            agencies tied ·
                            {{ number_format($topFaqCount) }}
                            FAQs

                        @else

                            {{ $topFaqContributors->first() ?? 'Unknown agency' }}
                            ·
                            {{ number_format($topFaqCount) }}
                            FAQs

                        @endif

                    </span>

                @else

                    <span class="overview-meta-label">
                        No FAQ data yet
                    </span>

                @endif

            </div>

        </div>


        <i
            class="ph-light ph-arrow-up-right overview-card-arrow"
            aria-hidden="true"
        ></i>

    </a>



    {{-- =================================================
         PUBLIC USERS
         ================================================= --}}

    <a
        href="{{ route('admin.users') }}"
        class="overview-card overview-users"
    >

        <div class="overview-icon">

            <i
                class="ph-light ph-users"
                aria-hidden="true"
            ></i>

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


        <i
            class="ph-light ph-arrow-up-right overview-card-arrow"
            aria-hidden="true"
        ></i>

    </a>



    {{-- =================================================
         PENDING INQUIRIES
         ================================================= --}}

    <a
        href="{{ route('admin.support.requests') }}"
        class="overview-card overview-pending {{ $pendingInquiries > 0 ? 'has-attention' : '' }}"
    >

        <div class="overview-icon">

            <i
                class="ph-light ph-chat-circle-text"
                aria-hidden="true"
            ></i>

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


        <i
            class="ph-light ph-arrow-up-right overview-card-arrow"
            aria-hidden="true"
        ></i>

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
                <i
                    class="ph-light ph-warning-circle"
                    aria-hidden="true"
                ></i>
            </div>

            <div class="section-heading-copy">

                <span class="eyebrow">
                    Attention
                </span>

                <h2>
                    Needs attention
                </h2>

                <p>
                    Administrative tasks that may require action.
                </p>

            </div>

        </div>

    </div>


    <div class="attention-list">

        {{-- =================================================
             PENDING INQUIRIES
             ================================================= --}}

        @if($pendingInquiries > 0)

            <a
                href="{{ route('admin.support.requests', ['status' => 'pending']) }}"
                class="attention-item attention-warning"
            >

                <span class="attention-icon">
                    <i
                        class="ph-light ph-clock"
                        aria-hidden="true"
                    ></i>
                </span>


                <span class="attention-content">

                    <span class="attention-item-heading">

                        <strong>
                            {{ number_format($pendingInquiries) }}
                            {{ $pendingInquiries === 1 ? 'inquiry' : 'inquiries' }}
                            awaiting response
                        </strong>

                        <span class="attention-priority attention-priority-high">
                            High
                        </span>

                    </span>


                    <span class="attention-description">
                        Citizens are waiting for an administrator's response.
                    </span>


                    <span class="attention-meta">
                        <i
                            class="ph-light ph-chats"
                            aria-hidden="true"
                        ></i>

                        User inquiries
                    </span>

                </span>


                <i
                    class="ph-light ph-arrow-right attention-arrow"
                    aria-hidden="true"
                ></i>

            </a>

        @endif


        {{-- =================================================
             INCOMPLETE AGENCIES
             ================================================= --}}

        @if($incompleteAgencies > 0)

            <a
                href="{{ route('admin.nga', ['filter' => 'incomplete']) }}"
                class="attention-item attention-info"
            >

                <span class="attention-icon">
                    <i
                        class="ph-light ph-buildings"
                        aria-hidden="true"
                    ></i>
                </span>


                <span class="attention-content">

                    <span class="attention-item-heading">

                        <strong>
                            {{ number_format($incompleteAgencies) }}
                            {{ $incompleteAgencies === 1 ? 'agency record needs' : 'agency records need' }}
                            attention
                        </strong>

                        <span class="attention-priority attention-priority-medium">
                            Medium
                        </span>

                    </span>


                    <span class="attention-description">
                        Required directory information is missing from some records.
                    </span>


                    <span class="attention-meta">
                        <i
                            class="ph-light ph-database"
                            aria-hidden="true"
                        ></i>

                        Agency directory
                    </span>

                </span>


                <i
                    class="ph-light ph-arrow-right attention-arrow"
                    aria-hidden="true"
                ></i>

            </a>

        @endif


        {{-- =================================================
             INCOMPLETE FAQ TRANSLATIONS
             ================================================= --}}

        @if($incompleteFaqs > 0)

            <a
                href="{{ route('faqs.index', ['filter' => 'missing_translation']) }}"
                class="attention-item attention-warning"
            >

                <span class="attention-icon">
                    <i
                        class="ph-light ph-translate"
                        aria-hidden="true"
                    ></i>
                </span>


                <span class="attention-content">

                    <span class="attention-item-heading">

                        <strong>
                            {{ number_format($incompleteFaqs) }}
                            {{ $incompleteFaqs === 1 ? 'FAQ needs' : 'FAQs need' }}
                            translation
                        </strong>

                        <span class="attention-priority attention-priority-medium">
                            Medium
                        </span>

                    </span>


                    <span class="attention-description">
                        Filipino or Taglish content is incomplete.
                    </span>


                    <span class="attention-meta">
                        <i
                            class="ph-light ph-book-open-text"
                            aria-hidden="true"
                        ></i>

                        Knowledge base
                    </span>

                </span>


                <i
                    class="ph-light ph-arrow-right attention-arrow"
                    aria-hidden="true"
                ></i>

            </a>

        @endif


        {{-- =================================================
             NOTHING REQUIRES ATTENTION
             ================================================= --}}

        @if(
            $pendingInquiries === 0 &&
            $incompleteAgencies === 0 &&
            $incompleteFaqs === 0
        )

            <div class="attention-item attention-success">

                <span class="attention-icon">
                    <i
                        class="ph-light ph-check-circle"
                        aria-hidden="true"
                    ></i>
                </span>


                <span class="attention-content">

                    <span class="attention-item-heading">

                        <strong>
                            Everything looks good
                        </strong>

                        <span class="attention-priority attention-priority-clear">
                            Clear
                        </span>

                    </span>


                    <span class="attention-description">
                        No immediate administrative issues were detected.
                    </span>


                    <span class="attention-meta">
                        <i
                            class="ph-light ph-shield-check"
                            aria-hidden="true"
                        ></i>

                        System status
                    </span>

                </span>

            </div>

        @endif

    </div>


    {{-- =================================================
         TEAM COLLABORATION
         ================================================= --}}

    <div class="team-collaboration-card">

        <div class="team-collaboration-header">
            <div>
                <span class="eyebrow">Team activity</span>
                <h3>Who is handling the queue?</h3>
            </div>

            <span class="team-collaboration-count">
                {{ number_format($answeredToday) }} answered today
            </span>
        </div>

        @if($teamRespondersToday->isNotEmpty())

            <div class="team-responder-list">
                @foreach($teamRespondersToday as $responder)
                    <div class="team-responder">
                        <span class="team-responder-avatar">
                            {{ strtoupper(substr($responder->user?->first_name ?? 'A', 0, 1)) }}
                        </span>

                        <span class="team-responder-copy">
                            <strong>
                                {{ trim(($responder->user?->first_name ?? 'Admin') . ' ' . ($responder->user?->last_name ?? '')) }}
                            </strong>
                            <small>
                                {{ $responder->user?->role === 'superadmin' ? 'Superadmin' : 'Administrator' }}
                            </small>
                        </span>
                    </div>
                @endforeach
            </div>

        @else

            <p class="team-collaboration-empty">
                No support responses have been recorded today.
            </p>

        @endif

        @if($recentTeamActivity->isNotEmpty())
            <div class="team-activity-strip">
                <i class="ph-light ph-activity" aria-hidden="true"></i>
                <span>
                    Latest:
                    {{ $recentTeamActivity->first()->description ?: $recentTeamActivity->first()->action_label }}
                </span>
                <time>
                    {{ $recentTeamActivity->first()->created_at?->diffForHumans() }}
                </time>
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



    <section class="dashboard-section analytics-cta-section">
        <div class="dashboard-analytics-cta">
            <div class="dashboard-analytics-cta-icon"><i class="ph-light ph-chart-line-up"></i></div>
            <div>
                <span class="eyebrow">Analytics</span>
                <h2>See the full operational picture</h2>
                <p>Review inquiry activity, response performance, chatbot matching, and knowledge gaps in one focused workspace.</p>
            </div>
            <a href="{{ route('admin.analytics') }}" class="section-action">Open analytics <i class="ph-light ph-arrow-right"></i></a>
        </div>
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