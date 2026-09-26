@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/form-system.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/faqs.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/faq-response-builder.css') }}">
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
        <nav class="support-dataset-tabs" aria-label="FAQ collections">
            <a
                href="{{ route('faqs.index', array_merge(request()->except('page'), ['status' => 'active'])) }}"
                class="support-dataset-tab {{ $status === 'active' ? 'is-active' : '' }}"
                aria-current="{{ $status === 'active' ? 'page' : 'false' }}"
            >
                <i class="ph-light ph-book-open" aria-hidden="true"></i>
                <span>Active</span>
                <span class="support-dataset-count">{{ $activeCount }}</span>
            </a>

            <a
                href="{{ route('faqs.index', array_merge(request()->except('page'), ['status' => 'trashed'])) }}"
                class="support-dataset-tab {{ $status === 'trashed' ? 'is-active' : '' }}"
                aria-current="{{ $status === 'trashed' ? 'page' : 'false' }}"
            >
                <i class="ph-light ph-trash" aria-hidden="true"></i>
                <span>Trashed</span>
                <span class="support-dataset-count">{{ $trashedCount }}</span>
            </a>
        </nav>
    @endif

    <section class="support-filter-toolbar" aria-label="FAQ filters">
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
                    placeholder="Search question..."
                    autocomplete="off"
                >
            </div>

            <div class="support-filter-field">
                <label for="faq-agency-filter" class="sr-only">Filter by agency</label>
                <i class="ph-light ph-buildings" aria-hidden="true"></i>
                <select name="agency" id="faq-agency-filter">
                    <option value="">All Agencies</option>
                    @foreach($agencies as $agency)
                        <option value="{{ $agency->id }}" {{ request('agency') == $agency->id ? 'selected' : '' }}>
                            {{ $agency->agency_name }}
                        </option>
                    @endforeach
                </select>
            </div>

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

            <button type="submit" class="support-filter-submit">
                <i class="ph-light ph-sliders-horizontal" aria-hidden="true"></i>
                <span>Filter</span>
            </button>

            @if(request()->has('search') || request()->has('agency') || request()->has('date') || request('sort', 'latest') !== 'latest')
                <a href="{{ route('faqs.index', ['status' => $status]) }}" class="support-filter-clear">
                    <i class="ph-light ph-x" aria-hidden="true"></i>
                    <span>Clear</span>
                </a>
            @endif

            @if($status === 'active')
                <button type="button" class="add-agencybtn" onclick="openFaqModal('add')">
                    <i class="ph-light ph-plus" aria-hidden="true"></i>
                    <span>Add FAQ</span>
                </button>
            @endif
        </form>
    </section>
</div>

    <!-- ================= TABLE ================= -->
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Agency</th>
                    <th>Question</th>
                    <th>Answer</th>
                    <th>Date</th>
                    <th>Action</th>
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

                    <td>
                        <div class="actor-cell">

                            <span class="actor-name">
                                {{ $faq->agency->agency_name ?? '—' }}

                                @if($faq->agency && $faq->agency->agency_abbreviation)
                                    <span class="abbr">
                                        ({{ $faq->agency->agency_abbreviation }})
                                    </span>
                                @endif
                            </span>

                            @if($faq->agency && $faq->agency->type)
                                <span class="type-badge {{ strtolower($faq->agency->type->name) }}">
                                    {{ $faq->agency->type->name }}
                                </span>
                            @endif

                        </div>
                    </td>

                    <td>
                        <span class="faq-table-text faq-question-text">
                            {{ $faq->question }}
                        </span>
                    </td>

                    <td>
                        <span class="faq-table-text faq-answer-text">
                            {{ Str::limit($faq->answer, 80) }}
                        </span>
                    </td>
                    <td>
                        @if($status === 'trashed')

                            {{ $faq->deleted_at?->format('M d, Y') ?? '—' }}

                        @else

                            {{ $faq->created_at?->format('M d, Y') ?? '—' }}

                        @endif
                    </td>

                    <td>

    <div class="tablebtn">

        {{-- =================================================
             ACTIVE FAQ
             =================================================
             Normal Admin + Superadmin
             ================================================= --}}

        @if($status === 'active')

            {{-- EDIT --}}
            <button
                type="button"
                class="btn btn-primary edit-btn"

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
                <i class="ph-light ph-pencil-simple"></i>
                Edit
            </button>


            {{-- SOFT DELETE --}}
            <form
                method="POST"
                action="{{ route('faqs.destroy', $faq->id) }}"
                class="delete-form"
            >

                @csrf
                @method('DELETE')

                <button
                    type="button"
                    class="btn btn-danger delete-btn"
                    data-faq-question="{{ $faq->question }}"
                >
                    <i class="ph-light ph-trash"></i>
                    Trash
                </button>

            </form>


        {{-- =================================================
             TRASHED FAQ
             =================================================
             Superadmin only.
             ================================================= --}}

        @elseif(
            $status === 'trashed'
            && auth()->user()->role === 'superadmin'
        )

            {{-- RESTORE --}}
            <form
                method="POST"
                action="{{ route(
                    'admin.faqs.restore',
                    $faq->id
                ) }}"
                class="restore-form"
            >

                @csrf
                @method('PATCH')

                <button
                    type="button"
                    class="btn btn-restore restore-btn"
                    data-faq-question="{{ $faq->question }}"
                >
                    <i class="ph-light ph-arrow-counter-clockwise"></i>
                    Restore
                </button>

            </form>


            {{-- PERMANENT DELETE --}}
            <form
                method="POST"
                action="{{ route(
                    'admin.faqs.force-delete',
                    $faq->id
                ) }}"
                class="force-delete-form"
            >

                @csrf
                @method('DELETE')

                <button
                    type="button"
                    class="btn btn-danger force-delete-btn"
                    data-faq-question="{{ $faq->question }}"
                >
                    <i class="ph-light ph-trash"></i>
                    Delete Permanently
                </button>

            </form>

        @endif

    </div>

</td>

                </tr>
                @empty
                <tr>
                    <td colspan="6" class="empty">
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
                            <small>Add one or more text blocks for the English answer.</small>
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
                            <small>Add one or more text blocks for the Tagalog / Taglish answer.</small>
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

@endsection

@push('scripts')

<!-- 🔥 FORM SYSTEM (same as NGA) -->
<script src="{{ asset('jsfiles/components/form-system.js') }}"></script>

<!-- 🔥 ALERT MODAL SYSTEM (REUSABLE) -->
<script src="{{ asset('jsfiles/components/modal-system.js') }}"></script>

<script>
    /*
     * Existing manual FAQ translation endpoint.
     */
    window.FAQ_TRANSLATE_URL =
        @json(route('admin.faqs.translate'));

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
<script src="{{ asset('jsfiles/admin/faqs.js') }}"></script>

<!-- 🔥 SUCCESS HANDLER (same pattern as NGA) -->
@if(session('success'))
<script>
    window.__FLASH_SUCCESS__ = @json(session('success'));
</script>
@endif

@endpush