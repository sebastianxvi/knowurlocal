@extends('layouts.admin')

@push('styles')
    <link rel="stylesheet" href="{{ asset('cssfiles/components/table.css') }}">
    <link rel="stylesheet" href="{{ asset('cssfiles/components/form-system.css') }}">
    <link rel="stylesheet" href="{{ asset('cssfiles/components/image-upload.css') }}">
    
    <link
    rel="stylesheet"
    href="{{ asset('cssfiles/admin/support-requests/index.css') }}"
>

<link
    rel="stylesheet"
    href="{{ asset('cssfiles/admin/support-requests/components/request-table.css') }}"
>

<link
    rel="stylesheet"
    href="{{ asset('cssfiles/admin/support-requests/components/manage-modal.css') }}"
>

<link
    rel="stylesheet"
    href="{{ asset('cssfiles/admin/support-requests/components/response-builder.css') }}"
>

<link
    rel="stylesheet"
    href="{{ asset('cssfiles/admin/support-requests/components/similar-faq.css') }}"
>
@endpush

@section('title', 'KNOWURLOCAL | ' . ucfirst(auth()->user()->role) . ' Module')

@section('page-title', 'Support Requests')
@section('page-subtitle', 'Review and respond to questions submitted by citizens')

@section('content')

<div class="support-page">

    {{-- =========================================================
         DATASET NAVIGATION
         ========================================================= --}}

    @if(auth()->user()->role === 'superadmin')

        <nav
            class="support-dataset-tabs"
            aria-label="Support request collections"
        >

            {{-- ACTIVE --}}
            <a
                href="{{ route(
                    'admin.support.requests',
                    array_merge(
                        request()->except('page'),
                        ['status' => 'active']
                    )
                ) }}"
                class="support-dataset-tab {{ $status === 'active' ? 'is-active' : '' }}"
                aria-current="{{ $status === 'active' ? 'page' : 'false' }}"
            >
                <i
                    class="ph-light ph-inbox"
                    aria-hidden="true"
                ></i>

                <span>Active</span>

                <span class="support-dataset-count">
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
                            'status_filter' => null,
                        ]
                    )
                ) }}"
                class="support-dataset-tab {{ $status === 'trashed' ? 'is-active' : '' }}"
                aria-current="{{ $status === 'trashed' ? 'page' : 'false' }}"
            >
                <i
                    class="ph-light ph-trash"
                    aria-hidden="true"
                ></i>

                <span>Trashed</span>

                <span class="support-dataset-count">
                    {{ $trashedCount }}
                </span>
            </a>

        </nav>

    @endif


    {{-- =========================================================
         FILTER TOOLBAR
         ========================================================= --}}

    @if($status === 'active')

        <section
            class="support-filter-toolbar"
            aria-label="Support request filters"
        >

            <form
                method="GET"
                action="{{ route('admin.support.requests') }}"
                class="support-filter-form"
            >

                {{-- Preserve current dataset --}}
                <input
                    type="hidden"
                    name="status"
                    value="active"
                >


                {{-- SEARCH --}}
                <div class="support-filter-field support-search-field">

                    <label
                        for="support-search"
                        class="sr-only"
                    >
                        Search support requests
                    </label>

                    <i
                        class="ph-light ph-magnifying-glass"
                        aria-hidden="true"
                    ></i>

                    <input
                        type="search"
                        name="search"
                        value="{{ $search ?? '' }}"
                        placeholder="Search question..."
                        autocomplete="off"
                    >

                </div>


                {{-- REQUEST STATUS --}}
                <div class="support-filter-field">

                    <label
                        for="support-status-filter"
                        class="sr-only"
                    >
                        Filter by request status
                    </label>

                    <i
                        class="ph-light ph-funnel"
                        aria-hidden="true"
                    ></i>

                    <select
                        name="status_filter"
                        id="support-status-filter"
                    >
                        <option
                            value=""
                            {{ $statusFilter === '' ? 'selected' : '' }}
                        >
                            All Status
                        </option>

                        <option
                            value="pending"
                            {{ $statusFilter === 'pending' ? 'selected' : '' }}
                        >
                            Pending
                        </option>

                        <option
                            value="awaiting_confirmation"
                            {{ $statusFilter === 'awaiting_confirmation' ? 'selected' : '' }}
                        >
                            Awaiting Confirmation
                        </option>

                        <option
                            value="needs_follow_up"
                            {{ $statusFilter === 'needs_follow_up' ? 'selected' : '' }}
                        >
                            Needs Follow-up
                        </option>

                        <option
                            value="answered"
                            {{ $statusFilter === 'answered' ? 'selected' : '' }}
                        >
                            Answered
                        </option>
                    </select>

                </div>


                {{-- AGENCY --}}
                <div class="support-filter-field">

                    <label
                        for="support-agency-filter"
                        class="sr-only"
                    >
                        Filter by agency
                    </label>

                    <i
                        class="ph-light ph-buildings"
                        aria-hidden="true"
                    ></i>

                    <select name="agency" id="support-agency-filter">
    <option value="">
        All Agencies
    </option>

    @foreach ($agencies as $agency)
        <option
            value="{{ $agency->id }}"
            {{ (string) $agencyFilter === (string) $agency->id ? 'selected' : '' }}
        >
            {{ $agency->agency_name }}
        </option>
    @endforeach
