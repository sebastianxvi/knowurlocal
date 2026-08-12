@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/theme.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/nga-management.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/form-system.css') }}">
@endpush

@section('title', 'KNOWURLOCAL | ' . ucfirst(auth()->user()->role) . ' Module')

@section('page-title', 'Agency Management')
@section('page-subtitle', 'Manage agencies and organizations')

@section('content')

<div class="logs-page">

    {{-- 🔴 DEBUG VALIDATION ERRORS --}}
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

    <!-- ================= FILTER BAR ================= -->
    <form method="GET" action="{{ route('admin.nga') }}">
        <div class="filter-card">

            <div class="filter-bar">

                <!-- 🔍 SEARCH -->
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search agency"
                    value="{{ request('search') }}"
                >

                <select name="type" id="filterType">
                    <option value="">All Types</option>

                    @foreach($types as $type)
                        <option value="{{ $type->id }}">
                            {{ $type->name }}
                        </option>
                    @endforeach
                </select>

                <select name="category" id="filterCategory">
                    <option value="">All Categories</option>

                    @foreach($categories as $category)
                        <option
                            value="{{ $category->id }}"
                            {{ request('category') == $category->id ? 'selected' : '' }}
                        >
                            {{ $category->category_name }}
                        </option>
                    @endforeach
                </select>

                <!-- 📅 SORT -->
                <select name="sort">
                    <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>
                        Newest First
                    </option>
                    <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>
                        Oldest First
                    </option>
                </select>

                <!-- 🚀 SUBMIT -->
                <button type="submit">Filter</button>

            </div>

            <!-- ➕ ADD BUTTON -->
            <div>
                <button type="button" class="add-agencybtn" onclick="openModal()">
                    + Add Agency
                </button>
            </div>

        </div>
    </form>

    <!-- ================= TABLE ================= -->
    <div class="table-wrapper">

        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Agency</th>
                    <th>Location</th>
                    <th>Email</th>
                    <th>Hotline</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                @forelse($agencies as $agency)
                <tr class="agency-row"

                    data-id="{{ $agency->id }}"
                    data-name="{{ $agency->agency_name }}"
                    data-abbreviation="{{ $agency->agency_abbreviation }}"
                    data-category_id="{{ $agency->category_id }}"
                    data-description="{{ $agency->agency_description }}"
                    data-services_offered="{{ $agency->services_offered }}"
                    data-location="{{ $agency->agency_location }}"
                    data-email="{{ $agency->agency_email }}"
                    data-hotline="{{ $agency->agency_hotline }}"
                    data-landline="{{ $agency->agency_landline }}"
                    data-website="{{ $agency->agency_website }}"
                    data-fb="{{ $agency->agency_fb }}"
                    data-office="{{ $agency->office_hours }}"
                    data-lat="{{ $agency->lat }}"
                    data-lng="{{ $agency->lng }}"
                    data-image="{{ $agency->agency_image }}"
                >

                    <td>{{ $agency->id }}</td>

                    <td>
                        <div class="actor-cell">

                            <span class="actor-name">
                                {{ $agency->agency_name }}

                                @if($agency->agency_abbreviation)
                                    <span class="abbr">
                                        ({{ $agency->agency_abbreviation }})
                                    </span>
                                @endif
                            </span>

                            @php
                                $type = $agency->type?->name;
                                $category = $agency->category;
                            @endphp

                            <span class="type-badge {{ strtolower($type) }}">
                                {{ $type ?? '—' }}
                            </span>

                            @if($category)
                                <span
                                    class="category-badge"
                                    style="--category-color: {{ $category->display_color }}"
                                >
                                    {{ $category->category_name }}
                                </span>
                            @endif

                        </div>
                    </td>

                    <td>{{ $agency->agency_location }}</td>
                    <td>{{ $agency->agency_email }}</td>
                    <td>{{ $agency->agency_hotline }}</td>

                    <!-- ACTIONS -->
                    <td>
                        <div class="tablebtn">

                            <!-- VIEW -->
                            <button 
                                type="button"
                                class="btn btn-primary"

                                data-id="{{ $agency->id }}"
                                data-name="{{ $agency->agency_name }}"
                                data-abbreviation="{{ $agency->agency_abbreviation }}"
                                data-type_id="{{ $agency->agency_type_id }}"
                                data-category_id="{{ $agency->category_id }}"
                                data-description="{{ $agency->agency_description }}"
                                data-services_offered="{{ $agency->services_offered }}"
                                data-location="{{ $agency->agency_location }}"
                                data-email="{{ $agency->agency_email }}"
                                data-hotline="{{ $agency->agency_hotline }}"
                                data-landline="{{ $agency->agency_landline }}"
                                data-website="{{ $agency->agency_website }}"
                                data-fb="{{ $agency->agency_fb }}"
                                data-office="{{ $agency->office_hours }}"
                                data-lat="{{ $agency->lat }}"
                                data-lng="{{ $agency->lng }}"
                                data-image="{{ $agency->agency_image }}"
                            >
                                Edit
                            </button>

                            <!-- DELETE (SECURE) -->
                            <form 
                                action="{{ route('admin.agencies.destroy', $agency->id) }}" 
                                method="POST"
                                class="delete-form"
                            >
                                @csrf
                                @method('DELETE')

                                <button type="submit" class="btn btn-danger delete-btn">
                                    Delete
                                </button>
                            </form>

                        </div>
                    </td>

                </tr>
                @empty

                <!-- EMPTY STATE -->
                <tr>
                    <td colspan="6" class="empty">
                        No agencies found.
                    </td>
                </tr>

                @endforelse

            </tbody>
        </table>


        

        <!-- ================= FOOTER ================= -->
        <div class="footer">

            <!-- RESULTS -->
            <span class="result-info">
                Showing {{ $agencies->firstItem() ?? 0 }} 
                to {{ $agencies->lastItem() ?? 0 }} 
                of {{ $agencies->total() }} results
            </span>

            <!-- PAGINATION -->
            <div class="pagination-modern">

                {{-- PREVIOUS --}}
                @if ($agencies->onFirstPage())
                    <span class="arrow disabled">
                        <i class="ph-light ph-caret-left"></i>
                    </span>
                @else
                    <a href="{{ $agencies->previousPageUrl() }}" class="arrow">
                        <i class="ph-light ph-caret-left"></i>
                    </a>
                @endif

                {{-- PAGE NUMBER --}}
                <span class="page-indicator">
                    Page {{ $agencies->currentPage() }}
                </span>

                {{-- NEXT --}}
                @if ($agencies->hasMorePages())
                    <a href="{{ $agencies->nextPageUrl() }}" class="arrow">
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

        <!-- HEADER -->
        <div class="modal-header">
            <h2 id="modal-title">Agency</h2>

            <div class="modal-actions">
                <button type="submit" form="agencyForm" class="btn-save">Save</button>
                <button type="button" onclick="closeModal()" class="btn-cancel">Cancel</button>
            </div>
        </div>

        <form 
            id="agencyForm" 
            method="POST" 
            action="{{ route('agencies.store') }}" 
            enctype="multipart/form-data" 
            novalidate
        >
            @csrf
            <input type="hidden" name="_method" id="form-method" value="POST">

            <!-- ================= IMAGE ================= -->
            <div class="form-card">

                <label class="upload-label">Agency Image</label>

                <div class="image-upload-box" id="agency-upload-box">

                    <!-- FILE INPUT (HIDDEN FOR SECURITY UX CONTROL) -->
                    <input 
                        type="file" 
                        name="agency_image" 
                        id="agency_image" 
                        accept="image/png, image/jpeg"
                        hidden
                    >

                    <!-- UPLOAD UI -->
                    <div class="upload-content" id="agency-upload-placeholder">
                        <i class="ph-light ph-image"></i>
                        <p>Click to upload image</p>
                        <span>PNG, JPG up to 2MB</span>
                    </div>

                    <!-- PREVIEW -->
                    <img id="agency-preview" class="faq-preview-img" style="display:none;">
                </div>

            </div>

            <!-- ================= AGENCY INFO ================= -->
            <div class="form-card">
                <label>Agency Info</label>

                <!-- REQUIRED -->
                <div class="floating-group" data-validate="required">
                    <input type="text" name="agency_name" id="agency_name" placeholder=" " required>
                    <label for="agency_name">Agency Name</label>
                    <span class="form-message"></span>
                </div>

                <!-- ABBREVIATION -->
                <div class="floating-group" data-validate="optional-text">
                    <input 
                        type="text" 
                        name="agency_abbreviation" 
                        id="agency_abbreviation" 
                        placeholder=" "
                    >
                    <label for="agency_abbreviation">
                        Abbreviation (e.g. DOH, DSWD)
                    </label>
                    <span class="form-message"></span>
                </div>

                <!-- AGENCY TYPE -->
                <div class="floating-group">

                    <select 
                        name="agency_type_id" 
                        id="agency_type_id" 
                        required
                    >
                        <option value="" disabled selected hidden></option>

                        @foreach($types as $type)
                            <option value="{{ $type->id }}">
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>

                    <label for="agency_type_id">
                        Agency Type
                    </label>

                </div>

                <!-- CATEGORY -->
                <div class="floating-group">

                    <select
                        name="category_id"
                        id="category_id"
                        required
                    >
                        <option value="" disabled selected hidden></option>

                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">
                                {{ $category->category_name }}
                            </option>
                        @endforeach
                    </select>

                    <label for="category_id">
                        Category
                    </label>

                </div>

                <!-- OPTIONAL (NO STRICT VALIDATION) -->
                <div class="floating-group" data-validate="optional-text">
                    <textarea name="agency_description" id="agency_description" placeholder=" "></textarea>
                    <label for="agency_description">Description</label>
                    <span class="form-message"></span>
                </div>

                <div class="floating-group" data-validate="optional-text">
                <textarea
                    name="services_offered"
                    id="services_offered"
                    placeholder=" "
                ></textarea>

                <label for="services_offered">
                    Services Offered
                </label>

                <span class="form-message"></span>
            </div>
            </div>

            

            <!-- ================= CONTACT ================= -->
            <div class="form-card">
                <label>Contact Information</label>

                <!-- OPTIONAL LANDLINE -->
                <div class="floating-group" data-validate="landline">
                    <input type="text" name="agency_landline" id="agency_landline" placeholder=" ">
                    <label for="agency_landline">Landline</label>
                    <span class="form-message"></span>
                </div>

                <!-- REQUIRED HOTLINE (based on your migration) -->
                <div class="floating-group" data-validate="phone">
                    <input type="text" name="agency_hotline" id="agency_hotline" placeholder=" " required>
                    <label for="agency_hotline">Hotline</label>
                    <span class="form-message"></span>
                </div>

                <!-- OPTIONAL EMAIL -->
                <div class="floating-group" data-validate="email">
                    <input type="email" name="agency_email" id="agency_email" placeholder=" ">
                    <label for="agency_email">Email</label>
                    <span class="form-message"></span>
                </div>

                <!-- OPTIONAL WEBSITE -->
                <div class="floating-group" data-validate="website">
                    <input type="text" name="agency_website" id="agency_website" placeholder=" ">
                    <label for="agency_website">Website</label>
                    <span class="form-message"></span>
                </div>

                <!-- OPTIONAL FACEBOOK -->
                <div class="floating-group" data-validate="facebook">
                    <input type="text" name="agency_fb" id="agency_fb" placeholder=" ">
                    <label for="agency_fb">Facebook</label>
                    <span class="form-message"></span>
                </div>
            </div>

            <!-- ================= OFFICE HOURS ================= -->
            <div class="form-card">
                <label>Office Hours</label>

                <!-- ⚠️ DEPENDS ON BACKEND -->
                <!-- If required in DB, keep "required" -->
                <!-- If optional, change to optional-text -->

                <div class="floating-group" data-validate="optional-text">
                    <textarea name="office_hours" id="office_hours" placeholder=" "></textarea>
                    <label for="office_hours">Office Hours</label>
                    <span class="form-message"></span>
                </div>
            </div>

            <!-- ================= LOCATION ================= -->
            <div class="form-card">
                <label>Location</label>

                <!-- REQUIRED -->
                <div class="floating-group" data-validate="required">
                    <input type="text" name="agency_location" id="agency_location" placeholder=" " required>
                    <label for="agency_location">Address</label>
                    <span class="form-message"></span>
                </div>

                <!-- 🔍 SEARCH -->
                <div class="map-search">
                    <div class="floating-group" style="flex:1;">
                        <input id="searchLocation" type="text" placeholder=" ">
                        <label for="searchLocation">Search location</label>
                    </div>

                    <button type="button" id="searchBtn">
                        Search
                    </button>
                </div>

                <!-- 🗺 MAP -->
                <div id="map"></div>

                <!-- 📍 COORDS -->
                <div class="coord-row">

                    <!-- SYSTEM GENERATED -->
                    <div class="floating-group">
                        <input type="text" id="lat" name="lat" placeholder=" ">
                        <label for="lat">Latitude</label>
                    </div>

                    <div class="floating-group">
                        <input type="text" id="lng" name="lng" placeholder=" ">
                        <label for="lng">Longitude</label>
                    </div>

                </div>
            </div>
        </form>

    </div>
</div>



</div>

<script src="{{ asset('jsfiles/components/form-system.js') }}"></script>

@endsection

@push('scripts')

<script src="{{ asset('jsfiles/components/modal-system.js') }}"></script>
<script src="{{ asset('jsfiles/admin/nga-management.js') }}"></script>

@if(session('success'))
<script>
    window.__FLASH_SUCCESS__ = @json(session('success'));
</script>
@endif

@endpush