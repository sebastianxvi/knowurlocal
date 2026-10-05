<div class="report-intro">
    <strong>Reading this report:</strong> The selected period is <strong>{{ $periodLabel }}</strong>. Use the support section for workload, the chatbot section for answer coverage, and the information section for content quality.
</div>

<section class="section">
    <div class="section-kicker">Support workload</div>
    <h2 class="section-title">How requests are moving</h2>
    <p class="section-subtitle">A current view of citizen requests and how quickly official answers are being delivered.</p>
    <table class="metric-table"><tr>
        <td class="metric-cell metric-cell-blue"><div class="metric-label">All requests</div><div class="metric-value blue">{{ number_format($totalInquiries) }}</div><div class="metric-note">Support requests currently recorded</div></td>
        <td class="metric-cell metric-cell-amber"><div class="metric-label">Need action</div><div class="metric-value amber">{{ number_format($openInquiries) }}</div><div class="metric-note">Pending, awaiting confirmation, or follow-up</div></td>
        <td class="metric-cell metric-cell-green"><div class="metric-label">Answered</div><div class="metric-value green">{{ number_format($answeredInquiries) }}</div><div class="metric-note">{{ $responseRate }}% of recorded requests are answered</div></td>
        <td class="metric-cell metric-cell-blue"><div class="metric-label">Average response time</div><div class="metric-value blue">{{ $averageResponseTime ?? '—' }}</div><div class="metric-note">From request received to official answer</div></td>
    </tr></table>
    <table class="status-row">
        <tr><td>Citizens who opened their answer</td><td class="green">{{ number_format($seenAnswers) }}</td></tr>
        <tr><td>Answers not yet opened</td><td class="amber">{{ number_format($unseenAnswers) }}</td></tr>
    </table>
</section>

<section class="section">
    <div class="section-kicker">Request activity</div>
    <h2 class="section-title">What happened during {{ $periodLabel }}?</h2>
    <p class="section-subtitle">The table compares requests received with official answers recorded on each day. Answers may exceed new requests when administrators are clearing an earlier backlog.</p>
    <table class="report-table">
        <thead><tr><th>Date</th><th>Day</th><th class="number">Requests received</th><th class="number">Answers sent</th></tr></thead>
        <tbody>
        @forelse($inquiryTrend as $day)
            <tr>
                <td>{{ \Carbon\Carbon::parse($day['date'])->format('M d, Y') }}</td>
                <td>{{ $day['label'] }}</td>
                <td class="number">{{ number_format($day['submitted']) }}</td>
                <td class="number">{{ number_format($day['answered']) }}</td>
            </tr>
        @empty
            <tr><td colspan="4">No request activity is available for this period.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="callout"><strong>{{ number_format($trendSubmitted) }} requests received</strong> and <strong>{{ number_format($trendAnswered) }} answers sent</strong> during the selected period.</div>
</section>

<section class="section">
    <div class="section-kicker">Current workload</div>
    <h2 class="section-title">Where requests stand now</h2>
    <p class="section-subtitle">These counts show the current queue, not only activity within the selected period.</p>
    <table class="status-row">
        <tr><td>Waiting for an administrator response</td><td class="amber">{{ number_format($pendingInquiries) }}</td></tr>
        <tr><td>Answer sent, waiting for citizen confirmation</td><td class="purple">{{ number_format($awaitingConfirmation) }}</td></tr>
        <tr><td>Citizen requested additional help</td><td class="blue">{{ number_format($needsFollowUp) }}</td></tr>
        <tr><td>Completed with an answer</td><td class="green">{{ number_format($answeredInquiries) }}</td></tr>
    </table>
</section>

