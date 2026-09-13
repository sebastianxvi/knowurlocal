@extends('layouts.admin')

@push('styles')

<link
    rel="stylesheet"
    href="{{ asset('cssfiles/components/table.css') }}"
>

<link
    rel="stylesheet"
    href="{{ asset('cssfiles/components/form-system.css') }}"
>

{{-- Shared image uploader design --}}
<link
    rel="stylesheet"
    href="{{ asset('cssfiles/components/image-upload.css') }}"
>

{{-- Support Request-specific styles --}}
<link
    rel="stylesheet"
    href="{{ asset('cssfiles/admin/support_requests.css') }}"
>

@endpush


@section('title', 'KNOWURLOCAL | Support Requests')

@section('page-title', 'Support Requests')
@section('page-subtitle', 'Manage user-submitted inquiries')


@section('content')

<div class="logs-page">


    {{-- =========================================================
         STATUS TABS
         ========================================================= --}}

    @if(auth()->user()->role === 'superadmin')

    <div class="support-status-tabs">

        {{-- ACTIVE --}}
        <a
            href="{{ route(
                'admin.support.requests',
                array_merge(
                    request()->except('page'),
                    ['status' => 'active']
                )
            ) }}"
            class="support-status-tab {{ $status === 'active' ? 'active' : '' }}"
        >

            <i class="ph-light ph-inbox"></i>

            <span>
                Active
            </span>

            <span class="status-count">
                {{ $activeCount }}
            </span>

        </a>


        {{-- TRASHED --}}
        <a
            href="{{ route(
                'admin.support.requests',
                array_merge(
                    request()->except('page'),
                    [
                        'status' => 'trashed',
                        'status_filter' => null
                    ]
                )
            ) }}"
            class="support-status-tab {{ $status === 'trashed' ? 'active' : '' }}"
        >

            <i class="ph-light ph-trash"></i>

            <span>
                Trashed
            </span>

            <span class="status-count">
                {{ $trashedCount }}
            </span>

        </a>

    </div>

    @endif



    {{-- =========================================================
         FILTER
         ========================================================= --}}

    <form
        method="GET"
        action="{{ route('admin.support.requests') }}"
    >

        {{-- Preserve the current dataset. --}}
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
                    placeholder="Search question..."
                    value="{{ request('search') }}"
                >


                {{-- REQUEST STATUS --}}
                <select name="status_filter">

                    <option value="">
                        All Status
                    </option>

                    <option
                        value="pending"
                        {{ request('status_filter') === 'pending' ? 'selected' : '' }}
                    >
                        Pending
                    </option>

                    <option
                        value="answered"
                        {{ request('status_filter') === 'answered' ? 'selected' : '' }}
                    >
                        Answered
                    </option>

                </select>


                {{-- AGENCY --}}
                <select name="agency">

                    <option value="">
                        All Agencies
                    </option>

                    @foreach($agencies as $agency)

                        <option
                            value="{{ $agency->id }}"
                            {{ (string) request('agency') === (string) $agency->id ? 'selected' : '' }}
                        >
                            {{ $agency->agency_name }}
                        </option>

                    @endforeach

                </select>


                {{-- FILTER BUTTON --}}
                <button type="submit">
                    Filter
                </button>

            </div>

        </div>

    </form>



    {{-- =========================================================
         TABLE
         ========================================================= --}}

    <div class="table-wrapper">

        <table class="table">

            <thead>

                <tr>

                    <th>ID</th>

                    <th>
                        User
                    </th>

                    <th>
                        Question
                    </th>

                    <th>
                        Agency
                    </th>

                    <th>
                        Status
                    </th>

                    <th>
                        Date
                    </th>

                    <th>
                        Action
                    </th>

                </tr>

            </thead>


            <tbody>

                @forelse($requests as $req)

                    <tr>

                        {{-- =================================================
                             ID
                             ================================================== --}}

                        <td>
                            {{ $req->id }}
                        </td>



                        {{-- =================================================
                             USER
                             ================================================== --}}

                        <td>

                            <div class="actor-cell">

                                <span class="actor-name">

                                    {{ $req->user->first_name ?? 'Guest' }}

                                </span>

                            </div>

                        </td>



                        {{-- =================================================
                             QUESTION
                             ================================================== --}}

                        <td>

                            {{ Str::limit(
                                $req->question,
                                60
                            ) }}

                        </td>



                        {{-- =================================================
                             AGENCY
                             ================================================== --}}

                        <td>

                            {{ $req->agency->agency_name ?? '—' }}

                        </td>



                        {{-- =================================================
                             STATUS
                             ================================================== --}}

                        <td>

                            <span class="badge {{ $req->status }}">

                                {{ ucfirst($req->status) }}

                            </span>

                        </td>



                        {{-- =================================================
                             DATE
                             ================================================== --}}

                        <td>

                            {{ $req->created_at->format('M d, Y') }}

                        </td>



                        {{-- =================================================
                             ACTIONS
                             ================================================== --}}

                        <td>

                            <div class="tablebtn">


                                {{-- =================================================
                                     ACTIVE REQUEST ACTIONS
                                     ================================================== --}}

                                @if($status === 'active')


                                    {{-- MANAGE --}}
                                    <button
                                        type="button"
                                        class="btn btn-primary view-btn"

                                        data-id="{{ $req->id }}"
                                        data-question="{{ $req->question }}"
                                        data-user="{{ $req->user->first_name ?? 'Guest' }}"
                                        data-agency="{{ $req->agency->agency_name ?? 'Unknown' }}"
                                        data-agency-id="{{ $req->agency_id }}"
                                        data-answer="{{ $req->answer }}"

                                        {{-- NEW: Existing answer image path --}}
                                        data-answer-image="{{ $req->answer_image }}"
                                    >

                                        <i class="ph-light ph-chat-centered-text"></i>

                                        <span>
                                            Manage
                                        </span>

                                    </button>



                                    @if(auth()->user()->role === 'superadmin')


                                        {{-- MOVE TO TRASH --}}
                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'admin.support.delete',
                                                $req->id
                                            ) }}"
                                        >

                                            @csrf

                                            @method('DELETE')


                                            <button
                                                type="submit"
                                                class="btn btn-danger delete-btn"
                                            >

                                                <i class="ph-light ph-trash"></i>

                                                <span>
                                                    Trash
                                                </span>

                                            </button>

                                        </form>



                                        {{-- ADD TO FAQ --}}
                                        <a
                                            href="{{ route(
                                                'admin.support.toFaq',
                                                $req->id
                                            ) }}"
                                            class="btn btn-secondary faq-btn"

                                            data-id="{{ $req->id }}"

                                            data-similar-url="{{ route(
                                                'admin.support.similarFaqs',
                                                $req->id
                                            ) }}"
                                        >

                                            <i class="ph-light ph-chat-centered-dots"></i>

                                            <span>
                                                To FAQ
                                            </span>

                                        </a>

                                    @endif



                                {{-- =================================================
                                     TRASHED REQUEST ACTIONS
                                     ================================================== --}}

                                @elseif($status === 'trashed')


                                    @if(auth()->user()->role === 'superadmin')


                                        {{-- RESTORE --}}
                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'admin.support.restore',
                                                $req->id
                                            ) }}"
                                        >

                                            @csrf

                                            @method('PATCH')


                                            <button
                                                type="submit"
                                                class="btn btn-primary restore-btn"
                                            >

                                                <i class="ph-light ph-arrow-counter-clockwise"></i>

                                                <span>
                                                    Restore
                                                </span>

                                            </button>

                                        </form>



                                        {{-- PERMANENT DELETE --}}
                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'admin.support.forceDelete',
                                                $req->id
                                            ) }}"
                                        >

                                            @csrf

                                            @method('DELETE')


                                            <button
                                                type="submit"
                                                class="btn btn-danger permanent-delete-btn"
                                            >

                                                <i class="ph-light ph-trash-simple"></i>

                                                Delete Permanently

                                            </button>

                                        </form>

                                    @endif


                                @endif

                            </div>

                        </td>

                    </tr>


                @empty

                    <tr>

                        <td
                            colspan="7"
                            class="empty"
                        >

                            @if($status === 'trashed')

                                No support requests in the trash.

                            @else

                                No support requests found.

                            @endif

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>



    {{-- =========================================================
         FOOTER / PAGINATION
         ========================================================= --}}

    <div class="footer">

        <span class="result-info">

            Showing
            {{ $requests->firstItem() ?? 0 }}

            to
            {{ $requests->lastItem() ?? 0 }}

            of
            {{ $requests->total() }}

            results

        </span>


        <div class="pagination-modern">


            {{-- PREVIOUS --}}
            @if($requests->onFirstPage())

                <span class="arrow disabled">

                    <i class="ph-light ph-caret-left"></i>

                </span>

            @else

                <a
                    href="{{ $requests->previousPageUrl() }}"
                    class="arrow"
                >

                    <i class="ph-light ph-caret-left"></i>

                </a>

            @endif



            {{-- CURRENT PAGE --}}
            <span class="page-indicator">

                Page {{ $requests->currentPage() }}

            </span>



            {{-- NEXT --}}
            @if($requests->hasMorePages())

                <a
                    href="{{ $requests->nextPageUrl() }}"
                    class="arrow"
                >

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





