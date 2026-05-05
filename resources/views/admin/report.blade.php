<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #111;
        }

        h1 {
            text-align: center;
            margin-bottom: 10px;
        }

        h2 {
            margin-top: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 4px;
        }

        .grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .card {
            border: 1px solid #ddd;
            padding: 10px;
            width: 48%;
        }

        .label {
            font-size: 11px;
            color: #555;
        }

        .value {
            font-size: 16px;
            font-weight: bold;
        }

    </style>
</head>
<body>

<h1>KNOWURLOCAL Dashboard Report</h1>
<p style="text-align:center;">Generated on {{ now()->format('F d, Y') }}</p>

<h2>Overview</h2>
<div class="grid">
    <div class="card">
        <div class="label">Total Agencies</div>
        <div class="value">{{ $totalAgencies }}</div>
    </div>

    <div class="card">
        <div class="label">Total FAQs</div>
        <div class="value">{{ $totalFaqs }}</div>
    </div>

    <div class="card">
        <div class="label">Total Queries</div>
        <div class="value">{{ $totalChats }}</div>
    </div>

    <div class="card">
        <div class="label">Accuracy</div>
        <div class="value">{{ $accuracy }}%</div>
    </div>
</div>

<h2>Analytics</h2>
<div class="grid">
    <div class="card">
        <div class="label">Today</div>
        <div class="value">{{ $todayChats }}</div>
    </div>

    <div class="card">
        <div class="label">This Week</div>
        <div class="value">{{ $weekChats }}</div>
    </div>

    <div class="card">
        <div class="label">Fallback Rate</div>
        <div class="value">{{ $fallbackRate }}%</div>
    </div>

    <div class="card">
        <div class="label">Fallback Count</div>
        <div class="value">{{ $fallbackCount }}</div>
    </div>
</div>

<h2>Insights</h2>
<div class="grid">
    <div class="card">
        <div class="label">Top Question</div>
        <div class="value">{{ $topQuestion }}</div>
    </div>

    <div class="card">
        <div class="label">Top Agency</div>
        <div class="value">{{ $topAgency }}</div>
    </div>
</div>

</body>
</html>