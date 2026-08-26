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

    {{-- STATUS TABS --}}
@if(auth()->user()->role === 'superadmin')

<div class="admin-status-tabs">

    {{-- ACTIVE --}}
    <a
        href="{{ route(
            'admin.admins',
            array_merge(
                request()->except('page'),
                ['status' => 'active']
            )
        ) }}"
        class="admin-status-tab {{ request('status') === 'active' ? 'active' : '' }}"
    >
        <i class="ph-light ph-user-check"></i>

        <span>Active</span>

        <span class="status-count">
            {{ $activeCount }}
        </span>
    </a>


    {{-- PENDING --}}
    <a
        href="{{ route(
            'admin.admins',
            array_merge(
                request()->except('page'),
                ['status' => 'pending']
            )
        ) }}"
        class="admin-status-tab {{ request('status') === 'pending' ? 'active' : '' }}"
    >
        <i class="ph-light ph-user-plus"></i>

        <span>Pending</span>

        <span class="status-count">
            {{ $pendingCount }}
        </span>
    </a>


    {{-- DEACTIVATED --}}
    <a
        href="{{ route(
            'admin.admins',
            array_merge(
                request()->except('page'),
                ['status' => 'deactivated']
            )
        ) }}"
        class="admin-status-tab {{ request('status') === 'deactivated' ? 'active' : '' }}"
    >
        <i class="ph-light ph-user-minus"></i>

        <span>Deactivated</span>

        <span class="status-count">
            {{ $deactivatedCount }}
        </span>
    </a>

</div>

@endif

    <!-- ================= FILTER + ACTION BAR ================= -->

<div class="filter-card">

    <form
        method="GET"
        action="{{ route('admin.admins') }}"
        class="filter-bar"
    >

        {{-- SEARCH --}}
        <input
            type="text"
            name="search"
            placeholder="Search admin..."
            value="{{ request('search') }}"
        >


        {{-- ROLE FILTER --}}
        <select name="role">

            <option value="">
                All Roles
            </option>

            <option
                value="admin"
                {{ request('role') === 'admin' ? 'selected' : '' }}
            >
                Admin
            </option>

            <option
                value="superadmin"
                {{ request('role') === 'superadmin' ? 'selected' : '' }}
            >
                Superadmin
            </option>

        </select>


        {{-- SORT --}}
        <select name="sort">

            <option
                value="desc"
                {{ request('sort', 'desc') === 'desc' ? 'selected' : '' }}
            >
                Newest First
            </option>

            <option
                value="asc"
                {{ request('sort') === 'asc' ? 'selected' : '' }}
            >
                Oldest First
            </option>

        </select>


        {{-- PRESERVE CURRENT STATUS TAB --}}
        <input
            type="hidden"
            name="status"
            value="{{ request('status') }}"
        >


        {{-- FILTER --}}
        <button type="submit">
            Filter
        </button>

    </form>


    {{-- INVITE --}}
    <button
        type="button"
        class="add-agencybtn"
        onclick="openInviteModal()"
    >

        <i class="ph-light ph-user-plus"></i>

        <span>
            Invite
        </span>

    </button>

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
            /*
             * Prevent the currently authenticated Super Admin
             * from modifying or deleting their own account.
             */
            $isSelf = $admin->id === auth()->id();
        @endphp


        {{-- =================================================
             PENDING
             ================================================= --}}
        @if($admin->status === 'pending')

            <form
                method="POST"
                action="{{ route('admin.approve', $admin->id) }}"
            >
                @csrf

                <button
                    type="button"
                    class="btn btn-primary approve-btn"
                >
                    <i class="ph-light ph-user-check"></i>
                    Approve
                </button>
            </form>

        @endif


        {{-- =================================================
             ACTIVE
             ================================================= --}}
        @if($admin->status === 'active')

            {{-- Promote normal Admin → Super Admin --}}
            @if($admin->role === 'admin')

                <form
                    method="POST"
                    action="{{ route('admin.promote', $admin->id) }}"
                >
                    @csrf

                    <button
                        type="button"
                        class="btn btn-primary promote-btn"
                    >
                        <i class="ph-light ph-arrow-up"></i>
                        Promote
                    </button>
                </form>

            @endif


            {{-- Demote Super Admin → Admin --}}
            @if($admin->role === 'superadmin' && !$isSelf)

                <form
                    method="POST"
                    action="{{ route('admin.demote', $admin->id) }}"
                >
                    @csrf

                    <button
                        type="button"
                        class="btn btn-danger demote-btn"
                    >
                        <i class="ph-light ph-arrow-down"></i>
                        Demote
                    </button>
                </form>

            @endif


            {{-- Deactivate --}}
            @if(!$isSelf)

                <form
                    method="POST"
                    action="{{ route('admin.deactivate', $admin->id) }}"
                >
                    @csrf

                    <button
                        type="button"
                        class="btn btn-danger deactivate-admin-btn"
                    >
                        <i class="ph-light ph-user-minus"></i>
                        Deactivate
                    </button>
                </form>

            @endif

        @endif


        {{-- =================================================
             DEACTIVATED
             ================================================= --}}
        @if($admin->status === 'deactivated' && !$isSelf)

            {{-- Reactivate --}}
            <form
                method="POST"
                action="{{ route('admin.reactivate', $admin->id) }}"
            >
                @csrf

                <button
                    type="button"
                    class="btn btn-primary reactivate-admin-btn"
                >
                    <i class="ph-light ph-user-check"></i>
                    Reactivate
                </button>
            </form>


            {{-- Permanent Delete --}}
            <form
                method="POST"
                action="{{ route('admin.delete', $admin->id) }}"
            >
                @csrf
                @method('DELETE')

                <button
                    type="button"
                    class="btn btn-danger delete-admin-btn"
                >
                    <i class="ph-light ph-trash"></i>
                    Delete Permanently
                </button>
            </form>

        @endif


    @else

        <span style="font-size:12px; color:var(--text-muted);">
            —
        </span>

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
        </div>

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