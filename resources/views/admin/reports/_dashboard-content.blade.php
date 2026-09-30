<section class="section">
    <h2 class="section-title">Current system snapshot</h2>
    <p class="section-subtitle">A short operational summary for administrators.</p>
    <table class="metric-table"><tr>
        <td class="metric-cell"><div class="metric-label">Agencies</div><div class="metric-value blue">{{ number_format($totalAgencies) }}</div><div class="metric-note">{{ number_format($totalNGA) }} NGA · {{ number_format($totalNGO) }} NGO</div></td>
        <td class="metric-cell"><div class="metric-label">FAQs</div><div class="metric-value purple">{{ number_format($totalFaqs) }}</div><div class="metric-note">{{ number_format($completeFaqs) }} complete · {{ number_format($incompleteFaqs) }} need completion</div></td>
        <td class="metric-cell"><div class="metric-label">Public users</div><div class="metric-value blue">{{ number_format($totalUsers) }}</div><div class="metric-note">Registered citizen accounts</div></td>
        <td class="metric-cell"><div class="metric-label">Support requests</div><div class="metric-value amber">{{ number_format($totalInquiries) }}</div><div class="metric-note">{{ number_format($pendingInquiries) }} currently pending</div></td>
    </tr></table>
</section>

<section class="section">
    <h2 class="section-title">What needs attention</h2>
    <p class="section-subtitle">Items that may require administrator action.</p>
    <table class="metric-table"><tr>
        <td class="metric-cell"><div class="metric-label">Pending requests</div><div class="metric-value amber">{{ number_format($pendingInquiries) }}</div><div class="metric-note">Waiting for an administrator response</div></td>
        <td class="metric-cell"><div class="metric-label">Incomplete agencies</div><div class="metric-value blue">{{ number_format($incompleteAgencies) }}</div><div class="metric-note">Missing required public information</div></td>
        <td class="metric-cell"><div class="metric-label">FAQs needing completion</div><div class="metric-value amber">{{ number_format($incompleteFaqs) }}</div><div class="metric-note">Missing bilingual or required content</div></td>
        <td class="metric-cell"><div class="metric-label">Total attention items</div><div class="metric-value amber">{{ number_format($totalNeedsAttention) }}</div><div class="metric-note">Combined workload categories</div></td>
    </tr></table>
    @if($totalNeedsAttention === 0)
        <div class="empty">No current attention items were detected.</div>
    @endif
</section>

<section class="section">
    <h2 class="section-title">FAQ answer feedback</h2>
    <p class="section-subtitle">Ratings are aggregated per FAQ, with review priority based on the combined feedback.</p>
    <table class="metric-table"><tr>
        <td class="metric-cell"><div class="metric-label">FAQs needing review</div><div class="metric-value amber">{{ number_format($faqFeedbackNeedsReview) }}</div><div class="metric-note">Minimum ratings met; dislikes exceed likes</div></td>
        <td class="metric-cell"><div class="metric-label">Total likes</div><div class="metric-value green">{{ number_format($faqFeedbackHelpful) }}</div><div class="metric-note">Helpful ratings</div></td>
        <td class="metric-cell"><div class="metric-label">Total dislikes</div><div class="metric-value blue">{{ number_format($faqFeedbackNotHelpful) }}</div><div class="metric-note">Negative ratings</div></td>
        <td class="metric-cell"><div class="metric-label">Total ratings</div><div class="metric-value purple">{{ number_format($faqFeedbackTotal) }}</div><div class="metric-note">All resident ratings</div></td>
    </tr></table>
</section>

<section class="section">
    <h2 class="section-title">Team collaboration</h2>
    <p class="section-subtitle">Explicit administrator-to-administrator work, not general system activity.</p>
    <table class="metric-table"><tr>
        <td class="metric-cell"><div class="metric-label">Open team tasks</div><div class="metric-value purple">{{ number_format($collaborationOpenCount) }}</div><div class="metric-note">Tasks still open or in progress</div></td>
        <td class="metric-cell"><div class="metric-label">Assigned to you</div><div class="metric-value blue">{{ number_format($collaborationYourActionCount) }}</div><div class="metric-note">Tasks requiring your action</div></td>
        <td class="metric-cell"><div class="metric-label">Reviews</div><div class="metric-value blue">{{ number_format($collaborationReviewCount) }}</div><div class="metric-note">Open review tasks assigned to you</div></td>
        <td class="metric-cell"><div class="metric-label">Handoffs</div><div class="metric-value purple">{{ number_format($collaborationHandoffCount) }}</div><div class="metric-note">Open work handed between admins</div></td>
    </tr></table>
    @if($collaborationTasks->isNotEmpty())
        <table class="report-table">
            <thead><tr><th>Task</th><th>Assigned to</th><th>Status</th><th>Due</th></tr></thead>
            <tbody>
            @foreach($collaborationTasks as $task)
                <tr>
                    <td><div class="task-title">{{ $task->title }}</div><div class="task-meta">{{ ucfirst(str_replace('_',' ', $task->task_type ?? 'task')) }}</div></td>
                    <td>{{ $task->assignee?->name ?? 'Unassigned' }}</td>
                    <td><span class="badge {{ $task->status === 'in_progress' ? 'badge-green' : '' }}">{{ ucfirst(str_replace('_',' ', $task->status)) }}</span></td>
                    <td>{{ $task->due_at ? $task->due_at->format('M d, Y') : 'No due date' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <div class="empty">No open collaboration tasks are currently visible.</div>
    @endif
</section>
