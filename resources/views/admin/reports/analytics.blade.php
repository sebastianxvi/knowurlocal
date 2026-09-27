<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>KNOWURLOCAL Analytics Report</title>@include('admin.reports._styles')</head><body>
@php($reportTitle = 'Analytics Report')
@include('admin.reports._header', ['reportMeta' => 'Period: ' . $periodLabel . ' · Generated on ' . now()->format('F d, Y \a\t h:i A')])
<main class="report-shell">
@include('admin.reports._analytics-content')
@include('admin.reports._footer')
</main>
</body></html>
