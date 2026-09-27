<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>KNOWURLOCAL Dashboard Summary</title>@include('admin.reports._styles')</head><body>
@php($reportTitle = 'Dashboard Summary')
@include('admin.reports._header')
<main class="report-shell">
@include('admin.reports._dashboard-content')
@include('admin.reports._footer')
</main>
</body></html>
