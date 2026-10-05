<div class="report-intro">
    <strong>At a glance:</strong> This report summarizes the current KNOWURLOCAL workspace, the items that may need administrator attention, citizen feedback, and active team work.
</div>

<section class="section">
    <div class="section-kicker">Current snapshot</div>
    <h2 class="section-title">How the system looks right now</h2>
    <p class="section-subtitle">These figures describe the current size and workload of the public service system.</p>
    <table class="metric-table"><tr>
        <td class="metric-cell metric-cell-blue"><div class="metric-label">Agencies</div><div class="metric-value blue">{{ number_format($totalAgencies) }}</div><div class="metric-note">{{ number_format($totalNGA) }} government · {{ number_format($totalNGO) }} non-government</div></td>
        <td class="metric-cell metric-cell-purple"><div class="metric-label">FAQs</div><div class="metric-value purple">{{ number_format($totalFaqs) }}</div><div class="metric-note">{{ number_format($completeFaqs) }} complete · {{ number_format($incompleteFaqs) }} need completion</div></td>
        <td class="metric-cell metric-cell-blue"><div class="metric-label">Citizen accounts</div><div class="metric-value blue">{{ number_format($totalUsers) }}</div><div class="metric-note">Registered public users</div></td>
        <td class="metric-cell metric-cell-amber"><div class="metric-label">Support requests</div><div class="metric-value amber">{{ number_format($totalInquiries) }}</div><div class="metric-note">{{ number_format($pendingInquiries) }} are currently waiting for a response</div></td>
    </tr></table>
</section>

<section class="section">
    <div class="section-kicker">Needs attention</div>
    <h2 class="section-title">What may need action</h2>
    <p class="section-subtitle">These are the areas most likely to benefit from administrator follow-up.</p>
    <table class="metric-table"><tr>
        <td class="metric-cell metric-cell-amber"><div class="metric-label">Pending requests</div><div class="metric-value amber">{{ number_format($pendingInquiries) }}</div><div class="metric-note">Waiting for an administrator response</div></td>
        <td class="metric-cell metric-cell-blue"><div class="metric-label">Incomplete agencies</div><div class="metric-value blue">{{ number_format($incompleteAgencies) }}</div><div class="metric-note">Missing one or more required public details</div></td>
        <td class="metric-cell metric-cell-amber"><div class="metric-label">FAQs to complete</div><div class="metric-value amber">{{ number_format($incompleteFaqs) }}</div><div class="metric-note">Missing required or bilingual information</div></td>
        <td class="metric-cell metric-cell-purple"><div class="metric-label">Feedback to review</div><div class="metric-value purple">{{ number_format($faqFeedbackOutstanding) }}</div><div class="metric-note">FAQ feedback that may need attention</div></td>
    </tr></table>
    @if($totalNeedsAttention === 0)
        <div class="empty">Everything currently tracked for attention is clear.</div>
    @else
        <div class="callout"><strong>{{ number_format($totalNeedsAttention) }} attention items</strong> are currently counted across requests, agency information, FAQs, and citizen feedback.</div>
    @endif
</section>

<section class="section">
    <div class="section-kicker">Information quality</div>
    <h2 class="section-title">Is public information ready to use?</h2>
    <p class="section-subtitle">Higher completion means citizens are more likely to find the information they need without missing details.</p>
    <table class="metric-table"><tr>
        <td class="metric-cell metric-cell-blue">
            <div class="metric-label">Agency profiles</div>
            <div class="metric-value blue">{{ $totalAgencies ? round(($completeAgencies / $totalAgencies) * 100) : 0 }}%</div>
            <div class="metric-note">{{ number_format($completeAgencies) }} of {{ number_format($totalAgencies) }} profiles have the required public information</div>
            <div class="progress-track"><div class="progress-fill" style="width:{{ $totalAgencies ? round(($completeAgencies / $totalAgencies) * 100) : 0 }}%"></div></div>
        </td>
        <td class="metric-cell metric-cell-green">
            <div class="metric-label">FAQ content</div>
            <div class="metric-value green">{{ $totalFaqs ? round(($completeFaqs / $totalFaqs) * 100) : 0 }}%</div>
            <div class="metric-note">{{ number_format($completeFaqs) }} of {{ number_format($totalFaqs) }} FAQs have the required English and Filipino content</div>
            <div class="progress-track"><div class="progress-fill green-fill" style="width:{{ $totalFaqs ? round(($completeFaqs / $totalFaqs) * 100) : 0 }}%"></div></div>
        </td>
    </tr></table>