<section class="section">
    <div class="section-kicker">Citizen demand</div>
    <h2 class="section-title">Which agencies receive the most requests?</h2>
    <p class="section-subtitle">This shows where citizens are asking for help most often. A higher number means more demand, not better or worse agency performance.</p>
    @if($topSupportAgencies->isNotEmpty())
        @php($maxAgencyRequests = max(1, (int) $topSupportAgencies->max('request_count')))
        <table class="bar-table">
            @foreach($topSupportAgencies as $item)
                @php($barWidth = min(100, round(((int) $item->request_count / $maxAgencyRequests) * 100)))
                <tr>
                    <td class="bar-name">{{ $item->agency?->agency_name ?? 'Unassigned agency' }}</td>
                    <td><div class="bar-track"><div class="bar-fill" style="width:{{ $barWidth }}%"></div></div></td>
                    <td class="bar-value">{{ number_format($item->request_count) }}</td>
                </tr>
            @endforeach
        </table>
    @else
        <div class="empty">No agency-linked support requests have been recorded.</div>
    @endif
</section>

<section class="section">
    <div class="section-kicker">Chatbot & FAQ health</div>
    <h2 class="section-title">Is the chatbot finding useful answers?</h2>
    <p class="section-subtitle">These figures show how often the chatbot can answer from the published FAQ knowledge base and where information may be missing.</p>
    <table class="metric-table"><tr>
        <td class="metric-cell metric-cell-purple"><div class="metric-label">Chatbot questions</div><div class="metric-value purple">{{ number_format($totalChatbotInteractions) }}</div><div class="metric-note">Total conversations recorded</div></td>
        <td class="metric-cell metric-cell-green"><div class="metric-label">Answered from FAQs</div><div class="metric-value green">{{ $faqAnswerRate }}%</div><div class="metric-note">{{ number_format($faqAnswered) }} questions were answered using an FAQ</div></td>
        <td class="metric-cell metric-cell-amber"><div class="metric-label">No matching FAQ</div><div class="metric-value amber">{{ $fallbackRate }}%</div><div class="metric-note">{{ number_format($fallbackQuestions) }} questions had no suitable FAQ match</div></td>
        <td class="metric-cell metric-cell-blue"><div class="metric-label">Needed clarification</div><div class="metric-value blue">{{ number_format($clarificationQuestions) }}</div><div class="metric-note">Questions where more context was needed</div></td>
    </tr></table>

    <div class="section-subtitle" style="margin-top:9px;margin-bottom:4px;">How the chatbot found the FAQ</div>
    @php($matchTotal = max(1, $semanticMatches + $similarityMatches + $ruleMatches))
    <table class="bar-table">
        @php($semanticWidth = round(($semanticMatches / $matchTotal) * 100))
        <tr><td class="bar-name">Meaning-based match</td><td><div class="bar-track"><div class="bar-fill bar-fill-purple" style="width:{{ $semanticWidth }}%"></div></div></td><td class="bar-value">{{ number_format($semanticMatches) }}</td></tr>
        @php($similarityWidth = round(($similarityMatches / $matchTotal) * 100))
        <tr><td class="bar-name">Similar wording</td><td><div class="bar-track"><div class="bar-fill" style="width:{{ $similarityWidth }}%"></div></div></td><td class="bar-value">{{ number_format($similarityMatches) }}</td></tr>
        @php($ruleWidth = round(($ruleMatches / $matchTotal) * 100))
        <tr><td class="bar-name">Rule-based match</td><td><div class="bar-track"><div class="bar-fill bar-fill-amber" style="width:{{ $ruleWidth }}%"></div></div></td><td class="bar-value">{{ number_format($ruleMatches) }}</td></tr>
    </table>
    <div class="callout"><strong>Meaning-based match:</strong> the question and FAQ can use different words but still mean the same thing. <strong>Similar wording:</strong> the question closely resembles the FAQ wording. <strong>Rule-based:</strong> a predefined matching rule selected the FAQ.</div>
</section>