{{-- =============================================================
     SUPPORT REQUEST MANAGEMENT MODAL
     ============================================================= --}}

<div
    id="support-modal-back"
    class="back"
>

    <div class="modal">


        {{-- HEADER --}}
        <div class="modal-header">

            <h2>
                Support Request
            </h2>


            <div class="modal-actions">

                <button
                    type="submit"
                    form="reply-form"
                    class="btn-save"
                >

                    Mark as Answered

                </button>


                <button
                    type="button"
                    onclick="closeSupportModal()"
                    class="btn-cancel"
                >

                    Cancel

                </button>

            </div>

        </div>



        {{-- FORM --}}
        <form
            method="POST"
            action="{{ route('admin.support.reply') }}"
            id="reply-form"

            data-reply-url="{{ route('admin.support.reply') }}"

            data-update-url="/admin/support-requests"

            {{-- NEW: Required for file uploads --}}
            enctype="multipart/form-data"
        >

            @csrf

            <input
                type="hidden"
                name="_method"
                id="form-method"
                value="POST"
            >


            <div class="form-card">


                {{-- SUPPORT REQUEST ID --}}
                <input
                    type="hidden"
                    name="request_id"
                    id="sr-id"
                >


                {{-- USER --}}
                <div class="floating-group">

                    <input
                        type="text"
                        id="sr-user"
                        placeholder=" "
                        readonly
                    >

                    <label>
                        User
                    </label>

                </div>



                {{-- =========================================================
     AGENCY SEARCHABLE SELECT
     ========================================================= --}}

