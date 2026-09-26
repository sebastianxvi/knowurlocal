@extends('layouts.admin')

@push('styles')

<link rel="stylesheet" href="{{ asset('cssfiles/theme.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/logs.css') }}">

@endpush

@section('title', 'KNOWURLOCAL | ' . ucfirst(auth()->user()->role) . ' Module')

@section('page-title', 'Chatbot Logs')
@section('page-subtitle', 'Monitor chatbot interactions')

@section('content')

<div class="logs-page">

    <!-- ================= FILTER ================= -->

    <div class="filter-card">

        <form method="GET" class="filter-bar">

            <!-- SEARCH -->
            <input
                type="text"
                name="search"
                value="{{ request('search') }}"
                placeholder="Search question, answer, user or agency"
            >

            <!-- OUTCOME -->
            <select name="outcome">

                <option value="">All Outcomes</option>

                @foreach($availableOutcomes as $outcome)

                    <option
                        value="{{ $outcome }}"
                        {{ request('outcome') === $outcome ? 'selected' : '' }}
                    >
                        {{ ucfirst(str_replace('_', ' ', $outcome)) }}
                    </option>

                @endforeach

            </select>


            <!-- MATCH METHOD -->
            <select name="match_method">

                <option value="">All Match Methods</option>

                @foreach($availableMatchMethods as $method)

                    <option
                        value="{{ $method }}"
                        {{ request('match_method') === $method ? 'selected' : '' }}
                    >
                        {{ ucfirst($method) }}
                    </option>

                @endforeach

            </select>


            <!-- DATE -->
            <select name="date">

                <option value="">All Dates</option>

                @foreach($availableDates as $date)

                    <option
                        value="{{ $date }}"
                        {{ request('date') === $date ? 'selected' : '' }}
                    >
                        {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                    </option>

                @endforeach

            </select>


            <!-- SORT -->
            <select name="sort">

                <option
                    value="desc"
                    {{ request('sort', 'desc') === 'desc' ? 'selected' : '' }}
                >
                    Newest First
                </option>

                <option
                    value="asc"
                    {{ request('sort') === 'asc' ? 'selected' : '' }}
                >
                    Oldest First
                </option>

            </select>


            <button type="submit">
                Filter
            </button>

        </form>

    </div>


    <!-- ================= TABLE ================= -->

    <div class="table-wrapper">

        <table class="table">

            <thead>
    <tr>
        <th>User</th>
        <th>Question</th>
        <th>Agency</th>
        <th>Outcome</th>
        <th>Match</th>
        <th>Score</th>
        <th>Date</th>
    </tr>
</thead>


            <tbody>

                @forelse($logs as $log)

<tr
    class="chatbot-log-row"
    data-id="{{ $log->id }}"
    data-user="{{ $log->user
        ? trim($log->user->first_name . ' ' . $log->user->last_name)
        : 'Unknown User'
    }}"
    data-user-id="{{ $log->user_id }}"
    data-question="{{ $log->question }}"
    data-answer="{{ $log->answer }}"
    data-agency="{{ $log->agency?->agency_name ?? '' }}"
    data-faq-id="{{ $log->faq_id ?? '' }}"
    data-faq-question="{{ $log->faq?->question ?? '' }}"
    data-outcome="{{ $log->outcome }}"
    data-match-method="{{ $log->match_method ?? '' }}"
    data-score="{{ $log->score ?? '' }}"
    data-ip="{{ $log->ip_address ?? '' }}"
    data-date="{{ $log->created_at?->format('M d, Y H:i') }}"
>

                    <!-- USER -->
                    <td>

                        <div class="actor-cell">

                            @if($log->user)

                                <span class="actor-name">
                                    {{ $log->user->first_name }}
                                    {{ $log->user->last_name }}
                                </span>

                            @else

                                <span class="actor-name text-muted">
                                    Unknown User
                                </span>

                            @endif

                        </div>

                    </td>


                    <!-- QUESTION -->
                    <td>

                        {{ \Illuminate\Support\Str::limit(
                            $log->question,
                            60
                        ) }}

                    </td>



                    <!-- AGENCY -->
                    <td>
    @if($log->agency)
        {{ $log->agency->agency_name }}
    @else
        <span class="text-muted">
            No agency context
        </span>
    @endif
