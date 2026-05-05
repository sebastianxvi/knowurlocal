@extends('layouts.admin')

@section('title', 'KNOWURLOCAL | ' . ucfirst(auth()->user()->role) . ' Module')

@section('page-title', 'Dashboard')
@section('page-subtitle', 'Overview and analytics of the system')

@section('content')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/admin/dashboard.css') }}">
@endpush

<!-- ================= TABS ================= -->

<div class="dashboard-tabs">

<button class="tab active" data-tab="overview">
    <i class="ph-light ph-house"></i>
    Overview
</button>

<button class="tab" data-tab="analytics">
    <i class="ph-light ph-chart-line"></i>
    Analytics
</button>

</div>

<!-- ================= CONTENT WRAPPER ================= -->

<div class="dashboard-content">
    

<!-- ================= OVERVIEW ================= -->

<div class="dashboard-section active" id="overview">

    <!-- ================= HEADER ================= -->
    <div class="section-header">
        <div class="section-label">
            <i class="ph-light ph-house"></i>
            <span>Overview</span>
        </div>
        <div class="section-line"></div>
    </div>

    <!-- ================= KPI GRID ================= -->
    <div class="cards">

        <!-- PRIMARY -->
        <div class="dashboard-card blue primary">
            <div class="card-header">
                <i class="ph-light ph-buildings"></i>
                <span>Total Agencies</span>
            </div>
            <h2>{{ $totalAgencies }}</h2>
            <small>Registered organizations</small>
        </div>

        <div class="dashboard-card purple">
            <div class="card-header">
                <i class="ph-light ph-chat-centered-text"></i>
                <span>Total FAQs</span>
            </div>
            <h2>{{ $totalFaqs }}</h2>
            <small>Knowledge base entries</small>
        </div>

        <div class="dashboard-card green">
            <div class="card-header">
                <i class="ph-light ph-chat-circle-dots"></i>
                <span>Total Queries</span>
            </div>
            <h2>{{ $totalChats }}</h2>
            <small>All chatbot interactions</small>
        </div>

        <div class="dashboard-card orange">
            <div class="card-header">
                <i class="ph-light ph-target"></i>
                <span>Accuracy</span>
            </div>
            <h2>{{ $accuracy }}%</h2>
            <small>Successful responses</small>
        </div>

    </div>

    <!-- ================= ACTIVITY ================= -->
    <div class="section-header">
        <div class="section-label">
            <i class="ph-light ph-pulse"></i>
            <span>Activity</span>
        </div>
        <div class="section-line"></div>
    </div>

    <div class="cards">

        <div class="dashboard-card blue">
            <div class="card-header">
                <i class="ph-light ph-calendar"></i>
                <span>Today</span>
            </div>
            <h2>{{ $todayChats }}</h2>
            <small>Queries today</small>
        </div>

        <div class="dashboard-card purple">
            <div class="card-header">
                <i class="ph-light ph-calendar-blank"></i>
                <span>This Week</span>
            </div>
            <h2>{{ $weekChats }}</h2>
            <small>Weekly activity</small>
        </div>

        <div class="dashboard-card orange">
            <div class="card-header">
                <i class="ph-light ph-warning-circle"></i>
                <span>Fallback Rate</span>
            </div>
            <h2>{{ $fallbackRate }}%</h2>
            <small>Unanswered queries</small>
        </div>

        <div class="dashboard-card red">
            <div class="card-header">
                <i class="ph-light ph-x-circle"></i>
                <span>Failed Queries</span>
            </div>
            <h2>{{ $failedQueries }}</h2>
            <small>Fallback count</small>
        </div>

    </div>

    <!-- ================= INSIGHTS ================= -->
    <div class="section-header">
        <div class="section-label">
            <i class="ph-light ph-lightbulb"></i>
            <span>Insights</span>
        </div>
        <div class="section-line"></div>
    </div>

    <div class="cards">

        <div class="dashboard-card green">
            <div class="card-header">
                <i class="ph-light ph-chat-centered"></i>
                <span>Top Question</span>
            </div>
            <h2>{{ $topQuestionCount }}</h2>
            <small>{{ $topQuestion }}</small>
        </div>

        <div class="dashboard-card blue">
            <div class="card-header">
                <i class="ph-light ph-buildings"></i>
                <span>Top Agency</span>
            </div>
            <h2>{{ $topAgencyCount }}</h2>
            <small>{{ $topAgency }}</small>
        </div>

        <div class="dashboard-card purple">
            <div class="card-header">
                <i class="ph-light ph-clock"></i>
                <span>Peak Hour</span>
            </div>
            <h2>{{ $peakHour }}</h2>
            <small>Most active time</small>
        </div>

        <div class="dashboard-card orange">
            <div class="card-header">
                <i class="ph-light ph-users"></i>
                <span>Active Users</span>
            </div>
            <h2>{{ $activeUsersToday }}</h2>
            <small>Today</small>
        </div>

    </div>

    <!-- ================= ADMIN ================= -->
    <div class="section-header">
        <div class="section-label">
            <i class="ph-light ph-user-gear"></i>
            <span>Admin</span>
        </div>
        <div class="section-line"></div>
    </div>

    <div class="cards">

        <div class="dashboard-card blue">
            <div class="card-header">
                <i class="ph-light ph-sign-in"></i>
                <span>Admin Logins</span>
            </div>
            <h2>{{ $adminLogins }}</h2>
            <small>Today</small>
        </div>

        <div class="dashboard-card purple">
            <div class="card-header">
                <i class="ph-light ph-clock-counter-clockwise"></i>
                <span>Actions</span>
            </div>
            <h2>{{ $changesToday }}</h2>
            <small>All activity</small>
        </div>

        <div class="dashboard-card green">
            <div class="card-header">
                <i class="ph-light ph-crown"></i>
                <span>Top Admin</span>
            </div>
            <h2>{{ $mostActiveAdminCount }}</h2>
            <small>{{ $mostActiveAdmin }}</small>
        </div>

        <div class="dashboard-card red">
            <div class="card-header">
                <i class="ph-light ph-warning-circle"></i>
                <span>Last Action</span>
            </div>
            <h2>{{ $lastAction }}</h2>
            <small>{{ $lastActionUser }}</small>
        </div>

    </div>

