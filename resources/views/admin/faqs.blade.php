@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/components/action-menu.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/form-system.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/faqs.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/faq-response-builder.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/faq-feedback-modal.css') }}?v={{ filemtime(public_path('cssfiles/admin/faq-feedback-modal.css')) }}">
@endpush

@section('title', 'KNOWURLOCAL | ' . ucfirst(auth()->user()->role) . ' Module')

@section('page-title', 'FAQ Management')
@section('page-subtitle', 'Manage frequently asked questions')

@section('content')

<div class="logs-page">

    {{-- =========================================================
         FAQ STATUS CONTROLS
         =========================================================

         Only Superadmins can switch between Active and Trashed.

         The backend still controls authorization. This Blade
         condition only controls what the current user sees.
         ========================================================= --}}

    <div class="faq-controls">

    @if(auth()->user()->role === 'superadmin')
        @include('admin.components.status-tabs', [
            'ariaLabel' => 'FAQ collections',
            'tabs' => [
                [
                    'href' => route('faqs.index', array_merge(request()->except('page'), ['status' => 'active'])),
                    'label' => 'Active',
                    'icon' => 'ph-book-open',
                    'count' => $activeCount,
                    'active' => $status === 'active',
                ],
                [
                    'href' => route('faqs.index', array_merge(request()->except('page'), ['status' => 'trashed'])),
                    'label' => 'Trashed',
                    'icon' => 'ph-trash',
                    'count' => $trashedCount,
                    'active' => $status === 'trashed',
                ],
            ],
        ])
    @endif

    <section class="support-filter-toolbar admin-filter-toolbar" aria-label="FAQ filters">
        <form method="GET" action="{{ route('faqs.index') }}" class="support-filter-form">
            <input type="hidden" name="status" value="{{ $status }}">

            <div class="support-filter-field support-search-field">
                <label for="faq-search" class="sr-only">Search FAQs</label>
                <i class="ph-light ph-magnifying-glass" aria-hidden="true"></i>
                <input
                    type="search"
                    id="faq-search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search question, answer, keyword, or agency..."
                    autocomplete="off"
                >
            </div>

            @include('admin.components.searchable-agency-filter', [
                'id' => 'faq-agency-filter',
                'name' => 'agency',
                'value' => request('agency'),
                'agencies' => $agencies,
                'label' => 'Filter by agency',
            ])

            <div class="support-filter-field">
                <label for="faq-date-filter" class="sr-only">Filter by date</label>
                <i class="ph-light ph-calendar-blank" aria-hidden="true"></i>
                <select name="date" id="faq-date-filter">
                    <option value="">All Dates</option>
                    @foreach($availableDates as $date)
                        <option value="{{ $date }}" {{ request('date') === $date ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="support-filter-field">
                <label for="faq-sort" class="sr-only">Sort FAQs</label>
                <i class="ph-light ph-arrows-down-up" aria-hidden="true"></i>
                <select name="sort" id="faq-sort">
                    <option value="latest" {{ request('sort') === 'latest' ? 'selected' : '' }}>Newest first</option>
                    <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest first</option>
                </select>
            </div>

            <div class="support-filter-field faq-feedback-filter-field">
                <label for="faq-feedback-filter" class="sr-only">Filter FAQs by feedback</label>
                <i class="ph-light ph-thumbs-down" aria-hidden="true"></i>
                <select name="feedback" id="faq-feedback-filter">
                    <option value="" {{ request('feedback') !== 'needs_review' ? 'selected' : '' }}>All feedback</option>
                    <option value="needs_review" {{ request('feedback') === 'needs_review' ? 'selected' : '' }}>Needs review</option>
                </select>
            </div>

            <button type="submit" class="support-filter-submit admin-icon-button" aria-label="Apply filters" title="Apply filters">
                <i class="ph-light ph-sliders-horizontal" aria-hidden="true"></i>
                <span class="sr-only">Filter</span>
            </button>

            @if(request()->filled('search') || request()->filled('agency') || request()->filled('date') || request()->filled('feedback') || request('sort', 'latest') !== 'latest')
                <a href="{{ route('faqs.index', ['status' => $status]) }}" class="support-filter-clear admin-icon-button" aria-label="Clear filters" title="Clear filters">
                    <i class="ph-light ph-x" aria-hidden="true"></i>
                    <span class="sr-only">Clear filters</span>
                </a>
            @endif

            @if($status === 'active')
                <button type="button" class="add-agencybtn admin-add-action" onclick="openFaqModal('add')" aria-label="Add FAQ" title="Add FAQ">
                    <i class="ph-light ph-plus" aria-hidden="true"></i>
                    <span class="sr-only">Add FAQ</span>
                </button>
            @endif

        </form>
    </section>
</div>

    <!-- ================= TABLE ================= -->
    @include('admin.components.list-result-meta', [
    'count' => $faqs->total(),
    'label' => 'FAQ',
])

<div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Question</th>
                    <th>Feedback</th>
                    <th>Date updated</th>
                    @if($status === 'trashed')
                        <th>Trash Reason</th>
                    @endif
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse($faqs as $faq)
                <tr
                    class="faq-row"
                    data-id="{{ $faq->id }}"
                    data-agency="{{ $faq->agency_id }}"

                    data-question="{{ $faq->question }}"
                    data-answer="{{ $faq->answer }}"

                    data-question-fil="{{ $faq->question_fil }}"
                    data-answer-fil="{{ $faq->answer_fil }}"

                    data-keywords="{{ $faq->keywords ?? '' }}"
                    data-image="{{ $faq->image ?? '' }}"
                    data-response-components='{{ json_encode($faq->response_components ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}'
                >

                    <td>{{ $faq->id }}</td>

                    <td class="faq-question-column">
                        <span class="faq-table-text faq-question-text">{{ $faq->question }}</span>
                        <span class="faq-agency-subline">{{ $faq->agency->agency_name ?? 'Agency unavailable' }}</span>
                    </td>

                    <td class="faq-feedback-summary-cell">
                        @php
                            $faqLikes = (int) ($faq->feedback_likes_count ?? 0);
                            $faqDislikes = (int) ($faq->feedback_dislikes_count ?? 0);
                            $faqRatingTotal = $faqLikes + $faqDislikes;
                            $minimumRatings = (int) config('faq_feedback.minimum_ratings_for_review', 5);
                            $priorityMinimum = (int) config('faq_feedback.priority_minimum_ratings', 10);
                            $negativeRate = $faqRatingTotal > 0 ? $faqDislikes / $faqRatingTotal : 0;
                            $faqNeedsReview = $faqRatingTotal >= $minimumRatings && $faqDislikes > $faqLikes;
                            $faqPriorityReview = $faqRatingTotal >= $priorityMinimum && $negativeRate >= (float) config('faq_feedback.priority_negative_rate', 0.60);
                        @endphp
                        <div class="faq-feedback-counts" aria-label="{{ $faqLikes }} likes and {{ $faqDislikes }} dislikes">
                            <span class="faq-feedback-count is-like"><i class="ph-light ph-thumbs-up" aria-hidden="true"></i>{{ number_format($faqLikes) }}</span>
                            <span class="faq-feedback-count is-dislike"><i class="ph-light ph-thumbs-down" aria-hidden="true"></i>{{ number_format($faqDislikes) }}</span>
                        </div>
                        @if($faqPriorityReview)
                            <span class="faq-review-badge is-priority">Priority review</span>
                        @elseif($faqNeedsReview)
                            <span class="faq-review-badge">Needs review</span>
                        @elseif($faqRatingTotal < $minimumRatings)
                            <span class="faq-review-badge is-muted">Collecting feedback</span>
                        @endif
                    </td>
                    <td>
                        @if($status === 'trashed')
                            {{ $faq->deleted_at?->format('M d, Y') ?? '—' }}
                        @else
                            {{ $faq->updated_at?->format('M d, Y') ?? '—' }}
                        @endif
                    </td>

                    @if($status === 'trashed')
                        <td class="admin-trash-reason-cell">
                            @if($faq->trash_reason)
                                <div class="admin-trash-reason-content">
                                    <span class="admin-trash-reason-text" title="{{ $faq->trash_reason }}">
                                        {{ $faq->trash_reason }}
                                    </span>

                                    @if(mb_strlen($faq->trash_reason) > 140)
                                        <button
                                            type="button"
                                            class="admin-trash-reason-view"
                                            data-trash-reason="{{ $faq->trash_reason }}"
                                            aria-label="View full trash reason for FAQ #{{ $faq->id }}"
                                        >
                                            View full
                                        </button>
                                    @endif
                                </div>
                            @else
                                <span class="admin-trash-reason-empty">No reason recorded</span>
                            @endif
                        </td>
                    @endif

                    <td>

    <div class="admin-row-actions">

    @if($status === 'active')

        {{-- PRIMARY: EDIT --}}
        <button
            type="button"
            class="admin-action-primary edit-btn"
            aria-label="Edit FAQ #{{ $faq->id }}"
            title="Edit FAQ"
            data-id="{{ $faq->id }}"
            data-agency="{{ $faq->agency_id }}"
            data-question="{{ $faq->question }}"
            data-answer="{{ $faq->answer }}"
            data-question-fil="{{ $faq->question_fil }}"
            data-answer-fil="{{ $faq->answer_fil }}"
            data-keywords="{{ e($faq->keywords ?? '') }}"
            data-image="{{ $faq->image }}"
            data-response-components='{{ json_encode($faq->response_components ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) }}'
        >
            <i class="ph-light ph-pencil-simple" aria-hidden="true"></i>
            <span>Edit</span>
        </button>

        {{-- SECONDARY ACTIONS --}}
        <div class="admin-action-menu">
            <button
                type="button"
                class="admin-action-menu-trigger"
                aria-label="More actions for FAQ #{{ $faq->id }}"
                aria-expanded="false"
                aria-haspopup="menu"
            >
                <i class="ph-light ph-dots-three-vertical" aria-hidden="true"></i>
            </button>

            <div class="admin-action-menu-content" role="menu">

                {{-- VIEW FEEDBACK --}}
                <button
                    type="button"
                    class="admin-menu-action admin-menu-secondary faq-feedback-open"
                    data-feedback-url="{{ route('admin.faqs.feedback', $faq->id) }}"
                    data-faq-question="{{ $faq->question }}"
                    role="menuitem"
                >
                    <i class="ph-light ph-chat-circle-text" aria-hidden="true"></i>
                    <span>View feedback</span>
                </button>

                {{-- MOVE TO TRASH --}}
                <form
                    method="POST"
                    action="{{ route('faqs.destroy', $faq->id) }}"
                    class="delete-form"
                >
                    @csrf
                    @method('DELETE')

                    <button
                        type="submit"
                        class="admin-menu-action admin-menu-danger delete-btn"
                        data-faq-question="{{ $faq->question }}"
                        role="menuitem"
                    >
                        <i class="ph-light ph-trash" aria-hidden="true"></i>
                        <span>Move to trash</span>
                    </button>
                </form>

            </div>
        </div>

    @elseif(
        $status === 'trashed'
        && auth()->user()->role === 'superadmin'
    )

        {{-- PRIMARY: RESTORE --}}
        <form
            method="POST"
            action="{{ route('admin.faqs.restore', $faq->id) }}"
            class="restore-form"
        >
            @csrf
            @method('PATCH')

            <button
                type="submit"
                class="admin-action-primary restore-btn"
                data-faq-question="{{ $faq->question }}"
                aria-label="Restore FAQ #{{ $faq->id }}"
            >
                <i class="ph-light ph-arrow-counter-clockwise" aria-hidden="true"></i>
                <span>Restore</span>
            </button>
        </form>

        {{-- SECONDARY ACTIONS --}}
        <div class="admin-action-menu">
            <button
                type="button"
                class="admin-action-menu-trigger"
                aria-label="More actions for FAQ #{{ $faq->id }}"
                aria-expanded="false"
                aria-haspopup="menu"
            >
                <i class="ph-light ph-dots-three-vertical" aria-hidden="true"></i>
            </button>

            <div class="admin-action-menu-content" role="menu">

                {{-- PERMANENT DELETE --}}
                <form
                    method="POST"
                    action="{{ route('admin.faqs.force-delete', $faq->id) }}"
                    class="force-delete-form"
                >
                    @csrf
                    @method('DELETE')

                    <button
                        type="submit"
                        class="admin-menu-action admin-menu-danger force-delete-btn"
                        data-faq-question="{{ $faq->question }}"
                        role="menuitem"
                    >
                        <i class="ph-light ph-trash" aria-hidden="true"></i>
                        <span>Delete permanently</span>
                    </button>
                </form>

            </div>
        </div>

    @endif

</div>


</td>

                </tr>
                @empty
                <tr>
                    <td colspan="{{ $status === 'trashed' ? 6 : 5 }}" class="empty">
                        @if($status === 'trashed')
                            No deleted FAQs found.
                        @else
                            No FAQs found.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        <!-- ================= FOOTER ================= -->
        <div class="footer">

            <!-- RESULTS -->
            <span class="result-info">
                Showing {{ $faqs->firstItem() ?? 0 }} 
                to {{ $faqs->lastItem() ?? 0 }} 
                of {{ $faqs->total() }} results
            </span>

            <!-- PAGINATION -->
            <div class="pagination-modern">

                {{-- PREVIOUS --}}
                @if ($faqs->onFirstPage())
                    <span class="arrow disabled">
                        <i class="ph-light ph-caret-left"></i>
                    </span>
                @else
                    <a href="{{ $faqs->previousPageUrl() }}" class="arrow">
                        <i class="ph-light ph-caret-left"></i>
                    </a>
                @endif

                {{-- PAGE NUMBER --}}
                <span class="page-indicator">
                    Page {{ $faqs->currentPage() }}
                </span>

                {{-- NEXT --}}
                @if ($faqs->hasMorePages())
                    <a href="{{ $faqs->nextPageUrl() }}" class="arrow">
                        <i class="ph-light ph-caret-right"></i>
                    </a>
                @else
                    <span class="arrow disabled">
                        <i class="ph-light ph-caret-right"></i>
                    </span>
                @endif

            </div>

        

    </div>

</div>

<!-- ================= MODAL ================= -->
<div id="modal-back" class="back">
    <div class="modal">

        <header class="modal-header admin-modal-header">
            <div class="admin-modal-heading">
                <div class="admin-modal-icon" aria-hidden="true">
                    <i class="ph-light ph-chat-centered-text"></i>
                </div>
                <div>
                    <span class="admin-modal-eyebrow">Knowledge base</span>
                    <h2 id="faq-modal-title">FAQ</h2>
                </div>
            </div>

            <div class="modal-actions">
                <button type="submit" form="faqForm" class="btn-save">
                    <i class="ph-light ph-floppy-disk"></i>
                    <span>Save FAQ</span>
                </button>
                <button type="button" onclick="closeFaqModal()" class="btn-cancel">
                    <i class="ph-light ph-x"></i>
                    <span>Cancel</span>
                </button>
            </div>
        </header>

        <form
    id="faqForm"
    method="POST"
    action="{{ route('faqs.store') }}"
    data-store-url="{{ route('faqs.store') }}"
    data-base-url="{{ route('faqs.index') }}"
    enctype="multipart/form-data"
>
    @csrf

    {{-- 
        Laravel uses this field to determine whether the form
        performs a POST request for creating an FAQ or a PUT/PATCH
        request for updating an existing FAQ.
    --}}
    <input
        type="hidden"
        name="_method"
        id="faq-method"
        value="POST"
    >

    {{--
        Contains the original Support Request ID when a completed
        Support Request is prepared as an FAQ.

        Laravel uses this trusted server-side reference to validate
        and copy selected response attachments into FAQ storage.
    --}}
    <input
        type="hidden"
        name="support_request_id"
        id="support_request_id"
        value=""
    >

            <div class="form-card">

                <div class="floating-group searchable-select" id="agency-searchable">

    {{-- =====================================================
         SEARCH INPUT
         =====================================================

         This is the visible field administrators interact with.
         It searches both the agency full name and abbreviation.
    --}}
    <input
        type="text"
        id="faq_agency_search"
        class="searchable-select-input"
        placeholder=" "
        autocomplete="off"
        role="combobox"
        aria-expanded="false"
        aria-controls="faq-agency-options"
        aria-autocomplete="list"
        required
    >

    <label
        for="faq_agency_search"
        class="searchable-select-label"
    >
        Agency
    </label>

    <i
        class="ph-light ph-caret-down searchable-select-arrow"
        aria-hidden="true"
    ></i>


    {{-- =====================================================
         CUSTOM DROPDOWN OPTIONS
         =====================================================

         JavaScript generates the searchable options here.
    --}}
    <div
        id="faq-agency-options"
        class="searchable-select-options"
        role="listbox"
    ></div>


    {{-- =====================================================
         NATIVE SELECT
         =====================================================

         This remains the real form field.

         Laravel receives agency_id from this select.
         JavaScript only synchronizes it with the visible search.
    --}}
    <select
        name="agency_id"
        id="faq_agency"
        class="searchable-select-native"
    >
        <option value="" selected disabled hidden>
            Choose agency
        </option>

        @foreach($agencies as $agency)

            <option
                value="{{ $agency->id }}"
                data-abbr="{{ strtolower($agency->agency_abbreviation ?? '') }}"
                data-full-name="{{ $agency->agency_name }}"
            >
                {{ $agency->agency_name }}
            </option>

        @endforeach
    </select>

    <div class="form-message">
        Please select an agency.
    </div>

</div>


                <div class="floating-group keyword-field-group">

                    <textarea
                        name="keywords"
                        id="faq_keywords"
                        placeholder=" "
                        rows="1"
                    ></textarea>

                    <label>Keywords</label>

                </div>

                {{-- =========================================================
                     ENGLISH QUESTION + RESPONSE
                     ========================================================= --}}
                <section class="faq-language-block" aria-labelledby="faq-english-heading">
                    <div class="faq-language-heading faq-question-heading">
                        <div class="faq-language-heading-copy">
                            <span class="faq-section-eyebrow">English</span>
                            <strong id="faq-english-heading">English question</strong>
                        </div>
                    </div>

                    <div class="floating-group">
                        <textarea
                            name="question"
                            id="faq_question"
                            placeholder=" "
                            rows="1"
                            required
                        ></textarea>
                        <label>English question</label>
                    </div>

                    <div class="faq-text-response-heading">
                        <div>
                            <span>Text response</span>
                            <small>Optional when the FAQ response is provided through attachments.</small>
                        </div>
                    </div>

                    <div id="faq-response-english" class="faq-response-components" aria-live="polite"></div>

                    <button type="button" class="faq-response-add faq-response-add-language" data-response-language="en">
                        <i class="ph-light ph-plus"></i>
                        Add text response
                    </button>
                </section>

                {{-- =========================================================
                     TAGALOG / TAGLISH QUESTION + RESPONSE
                     ========================================================= --}}
                <section class="faq-language-block faq-language-block-tagalog" aria-labelledby="faq-filipino-heading">
                    <div class="faq-language-heading">
                        <div class="faq-language-heading-copy">
                            <span class="faq-section-eyebrow">Filipino / Taglish</span>
                            <strong id="faq-filipino-heading">Tagalog / Taglish question</strong>
                        </div>

                        <button
                            type="button"
                            class="btn-ai-translate"
                            id="translateFaqBtn"
                        >
                            <i class="ph-light ph-sparkle"></i>
                            <span>Translate with AI</span>
                        </button>
                    </div>

                    <div class="floating-group">
                        <textarea
                            name="question_fil"
                            id="faq_question_fil"
                            placeholder=" "
                            rows="1"
                        ></textarea>
                        <label>Tagalog / Taglish question</label>
                    </div>

                    <div class="faq-text-response-heading">
                        <div>
                            <span>Text response</span>
                            <small>Optional when the FAQ response is provided through attachments.</small>
                        </div>
                    </div>

                    <div id="faq-response-filipino" class="faq-response-components" aria-live="polite"></div>

                    <button type="button" class="faq-response-add faq-response-add-language" data-response-language="fil">
                        <i class="ph-light ph-plus"></i>
                        Add text response
                    </button>
                </section>

                {{-- =========================================================
                     ADDITIONAL ATTACHMENTS
                     ========================================================= --}}
                <section class="faq-attachments-section" aria-labelledby="faq-attachments-heading">
                    <div class="faq-response-intro">
                        <div class="faq-response-heading">
                            <div class="faq-response-heading-icon" aria-hidden="true">
                                <i class="ph-light ph-paperclip"></i>
                            </div>
                            <div>
                                <span class="faq-response-eyebrow">Supporting resources</span>
                                <h3 id="faq-attachments-heading">Additional attachments</h3>
                                <p>Attach an image, document, official link, or QR destination related to this FAQ.</p>
                            </div>
                        </div>
                    </div>

                    <div id="faq-attachment-components" class="faq-response-components" aria-live="polite"></div>

                    <div class="faq-attachments-empty" id="faq-attachments-empty">
                        <i class="ph-light ph-paperclip" aria-hidden="true"></i>
                        <span>No additional attachments.</span>
                    </div>

                    <div class="faq-response-add-wrap">
                        <button type="button" class="faq-response-add" id="faq-attachment-add" aria-expanded="false" aria-controls="faq-attachment-menu">
                            <i class="ph-light ph-plus"></i>
                            Add attachment
                        </button>

                        <div class="faq-response-menu" id="faq-attachment-menu" hidden>
                            <button type="button" class="faq-response-option" data-attachment-type="image">
                                <span class="faq-response-option-icon"><i class="ph-light ph-image"></i></span>
                                <span><strong>Image</strong><small>Attach a visual guide or form.</small></span>
                            </button>
                            <button type="button" class="faq-response-option" data-attachment-type="file">
                                <span class="faq-response-option-icon"><i class="ph-light ph-file"></i></span>
                                <span><strong>File</strong><small>Attach a document citizens may need.</small></span>
                            </button>
                            <button type="button" class="faq-response-option" data-attachment-type="link">
                                <span class="faq-response-option-icon"><i class="ph-light ph-link"></i></span>
                                <span><strong>Link</strong><small>Point citizens to an official resource.</small></span>
                            </button>
                            <button type="button" class="faq-response-option" data-attachment-type="qr_code">
                                <span class="faq-response-option-icon"><i class="ph-light ph-qr-code"></i></span>
                                <span><strong>QR code</strong><small>Store an official destination for QR access.</small></span>
                            </button>
                        </div>
                    </div>
                </section>

        </form>

    </div>
</div>

<div class="faq-feedback-modal-backdrop" id="faqFeedbackModal" aria-hidden="true">
    <section class="faq-feedback-modal" role="dialog" aria-modal="true" aria-labelledby="faqFeedbackModalTitle" tabindex="-1">
        <header class="faq-feedback-modal-header">
            <div><span class="faq-feedback-modal-eyebrow">Answer quality</span><h2 id="faqFeedbackModalTitle">FAQ feedback</h2><p id="faqFeedbackModalQuestion"></p><span id="faqFeedbackModalAgency" class="faq-feedback-modal-agency"></span></div>
            <button type="button" class="faq-feedback-modal-close" data-faq-feedback-close aria-label="Close feedback"><i class="ph-light ph-x" aria-hidden="true"></i></button>
        </header>
        <div class="faq-feedback-modal-body">
            <div class="faq-feedback-modal-stats">
                <div><span>Total ratings</span><strong id="faqFeedbackTotal">0</strong></div>
                <div class="is-like"><span><i class="ph-light ph-thumbs-up" aria-hidden="true"></i> Likes</span><strong id="faqFeedbackLikes">0</strong></div>
                <div class="is-dislike"><span><i class="ph-light ph-thumbs-down" aria-hidden="true"></i> Dislikes</span><strong id="faqFeedbackDislikes">0</strong></div>
            </div>
            <div class="faq-feedback-negative-track" aria-hidden="true"><span id="faqFeedbackNegativeBar"></span></div>
            <div class="faq-feedback-modal-status" id="faqFeedbackModalStatus"></div>

            <section class="faq-feedback-version-card" aria-labelledby="faqFeedbackVersionTitle">
                <div class="faq-feedback-version-head">
                    <div>
                        <span class="faq-feedback-version-eyebrow">Response version</span>
                        <strong id="faqFeedbackVersionTitle">Version 1</strong>
                    </div>
                    <span id="faqFeedbackVersionState" class="faq-feedback-version-state">Current</span>
                </div>
                <div id="faqFeedbackVersionMeta" class="faq-feedback-version-meta"></div>
                <p id="faqFeedbackVersionAnswer">Loading response…</p>
            </section>

            <section class="faq-feedback-history" aria-labelledby="faqFeedbackHistoryTitle">
                <div class="faq-feedback-history-head">
                    <div>
                        <strong id="faqFeedbackHistoryTitle">Response history</strong>
                        <span>Older response versions are read-only.</span>
                    </div>
                </div>
                <div id="faqFeedbackHistoryList" class="faq-feedback-history-list"></div>
            </section>

            <div class="faq-feedback-modal-tabs" role="group" aria-label="Filter feedback">
                <button type="button" class="is-active" data-feedback-rating="all">All</button>
                <button type="button" data-feedback-rating="not_helpful">Dislikes</button>
                <button type="button" data-feedback-rating="helpful">Likes</button>
            </div>
            <div id="faqFeedbackList" class="faq-feedback-list" aria-live="polite"><div class="faq-feedback-loading">Loading feedback…</div></div>
            <footer class="faq-feedback-modal-footer"><span id="faqFeedbackPaginationInfo"></span><div><button type="button" id="faqFeedbackPrev" disabled>Previous</button><button type="button" id="faqFeedbackNext" disabled>Next</button></div></footer>
        </div>
    </section>
</div>

@endsection

@push('scripts')

<!-- 🔥 FORM SYSTEM (same as NGA) -->
<script src="{{ asset('jsfiles/components/form-system.js') }}"></script>

<!-- 🔥 ALERT MODAL SYSTEM (REUSABLE) -->


<script>
    /*
     * Existing manual FAQ translation endpoint.
     */
    window.FAQ_TRANSLATE_URL =
        @json(route('faqs.translate'));

    /*
     * Private FAQ response attachment endpoint.
     *
     * The attachment files live on the private disk, so the browser must
     * use the authenticated admin route rather than a public /faqs/... URL.
     * The response builder replaces these placeholders with the actual FAQ
     * and component IDs when an existing attachment is displayed.
     */
    @php
        $faqAttachmentUrlTemplate = route(
            'admin.faqs.response-attachment',
            [
                'faqId' => '__FAQ_ID__',
                'componentIndex' => '__COMPONENT_INDEX__',
            ]
        );
    @endphp
    window.FAQ_ATTACHMENT_URL_TEMPLATE = @json($faqAttachmentUrlTemplate);

    /*
     * Support Request → FAQ conversion data.
     *
     * This variable only exists when the FAQ page
     * was opened from a support request.
     */
    window.SUPPORT_FAQ_DATA =
        @json($conversionSupport ?? null);

    /*
     * Endpoint used to prepare the bilingual draft.
     */
    /*
 * Endpoint used to prepare a bilingual FAQ draft
 * from an existing Support Request.
 *
 * The endpoint receives the Support Request ID,
 * then retrieves the authoritative question and
 * answer directly from the database.
 */
window.SUPPORT_FAQ_PREPARE_URL =
    @json(
        !empty($conversionSupport)
            ? route(
                'admin.faqs.prepareFromSupport',
                $conversionSupport['id']
            )
            : null
    );
</script>

<script src="{{ asset('jsfiles/admin/faq-response-builder.js') }}"></script>
<script src="{{ asset('jsfiles/admin/trash-reason.js') }}"></script>
<script src="{{ asset('jsfiles/admin/components/action-menu.js') }}"></script>
<script src="{{ asset('jsfiles/admin/faqs.js') }}"></script>
<script src="{{ asset('jsfiles/admin/faq-feedback-modal.js') }}?v={{ filemtime(public_path('jsfiles/admin/faq-feedback-modal.js')) }}"></script>

<!-- 🔥 SUCCESS HANDLER (same pattern as NGA) -->
@if(session('success'))
<script>
    window.__FLASH_SUCCESS__ = @json(session('success'));
</script>
@endif

@endpush