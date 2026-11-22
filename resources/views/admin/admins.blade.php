@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/theme.css') }}"> <!-- 🔥 REQUIRED -->
<link rel="stylesheet" href="{{ asset('cssfiles/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/form-system.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/admin-modules.css') }}">

<style>
.account-deactivation-details{display:flex;flex-direction:column;align-items:flex-start;gap:6px;margin-top:9px;max-width:280px;white-space:normal}
.account-deactivation-reason{color:var(--text-secondary,#64748b);font-size:12px;line-height:1.45;overflow-wrap:anywhere;white-space:normal}
.account-deactivation-date{color:var(--text-muted,#8490a3);font-size:11px;line-height:1.4;white-space:normal}

.deactivation-reason-cell{min-width:230px;max-width:360px;white-space:normal;vertical-align:middle}
.deactivation-reason-cell .account-deactivation-details{display:flex;flex-direction:column;align-items:flex-start;gap:6px;margin:0;max-width:none;white-space:normal}
.deactivation-reason-cell .account-deactivation-reason{display:block;max-width:100%;color:var(--text-secondary,#64748b);font-size:12px;line-height:1.5;overflow-wrap:anywhere;white-space:normal}
.deactivation-reason-cell .account-deactivation-date{display:block;color:var(--text-muted,#8490a3);font-size:11px;line-height:1.4;white-space:normal}
.deactivation-reason-empty{color:var(--text-muted,#94a3b8)}
@media(max-width:768px){.deactivation-reason-cell{min-width:190px;max-width:260px}}
</style>

{{-- Keep deactivation details consistently centered across all account-management tables. --}}
<style>
.deactivation-reason-cell { text-align: center !important; vertical-align: middle !important; }
.deactivation-reason-cell .account-deactivation-details {
    align-items: center !important;
    justify-content: center !important;
    margin-inline: auto !important;
    text-align: center !important;
}
.deactivation-reason-cell .account-deactivation-reason,
.deactivation-reason-cell .account-deactivation-date {
    text-align: center !important;
}
</style>

@endpush

@section('title', 'KNOWURLOCAL | ' . ucfirst(auth()->user()->role) . ' Module')

@section('page-title', 'Admin Management')
@section('page-subtitle', 'Manage administrators and invitations')

@section('content')
@if($errors->any() && old('email'))
    <script>
        window.__ADMIN_INVITE_VALIDATION_ERROR__ = @json($errors->first());
        window.__ADMIN_INVITE_OLD_EMAIL__ = @json(old('email'));
    </script>
@endif


<div class="admin-page" data-lifecycle-password-url="{{ route('admin.admins.verify-lifecycle-password') }}">

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
                <input type="search" id="admin-search" name="search" placeholder="Search name or email..." value="{{ request('search') }}" autocomplete="off">
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
                    @if(request()->query('status', 'active') === 'deactivated')
                        <th>Deactivation Reason</th>
                    @endif
                    <th>Date Created</th>
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

                    @if(request()->query('status', 'active') === 'deactivated')
                    <!-- DEACTIVATION REASON: only relevant in the deactivated tab -->
                    <td class="deactivation-reason-cell">
                        @if($admin->status === 'deactivated')
                            <div class="account-deactivation-details">
                                <span class="account-deactivation-reason">{{ $admin->deactivation_reason ?: 'No reason recorded.' }}</span>
                                @if($admin->deactivated_at)
                                    <span class="account-deactivation-date">Deactivated {{ $admin->deactivated_at->format('M d, Y · g:i A') }}</span>
                                @endif
                            </div>
                        @else
                            <span class="deactivation-reason-empty">—</span>
                        @endif
                    </td>
                    @endif

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

        @if($admin->role === 'admin' && $admin->status === 'active')
            <form method="POST" action="{{ route('admin.admins.evaluation-moderation', $admin->id) }}" class="admin-inline-permission-form">
                @csrf
                @method('PATCH')
                <input type="hidden" name="can_moderate_evaluations" value="{{ $admin->can_moderate_evaluations ? 0 : 1 }}">
                <button type="submit" class="btn {{ $admin->can_moderate_evaluations ? 'btn-success' : 'btn-secondary' }} admin-table-icon-action evaluation-moderation-btn" type="button"
                    title="{{ $admin->can_moderate_evaluations ? 'Revoke evaluation moderation' : 'Grant evaluation moderation' }}"
                    aria-label="{{ $admin->can_moderate_evaluations ? 'Revoke evaluation moderation' : 'Grant evaluation moderation' }}">
                    <i class="ph-light {{ $admin->can_moderate_evaluations ? 'ph-shield-check' : 'ph-shield-plus' }}"></i>
                    <span class="sr-only">{{ $admin->can_moderate_evaluations ? 'Revoke moderation' : 'Grant moderation' }}</span>
                </button>
            </form>
        @endif


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
                    class="js-account-deactivation-form"
                    data-verification-url="{{ route('admin.admins.verify-lifecycle-password') }}"
                >
                    @csrf

                    <button
                        type="submit"
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
                    <td colspan="{{ request()->query('status', 'active') === 'deactivated' ? 7 : 6 }}" class="empty">
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
                    <input type="email" name="email" value="{{ old('email') }}" placeholder=" " required>
                    <label>Email Address</label>
                </div>

            </div>

        </form>

    </div>

</div>

@endsection

@push('scripts')
<script src="{{ asset('jsfiles/admin/admin-password-confirmation.js') }}?v={{ filemtime(public_path('jsfiles/admin/admin-password-confirmation.js')) }}"></script>
<script src="{{ asset('jsfiles/admin/admins.js') }}?v={{ hash_file('sha256', public_path('jsfiles/admin/admins.js')) }}"></script>
<script src="{{ asset('jsfiles/admin/account-deactivation.js') }}?v={{ hash_file('sha256', public_path('jsfiles/admin/account-deactivation.js')) }}"></script>
@endpush
