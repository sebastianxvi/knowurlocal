@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/theme.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/form-layout.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/category-management.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/form-system.css') }}">
@endpush

@section('title', 'KNOWURLOCAL | Category Management')

@section('page-title', 'Category Management')
@section('page-subtitle', 'Manage agency categories')

@section('content')

<div class="logs-page">
    {{-- =========================================================
     CATEGORY STATUS CONTROLS
     =========================================================

     Only Superadmins can switch between Active and Trashed.

     The backend must still enforce authorization.
     This only controls what is displayed in the interface.
     ========================================================= --}}

{{-- =========================================================
     STICKY CATEGORY CONTROLS
     =========================================================

     Keeps the status tabs and filter bar visible while the
     administrator scrolls through the category table.
     ========================================================= --}}

<div class="admin-data-controls">

    @if(auth()->user()->role === 'superadmin')
        @include('admin.components.status-tabs', [
            'ariaLabel' => 'Category collections',
            'tabs' => [
                [
                    'href' => route('admin.categories', array_merge(request()->except('page', 'status'), ['status' => 'active'])),
                    'label' => 'Active',
                    'icon' => 'ph-folders',
                    'count' => $activeCount ?? 0,
                    'active' => ($status ?? 'active') === 'active',
                ],
                [
                    'href' => route('admin.categories', array_merge(request()->except('page', 'status'), ['status' => 'trashed'])),
                    'label' => 'Trashed',
                    'icon' => 'ph-trash',
                    'count' => $trashedCount ?? 0,
                    'active' => ($status ?? 'active') === 'trashed',
                ],
            ],
        ])
    @endif

    <section class="support-filter-toolbar admin-filter-toolbar" aria-label="Category filters">
        <form method="GET" action="{{ route('admin.categories') }}" class="support-filter-form">
            <input type="hidden" name="status" value="{{ $status ?? 'active' }}">

            <div class="support-filter-field support-search-field">
                <label for="category-search" class="sr-only">Search categories</label>
                <i class="ph-light ph-magnifying-glass" aria-hidden="true"></i>
                <input type="search" id="category-search" name="search" placeholder="Search category..." value="{{ request('search') }}" autocomplete="off">
            </div>

            <div class="support-filter-field">
                <label for="category-sort" class="sr-only">Sort categories</label>
                <i class="ph-light ph-arrows-down-up" aria-hidden="true"></i>
                <select name="sort" id="category-sort">
                    <option value="latest" {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>Newest First</option>
                    <option value="oldest" {{ request('sort') === 'oldest' ? 'selected' : '' }}>Oldest First</option>
                </select>
            </div>

            <button type="submit" class="support-filter-submit admin-icon-button" aria-label="Apply filters" title="Apply filters">
                <i class="ph-light ph-sliders-horizontal" aria-hidden="true"></i>
                <span class="sr-only">Filter</span>
            </button>
        
            @if(($status ?? 'active') === 'active')
                <button type="button" class="add-agencybtn admin-add-action" onclick="openModal()" aria-label="Add category" title="Add category">
                    <i class="ph-light ph-plus" aria-hidden="true"></i>
                    <span class="sr-only">Add category</span>
                </button>
            @endif

        </form>

    </section>

</div>

@include('admin.components.list-result-meta', [
    'count' => $categories->total(),
    'label' => 'category',
])

<div class="table-wrapper">

<table class="table">

<thead>

<tr>

<th>ID</th>

<th>Color</th>

<th>Category</th>

<th>Agencies</th>

<th>Action</th>

</tr>

</thead>

<tbody>

@forelse($categories as $category)

<tr>

<td>{{ $category->id }}</td>

<td>

<div
    class="category-color"
    style="background: {{ $category->display_color }}">
</div>

</td>

<td>

{{ $category->category_name }}

</td>

<td>

{{ $category->agencies_count }}

</td>

<td>

    <div class="tablebtn">

        {{-- =================================================
             ACTIVE CATEGORY
             ================================================= --}}

        @if(($status ?? 'active') === 'active')

            {{-- EDIT --}}
            <button
                type="button"
                class="btn btn-primary edit-category admin-table-icon-action" aria-label="Edit" title="Edit"

                data-id="{{ $category->id }}"
                data-name="{{ $category->category_name }}"
                data-color="{{ $category->display_color }}"

                data-update="{{ route(
                    'admin.categories.update',
                    $category
                ) }}"
            >
                <i class="ph-light ph-pencil-simple"></i>
                <span class="sr-only">Edit</span>
            </button>


            {{-- MOVE TO TRASH --}}
            <form
                action="{{ route(
                    'admin.categories.destroy',
                    $category
                ) }}"
                method="POST"
                class="delete-category-form"
            >

                @csrf
                @method('DELETE')

                <button
                    type="button"
                    class="btn btn-danger delete-category admin-table-icon-action" aria-label="Trash" title="Trash"
                    data-category-name="{{ $category->category_name }}"
                >
                    <i class="ph-light ph-trash"></i>
                    <span class="sr-only">Trash</span>
                </button>

            </form>


        {{-- =================================================
             TRASHED CATEGORY
             =================================================
             Only Superadmins should reach this branch.
             The controller must enforce this server-side too.
             ================================================= --}}

        @elseif(
            ($status ?? 'active') === 'trashed'
            && auth()->user()->role === 'superadmin'
        )

            {{-- RESTORE --}}
            <form
                action="{{ route(
                    'admin.categories.restore',
                    $category
                ) }}"
                method="POST"
                class="restore-category-form"
            >

                @csrf
                @method('PATCH')

                <button
                    type="button"
                    class="btn btn-restore restore-category admin-table-icon-action" aria-label="Restore" title="Restore"
                    data-category-name="{{ $category->category_name }}"
                >
                    <i class="ph-light ph-arrow-counter-clockwise"></i>
                    <span class="sr-only">Restore</span>
                </button>

            </form>


            {{-- PERMANENT DELETE --}}
            <form
                action="{{ route(
                    'admin.categories.force-delete',
                    $category
                ) }}"
                method="POST"
                class="force-delete-category-form"
            >

                @csrf
                @method('DELETE')

                <button
                    type="button"
                    class="btn btn-danger force-delete-category admin-table-icon-action" aria-label="Delete Permanently" title="Delete Permanently"
                    data-category-name="{{ $category->category_name }}"
                >
                    <i class="ph-light ph-trash"></i>
                    <span class="sr-only">Delete Permanently</span>
                </button>

            </form>

        @endif

    </div>

