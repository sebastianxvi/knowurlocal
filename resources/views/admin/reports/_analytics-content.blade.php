<section class="section">
    <h2 class="section-title">Support request overview</h2>
    <p class="section-subtitle">Current workload plus the selected trend period: {{ $periodLabel }}.</p>
    <table class="metric-table"><tr>
        <td class="metric-cell"><div class="metric-label">Requests received</div><div class="metric-value blue">{{ number_format($totalInquiries) }}</div><div class="metric-note">All active support requests</div></td>
        <td class="metric-cell"><div class="metric-label">Requests needing action</div><div class="metric-value amber">{{ number_format($openInquiries) }}</div><div class="metric-note">Pending, confirmation, or follow-up</div></td>
        <td class="metric-cell"><div class="metric-label">Requests answered</div><div class="metric-value green">{{ number_format($answeredInquiries) }}</div><div class="metric-note">{{ $responseRate }}% of all active requests</div></td>
        <td class="metric-cell"><div class="metric-label">Average response time</div><div class="metric-value blue">{{ $averageResponseTime ?? '—' }}</div><div class="metric-note">Submission to official answer</div></td>
    </tr></table>
    <table class="status-row">
        <tr><td>Answers opened by citizens</td><td class="green">{{ number_format($seenAnswers) }}</td></tr>
        <tr><td>Answers not yet opened</td><td class="amber">{{ number_format($unseenAnswers) }}</td></tr>
    </table>
</section>

<section class="section">
    <h2 class="section-title">Request activity — {{ $periodLabel }}</h2>
    <p class="section-subtitle">Daily requests submitted and official answers during the selected period.</p>
    <table class="report-table"><thead><tr><th>Date</th><th>Day</th><th class="number">Submitted</th><th class="number">Answered</th></tr></thead><tbody>
    @forelse($inquiryTrend as $day)
        <tr><td>{{ \Carbon\Carbon::parse($day['date'])->format('M d, Y') }}</td><td>{{ $day['label'] }}</td><td class="number">{{ number_format($day['submitted']) }}</td><td class="number">{{ number_format($day['answered']) }}</td></tr>
    @empty
        <tr><td colspan="4">No request activity is available for this period.</td></tr>
    @endforelse
    </tbody></table>
</section>

<section class="section">
    <h2 class="section-title">Current request queue</h2>
    <table class="status-row">
        <tr><td>Pending — needs administrator response</td><td class="amber">{{ number_format($pendingInquiries) }}</td></tr>
        <tr><td>Awaiting confirmation — answer sent, citizen has not confirmed</td><td class="purple">{{ number_format($awaitingConfirmation) }}</td></tr>
        <tr><td>Needs follow-up — citizen requested more help</td><td class="blue">{{ number_format($needsFollowUp) }}</td></tr>
        <tr><td>Answered — completed state</td><td class="green">{{ number_format($answeredInquiries) }}</td></tr>
    </table>
</section>

<section class="section">
    <h2 class="section-title">Service demand</h2>
    <p class="section-subtitle">Agencies receiving the most support requests. Higher volume indicates more incoming demand, not better or worse performance.</p>
    @if($topSupportAgencies->isNotEmpty())
        <table class="report-table"><thead><tr><th>#</th><th>Agency</th><th class="number">Support requests</th></tr></thead><tbody>
        @foreach($topSupportAgencies as $item)
            <tr><td>{{ $loop->iteration }}</td><td>{{ $item->agency?->agency_name ?? 'Unassigned agency' }}</td><td class="number">{{ number_format($item->request_count) }}</td></tr>
        @endforeach
        </tbody></table>
    @else <div class="empty">No agency-linked support requests have been recorded.</div> @endif
</section>

<section class="section">
    <h2 class="section-title">Chatbot and FAQ performance</h2>
    <p class="section-subtitle">How effectively the public knowledge base is covering citizen questions.</p>
    <table class="metric-table"><tr>
        <td class="metric-cell"><div class="metric-label">Chatbot questions</div><div class="metric-value purple">{{ number_format($totalChatbotInteractions) }}</div><div class="metric-note">Total chatbot questions logged</div></td>
        <td class="metric-cell"><div class="metric-label">Answered from FAQs</div><div class="metric-value green">{{ $faqAnswerRate }}%</div><div class="metric-note">{{ number_format($faqAnswered) }} questions matched an FAQ</div></td>
        <td class="metric-cell"><div class="metric-label">Questions without FAQ match</div><div class="metric-value amber">{{ $fallbackRate }}%</div><div class="metric-note">{{ number_format($fallbackQuestions) }} needed another answer path</div></td>
        <td class="metric-cell"><div class="metric-label">Clarification needed</div><div class="metric-value blue">{{ number_format($clarificationQuestions) }}</div><div class="metric-note">Questions needing more context</div></td>
    </tr></table>
    <table class="status-row">
        <tr><td>Rule-based matches</td><td>{{ number_format($ruleMatches) }}</td></tr>
        <tr><td>AI-assisted matches</td><td class="purple">{{ number_format($semanticMatches) }}</td></tr>
        <tr><td>FAQs used to answer questions</td><td class="green">{{ number_format($faqAnswered) }}</td></tr>
    </table>
