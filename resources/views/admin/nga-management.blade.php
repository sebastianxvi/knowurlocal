@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/theme.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/nga-management.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/form-system.css') }}">
@endpush

@section('title', 'KNOWURLOCAL | ' . ucfirst(auth()->user()->role) . ' Module')

@section('page-title', 'NGA & NGO Management')
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

    {{-- =========================================================
     AGENCY STATUS TABS
     =========================================================
     
     Only Superadmins can access the Trashed view.
     
     The actual authorization is still enforced by the
     backend. This condition only controls the interface.
     ========================================================= --}}


     <!-- =========================================================
     STICKY NGA & NGO CONTROLS
     ========================================================= -->

<div class="agency-controls">

    {{-- =====================================================
         STATUS TABS
         ===================================================== --}}

    @if(auth()->user()->role === 'superadmin')

        <div class="agency-status-tabs">

            {{-- ACTIVE --}}
            <a
                href="{{ route('admin.nga', array_merge(
                    request()->except('page', 'status'),
                    ['status' => 'active']
                )) }}"
                class="agency-status-tab {{ $status === 'active' ? 'active' : '' }}"
            >
                <i class="ph-light ph-buildings"></i>

                <span>Active</span>

                <span class="status-count">
                    {{ $activeCount }}
                </span>
            </a>


            {{-- TRASHED --}}
            <a
                href="{{ route('admin.nga', array_merge(
                    request()->except('page', 'status'),
                    ['status' => 'trashed']
                )) }}"
                class="agency-status-tab {{ $status === 'trashed' ? 'active' : '' }}"
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

    <form method="GET" action="{{ route('admin.nga') }}">

        <input
            type="hidden"
            name="status"
            value="{{ $status }}"
        >

        <div class="filter-card">

            <div class="filter-bar">

                <!-- SEARCH -->
                <input
                    type="text"
                    name="search"
                    placeholder="Search agency"
                    value="{{ request('search') }}"
                >

                <!-- TYPE -->
                <select name="type" id="filterType">

                    <option value="">
                        All Types
                    </option>

                    @foreach($types as $type)

                        <option
                            value="{{ $type->id }}"
                            {{ request('type') == $type->id ? 'selected' : '' }}
                        >
                            {{ $type->name }}
                        </option>

                    @endforeach

                </select>


                <!-- CATEGORY -->
                <select name="category" id="filterCategory">

                    <option value="">
                        All Categories
                    </option>

                    @foreach($categories as $category)

                        <option
                            value="{{ $category->id }}"
                            {{ request('category') == $category->id ? 'selected' : '' }}
                        >
                            {{ $category->category_name }}
                        </option>

                    @endforeach

                </select>


                <!-- SORT -->
                <select name="sort">

                    <option
                        value="latest"
                        {{ request('sort') == 'latest' ? 'selected' : '' }}
                    >
                        Newest First
                    </option>

                    <option
                        value="oldest"
                        {{ request('sort') == 'oldest' ? 'selected' : '' }}
                    >
                        Oldest First
                    </option>

                </select>


                <!-- FILTER -->
                <button type="submit">
                    Filter
                </button>

            </div>


            <!-- ADD AGENCY -->
            <div>

                @if($status === 'active')

                    <button
                        type="button"
                        class="add-agencybtn"
                        onclick="openModal()"
                    >
                        + Add Agency
                    </button>

                @endif

            </div>

        </div>

    </form>

</div>

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

                @php
                    $agencyContacts = $agency->contacts->map(function ($contact) {
                        return [
                            'id' => $contact->id,
                            'contact_type_id' => $contact->contact_type_id,
                            'type_name' => $contact->contactType?->name,
                            'type_slug' => $contact->contactType?->slug,
                            'label' => $contact->label,
                            'value' => $contact->value,
                            'is_primary' => (bool) $contact->is_primary,
                            'sort_order' => $contact->sort_order,
                        ];
                    })->values();
                @endphp
                <tr
    class="agency-row"

    data-id="{{ $agency->id }}"
    data-name="{{ $agency->agency_name }}"
    data-abbreviation="{{ $agency->agency_abbreviation }}"
    data-type_id="{{ $agency->agency_type_id }}"
    data-category_id="{{ $agency->category_id }}"
    data-description="{{ $agency->agency_description }}"
    data-services_offered="{{ $agency->services_offered }}"
    data-location="{{ $agency->agency_location }}"
    data-office="{{ $agency->office_hours }}"
    data-lat="{{ $agency->lat }}"
    data-lng="{{ $agency->lng }}"
    data-image="{{ $agency->agency_image }}"

    data-contacts="{{ $agencyContacts->toJson() }}"
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

                            <div class="agency-meta">

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

                        </div>
                    </td>

                    <td>{{ $agency->agency_location }}</td>
                    <td>
                        @php
                            $primaryEmail = $agency->contacts
                                ->first(function ($contact) {
                                    return $contact->contactType?->slug === 'email'
                                        && $contact->is_primary;
                                });
                        @endphp

                        {{ $primaryEmail?->value ?? '—' }}
                    </td>

                    <td>
                        @php
                            $primaryHotline = $agency->contacts
                                ->first(function ($contact) {
                                    return $contact->contactType?->slug === 'hotline'
                                        && $contact->is_primary;
                                });
                        @endphp

                        {{ $primaryHotline?->value ?? '—' }}
                    </td>

                    <!-- =====================================================
     ACTIONS
     ===================================================== -->