</td>


                    <!-- OUTCOME -->
                    <td>

                        @php

                            $outcomeIcon = match($log->outcome) {

                                'answered' =>
                                    'ph-check-circle',

                                'fallback' =>
                                    'ph-warning-circle',

                                'greeting' =>
                                    'ph-hand-waving',

                                'thanks' =>
                                    'ph-smiley',

                                'irrelevant' =>
                                    'ph-prohibit',

                                'clarification' =>
                                    'ph-chat-circle-dots',

                                'wrong_agency' =>
                                    'ph-arrow-bend-up-left',

                                default =>
                                    'ph-chat-centered-text',

                            };

                        @endphp


                        <span
                            class="badge action {{ $log->outcome }}"
                        >

                            <i class="ph-light {{ $outcomeIcon }}"></i>

                            {{ ucfirst(
                                str_replace(
                                    '_',
                                    ' ',
                                    $log->outcome
                                )
                            ) }}

                        </span>

                    </td>


                    <!-- MATCH METHOD -->
                    <td>

    @if($log->match_method)

        @php

            $matchIcon = match($log->match_method) {

                'rule' =>
                    'ph-faders',

                'semantic' =>
                    'ph-brain',

                default =>
                    'ph-chat-centered-text',

            };

        @endphp

        <span class="badge action match-{{ $log->match_method }}">

            <i class="ph-light {{ $matchIcon }}"></i>

            {{ ucfirst($log->match_method) }}

        </span>

    @else

        <span class="text-muted">
            Not evaluated
        </span>

    @endif

</td>


                    <!-- SCORE -->
                    <td>

                        @if($log->score !== null)

                            @php

                                /*
                                 * Scores are now stored consistently
                                 * as integers from 0 to 100.
                                 */
                                $percent = max(
                                    0,
                                    min(100, (int) $log->score)
                                );

                            @endphp


                            <span
                                class="badge action
                                    {{ $percent >= 80
                                        ? 'score-high'
                                        : ''
                                    }}

                                    {{ $percent >= 50 && $percent < 80
                                        ? 'score-medium'
                                        : ''
                                    }}

                                    {{ $percent < 50
                                        ? 'score-low'
                                        : ''
                                    }}"
                            >

                                <i class="ph-light
                                    {{ $percent >= 80
                                        ? 'ph-check-circle'
                                        : ''
                                    }}

                                    {{ $percent >= 50 && $percent < 80
                                        ? 'ph-chart-line'
                                        : ''
                                    }}

                                    {{ $percent < 50
                                        ? 'ph-warning-circle'
                                        : ''
                                    }}"
                                ></i>

                                {{ $percent }}%

                            </span>

                        @else

    <span class="text-muted">
        Not evaluated
    </span>

@endif

                    </td>


                    <!-- DATE -->
                    <td>

                        {{ $log->created_at->format('M d, Y H:i') }}

                    </td>

                </tr>


                @empty

                <tr>

                    <td colspan="7" class="empty">
                        No chatbot logs found.
                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>
        </div>


        <!-- ================= FOOTER ================= -->

        <div class="footer">

            <div class="result-info">

                Showing {{ $logs->firstItem() ?? 0 }}
                to {{ $logs->lastItem() ?? 0 }}
                of {{ $logs->total() }} results

            </div>


            <div class="pagination-modern">

                <!-- PREVIOUS -->

                @if ($logs->onFirstPage())

                    <span class="arrow disabled">

                        <svg
                            viewBox="0 0 24 24"
                            width="14"
                            height="14"
                        >

                            <path
                                d="M15 6L9 12L15 18"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />

                        </svg>

                    </span>

                @else

                    <a
                        href="{{ $logs->previousPageUrl() }}"
                        class="arrow"
                    >

                        <svg
                            viewBox="0 0 24 24"
                            width="14"
                            height="14"
                        >

                            <path
                                d="M15 6L9 12L15 18"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />

                        </svg>

                    </a>

                @endif


                <!-- CURRENT PAGE -->

                <span class="page-indicator">

                    Page {{ $logs->currentPage() }}

                </span>


                <!-- NEXT -->

                @if ($logs->hasMorePages())

                    <a
                        href="{{ $logs->nextPageUrl() }}"
                        class="arrow"
                    >

                        <svg
                            viewBox="0 0 24 24"
                            width="14"
                            height="14"
                        >

                            <path
                                d="M9 6L15 12L9 18"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />

                        </svg>

                    </a>

                @else

                    <span class="arrow disabled">

                        <svg
                            viewBox="0 0 24 24"
                            width="14"
                            height="14"
                        >

                            <path
                                d="M9 6L15 12L9 18"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />

                        </svg>

                    </span>

                @endif

            </div>

        

    </div>

