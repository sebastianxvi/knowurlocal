<header class="report-header">
    <table class="report-header-table">
        <tr>
            <td class="report-brand-cell">
                <div class="brand">KNOWURLOCAL</div>
                <div class="brand-subtitle">ADMIN WORKSPACE</div>
            </td>
            <td class="report-meta-cell">
                <div class="report-type">{{ $reportTitle }}</div>
                <div class="report-meta">{{ $reportMeta ?? 'Generated on ' . now()->format('F d, Y \a\t h:i A') }}</div>
            </td>
        </tr>
    </table>
</header>
