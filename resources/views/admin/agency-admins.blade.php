@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/theme.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/form-system.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/admin-modules.css') }}">
@endpush

@section('title', 'KNOWURLOCAL | Agency Admin Management')
@section('page-title', 'Agency Admin Management')
@section('page-subtitle', 'Manage agency administrators and invitations')

@section('content')
<div class="admin-page agency-admin-page">
    @if(session('success'))<script>window.__AGENCY_ADMIN_FLASH_SUCCESS__ = @json(session('success'));</script>@endif
    @if($errors->any())<div class="aa-alert error" role="alert">{{ $errors->first() }}</div>@endif

    @include('admin.components.status-tabs', [
        'ariaLabel' => 'Agency administrator status',
        'tabs' => [
            ['href' => route('admin.agency-admins', array_merge(request()->except(['page', 'status']), ['status' => 'active']), false), 'label' => 'Active', 'icon' => 'ph-user-check', 'count' => $activeCount, 'active' => $status === 'active'],
            ['href' => route('admin.agency-admins', array_merge(request()->except(['page', 'status']), ['status' => 'pending']), false), 'label' => 'Pending', 'icon' => 'ph-user-plus', 'count' => $pendingCount, 'active' => $status === 'pending'],
            ['href' => route('admin.agency-admins', array_merge(request()->except(['page', 'status']), ['status' => 'deactivated']), false), 'label' => 'Deactivated', 'icon' => 'ph-user-minus', 'count' => $deactivatedCount, 'active' => $status === 'deactivated'],
        ],
    ])

    <section class="support-filter-toolbar admin-filter-toolbar" aria-label="Agency administrator filters">
        <form method="GET" action="{{ route('admin.agency-admins') }}" class="support-filter-form">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="support-filter-field support-search-field">
                <label for="agency-admin-search" class="sr-only">Search agency administrators</label>
                <i class="ph-light ph-magnifying-glass" aria-hidden="true"></i>
                <input type="search" id="agency-admin-search" name="search" placeholder="Search name or email..." value="{{ request('search') }}" autocomplete="off">
            </div>
            <button type="submit" class="support-filter-submit admin-icon-button" aria-label="Apply filters" title="Apply filters"><i class="ph-light ph-sliders-horizontal" aria-hidden="true"></i><span class="sr-only">Filter</span></button>
            <button type="button" class="add-agencybtn admin-add-action" onclick="openAgencyInviteModal()" aria-label="Invite agency administrator" title="Invite agency administrator" @disabled($agencies->isEmpty())><i class="ph-light ph-user-plus" aria-hidden="true"></i><span class="sr-only">Invite agency administrator</span></button>
        </form>
    </section>

    @include('admin.components.list-result-meta', ['count' => $status === 'pending' ? $pendingInvites->count() : $agencyAdmins->count(), 'label' => $status === 'pending' ? 'invitation' : 'agency administrator'])

    @if($status === 'pending')
    <section class="agency-admin-table-section">
        <div class="admin-section-heading"><div><h3>Pending invitations</h3><p class="aa-muted">Unexpired invitations waiting for the representative to complete registration.</p></div></div>
        <div class="table-wrapper aa-table-wrap">
            <table class="table">
                <thead><tr><th>Invited email</th><th>Agency</th><th>Sent by</th><th>Expires</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($pendingInvites as $invite)
                    <tr>
                        <td><div class="actor-cell"><span class="actor-name">{{ $invite->email }}</span></div></td>
                        <td>{{ $invite->agency?->agency_name ?? 'Agency unavailable' }}</td>
                        <td>{{ $invite->creator?->email ?? 'System administrator' }}</td>
                        <td>{{ $invite->expires_at->format('M j, Y g:i A') }}</td>
                        <td><span class="badge pending">Pending</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">No pending Agency Admin invitations match your search.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @else
    <section class="agency-admin-table-section">
        <div class="admin-section-heading"><div><h3>{{ $status === 'active' ? 'Active Agency Admins' : 'Deactivated Agency Admins' }}</h3><p class="aa-muted">Each account is restricted to the agency assigned to it.</p></div><span class="support-dataset-count">{{ $agencyAdmins->count() }} shown</span></div>
        <div class="table-wrapper aa-table-wrap">
            <table class="table">
                <thead><tr><th>Administrator</th><th>Agency</th><th>Email</th><th>Account status</th></tr></thead>
                <tbody>
                @forelse($agencyAdmins as $admin)
                    <tr>
                        <td><div class="actor-cell"><span class="actor-name">{{ trim(($admin->first_name ?? '').' '.($admin->last_name ?? '')) ?: $admin->name }}</span></div></td>
                        <td>{{ $admin->agency?->agency_name ?? 'Agency unavailable' }}</td>
                        <td>{{ $admin->email }}</td>
                        <td><span class="badge {{ $admin->status ?? 'active' }}">{{ ucfirst($admin->status ?? 'active') }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">No {{ $status }} Agency Admin accounts match your search.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
    @endif
</div>

<div id="agency-invite-modal" class="back" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="agency-invite-modal-title">
        <div class="modal-header admin-modal-header">
            <div class="admin-modal-heading"><div class="admin-modal-icon" aria-hidden="true"><i class="ph-light ph-user-plus"></i></div><div><span class="admin-modal-eyebrow">Administration</span><h2 id="agency-invite-modal-title">Invite Agency Admin</h2></div></div>
            <div class="modal-actions"><button type="submit" form="agencyInviteForm" class="btn-save" @disabled($agencies->isEmpty())>Send</button><button type="button" onclick="closeAgencyInviteModal()" class="btn-cancel">Cancel</button></div>
        </div>
        <form id="agencyInviteForm" method="POST" action="{{ route('admin.agency-admins.invite') }}">
            @csrf
            <div class="form-card">
                <div class="floating-group"><input id="agency-invite-email" type="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" placeholder=" " required><label for="agency-invite-email">Official Email Address</label></div>
                <div class="floating-group"><select id="agency-invite-agency" name="agency_id" required><option value="" disabled {{ old('agency_id') ? '' : 'selected' }}>Select an agency</option>@foreach($agencies as $agency)<option value="{{ $agency->id }}" @selected((string) old('agency_id') === (string) $agency->id)>{{ $agency->agency_name }}{{ $agency->agency_abbreviation ? ' · '.$agency->agency_abbreviation : '' }}</option>@endforeach</select><label for="agency-invite-agency">Assign Agency</label></div>
                @if($agencies->isEmpty())<p>No active agencies are available. Add an agency before sending an invitation.</p>@endif
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
 const modal=document.getElementById('agency-invite-modal'); const form=document.getElementById('agencyInviteForm'); let previousFocus=null;
 window.openAgencyInviteModal=function(){if(!modal||!form)return;previousFocus=document.activeElement;modal.classList.remove('hidden');modal.style.display='flex';void modal.offsetWidth;modal.classList.add('active');modal.setAttribute('aria-hidden','false');const email=form.querySelector('input[name="email"]');if(email)email.focus();};
 window.closeAgencyInviteModal=function(){if(!modal)return;modal.classList.remove('active');modal.setAttribute('aria-hidden','true');window.setTimeout(function(){modal.style.display='none';modal.classList.add('hidden');if(previousFocus&&previousFocus.focus)previousFocus.focus();},200);};
 if(modal){modal.addEventListener('click',function(e){if(e.target===modal)window.closeAgencyInviteModal();});document.addEventListener('keydown',function(e){if(e.key==='Escape'&&modal.classList.contains('active'))window.closeAgencyInviteModal();});}
 if(window.__AGENCY_ADMIN_FLASH_SUCCESS__){const message=window.__AGENCY_ADMIN_FLASH_SUCCESS__;window.__AGENCY_ADMIN_FLASH_SUCCESS__=null;if(typeof window.showAlertModal==='function')window.showAlertModal({title:'Success',text:message,icon:'✓',variant:'success',confirmText:'OK',showCancel:false,onConfirm:function(){if(typeof window.closeAlertModal==='function')window.closeAlertModal();}});}
 @if($errors->any() && old('email')) window.openAgencyInviteModal(); @endif
})();
</script>
@endpush
