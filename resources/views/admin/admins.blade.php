@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/theme.css') }}"> <!-- 🔥 REQUIRED -->
<link rel="stylesheet" href="{{ asset('cssfiles/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/form-system.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/admin-modules.css') }}">
@endpush

@section('title', 'KNOWURLOCAL | ' . ucfirst(auth()->user()->role) . ' Module')

@section('page-title', 'Admin Management')
@section('page-subtitle', 'Manage administrators and invitations')

@section('content')

<div class="admin-page">

    <!-- ================= FILTER + ACTION BAR ================= -->
    <div class="filter-card">

        <!-- LEFT: FILTER -->
        <form method="GET" action="{{ route('admin.admins') }}" class="filter-bar">

            <!-- SEARCH -->
            <input 
                type="text" 
                name="search"
                placeholder="Search admin..."
                value="{{ request('search') }}"
            >

            <!-- ROLE FILTER -->
            <select name="role">
                <option value="">All Roles</option>
                <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                <option value="superadmin" {{ request('role') == 'superadmin' ? 'selected' : '' }}>Superadmin</option>
            </select>

            <!-- SORT -->
            <select name="sort">
                <option value="desc" {{ request('sort') == 'desc' ? 'selected' : '' }}>Newest First</option>
                <option value="asc" {{ request('sort') == 'asc' ? 'selected' : '' }}>Oldest First</option>
            </select>

            <!-- KEEP STATUS -->
            <input type="hidden" name="status" value="{{ request('status') }}">

            <!-- SUBMIT -->
            <button type="submit">Filter</button>

        </form>

        <!-- RIGHT: TABS + ACTION -->
        <div class="log-tabs">

    <a href="{{ route('admin.admins', array_merge(request()->all(), ['status' => ''])) }}"
       class="tab {{ request('status') == '' ? 'active' : '' }}">
        All
    </a>

    <a href="{{ route('admin.admins', array_merge(request()->all(), ['status' => 'pending'])) }}"
       class="tab {{ request('status') == 'pending' ? 'active' : '' }}">
        Pending
    </a>

    <a href="{{ route('admin.admins', array_merge(request()->all(), ['status' => 'active'])) }}"
       class="tab {{ request('status') == 'active' ? 'active' : '' }}">
        Active
    </a>

    <button type="button" class="add-agencybtn" onclick="openInviteModal()">
        + Invite
    </button>

</div>

    </div>

    @if(session('success'))
<script>
    window.__FLASH_SUCCESS__ = @json(session('success'));
</script>
@endif

    <!-- ================= TABLE ================= -->
    <div class="table-wrapper">

        <table class="table">

            <thead>
                <tr>
                    <th>Admin</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>

                @forelse($admins as $admin)
                <tr>

                    <!-- ADMIN -->
                    <td>
                        <div class="actor-cell">
                            <span class="actor-name">
                                {{ $admin->first_name }} {{ $admin->last_name }}
                            </span>
                        </div>
                    </td>

                    <!-- EMAIL -->
                    <td>{{ $admin->email }}</td>

                    <!-- ROLE -->
                    <td>
                        <span class="role-badge {{ $admin->role }}">
                            {{ ucfirst($admin->role) }}
                        </span>
                    </td>

                    <!-- STATUS -->
                    <td>
                        <span class="badge {{ $admin->status ?? 'active' }}">
                            {{ ucfirst($admin->status ?? 'active') }}
                        </span>
                    </td>

                    <!-- DATE -->
                    <td>
                        {{ $admin->created_at->format('M d, Y') }}
                    </td>

                    <td class="tablebtn">

                        @if(auth()->user()->role === 'superadmin')

                            @php 
                                $isSelf = $admin->id === auth()->id(); 
                            @endphp

                            {{-- ================= APPROVE ================= --}}
                            @if($admin->status === 'pending')
                                <form method="POST" action="{{ route('admin.approve', $admin->id) }}">
                                    @csrf
                                    <button type="button" class="btn btn-primary approve-btn">
                                        Approve
                                    </button>
                                </form>
                            @endif

                            {{-- ================= PROMOTE ================= --}}
                            @if($admin->status === 'active' && $admin->role === 'admin')
                                <form method="POST" action="{{ route('admin.promote', $admin->id) }}">
                                    @csrf
                                    <button type="button" class="btn btn-primary promote-btn">
                                        Promote
                                    </button>
                                </form>
                            @endif

                            {{-- ================= DEMOTE ================= --}}
                            @if($admin->role === 'superadmin' && !$isSelf)
                                <form method="POST" action="{{ route('admin.demote', $admin->id) }}">
                                    @csrf
                                    <button type="button" class="btn btn-danger demote-btn">
                                        Demote
                                    </button>
                                </form>
                            @endif

                            {{-- ================= DELETE ================= --}}
                            @if(!$isSelf)
                                <form method="POST" action="{{ route('admin.delete', $admin->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-danger delete-admin-btn">
                                        Delete
                                    </button>
                                </form>
                            @endif

                        @else
                            <span style="font-size:12px; color:var(--text-muted);">—</span>
                        @endif

                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="5" class="empty">
                        No admins found.
                    </td>
                </tr>
                @endforelse

            </tbody>

        </table>

        <!-- ================= FOOTER ================= -->
        <div class="footer">

            <span class="result-info">
                Showing {{ $admins->firstItem() ?? 0 }} 
                to {{ $admins->lastItem() ?? 0 }} 
                of {{ $admins->total() }} results
            </span>

            <div class="pagination-modern">

                @if ($admins->onFirstPage())
                    <span class="arrow disabled">
                        <i class="ph-light ph-caret-left"></i>
                    </span>
                @else
                    <a href="{{ $admins->previousPageUrl() }}" class="arrow">
                        <i class="ph-light ph-caret-left"></i>
                    </a>
                @endif

                <span class="page-indicator">
                    Page {{ $admins->currentPage() }}
                </span>

                @if ($admins->hasMorePages())
                    <a href="{{ $admins->nextPageUrl() }}" class="arrow">
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

<!-- ================= INVITE MODAL ================= -->
<div id="invite-modal" class="back">

    <div class="modal">

        <!-- HEADER -->
        <div class="modal-header">
            <h2>Invite Admin</h2>

            <div class="modal-actions">
                <button type="submit" form="inviteForm" class="btn-save">Send</button>
                <button type="button" onclick="closeInviteModal()" class="btn-cancel">Cancel</button>
            </div>
        </div>

        <!-- FORM -->
        <form id="inviteForm" method="POST" action="{{ route('admin.invite') }}">
            @csrf

            <div class="form-card">

                <div class="floating-group">
                    <input type="email" name="email" placeholder=" " required>
                    <label>Email Address</label>
                </div>

            </div>

        </form>

    </div>

</div>

@endsection

@push('scripts')
<script src="{{ asset('jsfiles/components/modal-system.js') }}"></script>
<script src="{{ asset('jsfiles/admin/admins.js') }}"></script>
@endpush