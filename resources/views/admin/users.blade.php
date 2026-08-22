@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/theme.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/form-system.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/admin-modules.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/users.css') }}">
@endpush

@section('title', 'KNOWURLOCAL | ' . ucfirst(auth()->user()->role) . ' Module')

@section('page-title', 'User Management')
@section('page-subtitle', 'View and manage registered users')

@section('content')

<div class="admin-page">

    <!-- ================= FILTER ================= -->
    <div class="filter-card">

        <form method="GET" action="{{ route('admin.users') }}" class="filter-bar">

            <!-- SEARCH -->
            <input 
                type="text" 
                name="search"
                placeholder="Search user..."
                value="{{ request('search') }}"
            >

            <!-- SORT -->
            <select name="sort">
                <option value="desc" {{ request('sort') == 'desc' ? 'selected' : '' }}>Newest First</option>
                <option value="asc" {{ request('sort') == 'asc' ? 'selected' : '' }}>Oldest First</option>
            </select>

            <button type="submit">Filter</button>

        </form>

    </div>

    <!-- ================= TABLE ================= -->
    <div class="table-wrapper">

        <table class="table">

            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Date Registered</th>

                    @if(auth()->user()->role === 'superadmin')
                        <th>Action</th>
                    @endif
                </tr>
            </thead>

            <tbody>

                @forelse($users as $user)
                <tr class="user-row"

                    data-id="{{ $user->id }}"
                    data-name="{{ $user->first_name }} {{ $user->last_name }}"
                    data-date="{{ $user->created_at->format('M d, Y') }}"

                >
                    <!-- USER -->
                    <td>
                        <div class="actor-cell">
                            <span class="actor-name">
                                {{ $user->first_name }} {{ $user->last_name }}
                            </span>
                        </div>
                    </td>

                    <!-- EMAIL -->
                    <td>{{ $user->email }}</td>

                    <!-- ROLE -->
                    <td>
                        <span class="role-badge {{ $user->role }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>

                    <!-- DATE -->
                    <td>
                        {{ $user->created_at->format('M d, Y') }}
                    </td>

                    @if(auth()->user()->role === 'superadmin')
                    <td class="tablebtn">

                        @php
                            $isSelf = $user->id === auth()->id();
                        @endphp

                        @if(!$isSelf)
                        <form method="POST" action="{{ route('admin.users.delete', $user->id) }}">
                            @csrf
                            @method('DELETE')

                            <button type="submit" class="btn btn-danger delete-btn">
                                Delete
                            </button>
                        </form>
                        @else
                            <span style="font-size:12px; color:var(--text-muted);">—</span>
                        @endif

                    </td>
                    @endif

                </tr>
                @empty
                <tr>
                    <td colspan="5" class="empty">
                        No users found.
                    </td>
                </tr>
                @endforelse

            </tbody>

        </table>
        </div>

        <!-- ================= FOOTER ================= -->
        <div class="footer">

            <span class="result-info">
                Showing {{ $users->firstItem() ?? 0 }} 
                to {{ $users->lastItem() ?? 0 }} 
                of {{ $users->total() }} results
            </span>

            <div class="pagination-modern">

                @if ($users->onFirstPage())
                    <span class="arrow disabled">
                        <i class="ph-light ph-caret-left"></i>
                    </span>
                @else
                    <a href="{{ $users->previousPageUrl() }}" class="arrow">
                        <i class="ph-light ph-caret-left"></i>
                    </a>
                @endif

                <span class="page-indicator">
                    Page {{ $users->currentPage() }}
                </span>

                @if ($users->hasMorePages())
                    <a href="{{ $users->nextPageUrl() }}" class="arrow">
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




<div id="user-modal-back" class="back">
    <div class="modal">

        <div class="modal-header">
            <h2>User Inquiries</h2>

            <div class="modal-actions">
                <button type="button" onclick="closeUserModal()" class="btn-cancel">
                    Close
                </button>
            </div>
        </div>

        <div class="form-card">

    <!-- USER INFO -->
    <div class="floating-group">
        <input type="text" id="user_name" readonly>
        <label>User</label>
    </div>

    <div class="floating-group">
        <input type="text" id="user_date" readonly>
        <label>Date Registered</label>
    </div>

    <!-- DIVIDER -->
    <div style="margin:10px 0; font-size:12px; color:var(--text-muted);">
        User Inquiries
    </div>

    <!-- INQUIRIES LIST -->
    <div id="user-inquiries-container"></div>

</div>

    </div>
</div>

@endsection

@push('scripts')

<script>
document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("user-modal-back");

    const nameInput = document.getElementById("user_name");
    const dateInput = document.getElementById("user_date");
    const container = document.getElementById("user-inquiries-container");

    function openUserModal(data){

        modal.classList.remove("hidden");
        modal.style.display = "flex";

        void modal.offsetWidth;

        modal.classList.add("active");

        // 🔥 SET BASIC INFO (FAQ STYLE)
        nameInput.value = data.name;
        dateInput.value = data.date;

        // 🔥 LOAD INQUIRIES
        loadInquiries(data.id);
    }

    async function loadInquiries(userId){

        container.innerHTML = `<p style="font-size:12px;">Loading...</p>`;

        try{
            const res = await fetch(`{{ url('/admin/users') }}/${userId}/inquiries`);
            const data = await res.json();

            renderInquiries(data.logs);

        }catch(err){
            container.innerHTML = `<p style="color:red;">Failed to load</p>`;
        }
    }

    function renderInquiries(logs){

    if (!logs.length){
        container.innerHTML = `
            <div class="empty">
                <p>No inquiries yet</p>
            </div>
        `;
        return;
    }

    let html = "";

    logs.forEach(log => {

        html += `
            <div class="card" data-status="${log.status}">

                <div class="card-top">
                    <span class="status ${log.status}">
                        ${log.status}
                    </span>

                    <span>
                        ${new Date(log.created_at).toLocaleDateString()}
                    </span>
                </div>

                <div class="question">
                    ${log.question}
                </div>

                ${
                    log.status === "answered"
                    ? `<div class="answer">${log.answer}</div>`
                    : `<div class="pending-text">Waiting for response...</div>`
                }

            </div>
        `;
    });

    container.innerHTML = html;
}

    window.closeUserModal = function(){
        modal.classList.remove("active");

        setTimeout(() => {
            modal.style.display = "none";
        }, 200);
    };

    /* ================= ROW CLICK (FAQ STYLE) ================= */
    document.addEventListener("click", function (e) {

        const row = e.target.closest(".user-row");
        if (!row) return;

        if (e.target.closest("button") || e.target.closest("form")) return;

        openUserModal({
            id: row.dataset.id,
            name: row.dataset.name,
            date: row.dataset.date
        });

    });

});
</script>

@endpush