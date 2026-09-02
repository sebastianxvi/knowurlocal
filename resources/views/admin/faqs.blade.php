@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/form-system.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/faqs.css') }}">
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

            <div class="faq-status-tabs">

                {{-- ACTIVE --}}
                <a
                    href="{{ route('faqs.index', array_merge(
                        request()->except('page', 'status'),
                        ['status' => 'active']
                    )) }}"
                    class="faq-status-tab {{ $status === 'active' ? 'active' : '' }}"
                >
                    <i class="ph-light ph-chat-circle-text"></i>

                    <span>Active</span>

                    <span class="status-count">
                        {{ $activeCount }}
                    </span>
                </a>


                {{-- TRASHED --}}
                <a
                    href="{{ route('faqs.index', array_merge(
                        request()->except('page', 'status'),
                        ['status' => 'trashed']
                    )) }}"
                    class="faq-status-tab trashed-tab {{ $status === 'trashed' ? 'active' : '' }}"
                >
                    <i class="ph-light ph-trash"></i>

                    <span>Trashed</span>

                    <span class="status-count">
                        {{ $trashedCount }}
                    </span>
                </a>

            </div>

        @endif


        {{-- =====================================================
             FILTER BAR
             ===================================================== --}}

        <form
            method="GET"
            action="{{ route('faqs.index') }}"
        >

            {{-- Preserve the current Active / Trashed state. --}}
            <input
                type="hidden"
                name="status"
                value="{{ $status }}"
            >

            <div class="filter-card">

                <div class="filter-bar">

                    {{-- SEARCH --}}
                    <input
                        type="text"
                        name="search"
                        placeholder="Search FAQ..."
                        value="{{ request('search') }}"
                    >


                    {{-- AGENCY --}}
                    <select name="agency">

                        <option value="">
                            All Agencies
                        </option>

                        @foreach($agencies as $agency)

                            <option
                                value="{{ $agency->id }}"
                                {{ request('agency') == $agency->id ? 'selected' : '' }}
                            >
                                {{ $agency->agency_name }}
                            </option>

                        @endforeach

                    </select>


                    {{-- DATE --}}
                    <select name="date">

                        <option value="">
                            All Dates
                        </option>

                        @foreach($availableDates as $date)

                            <option
                                value="{{ $date }}"
                                {{ request('date') == $date ? 'selected' : '' }}
                            >
                                {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                            </option>

                        @endforeach

                    </select>


                    {{-- SORT --}}
                    <select name="sort">

                        <option
                            value="latest"
                            {{ request('sort') === 'latest' ? 'selected' : '' }}
                        >
                            Newest First
                        </option>

                        <option
                            value="oldest"
                            {{ request('sort') === 'oldest' ? 'selected' : '' }}
                        >
                            Oldest First
                        </option>

                    </select>


                    {{-- FILTER --}}
                    <button type="submit">
                        Filter
                    </button>

                </div>


                {{-- ADD FAQ ONLY EXISTS IN ACTIVE MODE --}}
                <div>

                    @if($status === 'active')

    <button
        type="button"
        class="add-agencybtn"
        onclick="openFaqModal('add')"
    >
        <i class="ph-light ph-plus"></i>
        Add FAQ
    </button>

@endif

                </div>

            </div>

        </form>

    </div>
    </form>

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

        <div class="modal-header">
            <h2 id="faq-modal-title">FAQ</h2>

            <div class="modal-actions">
                <button type="submit" form="faqForm" class="btn-save">Save</button>
                <button type="button" onclick="closeFaqModal()" class="btn-cancel">Cancel</button>
            </div>
        </div>

        <form id="faqForm" method="POST" action="{{ route('faqs.store') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" id="faq-method" value="POST">

            <div class="form-card">

                <div class="floating-group">
                    <select name="agency_id" id="faq_agency" required>
                        <option value="" disabled selected hidden></option>
                        @foreach($agencies as $agency)
                            <option
                                value="{{ $agency->id }}"
                                data-abbr="{{ strtolower($agency->agency_abbreviation) }}"
                                data-full-name="{{ $agency->agency_name }}"
                            >
                                {{ $agency->agency_name }}
                            </option>
                        @endforeach
                    </select>
                    <label>Agency</label>
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

                <div
                    id="keywordSuggestions"
                    class="keyword-suggestions"
                    hidden
                >
                    <div class="keyword-suggestions-header">

                        <span>
                            AI keyword suggestions
                        </span>

                        <div class="keyword-suggestion-actions">

                            <button
                                type="button"
                                id="regenerateKeywordSuggestions"
                                class="btn-regenerate-keywords"
                            >
                                <i class="ph-light ph-arrows-clockwise"></i>
                                Regenerate
                            </button>

                            <button
                                type="button"
                                id="addKeywordSuggestions"
                                disabled
                            >
                                Add selected (0)
                            </button>

                        </div>

                    </div>

                    <div
                        id="keywordSuggestionList"
                        class="keyword-suggestion-list"
                        role="group"
                        aria-label="AI keyword suggestions"
                    ></div>
                </div>

                <div class="language-section">

                    <div class="language-heading">
                        <span>English</span>
                        <small>Official</small>
                    </div>

                    <div class="floating-group">
                        <textarea
                            name="question"
                            id="faq_question"
                            placeholder=" "
                            rows="1"
                            required
                        ></textarea>
                        <label>Question</label>
                    </div>

                    <div class="floating-group">
                        <textarea
                            name="answer"
                            id="faq_answer"
                            placeholder=" "
                            required
                        ></textarea>
                        <label>Answer</label>
                    </div>

                </div>

                <div class="language-section filipino-section">

                    <div class="language-heading">
<div class="faq-language-heading">
    <div class="faq-language-title optional">
        Filipino / Taglish
        {{-- <span class="faq-optional-badge">Optional</span> --}}
    </div>

    <button
        type="button"
        class="btn-ai-translate"
        id="translateFaqBtn"
    >
        <i class="ph-light ph-sparkle"></i>
        Translate with AI
    </button>
</div>

                    <div class="floating-group">
                        <textarea
                            name="question_fil"
                            id="faq_question_fil"
                            placeholder=" "
                            rows="1"
                        ></textarea>
                        <label>Question</label>
                    </div>

                    <div class="floating-group">
                        <textarea
                            name="answer_fil"
                            id="faq_answer_fil"
                            placeholder=" "
                        ></textarea>
                        <label>Answer</label>
                    </div>

                </div>

                <div class="floating-group">

                    <div class="image-upload-box" id="image-upload-box">

                        <input 
                            type="file" 
                            name="image" 
                            id="faq_image" 
                            accept="image/*"
                            hidden
                        >

                        <!-- Upload UI -->
                        <div class="upload-content" id="upload-placeholder">
                            <i class="ph ph-image"></i>
                            <p>Click to upload image</p>
                            <span>PNG, JPG up to 2MB</span>
                        </div>

                        <!-- Preview -->
                        <img id="preview-img" class="faq-preview-img" style="display:none;">

                    </div>

                    <label>Upload Image (optional)</label>
                </div>

            </div>

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
        @json(route('faqs.translate'));

    window.FAQ_KEYWORDS_URL =
        @json(route('admin.faqs.generateKeywords'));

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

<script src="{{ asset('jsfiles/admin/faqs.js') }}"></script>

<!-- 🔥 SUCCESS HANDLER (same pattern as NGA) -->
@if(session('success'))
<script>
    window.__FLASH_SUCCESS__ = @json(session('success'));
</script>
@endif

@endpush