</select>

                </div>

                {{-- SORT ORDER --}}
<div class="support-filter-field">
    <label for="support-sort" class="sr-only">
        Sort support requests
    </label>

    <i
        class="ph-light ph-arrows-down-up"
        aria-hidden="true"
    ></i>

    <select
        name="sort"
        id="support-sort"
    >
        <option
            value="newest"
            {{ $sort === 'newest' ? 'selected' : '' }}
        >
            Newest first
        </option>

        <option
            value="oldest"
            {{ $sort === 'oldest' ? 'selected' : '' }}
        >
            Oldest first
        </option>
    </select>
</div>


                {{-- FILTER --}}
                <button
                    type="submit"
                    class="support-filter-submit"
                >
                    <i
                        class="ph-light ph-sliders-horizontal"
                        aria-hidden="true"
                    ></i>

                    <span>Filter</span>
                </button>


                {{-- CLEAR --}}
                @if(
                    request()->has('search') ||
                    request()->has('status_filter') ||
                    request()->has('agency') ||
                    $sort !== 'newest'
                )

                    <a
                        href="{{ route('admin.support.requests', [
                            'status' => 'active'
                        ]) }}"
                        class="support-filter-clear"
                    >
                        <i
                            class="ph-light ph-x"
                            aria-hidden="true"
                        ></i>

                        <span>Clear</span>
                    </a>

                @endif

            </form>

        </section>

    @endif


    {{-- =========================================================
         REQUEST TABLE
         ========================================================= --}}

    <section class="support-request-section">

        <div class="support-section-heading">

            <div>

                <span class="support-section-eyebrow">
                    {{ $status === 'active' ? 'Current requests' : 'Archived requests' }}
                </span>

                <h2>
                    {{ $status === 'active' ? 'Support queue' : 'Trash' }}
                </h2>

            </div>


            @if($status === 'active')

                <div class="support-section-meta">

                    <i
                        class="ph-light ph-list-dashes"
                        aria-hidden="true"
                    ></i>

                    <span>
                        {{ $requests->total() }}
                        {{ Str::plural('request', $requests->total()) }}
                    </span>

                </div>

            @endif

        </div>

        @include('admin.support-requests.components.request-table', [
            'requests' => $requests,
            'status' => $status,
        ])


        {{-- =====================================================
             PAGINATION
             ===================================================== --}}

        @if($requests->hasPages())

            <footer class="support-pagination">

                <div class="support-pagination-summary">

                    Showing

                    <strong>
                        {{ $requests->firstItem() }}
                    </strong>

                    –

                    <strong>
                        {{ $requests->lastItem() }}
                    </strong>

                    of

                    <strong>
                        {{ $requests->total() }}
                    </strong>

                </div>


                <div class="support-pagination-controls">

                    {{-- PREVIOUS --}}
                    @if($requests->onFirstPage())

                        <span
                            class="support-pagination-button is-disabled"
                            aria-disabled="true"
                        >
                            <i
                                class="ph-light ph-caret-left"
                                aria-hidden="true"
                            ></i>
                        </span>

                    @else

                        <a
                            href="{{ $requests->previousPageUrl() }}"
                            class="support-pagination-button"
                            aria-label="Previous page"
                        >
                            <i
                                class="ph-light ph-caret-left"
                                aria-hidden="true"
                            ></i>
                        </a>

                    @endif


                    {{-- CURRENT PAGE --}}
                    <span class="support-pagination-current">
                        Page {{ $requests->currentPage() }}
                        of {{ $requests->lastPage() }}
                    </span>


                    {{-- NEXT --}}
                    @if($requests->hasMorePages())

                        <a
                            href="{{ $requests->nextPageUrl() }}"
                            class="support-pagination-button"
                            aria-label="Next page"
                        >
                            <i
                                class="ph-light ph-caret-right"
                                aria-hidden="true"
                            ></i>
                        </a>

                    @else

                        <span
                            class="support-pagination-button is-disabled"
                            aria-disabled="true"
                        >
                            <i
                                class="ph-light ph-caret-right"
                                aria-hidden="true"
                            ></i>
                        </span>

                    @endif

                </div>

            </footer>

        @endif

    </section>

</div>


@include('admin.support-requests.components.manage-modal', [
    'agencies' => $agencies,
])

@include('admin.support-requests.components.similar-faq-modal')


@endsection


@push('scripts')

    <script src="{{ asset('jsfiles/components/modal-system.js') }}"></script>

    <script>
        window.__FLASH_SUCCESS__ = @json(session('success'));
    </script>

    <script
        type="module"
        src="{{ asset('jsfiles/admin/support-requests/index.js') }}"
    ></script>

@endpush