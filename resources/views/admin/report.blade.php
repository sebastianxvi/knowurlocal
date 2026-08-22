<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>
        KNOWURLOCAL Administrative Report
    </title>

    <style>

        /*
         * DomPDF works best with simple, print-oriented CSS.
         *
         * We intentionally avoid the dashboard's modern CSS,
         * animations, external fonts, and browser-only effects.
         */

        @page {
            margin: 32px 36px;
        }


        body {
            margin: 0;

            font-family: DejaVu Sans, Arial, sans-serif;

            font-size: 10px;

            line-height: 1.45;

            color: #172033;

            background: #ffffff;
        }


        /*
         * =====================================================
         * REPORT HEADER
         * =====================================================
         */

        .report-header {
            padding-bottom: 16px;

            border-bottom: 2px solid #315fbd;

            margin-bottom: 20px;
        }


        .brand {
            margin: 0;

            font-size: 22px;

            font-weight: bold;

            letter-spacing: 0.5px;

            color: #172033;
        }


        .report-title {
            margin: 3px 0 0;

            font-size: 13px;

            font-weight: normal;

            color: #61708a;
        }


        .report-meta {
            margin-top: 8px;

            font-size: 9px;

            color: #7b8799;
        }


        /*
         * =====================================================
         * SECTION
         * =====================================================
         */

        .section {
            margin-bottom: 20px;

            page-break-inside: avoid;
        }


        .section-title {
            margin: 0 0 9px;

            padding-bottom: 5px;

            border-bottom: 1px solid #dfe5ee;

            font-size: 12px;

            font-weight: bold;

            color: #172033;
        }


        .section-subtitle {
            margin: -4px 0 9px;

            font-size: 9px;

            color: #7b8799;
        }


        /*
         * =====================================================
         * METRIC GRID
         * =====================================================
         */

        .metric-table {
            width: 100%;

            border-collapse: separate;

            border-spacing: 6px;

            margin: -6px;
        }


        .metric-cell {
            width: 25%;

            padding: 10px;

            border: 1px solid #e1e7ef;

            background: #f8fafc;

            vertical-align: top;
        }


        .metric-label {
            font-size: 8px;

            color: #718096;

            text-transform: uppercase;

            letter-spacing: 0.3px;
        }


        .metric-value {
            margin-top: 3px;

            font-size: 17px;

            font-weight: bold;

            color: #172033;
        }


        .metric-note {
            margin-top: 2px;

            font-size: 8px;

            color: #8a96a8;
        }


        /*
         * =====================================================
         * SEMANTIC METRIC COLORS
         * =====================================================
         */

        .blue {
            color: #315fbd;
        }

        .green {
            color: #18804b;
        }

        .amber {
            color: #a36b00;
        }

        .purple {
            color: #6957c9;
        }


        /*
         * =====================================================
         * TABLES
         * =====================================================
         */

        .report-table {
            width: 100%;

            border-collapse: collapse;

            margin-top: 6px;
        }


        .report-table th {
            padding: 7px 8px;

            border-bottom: 1px solid #d9e0ea;

            background: #f5f7fa;

            font-size: 8px;

            font-weight: bold;

            text-align: left;

            color: #61708a;

            text-transform: uppercase;

            letter-spacing: 0.25px;
        }


        .report-table td {
            padding: 7px 8px;

            border-bottom: 1px solid #edf0f4;

            font-size: 9px;

            vertical-align: top;
        }


        .report-table tr:last-child td {
            border-bottom: none;
        }


        .number {
            text-align: right;

            font-weight: bold;
        }


        /*
         * =====================================================
         * TWO-COLUMN TABLES
         * =====================================================
         */

        .two-column {
            width: 100%;

            border-collapse: separate;

            border-spacing: 10px 0;

            margin: 0 -10px;
        }


        .two-column-cell {
            width: 50%;

            vertical-align: top;
        }


        /*
         * =====================================================
         * STATUS ROW
         * =====================================================
         */

        .status-row {
            width: 100%;

            border-collapse: collapse;
        }


        .status-row td {
            padding: 7px 8px;

            border-bottom: 1px solid #edf0f4;
        }


        .status-row td:last-child {
            text-align: right;

            font-weight: bold;
        }


        /*
         * =====================================================
         * EMPTY STATE
         * =====================================================
         */

        .empty {
            padding: 14px;

            border: 1px solid #e5e9f0;

            background: #fafbfd;

            text-align: center;

            font-size: 9px;

            color: #8a96a8;
        }


        /*
         * =====================================================
         * ACTIVITY TABLE
         * =====================================================
         */

        .activity-action {
            font-weight: bold;
        }


        .activity-description {
            color: #61708a;
        }


        /*
         * =====================================================
         * FOOTER
         * =====================================================
         */

        .report-footer {
            margin-top: 24px;

            padding-top: 8px;

            border-top: 1px solid #dfe5ee;

            font-size: 8px;

            color: #8a96a8;

            text-align: center;
        }


        /*
         * =====================================================
         * PAGE BREAK
         * =====================================================
         */

        .page-break {
            page-break-before: always;
        }

    </style>

