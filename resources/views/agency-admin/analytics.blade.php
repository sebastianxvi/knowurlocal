@extends('layouts.admin')
@section('title', 'KNOWURLOCAL | Agency Analytics')
@section('page-title', 'Agency Analytics')
@section('page-subtitle', 'Support trends and FAQ opportunities for your assigned agency')
@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/admin/analytics.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/dashboard.css') }}">
<style>
.ag-analytics{display:grid;gap:18px;color:var(--text-primary,#172033)}.ag-analytics-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.ag-analytics-head p{margin:5px 0 0;color:var(--text-secondary,#64748b);font-size:13px}.ag-export{border:0;border-radius:10px;background:#2859a8;color:white;padding:11px 15px;font-weight:700;cursor:pointer}.ag-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.ag-metric,.ag-panel{border:1px solid var(--border-color,#e5e9f0);border-radius:15px;background:var(--surface,#fff);padding:19px;min-width:0}.ag-metric span{font-size:12px;color:var(--text-secondary,#64748b)}.ag-metric strong{display:block;font-size:28px;margin-top:9px}.ag-panel h3{font-size:16px;margin:0 0 6px}.ag-muted{font-size:12px;color:var(--text-secondary,#64748b);margin:0 0 16px}.ag-chart{height:220px;display:flex;align-items:end;gap:4px;padding:10px 0 0;border-bottom:1px solid var(--border-color,#e5e9f0)}.ag-bar-wrap{flex:1;min-width:0;height:100%;display:flex;flex-direction:column;justify-content:end;align-items:center;gap:6px}.ag-bar{width:100%;max-width:22px;min-height:2px;border-radius:5px 5px 0 0;background:#4778c7}.ag-bar-label{font-size:9px;color:var(--text-secondary,#64748b);white-space:nowrap;transform:rotate(-35deg);transform-origin:top center;margin-top:4px}.ag-table{width:100%;border-collapse:collapse}.ag-table th,.ag-table td{padding:11px 9px;text-align:left;border-bottom:1px solid var(--border-color,#edf0f5);font-size:13px}.ag-table th{font-size:10px;text-transform:uppercase;color:var(--text-secondary,#64748b)}.ag-table tr:last-child td{border:0}.ag-empty{text-align:center;padding:25px;color:var(--text-secondary,#64748b);font-size:13px}
.ag-metric,.ag-panel{box-shadow:0 1px 2px rgba(16,24,40,.025);transition:border-color .16s,box-shadow .16s}
.ag-metric:hover,.ag-panel:hover{border-color:#c5d5ee;box-shadow:0 5px 18px rgba(24,49,88,.06)}
.ag-metric [data-tip],.ag-panel [data-tip],.ag-table [data-tip]{position:relative;cursor:help;border-bottom:1px dotted #9aa9bd}
.ag-metric [data-tip]:focus::after,.ag-metric [data-tip]:hover::after,.ag-panel [data-tip]:focus::after,.ag-panel [data-tip]:hover::after,.ag-table [data-tip]:focus::after,.ag-table [data-tip]:hover::after{content:attr(data-tip);position:absolute;z-index:20;left:0;top:calc(100% + 8px);width:max-content;max-width:min(280px,75vw);white-space:normal;background:#17243a;color:#fff;padding:9px 11px;border-radius:8px;font-size:11px;line-height:1.45;font-weight:500;box-shadow:0 8px 24px rgba(15,23,42,.18)}
.ag-chart{height:230px;display:flex;align-items:stretch;gap:5px;padding:18px 4px 28px;position:relative;border-bottom:1px solid var(--border-color,#e5e9f0);background:repeating-linear-gradient(to top,transparent 0,transparent 49px,rgba(148,163,184,.13) 50px)}
.ag-bar-wrap{position:relative;flex:1;min-width:5px;height:100%;display:flex;flex-direction:column;justify-content:end;align-items:center;gap:0}
.ag-bar{display:block;width:min(70%,22px);max-width:22px;min-height:0;border-radius:5px 5px 0 0;background:linear-gradient(180deg,#5b8ee0,#3c6fc0);transition:filter .15s,transform .15s;cursor:help}
.ag-bar-wrap:hover .ag-bar,.ag-bar-wrap:focus-within .ag-bar{filter:brightness(.9);transform:scaleX(1.12)}
.ag-bar-label{position:absolute;bottom:-23px;left:50%;transform:translateX(-50%);font-size:9px;color:var(--text-secondary,#64748b);white-space:nowrap}
.ag-chart-key{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;color:var(--text-secondary,#64748b);font-size:11px;margin-top:12px}
.ag-note{display:flex;gap:10px;align-items:flex-start;padding:12px 14px;border-radius:10px;background:#f4f7fc;color:#52647d;font-size:12px;line-height:1.5}
.ag-note i{color:#4778c7;font-size:16px;flex:none}
.ag-table tbody tr:hover{background:#f8faff}
\n@media(max-width:760px){.ag-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.ag-metric,.ag-panel{padding:14px}.ag-chart{gap:2px;height:180px}.ag-bar-label{font-size:8px}}@media print{.admin-sidebar,.sidebar,.topbar,.admin-header,.ag-export,button{display:none!important}body{background:white!important}.main-content,.content-wrapper{margin:0!important;padding:0!important}.ag-panel,.ag-metric{break-inside:avoid;box-shadow:none!important}.ag-chart{height:160px}}
@media print {
    @page { size: A4 portrait; margin: 12mm; }
    body { background:#fff !important; }
    .ag-analytics { gap:12px !important; color:#172033 !important; }
    .analytics-export-menu, .admin-export-dropdown, button, .ag-export { display:none !important; }
    .ag-metrics { grid-template-columns:repeat(2,minmax(0,1fr)) !important; }
    .ag-panel, .ag-metric { break-inside:avoid; box-shadow:none !important; }
    .ag-table { min-width:0 !important; }
    .ag-chart { break-inside:avoid; }
}
</style>
@endpush
@section('content')
@php($maxDaily = max(1, max($dailyValues ?: [0])))
<div class="ag-analytics">
    <div class="ag-analytics-head">
        <div><h2 style="margin:0;font-size:21px">{{ $agency->name ?? 'Agency' }} analytics</h2><p>Support requests over the last 30 days · repeated questions over the last 90 days</p></div>
        <details class="analytics-export-menu">
            <summary class="analytics-export-button" aria-label="Export agency analytics report">
                <i class="ph-light ph-export" aria-hidden="true"></i><span>Export</span><i class="ph-light ph-caret-down export-caret" aria-hidden="true"></i>
            </summary>
            <div class="admin-export-dropdown">
                <a href="#" onclick="window.print(); return false;">
                    <i class="ph-light ph-file-pdf" aria-hidden="true"></i>
                    <span><strong>Analytics report</strong><small>Save agency analytics as PDF</small></span>
                </a>
            </div>
        </details>
    </div>
    <section class="ag-metrics">
        <div class="ag-metric"><span tabindex="0" data-tip="All non-deleted support tickets recorded for your agency.">Total support requests</span><strong>{{ number_format($total) }}</strong></div>
        <div class="ag-metric"><span tabindex="0" data-tip="Tickets that still need a reply or another action from your team.">Needs attention</span><strong>{{ number_format($openCount) }}</strong></div>
        <div class="ag-metric"><span tabindex="0" data-tip="Tickets currently marked as answered. This is a count, not a response-speed measure.">Answered</span><strong>{{ number_format((int)($statusCounts['answered'] ?? 0)) }}</strong></div>
        <div class="ag-metric"><span tabindex="0" data-tip="FAQs currently available to citizens on your agency’s public information pages.">Published FAQs</span><strong>{{ number_format($faqCount) }}</strong></div>
    </section>
    <section class="ag-panel">
        <h3>Incoming support requests</h3><p class="ag-muted">Daily ticket volume for the past 30 days. Hover or focus a bar to see the exact date and count.</p>
        <div class="ag-chart" role="img" aria-label="Daily support request volume for the last 30 days">
            @foreach($dailyValues as $index => $value)
                <div class="ag-bar-wrap">
                    <div class="ag-bar" tabindex="0" role="img"
                         aria-label="{{ $dailyLabels[$index] }}: {{ $value }} support requests"
                         data-tip="{{ $dailyLabels[$index] }} · {{ $value }} support requests"
                         title="{{ $dailyLabels[$index] }}: {{ $value }} support requests"
                         style="height:{{ $value > 0 ? max(3, ($value / $maxDaily) * 100) : 0 }}%"></div>
                    @if($index % 5 === 0 || $index === count($dailyValues)-1)<span class="ag-bar-label">{{ $dailyLabels[$index] }}</span>@endif
                </div>
            @endforeach
        </div>
    </section>
    <section class="ag-panel">
        <h3>Request status breakdown</h3><p class="ag-muted">Current non-deleted tickets belonging to your agency only.</p>
        <div style="overflow-x:auto"><table class="ag-table"><thead><tr><th>Status</th><th>Tickets</th><th>Share <span tabindex="0" data-tip="Share is the percentage of all current tickets represented by this status.">ⓘ</span></th></tr></thead><tbody>
        @foreach(['pending'=>'Needs response','needs_follow_up'=>'Needs follow-up','awaiting_confirmation'=>'Awaiting citizen','answered'=>'Answered'] as $key=>$label)
            @php($amount = (int)($statusCounts[$key] ?? 0))
            <tr><td>{{ $label }}</td><td>{{ number_format($amount) }}</td><td>{{ $total ? round($amount/$total*100) : 0 }}%</td></tr>
        @endforeach
        </tbody></table></div>
        <p class="ag-note" style="margin:12px 0 0"><i class="ph-light ph-info" aria-hidden="true"></i><span>Answered rate: {{ $responseRate }}%. This is a simple current-status indicator, not a measure of response speed.</span></p>
    </section>
    <section class="ag-panel">
        <h3>FAQ opportunities</h3><p class="ag-muted">Repeated question wording from the last 90 days. Use these patterns to decide which public answers may deserve an FAQ.</p><div class="ag-note"><i class="ph-light ph-lightbulb" aria-hidden="true"></i><span>These are suggestions, not automatic FAQ creation. Review each question and compare it with existing FAQs before publishing.</span></div>
        @if($popularQuestions->isEmpty())
            <div class="ag-empty">No repeated question patterns yet. As the agency receives more tickets, recurring wording will appear here.</div>
        @else
            <div style="overflow-x:auto"><table class="ag-table"><thead><tr><th>Repeated question</th><th>Tickets</th><th>Suggested action</th></tr></thead><tbody>
            @foreach($popularQuestions as $question)
                <tr><td>{{ $question['question'] }}</td><td>{{ $question['count'] }}</td><td>Review against existing FAQs</td></tr>
            @endforeach
            </tbody></table></div>
        @endif
    </section>
</div>
@endsection
