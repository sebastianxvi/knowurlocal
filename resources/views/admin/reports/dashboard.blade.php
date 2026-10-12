<!DOCTYPE html><html lang="en"><head>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/knowurlocal-kyl-logo.svg') }}?v=3" sizes="any">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=3">
<meta charset="UTF-8"><title>KNOWURLOCAL Dashboard Summary</title>@include('admin.reports._styles')</head><body>
@php($reportTitle = 'Dashboard Summary')
@include('admin.reports._header')
<main class="report-shell">
@include('admin.reports._dashboard-content')
@include('admin.reports._footer')
</main>
</body></html>