<section class="section">
    <div class="section-kicker">Knowledge usage</div>
    <h2 class="section-title">Which answers and agency topics are used most?</h2>
    <p class="section-subtitle">Frequently used FAQs can help administrators understand what information citizens rely on most.</p>
    <div class="two-column">
        <table class="report-table two-column-cell">
            <thead><tr><th>#</th><th>FAQ</th><th class="number">Uses</th></tr></thead>
            <tbody>
            @forelse($popularFaqs as $faq)
                <tr><td>{{ $loop->iteration }}</td><td>{{ $faq->faq?->question ?? 'FAQ unavailable' }}<br><span class="muted">{{ $faq->faq?->agency?->agency_name ?? 'No agency' }}</span></td><td class="number">{{ number_format($faq->usage_count) }}</td></tr>
            @empty <tr><td colspan="3">No FAQ usage recorded.</td></tr> @endforelse
            </tbody>
        </table>
        <table class="report-table two-column-cell">
            <thead><tr><th>#</th><th>Agency topic</th><th class="number">Questions</th></tr></thead>
            <tbody>
            @forelse($popularAgencies as $agency)
                <tr><td>{{ $loop->iteration }}</td><td>{{ $agency->agency?->agency_name ?? 'Agency unavailable' }}</td><td class="number">{{ number_format($agency->interaction_count) }}</td></tr>
            @empty <tr><td colspan="3">No agency-related chatbot questions recorded.</td></tr> @endforelse
            </tbody>
        </table>
    </div>
</section>

<section class="section">
    <div class="section-kicker">Citizen feedback</div>
    <h2 class="section-title">Which FAQ answers may need improvement?</h2>
    <p class="section-subtitle">Feedback is summarized per FAQ so repeated ratings help identify answers to review rather than creating duplicate work items.</p>
    <table class="metric-table"><tr>
        <td class="metric-cell metric-cell-amber"><div class="metric-label">FAQs to review</div><div class="metric-value amber">{{ number_format($faqFeedbackNeedsReview) }}</div><div class="metric-note">Enough ratings received and negative ratings outweigh positive ratings</div></td>
        <td class="metric-cell metric-cell-green"><div class="metric-label">Helpful ratings</div><div class="metric-value green">{{ number_format($faqFeedbackHelpful) }}</div><div class="metric-note">Citizens marked answers helpful</div></td>
        <td class="metric-cell metric-cell-blue"><div class="metric-label">Not helpful</div><div class="metric-value blue">{{ number_format($faqFeedbackNotHelpful) }}</div><div class="metric-note">Citizens marked answers not helpful</div></td>
        <td class="metric-cell metric-cell-purple"><div class="metric-label">All ratings</div><div class="metric-value purple">{{ number_format($faqFeedbackTotal) }}</div><div class="metric-note">All submitted feedback</div></td>
    </tr></table>
</section>

<section class="section">
    <div class="section-kicker">Information & team health</div>
    <h2 class="section-title">Is the system information being maintained?</h2>
    <table class="metric-table"><tr>
        <td class="metric-cell metric-cell-blue"><div class="metric-label">Agency profiles complete</div><div class="metric-value blue">{{ number_format($completeAgencies) }} / {{ number_format($totalAgencies) }}</div><div class="metric-note">{{ $totalAgencies ? round(($completeAgencies / $totalAgencies) * 100) : 0 }}% have the required public information</div></td>
        <td class="metric-cell metric-cell-green"><div class="metric-label">FAQs complete</div><div class="metric-value green">{{ number_format($completeFaqs) }} / {{ number_format($totalFaqs) }}</div><div class="metric-note">{{ $totalFaqs ? round(($completeFaqs / $totalFaqs) * 100) : 0 }}% contain required English and Filipino content</div></td>
        <td class="metric-cell metric-cell-purple"><div class="metric-label">Open team tasks</div><div class="metric-value purple">{{ number_format($collaborationOpen) }}</div><div class="metric-note">{{ number_format($collaborationOverdue) }} are overdue</div></td>
        <td class="metric-cell metric-cell-green"><div class="metric-label">Tasks completed</div><div class="metric-value green">{{ number_format($collaborationCompleted) }}</div><div class="metric-note">Completed during {{ $periodLabel }}</div></td>
    </tr></table>
</section>
