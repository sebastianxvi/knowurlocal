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

    <!-- ================= HEADER ================= -->
    <form method="GET" action="{{ route('faqs.index') }}">
        <div class="filter-card">

            <div class="filter-bar">

                <!-- 🔍 SEARCH -->
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search FAQ..."
                    value="{{ request('search') }}"
                >

                <!-- 🏢 AGENCY FILTER -->
                <select name="agency">
                    <option value="">All Agencies</option>
                    @foreach($agencies as $agency)
                        <option 
                            value="{{ $agency->id }}"
                            {{ request('agency') == $agency->id ? 'selected' : '' }}
                        >
                            {{ $agency->agency_name }}
                        </option>
                    @endforeach
                </select>

                <!-- 📅 DATE -->
                <select name="date">
                    <option value="">All Dates</option>

                    @foreach($availableDates as $date)
                        <option 
                            value="{{ $date }}"
                            {{ request('date') == $date ? 'selected' : '' }}
                        >
                            {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                        </option>
                    @endforeach
                </select>

                <!-- 🔃 SORT -->
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

            <!-- ➕ ADD -->
            <div>
                <button type="button" class="add-agencybtn" onclick="openFaqModal('add')">
                    + Add FAQ
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
                    <th>Question</th>
                    <th>Answer</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                @forelse($faqs as $faq)
                <tr class="faq-row"

                    data-id="{{ $faq->id }}"
                    data-agency="{{ $faq->agency_id }}"
                    data-question="{{ $faq->question }}"
                    data-answer="{{ $faq->answer }}"
                    data-keywords="{{ $faq->keywords }}"
                    data-image="{{ $faq->image }}"
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

                    <td>{{ $faq->question }}</td>
                    <td>{{ Str::limit($faq->answer, 80) }}</td>
                    <td>
                        {{ $faq->created_at->format('M d, Y') }}
                    </td>

                    <td>
                        <div class="tablebtn">

                            <button 
                                type="button"
                                class="btn btn-primary"

                                data-id="{{ $faq->id }}"
                                data-agency="{{ $faq->agency_id }}"
                                data-question="{{ $faq->question }}"
                                data-answer="{{ $faq->answer }}"
                                data-keywords="{{ $faq->keywords }}"
                                data-image="{{ $faq->image }}"
                            >
                                Edit
                            </button>

                            <form method="POST" action="/faqs/{{ $faq->id }}" class="delete-form">
                                @csrf
                                @method('DELETE')

                                <button type="button" class="btn btn-danger delete-btn">
                                    Delete
                                </button>
                            </form>

                        </div>
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="5" class="empty">No FAQs found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>

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
                                data-abbr="{{ strtolower($agency->agency_abbreviation) }}">
                                
                                {{ $agency->agency_name }}
                            </option>
                        @endforeach
                    </select>
                    <label>Agency</label>
                </div>

                <div class="floating-group">
                    <input type="text" name="keywords" id="faq_keywords" placeholder=" ">
                    <label>Keywords</label>
                </div>

                <div class="floating-group">
                    <input type="text" name="question" id="faq_question" placeholder=" " required>
                    <label>Question</label>
                </div>

                <div class="floating-group">
                    <textarea name="answer" id="faq_answer" placeholder=" " required></textarea>
                    <label>Answer</label>
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

<!-- 🔥 FAQ LOGIC -->
<script src="{{ asset('jsfiles/admin/faqs.js') }}"></script>

<!-- 🔥 SUCCESS HANDLER (same pattern as NGA) -->
@if(session('success'))
<script>
    window.__FLASH_SUCCESS__ = @json(session('success'));
</script>
@endif

@endpush