</section>

<section class="section">
    <div class="section-kicker">Citizen feedback</div>
    <h2 class="section-title">How citizens are rating FAQ answers</h2>
    <p class="section-subtitle">Feedback helps identify answers that are working well and answers that may need review.</p>
    <table class="metric-table"><tr>
        <td class="metric-cell metric-cell-amber"><div class="metric-label">FAQs needing review</div><div class="metric-value amber">{{ number_format($faqFeedbackNeedsReview) }}</div><div class="metric-note">Enough ratings were received and negative ratings outweigh positive ones</div></td>
        <td class="metric-cell metric-cell-green"><div class="metric-label">Helpful ratings</div><div class="metric-value green">{{ number_format($faqFeedbackHelpful) }}</div><div class="metric-note">Citizens marked answers as helpful</div></td>
        <td class="metric-cell metric-cell-blue"><div class="metric-label">Not helpful</div><div class="metric-value blue">{{ number_format($faqFeedbackNotHelpful) }}</div><div class="metric-note">Citizens marked answers as needing improvement</div></td>
        <td class="metric-cell metric-cell-purple"><div class="metric-label">All ratings</div><div class="metric-value purple">{{ number_format($faqFeedbackTotal) }}</div><div class="metric-note">Total feedback received</div></td>
    </tr></table>
</section>

<section class="section">
    <div class="section-kicker">Team work</div>
    <h2 class="section-title">Administrator collaboration</h2>
    <p class="section-subtitle">Work explicitly assigned between administrators, such as reviews and handoffs.</p>
    <table class="metric-table"><tr>
        <td class="metric-cell metric-cell-purple"><div class="metric-label">Open tasks</div><div class="metric-value purple">{{ number_format($collaborationOpenCount) }}</div><div class="metric-note">Tasks still open or in progress</div></td>
        <td class="metric-cell metric-cell-blue"><div class="metric-label">Assigned to you</div><div class="metric-value blue">{{ number_format($collaborationYourActionCount) }}</div><div class="metric-note">Tasks requiring your action</div></td>
        <td class="metric-cell metric-cell-blue"><div class="metric-label">Reviews</div><div class="metric-value blue">{{ number_format($collaborationReviewCount) }}</div><div class="metric-note">Open review tasks assigned to you</div></td>
        <td class="metric-cell metric-cell-purple"><div class="metric-label">Handoffs</div><div class="metric-value purple">{{ number_format($collaborationHandoffCount) }}</div><div class="metric-note">Open work handed between administrators</div></td>
    </tr></table>
    @if($collaborationTasks->isNotEmpty())
        <table class="report-table">
            <thead><tr><th>Task</th><th>Assigned to</th><th>Status</th><th>Due</th></tr></thead>
            <tbody>
            @foreach($collaborationTasks as $task)
                <tr>
                    <td><div class="task-title">{{ $task->title }}</div><div class="task-meta">{{ ucfirst(str_replace('_',' ', $task->task_type ?? 'task')) }}</div></td>
                    <td>{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                    <td><span class="badge {{ $task->status === 'in_progress' ? 'badge-green' : 'badge-blue' }}">{{ ucfirst(str_replace('_',' ', $task->status)) }}</span></td>
                    <td>{{ $task->due_at ? $task->due_at->format('M d, Y') : 'No due date' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">No open collaboration tasks are currently visible.</div>
    @endif
</section>
