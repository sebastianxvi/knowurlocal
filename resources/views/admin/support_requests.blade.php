@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/form-system.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/support_requests.css') }}">
@endpush

@section('title', 'KNOWURLOCAL | Support Requests')

@section('page-title', 'Support Requests')
@section('page-subtitle', 'Manage user-submitted inquiries')

@section('content')

<div class="logs-page">

    <!-- ================= FILTER ================= -->
    <form method="GET" action="{{ route('admin.support.requests') }}">
        <div class="filter-card">

            <div class="filter-bar">

                <!-- 🔍 SEARCH -->
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search question..."
                    value="{{ request('search') }}"
                >

                <!-- 📊 STATUS -->
                <select name="status">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status')=='pending' ? 'selected' : '' }}>
                        Pending
                    </option>
                    <option value="answered" {{ request('status')=='answered' ? 'selected' : '' }}>
                        Answered
                    </option>
                </select>

                <!-- 🚀 SUBMIT -->
                <button type="submit">Filter</button>

            </div>

        </div>
    </form>

    <!-- ================= TABLE ================= -->
    <div class="table-wrapper">
        <table class="table">

            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Question</th>
                    <th>Agency</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

                @forelse($requests as $req)
                <tr>

                    <!-- ID -->
                    <td>{{ $req->id }}</td>

                    <!-- USER -->
                    <td>
                        <div class="actor-cell">
                            <span class="actor-name">
                                {{ $req->user->first_name ?? 'Guest' }}
                            </span>

                            <span class="role-badge {{ $req->user ? 'user' : 'guest' }}">
                                {{ $req->user ? 'User' : 'Guest' }}
                            </span>
                        </div>
                    </td>

                    <!-- QUESTION -->
                    <td>{{ Str::limit($req->question, 60) }}</td>

                    <!-- AGENCY -->
                    <td>
                        {{ $req->agency->agency_name ?? '—' }}
                    </td>

                    <!-- STATUS -->
                    <td>
                        <span class="badge {{ $req->status }}">
                            {{ ucfirst($req->status) }}
                        </span>
                    </td>

                    <!-- DATE -->
                    <td>
                        {{ $req->created_at->format('M d, Y') }}
                    </td>

                    <!-- ACTION -->
                    <td>
                        <div class="tablebtn">

    <!-- VIEW -->
    <button 
    type="button"
    class="btn btn-primary view-btn"

    data-id="{{ $req->id }}"
    data-question="{{ $req->question }}"
    data-user="{{ $req->user->first_name ?? 'Guest' }}"
    data-agency="{{ $req->agency->agency_name ?? 'Unknown' }}"
    data-agency-id="{{ $req->agency_id }}"
    data-answer="{{ $req->answer }}"
>
    Manage
</button>


    @if(auth()->user()->role === 'superadmin')

        <!-- DELETE -->
        <form method="POST" action="{{ route('admin.support.delete', $req->id) }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger delete-btn">
                Delete
            </button>
        </form>

        <!-- ADD TO FAQ -->
        <a
            href="{{ route('admin.support.toFaq', $req->id) }}"
            class="btn btn-secondary faq-btn"
            data-id="{{ $req->id }}"
            data-similar-url="{{ route('admin.support.similarFaqs', $req->id) }}"
        >
            To FAQ
        </a>

    @endif

</div>
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="7" class="empty">
                        No support requests found.
                    </td>
                </tr>
                @endforelse

            </tbody>

        </table>
        </div>

        <!-- ================= FOOTER ================= -->
        <div class="footer">

            <span class="result-info">
                Showing {{ $requests->firstItem() ?? 0 }} 
                to {{ $requests->lastItem() ?? 0 }} 
                of {{ $requests->total() }} results
            </span>

            <div class="pagination-modern">

                @if ($requests->onFirstPage())
                    <span class="arrow disabled">
                        <i class="ph-light ph-caret-left"></i>
                    </span>
                @else
                    <a href="{{ $requests->previousPageUrl() }}" class="arrow">
                        <i class="ph-light ph-caret-left"></i>
                    </a>
                @endif

                <span class="page-indicator">
                    Page {{ $requests->currentPage() }}
                </span>

                @if ($requests->hasMorePages())
                    <a href="{{ $requests->nextPageUrl() }}" class="arrow">
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









<div id="support-modal-back" class="back">
    <div class="modal">

        <!-- HEADER -->
        <div class="modal-header">
            <h2>Support Request</h2>

            <div class="modal-actions">
                <button type="submit" form="reply-form" class="btn-save">
                    Mark as Answered
                </button>

                <button type="button" onclick="closeSupportModal()" class="btn-cancel">
                    Cancel
                </button>
            </div>
        </div>

        <!-- FORM -->
        <form 
    method="POST" 
    action="{{ route('admin.support.reply') }}" 
    id="reply-form"
    data-reply-url="{{ route('admin.support.reply') }}"
    data-update-url="/admin/support-requests"
>
    @csrf
    <input type="hidden" name="_method" id="form-method" value="POST">

    <div class="form-card">

        <input type="hidden" name="request_id" id="sr-id">

        <div class="floating-group">
            <input type="text" id="sr-user" placeholder=" " readonly>
            <label>User</label>
        </div>

        <div class="floating-group select-group">
    <select name="agency_id" id="sr-agency" required>
    <option value="" disabled selected hidden></option>

    @foreach($agencies as $agency)
        <option value="{{ $agency->id }}">
            {{ $agency->agency_name }}
        </option>
    @endforeach
</select>

    <label>Agency</label>
</div>

        <div class="floating-group">
            <textarea id="sr-question" placeholder=" " readonly></textarea>
            <label>Question</label>
        </div>

        <div class="floating-group">
            <textarea name="reply" id="sr-reply" placeholder=" " required></textarea>
            <label>Reply</label>
        </div>

    </div>
</form>

    </div>
</div>


<!-- =========================================================
     SIMILAR FAQ MODAL
     ========================================================= -->

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

        <!-- ================= HEADER ================= -->
<div class="similar-faq-header">

    <div class="similar-faq-heading">

        <div class="similar-faq-icon" aria-hidden="true">
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


    <!-- HEADER ACTIONS -->
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


        <!-- ================= DESCRIPTION ================= -->
        <div class="similar-faq-description">

            <p id="similar-faq-message">
                We found existing FAQs that may be related
                to this Support Request.
            </p>

        </div>


        <!-- ================= MATCHES ================= -->
        <div
            id="similar-faq-results"
            class="similar-faq-results"
        >
            <!-- JavaScript inserts matching FAQs here. -->
        </div>


        
        <!-- ================= FOOTER ================= -->
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
    window.__FLASH_SUCCESS__ = @json(session('success'));
</script>
@endif
@endpush