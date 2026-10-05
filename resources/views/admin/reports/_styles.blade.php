<style>
@page {
    margin: 28px 32px 34px 32px;
}
* { box-sizing: border-box; }
body {
    margin: 0;
    font-family: DejaVu Sans, Arial, sans-serif;
    font-size: 8.5px;
    line-height: 1.45;
    color: #172033;
    background: #ffffff;
}
.report-shell { width: 100%; }
.report-header {
    margin: 0 0 16px;
    padding: 16px 18px;
    border: 1px solid #dfe6f0;
    border-radius: 14px;
    background: #ffffff;
}
.report-header-table { width: 100%; border-collapse: collapse; }
.report-brand-cell { width: 54%; vertical-align: middle; }
.report-meta-cell { width: 46%; vertical-align: middle; text-align: right; }
.brand {
    margin: 0;
    font-size: 17px;
    line-height: 1.1;
    font-weight: 700;
    letter-spacing: .45px;
    color: #12213d;
}
.brand-subtitle {
    margin-top: 4px;
    font-size: 6.5px;
    line-height: 1;
    font-weight: 700;
    letter-spacing: 1.2px;
    color: #7c8aa1;
}
.report-type {
    display: inline-block;
    padding: 5px 8px;
    border: 1px solid #d7e3fb;
    border-radius: 999px;
    background: #f4f8ff;
    color: #315fbd;
    font-size: 7.5px;
    font-weight: 700;
}
.report-meta {
    margin-top: 6px;
    font-size: 7px;
    color: #7c8aa1;
}
.report-intro {
    margin: -5px 0 15px;
    padding: 10px 12px;
    border-left: 3px solid #315fbd;
    background: #f6f9ff;
    color: #596a84;
    font-size: 7.8px;
}
.section {
    margin: 0 0 13px;
    padding: 12px 13px 13px;
    border: 1px solid #dfe6f0;
    border-radius: 12px;
    background: #ffffff;
    page-break-inside: avoid;
}
.section-title {
    margin: 0 0 4px;
    padding: 0;
    font-size: 11.5px;
    line-height: 1.25;
    color: #12213d;
    font-weight: 700;
}
.section-kicker {
    margin: 0 0 4px;
    font-size: 6.5px;
    line-height: 1;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: #6b7da0;
}
.section-subtitle {
    margin: 0 0 9px;
    font-size: 7.5px;
    line-height: 1.45;
    color: #7c8aa1;
}
.metric-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 5px;
    margin: -3px -5px -4px;
}
.metric-cell {
    width: 25%;
    padding: 9px;
    border: 1px solid #e2e8f0;
    border-radius: 9px;
    background: #f8fafc;
    vertical-align: top;
}
.metric-label {
    font-size: 6.7px;
    color: #718096;
    text-transform: uppercase;
    letter-spacing: .35px;
    font-weight: 700;
}
.metric-value {
    margin-top: 3px;
    font-size: 16px;
    line-height: 1.1;
    font-weight: 700;
    color: #172033;
}
.metric-note {
    margin-top: 4px;
    font-size: 6.9px;
    line-height: 1.35;
    color: #7c8aa1;
}
.metric-cell-blue { background:#f5f8ff; border-color:#dce6fb; }
.metric-cell-green { background:#f3fbf7; border-color:#d8eee2; }
.metric-cell-amber { background:#fffaf0; border-color:#f3e3bb; }
.metric-cell-purple { background:#f8f6ff; border-color:#e5defb; }

.blue{color:#315fbd}.green{color:#18804b}.amber{color:#a36b00}.purple{color:#6957c9}.slate{color:#53647e}
.report-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 7px;
}
.report-table th {
    padding: 6px 7px;
    border-top: 1px solid #dfe6f0;
    border-bottom: 1px solid #d9e0ea;
    background: #f5f7fa;
    font-size: 6.6px;
    font-weight: 700;
    text-align: left;
    color: #61708a;
    text-transform: uppercase;
    letter-spacing: .3px;
}
.report-table td {
    padding: 6px 7px;
    border-bottom: 1px solid #edf0f4;
    font-size: 7.6px;
    vertical-align: top;
}
.report-table tbody tr:nth-child(even) td { background:#fbfcfe; }
.report-table tr:last-child td { border-bottom:none; }
.number { text-align:right; font-weight:700; }
.status-row {
    width:100%;
    border-collapse:collapse;
    margin-top:5px;
}
.status-row td {
    padding:6px 7px;
    border-bottom:1px solid #edf0f4;
    font-size:7.6px;
}
.status-row tr:last-child td { border-bottom:none; }
.status-row td:last-child { text-align:right; font-weight:700; }

.two-column {
    width:100%;
    border-collapse:separate;
    border-spacing:8px 0;
    margin:0 -8px;
}
.two-column-cell { width:50%; vertical-align:top; }

.empty {
    margin-top:7px;
    padding:10px;
    border:1px dashed #d7e0ec;
    border-radius:9px;
    background:#fafbfd;
    text-align:center;
    font-size:7.7px;
    color:#7c8aa1;
}
.callout {
    margin-top: 8px;
    padding: 8px 9px;
    border-radius: 8px;
    background: #f6f9ff;
    border: 1px solid #dce6fb;
    color: #52647f;
    font-size: 7.3px;
}
.callout strong { color:#20385f; }
.progress-track {
    width:100%;
    height:6px;
    margin-top:6px;
    border-radius:6px;
    background:#e9eef5;
}
.progress-fill {
    height:6px;
    border-radius:6px;
    background:#5d7ddd;
}
.progress-fill.green-fill { background:#43a86e; }
.progress-fill.amber-fill { background:#d39a36; }
.progress-label {
    margin-top:4px;
    font-size:6.8px;
    color:#7c8aa1;
}
.bar-table { width:100%; border-collapse:collapse; margin-top:4px; }
.bar-table td { padding:5px 0; vertical-align:middle; font-size:7.3px; }
.bar-name { width:36%; padding-right:8px !important; }
.bar-value { width:9%; text-align:right; font-weight:700; padding-left:8px !important; }
.bar-track {
    height:7px;
    width:100%;
    border-radius:7px;
    background:#e9eef5;
}
.bar-fill {
    height:7px;
    border-radius:7px;
    background:#6a84dc;
}
.bar-fill-green { background:#43a86e; }
.bar-fill-purple { background:#8170d7; }
.bar-fill-amber { background:#d39a36; }

.badge {
    display:inline-block;
    padding:2px 5px;
    border-radius:8px;
    background:#f1f5fb;
    color:#526078;
    font-size:6.7px;
}
.badge-amber{background:#fff5df;color:#a36b00}
.badge-green{background:#ecfdf3;color:#18804b}
.badge-blue{background:#edf4ff;color:#315fbd}
.badge-purple{background:#f3efff;color:#6957c9}
.task-title{font-weight:700}
.task-meta{color:#7b8799;font-size:6.7px;margin-top:2px}
.muted{color:#7b8799}
.report-divider{height:1px;background:#edf0f4;margin:0 0 10px}
.page-break{page-break-before:always}
.keep-together { page-break-inside: avoid; }
.report-footer {
    margin-top: 16px;
    padding-top: 8px;
    border-top: 1px solid #dfe6f0;
    font-size: 6.7px;
    line-height:1.5;
    color: #8793a6;
    text-align: center;
}
</style>