</td>

</tr>

@empty

<tr>

    <td colspan="5" class="empty">

        @if(($status ?? 'active') === 'trashed')

            No deleted categories found.

        @else

            No categories found.

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
                Showing {{ $categories->firstItem() ?? 0 }}
                to {{ $categories->lastItem() ?? 0 }}
                of {{ $categories->total() }} results
            </span>

            <!-- PAGINATION -->
            <div class="pagination-modern">

                {{-- PREVIOUS --}}
                @if ($categories->onFirstPage())
                    <span class="arrow disabled">
                        <i class="ph-light ph-caret-left"></i>
                    </span>
                @else
                    <a href="{{ $categories->previousPageUrl() }}" class="arrow">
                        <i class="ph-light ph-caret-left"></i>
                    </a>
                @endif

                {{-- PAGE NUMBER --}}
                <span class="page-indicator">
                    Page {{ $categories->currentPage() }}
                </span>

                {{-- NEXT --}}
                @if ($categories->hasMorePages())
                    <a href="{{ $categories->nextPageUrl() }}" class="arrow">
                        <i class="ph-light ph-caret-right"></i>
                    </a>
                @else
                    <span class="arrow disabled">
                        <i class="ph-light ph-caret-right"></i>
                    </span>
                @endif

            </div>

        
</div>

<div id="modal-back" class="back">

    <div class="modal">

        <div class="modal-header admin-modal-header">
            <div class="admin-modal-heading">
                <div class="admin-modal-icon" aria-hidden="true">
                    <i class="ph-light ph-tag"></i>
                </div>
                <div>
                    <span class="admin-modal-eyebrow">Category management</span>
                    <h2 id="modal-title">Add Category</h2>
                </div>
            </div>

            <div class="modal-actions">

                <button
                    type="submit"
                    form="categoryForm"
                    class="btn-save">

                    Save

                </button>

                <button
                    type="button"
                    class="btn-cancel"
                    onclick="closeModal()">

                    Cancel

                </button>

            </div>

        </div>

        <form
            id="categoryForm"
            method="POST"
            action="{{ route('admin.categories.store') }}">

            @csrf
            <input
            type="hidden"
            name="_method"
            id="form-method"
            value="POST">

            <div class="form-card">

                <label>
                    Category Information
                </label>

                <div
                    class="floating-group"
                    data-validate="required">

                    <input
                        type="text"
                        id="category_name"
                        name="category_name"
                        placeholder=" "
                        required>

                    <label for="category_name">

                        Category Name

                    </label>

                    <span class="form-message"></span>

                </div>

                <div class="category-color-picker">
                    <input
    type="hidden"
    name="display_color"
    id="display_color"
    value="#3B82F6">

<div class="color-section">

    <label class="color-title">

        Display Color

    </label>


        @php

$colors = [
    // Blue
    '#2563EB',

    // Sky
    '#0284C7',

    // Cyan
    '#0891B2',

    // Teal
    '#0F766E',

    // Green
    '#16A34A',

    // Lime
    '#65A30D',

    // Yellow
    '#CA8A04',

    // Amber
    '#D97706',

    // Orange
    '#EA580C',

    // Red
    '#DC2626',

    // Rose
    '#E11D48',

    // Pink
    '#DB2777',

    // Magenta
    '#C026D3',

    // Purple
    '#9333EA',

    // Violet
    '#7C3AED',

    // Indigo
    '#4F46E5',

    // Slate
    '#475569',

    // Brown
    '#92400E',

    // Neutral
    '#525252',

    // Deep Navy
    '#1E3A5F',

    // Forest
    '#166534',

    // Burgundy
    '#9F1239',

    // Plum
    '#701A75',

    // Steel
    '#334155'
];

@endphp

<div class="color-grid">

@foreach($colors as $index => $color)

<button
    type="button"
    class="color-chip {{ $index === 0 ? 'active' : '' }}"
    data-color="{{ $color }}"
    style="background: {{ $color }}">
</button>

@endforeach

</div>

<div
    id="color-usage"
    class="color-usage"
    aria-live="polite"
>
</div>

    </div>

                </div>
</div>

            </div>

        </form>

    </div>

</div>


<script src="{{ asset('jsfiles/components/form-system.js') }}"></script>

@endsection

@push('scripts')

<script>

window.categoryRoutes = {

    store: "{{ route('admin.categories.store') }}"

};


/*
 * Existing category colors supplied by Laravel.
 *
 * The controller already limits this data to:
 * ID, category name, and display color.
 *
 * JavaScript uses it only for UX feedback.
 */
window.categoryColorUsage = @json($categoryColorUsage);

</script>

@if(session('success'))
<script>
window.__FLASH_SUCCESS__=@json(session('success'));
</script>
@endif

@if(session('error'))
<script>
window.__FLASH_ERROR__ = @json(session('error'));
</script>
@endif




<script src="{{ asset('jsfiles/admin/category-management.js') }}"></script>


@endpush