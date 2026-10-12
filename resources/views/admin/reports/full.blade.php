<!DOCTYPE html><html lang="en"><head>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/knowurlocal-kyl-logo.svg') }}?v=3" sizes="any">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=3">
<meta charset="UTF-8"><title>KNOWURLOCAL Full Administrative Report</title>@include('admin.reports._styles')</head><body>
@php($reportTitle = 'Full Administrative Report')
@include('admin.reports._header', ['reportMeta' => 'Dashboard summary + analytics · Period: ' . $periodLabel . ' · Generated on ' . now()->format('F d, Y \a\t h:i A')])
<main class="report-shell">
@include('admin.reports._dashboard-content')
<div class="page-break"></div>
@include('admin.reports._analytics-content')
@include('admin.reports._footer')
</main>
</body></html>
