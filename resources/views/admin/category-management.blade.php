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

    @if ($errors->any())
<div style="background:red;color:white;padding:10px;margin-bottom:15px;border-radius:8px;">
    <strong>Validation Errors:</strong>

    <ul>
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="GET" action="{{ route('admin.categories') }}">

    <div class="filter-card">

        <div class="filter-bar">

            <input
                type="text"
                name="search"
                placeholder="Search category"
                value="{{ request('search') }}"
            >

            <select name="sort">
    <option
        value="latest"
        {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}
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

            <button type="submit">
                Filter
            </button>

        </div>

        <div>

            <button
                type="button"
                class="add-agencybtn"
                onclick="openModal()">

                + Add Category

            </button>

        </div>

    </div>

</form>

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

<button
    type="button"
    class="btn btn-primary edit-category"

    data-id="{{ $category->id }}"
    data-name="{{ $category->category_name }}"
    data-color="{{ $category->display_color }}"
    data-update="{{ route('admin.categories.update', $category) }}">

    Edit

</button>

<form
action="{{ route('admin.categories.destroy',$category) }}"
method="POST">

@csrf
@method('DELETE')

<button
    type="submit"
    class="btn btn-danger delete-category">

    Delete

</button>

</form>

</div>

</td>

</tr>

@empty

<tr>

<td colspan="5">

No categories found.

</td>

</tr>

@endforelse

</tbody>

</table>

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
</div>

<div id="modal-back" class="back">

    <div class="modal">

        <div class="modal-header">

            <h2 id="modal-title">
                Add Category
            </h2>

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

    <div class="color-grid">

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

</script>
@if(session('success'))
<script>
window.__FLASH_SUCCESS__=@json(session('success'));
</script>
@endif



<script src="{{ asset('jsfiles/components/modal-system.js') }}"></script>
<script src="{{ asset('jsfiles/admin/category-management.js') }}"></script>
<script>
console.log("FLASH:", window.__FLASH_SUCCESS__);
</script>


@endpush