</head>


<body>


    {{-- =====================================================
         REPORT HEADER
         ===================================================== --}}

    <header class="report-header">

        <h1 class="brand">
            KNOWURLOCAL
        </h1>

        <p class="report-title">
            Administrative Dashboard Report
        </p>

        <p class="report-meta">
            Generated on
            {{ now()->format('F d, Y \a\t h:i A') }}
        </p>

    </header>



    {{-- =====================================================
         SYSTEM OVERVIEW
         ===================================================== --}}

    <section class="section">

        <h2 class="section-title">
            System Overview
        </h2>

        <table class="metric-table">

            <tr>

                <td class="metric-cell">

                    <div class="metric-label">
                        Total Agencies
                    </div>

                    <div class="metric-value blue">
                        {{ number_format($totalAgencies) }}
                    </div>

                </td>


                <td class="metric-cell">

                    <div class="metric-label">
                        Total FAQs
                    </div>

                    <div class="metric-value purple">
                        {{ number_format($totalFaqs) }}
                    </div>

                </td>


                <td class="metric-cell">

                    <div class="metric-label">
                        Public Users
                    </div>

                    <div class="metric-value">
                        {{ number_format($totalUsers) }}
                    </div>

                </td>


                <td class="metric-cell">

                    <div class="metric-label">
                        Administrators
                    </div>

                    <div class="metric-value">
                        {{ number_format($totalAdmins) }}
                    </div>

                </td>

            </tr>

        </table>

    </section>



    {{-- =====================================================
         DIRECTORY DATA HEALTH
         ===================================================== --}}

    <section class="section">

        <h2 class="section-title">
            Directory Data Health
        </h2>

        <table class="metric-table">

            <tr>

                <td class="metric-cell">

                    <div class="metric-label">
                        Complete Agencies
                    </div>

                    <div class="metric-value green">
                        {{ number_format($completeAgencies) }}
                    </div>

                </td>


                <td class="metric-cell">

                    <div class="metric-label">
                        Agencies Needing Attention
                    </div>

                    <div class="metric-value amber">
                        {{ number_format($incompleteAgencies) }}
                    </div>

                </td>


                <td class="metric-cell">

                    <div class="metric-label">
                        Complete FAQs
                    </div>

                    <div class="metric-value green">
                        {{ number_format($completeFaqs) }}
                    </div>

                </td>


                <td class="metric-cell">

                    <div class="metric-label">
                        FAQs Needing Attention
                    </div>

                    <div class="metric-value amber">
                        {{ number_format($incompleteFaqs) }}
                    </div>

                </td>

            </tr>

        </table>

    </section>



    {{-- =====================================================
         CITIZEN INQUIRIES
         ===================================================== --}}

    <section class="section">

        <h2 class="section-title">
            Citizen Inquiries
        </h2>

        <table class="metric-table">

            <tr>

                <td class="metric-cell">

                    <div class="metric-label">
                        Total Inquiries
                    </div>

                    <div class="metric-value blue">
                        {{ number_format($totalInquiries) }}
                    </div>

                </td>


                <td class="metric-cell">

                    <div class="metric-label">
                        Pending
                    </div>

                    <div class="metric-value amber">
                        {{ number_format($pendingInquiries) }}
                    </div>

                </td>


                <td class="metric-cell">

                    <div class="metric-label">
                        Answered
                    </div>

                    <div class="metric-value green">
                        {{ number_format($answeredInquiries) }}
                    </div>

                </td>


                <td class="metric-cell">

                    <div class="metric-label">
                        Response Rate
                    </div>

                    <div class="metric-value blue">
                        {{ $responseRate }}%
                    </div>

                </td>

            </tr>

        </table>


        <table class="status-row">

            <tr>

                <td>
                    Average Response Time
                </td>

                <td>
                    {{ $averageResponseTime ?? 'No answered inquiries yet' }}
                </td>

            </tr>


            <tr>

                <td>
                    Answers Seen
                </td>

                <td class="green">
                    {{ number_format($seenAnswers) }}
                </td>

            </tr>


            <tr>

                <td>
                    Awaiting Citizen View
                </td>

                <td class="amber">
                    {{ number_format($unseenAnswers) }}
                </td>

            </tr>

        </table>

    </section>



    {{-- =====================================================
         INQUIRY ACTIVITY
         ===================================================== --}}

    <section class="section">

        <h2 class="section-title">
            Inquiry Activity — Last 7 Days
        </h2>

        <p class="section-subtitle">
            Daily citizen inquiries submitted and administrator responses.
        </p>


        <table class="report-table">

            <thead>

                <tr>

                    <th>
                        Date
                    </th>

                    <th>
                        Day
                    </th>

                    <th class="number">
                        Submitted
                    </th>

                    <th class="number">
                        Answered
                    </th>

                </tr>

            </thead>


            <tbody>

                @forelse($inquiryTrend as $day)

                    <tr>

                        <td>
                            {{ \Carbon\Carbon::parse($day['date'])->format('M d, Y') }}
                        </td>

                        <td>
                            {{ $day['label'] }}
                        </td>

                        <td class="number">
                            {{ number_format($day['submitted']) }}
                        </td>

                        <td class="number">
                            {{ number_format($day['answered']) }}
                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="4">
                            No inquiry activity is available.
                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </section>



    {{-- =====================================================
         CHATBOT PERFORMANCE
         ===================================================== --}}

    <section class="section">

        <h2 class="section-title">
            Chatbot & Knowledge Base Performance
        </h2>

        <table class="metric-table">

            <tr>

                <td class="metric-cell">

                    <div class="metric-label">
                        Knowledge Questions
                    </div>

                    <div class="metric-value purple">
                        {{ number_format($knowledgeQuestions) }}
                    </div>

                </td>


                <td class="metric-cell">

                    <div class="metric-label">
                        FAQ Answer Rate
                    </div>

                    <div class="metric-value blue">
                        {{ $faqAnswerRate }}%
                    </div>

                </td>


                <td class="metric-cell">

                    <div class="metric-label">
                        FAQ Answers
                    </div>

                    <div class="metric-value green">
                        {{ number_format($faqAnswered) }}
                    </div>

                </td>


                <td class="metric-cell">

                    <div class="metric-label">
                        Fallback Rate
                    </div>

                    <div class="metric-value amber">
                        {{ $fallbackRate }}%
                    </div>

                </td>

            </tr>

        </table>


        <table class="status-row">

            <tr>

                <td>
                    Fallback Questions
                </td>

                <td class="amber">
                    {{ number_format($fallbackQuestions) }}
                </td>

            </tr>


            <tr>

                <td>
                    Clarifications
                </td>

                <td>
                    {{ number_format($clarificationQuestions) }}
                </td>

            </tr>


            <tr>

                <td>
                    Rule-Based Matches
                </td>

                <td>
                    {{ number_format($ruleMatches) }}
                </td>

            </tr>


            <tr>

                <td>
                    Semantic Matches
                </td>

                <td class="purple">
                    {{ number_format($semanticMatches) }}
                </td>

            </tr>


            <tr>

                <td>
                    Total Chatbot Interactions
                </td>

                <td>
                    {{ number_format($totalChatbotInteractions) }}
                </td>

            </tr>

        </table>

    </section>



    {{-- =====================================================
         MOST USED FAQs
         ===================================================== --}}

    <section class="section">

        <h2 class="section-title">
            Most Used FAQs
        </h2>


        @if($popularFaqs->isNotEmpty())

            <table class="report-table">

                <thead>

                    <tr>

                        <th>
                            #
                        </th>

                        <th>
                            FAQ
                        </th>

                        <th>
                            Agency
                        </th>

                        <th class="number">
                            Uses
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @foreach($popularFaqs as $faq)

                        <tr>

                            <td>
                                {{ $loop->iteration }}
                            </td>

                            <td>
                                {{ $faq->faq?->question ?? 'FAQ no longer available' }}
                            </td>

                            <td>
                                {{ $faq->faq?->agency?->agency_name ?? 'Agency no longer available' }}
                            </td>

                            <td class="number">
                                {{ number_format($faq->usage_count) }}
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        @else

            <div class="empty">
                No FAQ usage has been recorded yet.
            </div>

        @endif

    </section>



    {{-- =====================================================
         MOST REQUESTED AGENCIES
         ===================================================== --}}

    <section class="section">

        <h2 class="section-title">
            Most Requested Agencies
        </h2>


        @if($popularAgencies->isNotEmpty())

            <table class="report-table">

                <thead>

                    <tr>

                        <th>
                            #
                        </th>

                        <th>
                            Agency
                        </th>

                        <th class="number">
                            Chatbot Interactions
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @foreach($popularAgencies as $agency)

                        <tr>

                            <td>
                                {{ $loop->iteration }}
                            </td>

                            <td>
                                {{ $agency->agency?->agency_name ?? 'Agency no longer available' }}
                            </td>

                            <td class="number">
                                {{ number_format($agency->interaction_count) }}
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        @else

            <div class="empty">
                No agency-related chatbot interactions have been recorded yet.
            </div>

        @endif

    </section>



    {{-- =====================================================
         RECENT SYSTEM ACTIVITY
         ===================================================== --}}

    <section class="section">

        <h2 class="section-title">
            Recent System Activity
        </h2>

        <p class="section-subtitle">
            Most recent administrative activity recorded by KNOWURLOCAL.
        </p>


        @if($recentActivity->isNotEmpty())

            <table class="report-table">

                <thead>

                    <tr>

                        <th>
                            Date
                        </th>

                        <th>
                            USER
                        </th>

                        <th>
                            ROLE
                        </th>

                        <th>
                            ACTION
                        </th>

                        <th>
                            Page
                        </th>

                        <th>
                            Description
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @foreach($recentActivity as $activity)

                        <tr>

                            <td>
                                {{ optional($activity->created_at)->format('M d, Y H:i') }}
                            </td>

                            <td>
                                {{ $activity->actor_name }}
                            </td>

                            <td>
                                {{ match ($activity->user?->role) {
                                    'superadmin' => 'Superadmin',
                                    'admin' => 'Admin',
                                    'user' => 'Public User',
                                    default => 'Unknown',
                                } }}
                            </td>

                            <td class="activity-action">
                                {{ $activity->action_label }}
                            </td>

                            <td>
                                {{ $activity->page_label }}
                            </td>

                            <td class="activity-description">
                                {{ $activity->description ?? $activity->action_label }}
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        @else

            <div class="empty">
                No recent system activity is available.
            </div>

        @endif

    </section>



    {{-- =====================================================
         FOOTER
         ===================================================== --}}

    <footer class="report-footer">

        KNOWURLOCAL — Administrative Dashboard Report

        <br>

        This report was generated automatically by the system.

    </footer>


</body>

</html>