</section>

<section class="section">
    <h2 class="section-title">Knowledge-base usage</h2>
    <div class="two-column"><table class="report-table two-column-cell"><thead><tr><th>#</th><th>FAQ</th><th class="number">Uses</th></tr></thead><tbody>
        @forelse($popularFaqs as $faq)
            <tr><td>{{ $loop->iteration }}</td><td>{{ $faq->faq?->question ?? 'FAQ unavailable' }}<br><span class="muted">{{ $faq->faq?->agency?->agency_name ?? 'No agency' }}</span></td><td class="number">{{ number_format($faq->usage_count) }}</td></tr>
        @empty <tr><td colspan="3">No FAQ usage recorded.</td></tr> @endforelse
    </tbody></table><table class="report-table two-column-cell"><thead><tr><th>#</th><th>Agency topic</th><th class="number">Questions</th></tr></thead><tbody>
        @forelse($popularAgencies as $agency)
            <tr><td>{{ $loop->iteration }}</td><td>{{ $agency->agency?->agency_name ?? 'Agency unavailable' }}</td><td class="number">{{ number_format($agency->interaction_count) }}</td></tr>
        @empty <tr><td colspan="3">No agency-related chatbot questions recorded.</td></tr> @endforelse
    </tbody></table></div>
</section>

<section class="section">
    <h2 class="section-title">FAQ answer feedback</h2>
    <p class="section-subtitle">Ratings are summarized per FAQ so repeated feedback on one answer does not become separate admin tasks.</p>
    <table class="metric-table"><tr>
        <td class="metric-cell"><div class="metric-label">FAQs needing review</div><div class="metric-value amber">{{ number_format($faqFeedbackNeedsReview) }}</div><div class="metric-note">Minimum rating threshold met and dislikes exceed likes</div></td>
        <td class="metric-cell"><div class="metric-label">Total likes</div><div class="metric-value green">{{ number_format($faqFeedbackHelpful) }}</div><div class="metric-note">Helpful ratings across all FAQs</div></td>
        <td class="metric-cell"><div class="metric-label">Total dislikes</div><div class="metric-value blue">{{ number_format($faqFeedbackNotHelpful) }}</div><div class="metric-note">Negative ratings across all FAQs</div></td>
        <td class="metric-cell"><div class="metric-label">Total ratings</div><div class="metric-value purple">{{ number_format($faqFeedbackTotal) }}</div><div class="metric-note">All submitted ratings</div></td>
    </tr></table>
</section>

<section class="section">
    <h2 class="section-title">Data quality and team workload</h2>
    <table class="metric-table"><tr>
        <td class="metric-cell"><div class="metric-label">Agency profiles complete</div><div class="metric-value blue">{{ number_format($completeAgencies) }} / {{ number_format($totalAgencies) }}</div><div class="metric-note">{{ $totalAgencies ? round(($completeAgencies / $totalAgencies) * 100) : 0 }}% have required public information</div></td>
        <td class="metric-cell"><div class="metric-label">FAQs complete</div><div class="metric-value green">{{ number_format($completeFaqs) }} / {{ number_format($totalFaqs) }}</div><div class="metric-note">{{ $totalFaqs ? round(($completeFaqs / $totalFaqs) * 100) : 0 }}% complete in English and Filipino</div></td>
        <td class="metric-cell"><div class="metric-label">Team tasks open</div><div class="metric-value purple">{{ number_format($collaborationOpen) }}</div><div class="metric-note">{{ number_format($collaborationOverdue) }} overdue</div></td>
        <td class="metric-cell"><div class="metric-label">Team tasks completed</div><div class="metric-value green">{{ number_format($collaborationCompleted) }}</div><div class="metric-note">Completed during {{ $periodLabel }}</div></td>
    </tr></table>
</section>