<div
    class="floating-group searchable-select"
    id="support-agency-searchable"
>

    {{-- Visible search field --}}
    <input
        type="text"
        id="sr-agency-search"
        class="searchable-select-input"
        placeholder=" "
        autocomplete="off"
        role="combobox"
        aria-expanded="false"
        aria-controls="sr-agency-options"
        aria-autocomplete="list"
        required
    >

    <label
        for="sr-agency-search"
        class="searchable-select-label"
    >
        Agency
    </label>

    <i
        class="ph-light ph-caret-down searchable-select-arrow"
        aria-hidden="true"
    ></i>


    {{-- Search results --}}
    <div
        id="sr-agency-options"
        class="searchable-select-options"
        role="listbox"
    ></div>


    {{-- Actual submitted field --}}
    <select
        name="agency_id"
        id="sr-agency"
        class="searchable-select-native"
    >

        <option
            value=""
            selected
            disabled
            hidden
        >
        
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



                {{-- QUESTION --}}
                <div class="floating-group">

                    <textarea
                        id="sr-question"
                        placeholder=" "
                        readonly
                    ></textarea>

                    <label>
                        Question
                    </label>

                </div>



                {{-- REPLY --}}
                <div class="floating-group">

                    <textarea
                        name="reply"
                        id="sr-reply"
                        placeholder=" "
                        required
                    ></textarea>

                    <label>
                        Reply
                    </label>

                </div>



                {{-- =========================================================
     ANSWER IMAGE UPLOAD
     ========================================================= --}}

