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
    <section class="support-filter-toolbar admin-filter-toolbar" aria-label="Chatbot log filters">
        <form method="GET" class="support-filter-form">
            <div class="support-filter-field support-search-field">
                <label for="chatbot-search" class="sr-only">Search chatbot logs</label>
                <i class="ph-light ph-magnifying-glass" aria-hidden="true"></i>
                <input
                    type="search"
                    id="chatbot-search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search question, answer, user or agency..."
                    autocomplete="off"
                >
            </div>

            <div class="support-filter-field">
                <label for="chatbot-outcome" class="sr-only">Filter by outcome</label>
                <i class="ph-light ph-target" aria-hidden="true"></i>
                <select name="outcome" id="chatbot-outcome">
                    <option value="">All Outcomes</option>
                    @foreach($availableOutcomes as $outcome)
                        <option value="{{ $outcome }}" {{ request('outcome') === $outcome ? 'selected' : '' }}>
                            {{ ucfirst(str_replace('_', ' ', $outcome)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="support-filter-field">
                <label for="chatbot-match-method" class="sr-only">Filter by match method</label>
                <i class="ph-light ph-git-branch" aria-hidden="true"></i>
                <select name="match_method" id="chatbot-match-method">
                    <option value="">All Match Methods</option>
                    @foreach($availableMatchMethods as $method)
                        <option value="{{ $method }}" {{ request('match_method') === $method ? 'selected' : '' }}>
                            {{ ucfirst($method) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="support-filter-field">
                <label for="chatbot-date" class="sr-only">Filter by date</label>
                <i class="ph-light ph-calendar-blank" aria-hidden="true"></i>
                <select name="date" id="chatbot-date">
                    <option value="">All Dates</option>
                    @foreach($availableDates as $date)
                        <option value="{{ $date }}" {{ request('date') === $date ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="support-filter-field">
                <label for="chatbot-sort" class="sr-only">Sort chatbot logs</label>
                <i class="ph-light ph-arrows-down-up" aria-hidden="true"></i>
                <select name="sort" id="chatbot-sort">
                    <option value="desc" {{ request('sort', 'desc') === 'desc' ? 'selected' : '' }}>Newest First</option>
                    <option value="asc" {{ request('sort') === 'asc' ? 'selected' : '' }}>Oldest First</option>
                </select>
            </div>

            <button type="submit" class="support-filter-submit admin-icon-button" aria-label="Apply filters" title="Apply filters">
                <i class="ph-light ph-sliders-horizontal" aria-hidden="true"></i>
                <span class="sr-only">Filter</span>
            </button>
        </form>
    </section>

    <!-- ================= TABLE ================= -->

    @include('admin.components.list-result-meta', [
    'count' => $logs->total(),
    'label' => 'log',
])

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

@php
    $responseLanguage = $log->response_language ?: null;
    $responseComponents = collect($log->faqVersion?->response_components ?? [])
        ->filter(function ($component) use ($responseLanguage) {
            if (!is_array($component) || empty($component['type'])) {
                return false;
            }

            $componentLanguage = $component['language'] ?? null;

            return !$responseLanguage
                || in_array($component['type'], ['image', 'file', 'link', 'qr_code'], true)
                || $componentLanguage === $responseLanguage
                || $componentLanguage === null;
        })
        ->map(function ($component) {
            $type = (string) ($component['type'] ?? '');
            $language = (string) ($component['language'] ?? '');
            $label = trim((string) ($component['label'] ?? ''));
            $content = trim((string) ($component['content'] ?? ''));

            return [
                'type' => $type,
                'language' => $language,
                'label' => $label !== '' ? $label : null,
                'content' => in_array($type, ['image', 'file'], true)
                    ? null
                    : ($content !== '' ? $content : null),
                'summary' => match ($type) {
                    'image' => 'Private image attachment',
                    'file' => 'Private file attachment',
                    'link' => $label !== '' ? $label : ($content !== '' ? $content : 'Link'),
                    'qr_code' => $label !== '' ? $label : 'QR code',
                    default => $content,
                },
            ];
        })
        ->values()
        ->all();
@endphp

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
    data-faq-question="{{ $log->faqVersion?->question ?? $log->faq?->question ?? '' }}"
    data-faq-version-id="{{ $log->faq_version_id ?? '' }}"
    data-faq-version-number="{{ $log->faqVersion?->version_number ?? '' }}"
    data-faq-version-published="{{ $log->faqVersion?->created_at?->format('M d, Y H:i') ?? '' }}"
    data-response-language="{{ $log->response_language ?? '' }}"
    data-response-components="{{ e(json_encode($responseComponents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}"
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

                'similarity' =>
                    'ph-chart-line',

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

<div id="chatbotLogModal" class="log-modal" aria-hidden="true">
    <div
        class="modal-content chatbot-detail-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="chatbotModalTitle"
    >
        <div class="modal-header">
            <div class="modal-title-group">
                <div>
                    <span class="modal-eyebrow">CHATBOT INTERACTION</span>
                    <span id="chatbotModalTitle">Interaction Details</span>
                </div>
                <span id="chatbotModalId" class="modal-reference"></span>
            </div>
            <button type="button" id="closeChatbotModal" class="modal-close" aria-label="Close chatbot interaction details">
                <i class="ph-light ph-x"></i>
            </button>
        </div>

        <div class="modal-body chatbot-detail-body">
            <section class="chatbot-log-block">
                <div class="chatbot-block-heading">
                    <span class="modal-label">INTERACTION</span>
                </div>
                <div class="chatbot-detail-grid">
                    <div class="chatbot-detail-section"><span class="modal-label">User</span><div id="chatbotUser" class="chatbot-detail-value"></div></div>
                    <div class="chatbot-detail-section"><span class="modal-label">Agency</span><div id="chatbotAgency" class="chatbot-detail-value"></div></div>
                </div>
                <div class="chatbot-detail-section chatbot-detail-wide">
                    <span class="modal-label">Question</span>
                    <div id="chatbotQuestion" class="chatbot-detail-box"></div>
                </div>
            </section>

            <section class="chatbot-log-block">
                <div class="chatbot-block-heading"><span class="modal-label">RESULT</span></div>
                <div class="chatbot-detail-grid">
                    <div class="chatbot-detail-section"><span class="modal-label">Outcome</span><div id="chatbotOutcome" class="chatbot-detail-value"></div></div>
                    <div class="chatbot-detail-section"><span class="modal-label">Match Method</span><div id="chatbotMatchMethod" class="chatbot-detail-value"></div></div>
                    <div class="chatbot-detail-section"><span id="chatbotScoreLabel" class="modal-label">Match Score</span><div id="chatbotScore" class="chatbot-detail-value"></div></div>
                </div>
            </section>

            <section id="chatbotResponseBlock" class="chatbot-log-block">
                <div class="chatbot-block-heading"><span class="modal-label">RESPONSE DELIVERED</span></div>
                <div class="chatbot-detail-section">
                    <span class="modal-label">Answer</span>
                    <div id="chatbotAnswer" class="chatbot-detail-box"></div>
                </div>
                <div id="chatbotComponentsWrap" class="chatbot-detail-section chatbot-components-section">
                    <span class="modal-label">Response Components</span>
                    <div id="chatbotComponents" class="chatbot-components"></div>
                </div>
            </section>

            <section id="chatbotKnowledgeBlock" class="chatbot-log-block">
                <div class="chatbot-block-heading"><span class="modal-label">KNOWLEDGE USED</span></div>
                <div id="chatbotFaq" class="faq-reference"></div>
            </section>

            <section class="chatbot-log-block">
                <div class="chatbot-block-heading"><span class="modal-label">SYSTEM INFORMATION</span></div>
                <div class="chatbot-system-info">
                    <div><span>User ID</span><strong id="chatbotUserId"></strong></div>
                    <div><span>FAQ ID</span><strong id="chatbotFaqId"></strong></div>
                    <div><span>FAQ Version</span><strong id="chatbotFaqVersion"></strong></div>
                    <div><span>Response Language</span><strong id="chatbotResponseLanguage"></strong></div>
                    <div><span>Response Published</span><strong id="chatbotFaqPublished"></strong></div>
                    <div><span>IP Address</span><strong id="chatbotIp"></strong></div>
                    <div><span>Interaction Date</span><strong id="chatbotDate"></strong></div>
                </div>
            </section>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
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
    const faqVersion = document.getElementById('chatbotFaqVersion');
    const faqPublished = document.getElementById('chatbotFaqPublished');
    const responseLanguage = document.getElementById('chatbotResponseLanguage');
    const ipAddress = document.getElementById('chatbotIp');
    const createdAt = document.getElementById('chatbotDate');
    const responseBlock = document.getElementById('chatbotResponseBlock');
    const knowledgeBlock = document.getElementById('chatbotKnowledgeBlock');
    const componentsWrap = document.getElementById('chatbotComponentsWrap');
    const components = document.getElementById('chatbotComponents');

    function escapeHtml(value) {
        const element = document.createElement('div');
        element.textContent = value ?? '';
        return element.innerHTML;
    }

    function displayValue(value, fallback) {
        return value === null || value === undefined || value === ''
            ? escapeHtml(fallback)
            : escapeHtml(value);
    }

    function formatLabel(value, fallback = 'Not available') {
        if (!value) return fallback;
        return String(value).replaceAll('_', ' ').replace(/\b\w/g, letter => letter.toUpperCase());
    }

    function renderOutcome(value) {
        if (!value) {
            return '<span class="text-muted">Not recorded</span>';
        }

        const icons = {
            answered: 'ph-check-circle',
            fallback: 'ph-warning-circle',
            error: 'ph-x-circle',
            greeting: 'ph-hand-waving',
            thanks: 'ph-smiley',
            irrelevant: 'ph-prohibit',
            clarification: 'ph-chat-circle-dots',
            wrong_agency: 'ph-arrow-bend-up-left'
        };

        return `<span class="badge action ${escapeHtml(value)}"><i class="ph-light ${icons[value] || 'ph-chat-centered-text'}"></i>${escapeHtml(formatLabel(value))}</span>`;
    }

    function renderMatchMethod(value) {
        if (!value) return '<span class="text-muted">Not evaluated</span>';

        const icons = {
            rule: 'ph-faders',
            semantic: 'ph-brain',
            similarity: 'ph-chart-line',
            ai: 'ph-sparkle',
            none: 'ph-minus-circle'
        };

        const labels = {
            rule: 'Rule',
            semantic: 'Semantic',
            similarity: 'Similarity',
            ai: 'AI',
            none: 'Not evaluated'
        };

        const label = labels[value] || formatLabel(value);

        return `<span class="badge action match-${escapeHtml(value)}"><i class="ph-light ${icons[value] || 'ph-git-branch'}"></i>${escapeHtml(label)}</span>`;
    }

    function renderScore(value) {
        if (value === null || value === undefined || value === '') {
            return '<span class="text-muted">Not evaluated</span>';
        }
        const numeric = Number(value);
        if (!Number.isFinite(numeric)) return '<span class="text-muted">Not evaluated</span>';
        const safe = Math.max(0, Math.min(100, numeric));
        const cls = safe >= 80 ? 'score-high' : (safe >= 50 ? 'score-medium' : 'score-low');
        const icon = safe >= 80 ? 'ph-check-circle' : (safe >= 50 ? 'ph-chart-line' : 'ph-warning-circle');
        return `<span class="badge action ${cls}"><i class="ph-light ${icon}"></i>${safe}%</span>`;
    }

    function renderComponents(raw) {
        let items = [];
        try { items = raw ? JSON.parse(raw) : []; } catch (_) { items = []; }
        if (!Array.isArray(items) || items.length === 0) {
            componentsWrap.hidden = true;
            components.innerHTML = '';
            return;
        }

        componentsWrap.hidden = false;
        components.innerHTML = items.map((item, index) => {
            const type = String(item.type || 'component');
            const icon = { text: 'ph-text-aa', image: 'ph-image', file: 'ph-file', link: 'ph-link', qr_code: 'ph-qr-code' }[type] || 'ph-cube';
            const summary = item.summary || item.content || item.label || 'No component content recorded';
            const language = item.language ? ` · ${escapeHtml(item.language.toUpperCase())}` : '';
            return `<div class="chatbot-component-item"><div class="chatbot-component-meta"><span class="chatbot-component-index">${index + 1}</span><i class="ph-light ${icon}"></i><strong>${escapeHtml(formatLabel(type))}</strong><span>${language}</span></div><div class="chatbot-component-content">${escapeHtml(summary)}</div></div>`;
        }).join('');
    }

    function openChatbotModal(log) {
        modalId.textContent = `#${log.id}`;
        user.innerHTML = displayValue(log.user_name, 'Unknown user');
        userId.textContent = log.user_id || 'Not recorded';
        question.innerHTML = displayValue(log.question, 'No question recorded');
        agency.innerHTML = displayValue(log.agency_name, 'No agency context');
        outcome.innerHTML = renderOutcome(log.outcome);
        matchMethod.innerHTML = renderMatchMethod(log.match_method);

        const scoreLabels = {
            semantic: 'Semantic Confidence',
            similarity: 'Similarity Score',
            rule: 'Rule Match Score'
        };
        const scoreLabel = scoreLabels[log.match_method] || 'Match Score';
        document.getElementById('chatbotScoreLabel').textContent = scoreLabel;

        score.innerHTML = renderScore(log.score);
        answer.innerHTML = displayValue(log.answer, 'No answer recorded');
        renderComponents(log.response_components);

        const hasKnowledge = Boolean(log.faq_id);
        knowledgeBlock.hidden = !hasKnowledge;
        responseBlock.hidden = !log.answer && !log.response_components;

        if (hasKnowledge) {
            faq.innerHTML = `<div><span class="faq-reference-title">${displayValue(log.faq_question, 'Matched FAQ')}</span><span class="faq-reference-meta">FAQ #${escapeHtml(log.faq_id)} · Version ${escapeHtml(log.faq_version_number || 'Unknown')}</span></div>`;
        } else {
            faq.innerHTML = '';
        }

        faqId.textContent = log.faq_id || 'Not applicable';
        faqVersion.textContent = log.faq_version_number ? `Version ${log.faq_version_number} · #${log.faq_version_id}` : 'Not applicable';
        const languageLabels = {
            en: 'English',
            fil: 'Filipino'
        };
        responseLanguage.textContent = languageLabels[log.response_language] || (log.response_language || 'Not recorded');
        faqPublished.textContent = log.faq_version_published || 'Not recorded';
        ipAddress.textContent = log.ip_address || 'Not recorded';
        createdAt.textContent = log.created_at || 'Not available';

        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    document.querySelectorAll('.chatbot-log-row').forEach(row => {
        row.addEventListener('click', () => openChatbotModal({
            id: row.dataset.id,
            user_name: row.dataset.user,
            user_id: row.dataset.userId,
            question: row.dataset.question,
            answer: row.dataset.answer,
            agency_name: row.dataset.agency,
            faq_id: row.dataset.faqId,
            faq_question: row.dataset.faqQuestion,
            faq_version_id: row.dataset.faqVersionId,
            faq_version_number: row.dataset.faqVersionNumber,
            faq_version_published: row.dataset.faqVersionPublished,
            response_language: row.dataset.responseLanguage,
            response_components: row.dataset.responseComponents,
            outcome: row.dataset.outcome,
            match_method: row.dataset.matchMethod,
            score: row.dataset.score || null,
            ip_address: row.dataset.ip,
            created_at: row.dataset.date
        }));
    });

    function closeChatbotModal() {
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    closeButton.addEventListener('click', closeChatbotModal);
    modal.addEventListener('click', event => { if (event.target === modal) closeChatbotModal(); });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && modal.classList.contains('active')) closeChatbotModal();
    });
});
</script>
@endpush

@endsection