</div>



<!-- ================= CHATBOT DETAIL MODAL ================= -->

<div
    id="chatbotLogModal"
    class="log-modal"
    aria-hidden="true"
>
    <div
        class="modal-content chatbot-detail-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="chatbotModalTitle"
    >

        <!-- HEADER -->
        <div class="modal-header">

            <div class="modal-title-group">
                <span id="chatbotModalTitle">
                    Chatbot Interaction
                </span>

                <span
                    id="chatbotModalId"
                    class="modal-reference"
                ></span>
            </div>

            <button
                type="button"
                id="closeChatbotModal"
                class="modal-close"
                aria-label="Close chatbot interaction details"
            >
                <i class="ph-light ph-x"></i>
            </button>

        </div>


        <!-- BODY -->
        <div class="modal-body">

            <!-- USER -->
            <div class="chatbot-detail-section">

                <span class="modal-label">
                    User
                </span>

                <div
                    id="chatbotUser"
                    class="chatbot-detail-value"
                ></div>

            </div>


            <!-- QUESTION -->
            <div class="chatbot-detail-section">

                <span class="modal-label">
                    Question
                </span>

                <div
                    id="chatbotQuestion"
                    class="chatbot-detail-box"
                ></div>

            </div>


            <!-- ANSWER -->
            <div class="chatbot-detail-section">

                <span class="modal-label">
                    Answer
                </span>

                <div
                    id="chatbotAnswer"
                    class="chatbot-detail-box"
                ></div>

            </div>


            <!-- RESULT -->
            <div class="chatbot-detail-grid">

                <div class="chatbot-detail-section">

                    <span class="modal-label">
                        Agency
                    </span>

                    <div
                        id="chatbotAgency"
                        class="chatbot-detail-value"
                    ></div>

                </div>


                <div class="chatbot-detail-section">

                    <span class="modal-label">
                        Outcome
                    </span>

                    <div
                        id="chatbotOutcome"
                        class="chatbot-detail-value"
                    ></div>

                </div>


                <div class="chatbot-detail-section">

                    <span class="modal-label">
                        Match Method
                    </span>

                    <div
                        id="chatbotMatchMethod"
                        class="chatbot-detail-value"
                    ></div>

                </div>


                <div class="chatbot-detail-section">

                    <span class="modal-label">
                        Score
                    </span>

                    <div
                        id="chatbotScore"
                        class="chatbot-detail-value"
                    ></div>

                </div>

            </div>


            <!-- KNOWLEDGE USED -->
            <div class="chatbot-detail-section">

                <span class="modal-label">
                    Knowledge Used
                </span>

                <div
                    id="chatbotFaq"
                    class="chatbot-detail-box"
                ></div>

            </div>


            <!-- SYSTEM INFORMATION -->
            <div class="chatbot-detail-section">

                <span class="modal-label">
                    System Information
                </span>

                <div class="chatbot-system-info">

                    <div>
                        <span>User ID</span>
                        <strong id="chatbotUserId"></strong>
                    </div>

                    <div>
                        <span>FAQ ID</span>
                        <strong id="chatbotFaqId"></strong>
                    </div>

                    <div>
                        <span>IP Address</span>
                        <strong id="chatbotIp"></strong>
                    </div>

                    <div>
                        <span>Date</span>
                        <strong id="chatbotDate"></strong>
                    </div>

                </div>

            </div>

        </div>

    </div>