<div class="floating-group">

    <div
        class="image-upload-box"
        id="support-image-upload-box"
    >

        {{-- File input --}}
        <input
            type="file"
            name="answer_image"
            id="support_answer_image"
            accept="image/jpeg, image/png, image/webp"
            hidden
        >

        {{-- Image removal flag --}}
        <input
            type="hidden"
            name="remove_answer_image"
            id="remove_answer_image"
            value="0"
        >

        {{-- Upload interface --}}
        <div
            class="upload-content"
            id="support-upload-placeholder"
        >

            <i class="ph ph-image"></i>

            <p>
                Click to upload image
            </p>

            <span>
                PNG, JPG, WebP up to 5MB
            </span>

        </div>

        {{-- Existing/new image preview --}}
        <img
            id="support-preview-img"
            class="faq-preview-img"
            alt="Answer image preview"
            style="display: none;"
        >

        {{-- Remove image button --}}
        <button
            type="button"
            class="remove-support-image-btn"
            id="remove-support-image-btn"
            aria-label="Remove answer image"
            title="Remove image"
            style="display: none;"
        >

            <i class="ph-light ph-x"></i>

        </button>

    </div>

    <label>
        Upload Image (optional)
    </label>

</div>


            </div>

        </form>

    </div>

</div>





{{-- =============================================================
     SIMILAR FAQ MODAL
     ============================================================= --}}

<div
    id="similar-faq-modal-back"
    class="similar-faq-modal-back"
    aria-hidden="true"
>

    <div
        class="similar-faq-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="similar-faq-title"
    >


        {{-- HEADER --}}
        <div class="similar-faq-header">

            <div class="similar-faq-heading">

                <div
                    class="similar-faq-icon"
                    aria-hidden="true"
                >

                    <i class="ph-light ph-copy-simple"></i>

                </div>


                <div>

                    <h2 id="similar-faq-title">
                        Similar FAQs found
                    </h2>

                    <p>
                        Existing FAQs may already cover this question.
                    </p>

                </div>

            </div>



            {{-- HEADER ACTIONS --}}
            <div class="similar-faq-actions">

                <button
                    type="button"
                    class="btn-cancel"
                    id="similar-faq-cancel"
                >

                    Cancel

                </button>


                <button
                    type="button"
                    class="btn-save"
                    id="similar-faq-continue"
                >

                    Continue to FAQ

                </button>

            </div>

        </div>



        {{-- DESCRIPTION --}}
        <div class="similar-faq-description">

            <p id="similar-faq-message">

                We found existing FAQs that may be related
                to this Support Request.

            </p>

        </div>



        {{-- MATCHES --}}
        <div
            id="similar-faq-results"
            class="similar-faq-results"
        >

            {{-- JavaScript inserts matching FAQs here. --}}

        </div>



        {{-- FOOTER --}}
        <div class="similar-faq-footer">

            <p class="similar-faq-note">

                Similarity is only a suggestion.
                You can still create a new FAQ if this is
                a different question.

            </p>

        </div>


    </div>

</div>


@endsection



@push('scripts')

<script src="{{ asset('jsfiles/components/modal-system.js') }}"></script>

<script src="{{ asset('jsfiles/admin/support_requests.js') }}"></script>


@if(session('success'))

<script>

    window.__FLASH_SUCCESS__ =
        @json(session('success'));

</script>

@endif

@endpush