</div>








<div class="dashboard-section" id="analytics">
    <div class="analytics-controls">
        <button onclick="window.location.href='/admin/dashboard/export'" class="print-btn">
            <i class="ph-light ph-download"></i>
            Export PDF
        </button>

    </div>

    <div class="content-container">
        <div class="analytics-container">

            <!-- ================= TOP GRID ================= -->
            <div class="bento-grid">

                <!-- INSIGHTS -->
                <div class="bento-card primary">
                    <div class="analytics-header">
                        <i class="ph-light ph-brain"></i>
                        <span>System Insights</span>
                    </div>

                    <ul class="insight-list">

                        <li>
                            📈 Usage increased by <strong>{{ $growthRate }}%</strong> this week
                        </li>

                        <li>
                            🔥 Peak activity at <strong>{{ $peakHour }}</strong>
                        </li>

                        <li>
                            🧠 Most asked about <strong>{{ $topAgency }}</strong>
                        </li>

                        <li>
                            🎯 Accuracy: <strong>{{ $accuracy }}%</strong>
                        </li>

                        @if($fallbackRate > 10)
                        <li class="warn">
                            ⚠️ High fallback rate ({{ $fallbackRate }}%)
                        </li>
                        @endif

                        <li>
                            👥 {{ $activeUsersToday }} active users today
                        </li>

                    </ul>
                </div>

                <!-- ENGAGEMENT -->
                <div class="bento-card blue">
                    <div class="analytics-header">
                        <i class="ph-light ph-trend-up"></i>
                        <span>Engagement</span>
                    </div>
                    <h2>{{ $avgQueries }}</h2>
                    <p class="muted">Queries per user</p>
                </div>

                <!-- TOTAL -->
                <div class="bento-card green">
                    <div class="analytics-header">
                        <i class="ph-light ph-chat-circle-dots"></i>
                        <span>Total Queries</span>
                    </div>
                    <h2>{{ $totalChats }}</h2>
                    <p class="muted">All interactions</p>
                </div>

            </div>

            <!-- ================= USAGE TREND ================= -->
            <div class="section-header">
                <div class="section-label">
                    <i class="ph-light ph-chart-line"></i>
                    <span>Usage Trend</span>
                </div>

                <div class="trend-summary">

                    <div class="trend-item">
                        <span>Today</span>
                        <strong>{{ $todayChats }}</strong>
                    </div>

                    <div class="trend-item">
                        <span>This Week</span>
                        <strong>{{ $weekChats }}</strong>
                    </div>

                    <div class="trend-item {{ $growthRate > 0 ? 'up' : 'down' }}">
                        <span>Growth</span>
                        <strong>
                            @if($growthRate > 0)
                                +{{ $growthRate }}%
                            @else
                                {{ $growthRate }}%
                            @endif
                        </strong>
                    </div>

                </div>
                <div class="section-line"></div>

                <div class="chart-insight">

                    @if($growthRate > 20)
                        🚀 Rapid growth — system usage increasing fast
                    @elseif($growthRate > 0)
                        📈 Steady growth in usage
                    @elseif($growthRate < 0)
                        📉 Decline in activity — investigate drop
                    @else
                        ➖ No significant change
                    @endif

                </div>
            </div>

            <div class="bento-card primary large">
                <div id="usageChart"></div>
            </div>

            <!-- ================= HEATMAP ================= -->
            <div class="section-header">
                <div class="section-label">
                    <i class="ph-light ph-grid-four"></i>
                    <span>Usage Heatmap</span>
                </div>
                <div class="section-line"></div>

                <div class="chart-insight">

                    @if($peakHour !== '-')
                        🔥 Most activity happens at <strong>{{ $peakHour }}</strong>
                    @endif

                    @if($activeUsersToday > 0)
                        👥 {{ $activeUsersToday }} users active today
                    @endif

                </div>
            </div>

            <div class="bento-card large">
                <div id="heatmapChart"></div>
            </div>

            <!-- ================= BREAKDOWN ================= -->
            <div class="bento-grid">

                <div class="bento-card large purple">
                    <div class="analytics-header">
                        <i class="ph-light ph-chart-pie"></i>
                        <span>Response Quality</span>
                    </div>

                    <div class="chart-stats">

                        <div class="stat">
                            <span class="label">Total Queries</span>
                            <span class="value">{{ $totalChats }}</span>
                        </div>

                        <div class="stat success">
                            <span class="label">Resolved</span>
                            <span class="value">{{ $faqCount }}</span>
                        </div>

                        <div class="stat danger">
                            <span class="label">Fallback</span>
                            <span class="value">{{ $fallbackCount }}</span>
                        </div>

                    </div>


                    <div class="chart-container">
                    <div id="accuracyChart"></div>
                </div>

                    <div class="chart-insight">

                        @if($fallbackRate > 10)
                            ⚠️ High fallback rate ({{ $fallbackRate }}%) — improve FAQs
                        @elseif($accuracy > 90)
                            ✅ Excellent response quality ({{ $accuracy }}%)
                        @else
                            ⚠️ Moderate accuracy — needs improvement
                        @endif

                    </div>
                </div>

                <div class="bento-card large blue">
                    <div class="analytics-header">
                        <i class="ph-light ph-chat-circle-text"></i>
                        Chatbot Response Breakdown
                    </div>


                    <div class="chart-summary">

                        <span>
                            Total Queries:
                            <strong>{{ $totalChats }}</strong>
                        </span>

                        <span class="success">
                            {{ $faqCount }} ({{ round(($faqCount/$totalChats)*100,1) }}%) answered
                        </span>

                        <span class="danger">
                            {{ $fallbackCount }} ({{ round(($fallbackCount/$totalChats)*100,1) }}%) failed
                        </span>

                    </div>

                    <div id="featureChart"></div>

                    <div class="chart-insight">

                        @if($fallbackCount > 0 && $fallbackCount/$totalChats > 0.2)
                            ⚠️ High failure rate — users are not getting answers
                        @elseif($faqCount > $fallbackCount)
                            ✅ Most queries are successfully answered
                        @else
                            ⚠️ Chatbot needs improvement
                        @endif

                    </div>
                </div>

            </div>

            <!-- ================= LOWER GRID ================= -->
            <div class="bento-grid">

                <div class="bento-card">
                    <div class="analytics-header">
                        <i class="ph-light ph-chat-centered"></i>
                        <span>Top Questions</span>
                    </div>

                    <ul class="mini-list">
                    @forelse($topQuestions as $q)
                        <li>
                            <span class="name">{{ $q->question }}</span>
                            <span class="count">{{ $q->count }}</span>
                        </li>
                    @empty
                        <li class="empty">No data yet</li>
                    @endforelse
                    </ul>
                </div>

                <div class="bento-card">
                    <div class="analytics-header">
                        <i class="ph-light ph-clock"></i>
                        <span>Recent Activity</span>
                    </div>

                    <ul class="activity-feed">
                    @forelse($recentLogs as $log)
                        <li>
                            <strong>{{ $log->action }}</strong>
                            <span>
                                {{ optional($log->user)->first_name ?? 'System' }}
                            </span>
                        </li>
                    @empty
                        <li class="empty">No activity yet</li>
                    @endforelse
                    </ul>
                </div>

            </div>

        </div>
    </div>