</div>

@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', () => {

    /*
     * Get references to the modal and all
     * elements that will receive log information.
     */
    const modal = document.getElementById('chatbotLogModal');
    const closeButton = document.getElementById('closeChatbotModal');

    const modalId = document.getElementById('chatbotModalId');

    const user = document.getElementById('chatbotUser');
    const userId = document.getElementById('chatbotUserId');

    const question = document.getElementById('chatbotQuestion');
    const answer = document.getElementById('chatbotAnswer');

    const agency = document.getElementById('chatbotAgency');

    const outcome = document.getElementById('chatbotOutcome');
    const matchMethod = document.getElementById('chatbotMatchMethod');
    const score = document.getElementById('chatbotScore');

    const faq = document.getElementById('chatbotFaq');
    const faqId = document.getElementById('chatbotFaqId');

    const ipAddress = document.getElementById('chatbotIp');
    const createdAt = document.getElementById('chatbotDate');


    /*
     * Escape dynamic content before placing it
     * inside HTML.
     *
     * This is important because chatbot questions
     * and answers are not trusted HTML content.
     */
    function escapeHtml(value) {

        const element = document.createElement('div');

        element.textContent = value ?? '';

        return element.innerHTML;
    }


    /*
     * Provides a meaningful message for null values.
     *
     * We deliberately avoid using "-".
     */
    function displayValue(value, fallback) {

        if (
            value === null ||
            value === undefined ||
            value === ''
        ) {
            return escapeHtml(fallback);
        }

        return escapeHtml(value);
    }


    /*
     * Converts database values such as:
     *
     * wrong_agency
     *
     * into:
     *
     * Wrong Agency
     */
    function formatLabel(value, fallback = 'Not evaluated') {

        if (!value) {
            return fallback;
        }

        return value
            .replaceAll('_', ' ')
            .replace(/\b\w/g, letter => letter.toUpperCase());
    }


    /*
     * Opens the chatbot interaction modal
     * using the data stored on the clicked row.
     */
    function openChatbotModal(log) {

        modalId.textContent = `#${log.id}`;


        /*
         * USER
         */
        user.innerHTML = displayValue(
            log.user_name,
            'Unknown user'
        );

        userId.innerHTML = displayValue(
            log.user_id,
            'No user ID'
        );


        /*
         * QUESTION / ANSWER
         */
        question.innerHTML = displayValue(
            log.question,
            'No question recorded'
        );

        answer.innerHTML = displayValue(
            log.answer,
            'No answer recorded'
        );


        /*
         * AGENCY
         */
        agency.innerHTML = displayValue(
            log.agency_name,
            'No agency context'
        );


        /*
         * CHATBOT RESULT
         */
        if (log.outcome) {

    const outcomeIcon = {
        answered: 'ph-check-circle',
        fallback: 'ph-warning-circle',
        greeting: 'ph-hand-waving',
        thanks: 'ph-smiley',
        irrelevant: 'ph-prohibit',
        clarification: 'ph-chat-circle-dots',
        wrong_agency: 'ph-arrow-bend-up-left'
    };

    const icon = outcomeIcon[log.outcome]
        || 'ph-chat-centered-text';

    outcome.innerHTML = `
        <span class="badge action ${escapeHtml(log.outcome)}">
            <i class="ph-light ${icon}"></i>
            ${escapeHtml(formatLabel(log.outcome))}
        </span>
    `;

} else {

    outcome.innerHTML = `
        <span class="text-muted">
            Not available
        </span>
    `;

}
        if (log.match_method) {

    const matchIcon = {
        rule: 'ph-faders',
        semantic: 'ph-brain'
    };

    const icon = matchIcon[log.match_method]
        || 'ph-chat-centered-text';

    matchMethod.innerHTML = `
        <span class="badge action match-${escapeHtml(log.match_method)}">
            <i class="ph-light ${icon}"></i>
            ${escapeHtml(formatLabel(log.match_method))}
        </span>
    `;

} else {

    matchMethod.innerHTML = `
        <span class="text-muted">
            Not evaluated
        </span>
    `;

}


        /*
         * SCORE
         *
         * Scores are stored from 0 to 100.
         */
        if (
    log.score !== null &&
    log.score !== undefined &&
    log.score !== ''
) {

    const numericScore = Number(log.score);

    const safeScore = Math.max(
        0,
        Math.min(100, numericScore)
    );

    let scoreClass;
    let scoreIcon;

    if (safeScore >= 80) {

        scoreClass = 'score-high';
        scoreIcon = 'ph-check-circle';

    } else if (safeScore >= 50) {

        scoreClass = 'score-medium';
        scoreIcon = 'ph-chart-line';

    } else {

        scoreClass = 'score-low';
        scoreIcon = 'ph-warning-circle';

    }

    score.innerHTML = `
        <span class="badge action ${scoreClass}">
            <i class="ph-light ${scoreIcon}"></i>
            ${safeScore}%
        </span>
    `;

} else {

    score.innerHTML = `
        <span class="text-muted">
            Not evaluated
        </span>
    `;

}


        /*
         * FAQ
         */
        if (log.faq_id) {

            faq.innerHTML = `
                <div class="faq-reference">

                    <span class="faq-reference-title">
                        ${displayValue(
                            log.faq_question,
                            'Matched FAQ'
                        )}
                    </span>

                    <span class="faq-reference-id">
                        FAQ #${escapeHtml(log.faq_id)}
                    </span>

                </div>
            `;

            faqId.textContent = log.faq_id;

        } else {

            faq.textContent = 'No FAQ matched';

            faqId.textContent = 'Not applicable';

        }


        /*
         * SYSTEM INFORMATION
         */
        ipAddress.innerHTML = displayValue(
            log.ip_address,
            'Not recorded'
        );

        createdAt.innerHTML = displayValue(
            log.created_at,
            'Not available'
        );


        /*
         * Show the modal.
         */
        modal.classList.add('active');

        modal.setAttribute(
            'aria-hidden',
            'false'
        );

        /*
         * Prevent the page underneath from scrolling.
         */
        document.body.style.overflow = 'hidden';

    }


    /*
     * Every chatbot row becomes clickable.
     *
     * This matches the existing Activity Logs UX.
     */
    document
    .querySelectorAll('.chatbot-log-row')
    .forEach(row => {

        row.addEventListener('click', () => {

            /*
             * Read the chatbot interaction directly
             * from the clicked table row.
             */
            const log = {
                id: row.dataset.id,
                user_name: row.dataset.user,
                user_id: row.dataset.userId,
                question: row.dataset.question,
                answer: row.dataset.answer,
                agency_name: row.dataset.agency,
                faq_id: row.dataset.faqId,
                faq_question: row.dataset.faqQuestion,
                outcome: row.dataset.outcome,
                match_method: row.dataset.matchMethod,
                score: row.dataset.score || null,
                ip_address: row.dataset.ip,
                created_at: row.dataset.date
            };

            openChatbotModal(log);

        });

    });


    /*
     * Close the modal.
     */
    function closeChatbotModal() {

        modal.classList.remove('active');

        modal.setAttribute(
            'aria-hidden',
            'true'
        );

        document.body.style.overflow = '';

    }


    closeButton.addEventListener(
        'click',
        closeChatbotModal
    );


    /*
     * Clicking the backdrop closes the modal.
     */
    modal.addEventListener('click', event => {

        if (event.target === modal) {
            closeChatbotModal();
        }

    });


    /*
     * Escape closes the modal.
     */
    document.addEventListener('keydown', event => {

        if (
            event.key === 'Escape' &&
            modal.classList.contains('active')
        ) {
            closeChatbotModal();
        }

    });

});
</script>

@endpush

@endsection