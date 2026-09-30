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

    {{-- Shared status navigation --}}
    @if(auth()->user()->role === 'superadmin')
        @include('admin.components.status-tabs', [
            'ariaLabel' => 'Administrator status',
            'tabs' => [
                [
                    'href' => route('admin.admins', array_merge(request()->except('page'), ['status' => 'active'])),
                    'label' => 'Active',
                    'icon' => 'ph-user-check',
                    'count' => $activeCount,
                    'active' => request('status', 'active') === 'active',
                ],
                [
                    'href' => route('admin.admins', array_merge(request()->except('page'), ['status' => 'pending'])),
                    'label' => 'Pending',
                    'icon' => 'ph-user-plus',
                    'count' => $pendingCount,
                    'active' => request('status') === 'pending',
                ],
                [
                    'href' => route('admin.admins', array_merge(request()->except('page'), ['status' => 'deactivated'])),
                    'label' => 'Deactivated',
                    'icon' => 'ph-user-minus',
                    'count' => $deactivatedCount,
                    'active' => request('status') === 'deactivated',
                ],
            ],
        ])
    @endif

    {{-- Shared filter toolbar --}}
    <section class="support-filter-toolbar admin-filter-toolbar" aria-label="Administrator filters">
        <form method="GET" action="{{ route('admin.admins') }}" class="support-filter-form">
            <input type="hidden" name="status" value="{{ request('status') }}">

            <div class="support-filter-field support-search-field">
                <label for="admin-search" class="sr-only">Search administrators</label>
                <i class="ph-light ph-magnifying-glass" aria-hidden="true"></i>
                <input type="search" id="admin-search" name="search" placeholder="Search admin..." value="{{ request('search') }}" autocomplete="off">
            </div>

            <div class="support-filter-field">
                <label for="admin-role-filter" class="sr-only">Filter by role</label>
                <i class="ph-light ph-users-three" aria-hidden="true"></i>
                <select name="role" id="admin-role-filter">
                    <option value="">All Roles</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                    <option value="superadmin" {{ request('role') === 'superadmin' ? 'selected' : '' }}>Superadmin</option>
                </select>
            </div>

            <div class="support-filter-field">
                <label for="admin-sort" class="sr-only">Sort administrators</label>
                <i class="ph-light ph-arrows-down-up" aria-hidden="true"></i>
                <select name="sort" id="admin-sort">
                    <option value="desc" {{ request('sort', 'desc') === 'desc' ? 'selected' : '' }}>Newest First</option>
                    <option value="asc" {{ request('sort') === 'asc' ? 'selected' : '' }}>Oldest First</option>
                </select>
            </div>

            <button type="submit" class="support-filter-submit admin-icon-button" aria-label="Apply filters" title="Apply filters">
                <i class="ph-light ph-sliders-horizontal" aria-hidden="true"></i>
                <span class="sr-only">Filter</span>
            </button>
        
            <button type="button" class="add-agencybtn admin-add-action" onclick="openInviteModal()" aria-label="Invite administrator" title="Invite administrator">
                <i class="ph-light ph-user-plus" aria-hidden="true"></i>
                <span class="sr-only">Invite administrator</span>
            </button>

        </form>

    </section>

    @if(session('success'))
<script>
    window.__FLASH_SUCCESS__ = @json(session('success'));
</script>
@endif

    <!-- ================= TABLE ================= -->
    @include('admin.components.list-result-meta', [
    'count' => $admins->total(),
    'label' => 'administrator',
])

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
                    class="btn btn-primary approve-btn admin-table-icon-action"
                 aria-label="Approve" title="Approve">
                    <i class="ph-light ph-user-check"></i><span class="sr-only">Approve</span></button>
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
                        class="btn btn-primary promote-btn admin-table-icon-action"
                     aria-label="Promote" title="Promote">
                        <i class="ph-light ph-arrow-up"></i><span class="sr-only">Promote</span></button>
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
                        class="btn btn-danger demote-btn admin-table-icon-action"
                     aria-label="Demote" title="Demote">
                        <i class="ph-light ph-arrow-down"></i><span class="sr-only">Demote</span></button>
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
                        class="btn btn-danger deactivate-admin-btn admin-table-icon-action"
                     aria-label="Deactivate" title="Deactivate">
                        <i class="ph-light ph-user-minus"></i><span class="sr-only">Deactivate</span></button>
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
                    class="btn btn-primary reactivate-admin-btn admin-table-icon-action"
                 aria-label="Reactivate" title="Reactivate">
                    <i class="ph-light ph-user-check"></i><span class="sr-only">Reactivate</span></button>
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
                    class="btn btn-danger delete-admin-btn admin-table-icon-action"
                 aria-label="Delete Permanently" title="Delete Permanently">
                    <i class="ph-light ph-trash"></i><span class="sr-only">Delete Permanently</span></button>
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
        <div class="modal-header admin-modal-header">
            <div class="admin-modal-heading">
                <div class="admin-modal-icon" aria-hidden="true">
                    <i class="ph-light ph-user-plus"></i>
                </div>
                <div>
                    <span class="admin-modal-eyebrow">Administration</span>
                    <h2>Invite Admin</h2>
                </div>
            </div>

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

<script src="{{ asset('jsfiles/admin/admins.js') }}"></script>
@endpush