</div>

@endsection

@push('scripts')


<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>


<script>
document.addEventListener("DOMContentLoaded", () => {

    /* ================= DATA ================= */
    const trendLabels = @json($chatTrend->pluck('date'));
    const trendValues = @json($chatTrend->pluck('count'));

    const agencyLabels = @json($agencyData->pluck('agency_name'));
    const agencyValues = @json($agencyData->pluck('count'));

    const responseLabels = @json($responseLabels);
    const responseValues = @json($responseValues);

    const featureLabels = @json($featureLabels);
    const featureValues = @json($featureValues);
    window.heatmapData = @json($heatmap ?? []);


    /* ================= USAGE CHART ================= */
    new ApexCharts(document.querySelector("#usageChart"), {
        chart: { type: 'area', height: 260, toolbar:{show:false} },
        series: [{ name:"Chats", data: trendValues }],
        stroke: { curve:'smooth', width:3 },
        fill: {
            type:'gradient',
            gradient:{ opacityFrom:0.4, opacityTo:0.05 }
        },
        colors:['#2563eb'],
        xaxis:{ categories: trendLabels },
        yaxis:{ show:false },
        grid:{ borderColor:'rgba(0,0,0,0.05)' }
    }).render();


    /* ================= RESPONSE ================= */
    new ApexCharts(document.querySelector("#accuracyChart"), {
        chart:{ type:'donut' },
        series: responseValues,
        labels: responseLabels,
        colors:['#22c55e','#ef4444','#f59e0b','#3b82f6']
    }).render();


    /* ================= FEATURE ================= */
    new ApexCharts(document.querySelector("#featureChart"), {
        chart:{ type:'bar' },
        series:[{ data: featureValues }],
        xaxis:{ categories: featureLabels },
        colors:['#10b981']
    }).render();


    /* ================= AGENCY ================= */
    new ApexCharts(document.querySelector("#agencyChart"), {
        chart:{ type:'bar' },
        series:[{ data: agencyValues }],
        xaxis:{ categories: agencyLabels },
        colors:['#7c3aed']
    }).render();

});

/* ================= HEATMAP ================= */
new ApexCharts(document.querySelector("#heatmapChart"), {
    chart: {
        type: 'heatmap',
        height: 260,
        toolbar: { show: false }
    },

    series: heatmapData,

    dataLabels: { enabled: false },

    colors: ['#2563eb'],

    plotOptions: {
        heatmap: {
            shadeIntensity: 0.5,
            radius: 4
        }
    },

    xaxis: {
        categories: [
            '0','1','2','3','4','5','6','7','8','9','10','11',
            '12','13','14','15','16','17','18','19','20','21','22','23'
        ]
    }

}).render();
</script>

<script src="{{ asset('jsfiles/admin/dashboard.js') }}"></script>

@endpush