<td>

    <div class="tablebtn">

        {{-- =================================================
             ACTIVE AGENCY
             =================================================
             Normal Admin + Superadmin
             ================================================= --}}

        @if($status === 'active')

            <!-- EDIT -->
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
                data-office="{{ $agency->office_hours }}"
                data-lat="{{ $agency->lat }}"
                data-lng="{{ $agency->lng }}"
                data-image="{{ $agency->agency_image }}"
                data-contacts="{{ $agencyContacts->toJson() }}"
            >
            <i class="ph-light ph-pencil-simple"></i>
                Edit
            </button>


            <!-- SOFT DELETE -->
            <form
                action="{{ route(
                    'admin.agencies.destroy',
                    $agency->id
                ) }}"
                method="POST"
                class="delete-form"
            >
                @csrf
                @method('DELETE')

                <button
                    type="button"
                    class="btn btn-danger delete-btn"
                    data-agency-name="{{ $agency->agency_name }}"
                >
                    <i class="ph-light ph-trash"></i>
                    Trash
                </button>

            </form>


        {{-- =================================================
             TRASHED AGENCY
             =================================================
             Superadmin only.
             The backend middleware remains the real security
             boundary.
             ================================================= --}}

        @elseif(
            $status === 'trashed'
            && auth()->user()->role === 'superadmin'
        )

            <!-- RESTORE -->
            <form
                action="{{ route(
                    'admin.agencies.restore',
                    $agency->id
                ) }}"
                method="POST"
                class="restore-form"
            >
                @csrf
                @method('PATCH')

                <button
                    type="button"
                    class="btn btn-restore restore-btn"
                    data-agency-name="{{ $agency->agency_name }}"
                >
                    <i class="ph-light ph-arrow-counter-clockwise"></i>
                    Restore
                </button>

            </form>


            <!-- PERMANENT DELETE -->
            <form
                action="{{ route(
                    'admin.agencies.force-delete',
                    $agency->id
                ) }}"
                method="POST"
                class="force-delete-form"
            >
                @csrf
                @method('DELETE')

                <button
                    type="button"
                    class="btn btn-danger force-delete-btn"
                    data-agency-name="{{ $agency->agency_name }}"
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

                <!-- EMPTY STATE -->
                <tr>
                    <td colspan="6" class="empty">
                        No agencies found.
                    </td>
                </tr>

                @endforelse

            </tbody>
                </table>

    </div>


    <!-- =====================================================
         TABLE FOOTER
         =====================================================

         IMPORTANT:

         The footer is intentionally OUTSIDE .table-wrapper.

         This means the table can have its own scrolling
         behavior without dragging the pagination along with it.
         ===================================================== -->

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

                <a
                    href="{{ $agencies->previousPageUrl() }}"
                    class="arrow"
                    aria-label="Previous page"
                >

                    <i class="ph-light ph-caret-left"></i>

                </a>

            @endif


            {{-- CURRENT PAGE --}}

            <span class="page-indicator">

                Page {{ $agencies->currentPage() }}

            </span>


            {{-- NEXT --}}

            @if ($agencies->hasMorePages())

                <a
                    href="{{ $agencies->nextPageUrl() }}"
                    class="arrow"
                    aria-label="Next page"
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
                    <textarea
                        name="agency_name"
                        id="agency_name"
                        placeholder=" "
                        rows="2"
                        required
                        maxlength="255"
                    ></textarea>

                    <label for="agency_name">
                        Agency Name
                    </label>

                    <span class="form-message"></span>
                </div>

                <!-- ABBREVIATION -->
                <div class="floating-group" data-validate="required">

                    <input
                        type="text"
                        name="agency_abbreviation"
                        id="agency_abbreviation"
                        placeholder=" "
                        required
                    >

                    <label for="agency_abbreviation">
                        Abbreviation
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

    <p class="form-helper">
        Add the agency's available contact information.
        At least one Hotline and one Email are required.
Additional contact information may be added.
    </p>

    <!--
        JavaScript will dynamically generate the individual
        contact fields inside this container.

        Each generated contact will submit data using:

        contacts[index][contact_type_id]
        contacts[index][label]
        contacts[index][value]
        contacts[index][is_primary]
        contacts[index][sort_order]
    -->
    <div
        id="agency-contacts"
        class="agency-contacts"
    ></div>


    <!--
        JavaScript uses this button to append another
        contact entry.
    -->
    <button
        type="button"
        id="add-contact-btn"
        class="add-contact-btn"
    >
        <i class="ph-light ph-plus"></i>
        Add Contact
    </button>


    <!--
        Backend validation errors for the entire contact
        collection will be displayed here.
    -->
    <span
        id="contacts-form-message"
        class="form-message"
    ></span>

</div>

            <!-- ================= OFFICE HOURS ================= -->
            <div class="form-card">

                <label>Office Hours</label>

                <div
                    class="floating-group"
                    data-validate="required"
                >

                    <textarea
                        name="office_hours"
                        id="office_hours"
                        placeholder=" "
                        required
                    ></textarea>

                    <label for="office_hours">
                        Office Hours
                    </label>

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

<script>
    /*
     * Contact types are loaded from the database by the controller.
     *
     * toJson() converts the Laravel collection into JSON that
     * JavaScript can safely consume.
     */
    window.agencyContactTypes = {!! $contactTypes->values()->toJson() !!};
</script>


<script src="{{ asset('jsfiles/admin/nga-management.js') }}"></script>

@if(session('success'))
<script>
    window.__FLASH_SUCCESS__ = @json(session('success'));
</script>


@endif

@endpush