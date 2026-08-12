@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/theme.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/logs.css') }}">
@endpush


@section('title', 'KNOWURLOCAL | ' . ucfirst(auth()->user()->role) . ' Module')

@section('page-title', 'Activity Logs')
@section('page-subtitle', 'Monitor user activities')

@section('content')

@php
    /*
     * Creates a short preview for long audit values.
     *
     * The complete value remains in the audit data and is
     * still available through the details modal.
     */
    $shortAuditValue = function ($value, int $limit = 32) {

        if ($value === null || $value === '') {
            return 'No value';
        }

        return \Illuminate\Support\Str::limit(
            (string) $value,
            $limit,
            '...'
        );
    };
@endphp

<div class="logs-page">

    <!-- FILTER -->
    <div class="filter-card">

        <!-- LEFT -->
        <form method="GET" class="filter-bar">

            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search user or action">

            <select name="action">
                <option value="">All Actions</option>

                @foreach($availableActions as $action)
                    <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_',' ', $action)) }}
                    </option>
                @endforeach
            </select>

            <select name="date">
                <option value="">All Dates</option>

                @foreach($availableDates as $date)
                    <option value="{{ $date }}" {{ request('date') == $date ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                    </option>
                @endforeach
            </select>

            <select name="sort">
                <option value="desc" {{ request('sort') == 'desc' ? 'selected' : '' }}>Newest First</option>
                <option value="asc" {{ request('sort') == 'asc' ? 'selected' : '' }}>Oldest First</option>
            </select>

            <!-- KEEP ROLE -->
            <input type="hidden" name="role" value="{{ request('role') }}">

            <button type="submit">Filter</button>

        </form>

        <!-- RIGHT -->
        <div class="log-tabs">

            <a href="{{ route('admin.logs', array_merge(request()->all(), ['role' => ''])) }}"
               class="tab {{ request('role') == '' ? 'active' : '' }}">
               All
            </a>

            <a href="{{ route('admin.logs', array_merge(request()->all(), ['role' => 'admin'])) }}"
               class="tab {{ request('role') == 'admin' ? 'active' : '' }}">
               Admin
            </a>

            <a href="{{ route('admin.logs', array_merge(request()->all(), ['role' => 'user'])) }}"
               class="tab {{ request('role') == 'user' ? 'active' : '' }}">
               User
            </a>

        </div>

    </div>

    <!-- TABLE -->
    <div class="table-wrapper">

        <table class="table">

            <thead>
                <tr>
                    <th>User</th>
                    <th>Target</th>
                    <th>Action</th>
                    <th>Change</th>
                    <th>Context</th>
                    <th>Page</th>
                    <th>Date</th>
                </tr>
            </thead>

            <tbody>

                @forelse($logs as $log)
                <tr class="log-row"
    data-action="{{ $log->action }}"
                    data-old='@json(
                        $log->old_values !== null
                            ? $log->old_values
                            : $log->old_value
                    )'
                    data-new='@json(
                        $log->new_values !== null
                            ? $log->new_values
                            : $log->new_value
                    )'
                >

                    <!-- USER -->
                    <td>
                        <div class="actor-cell">

                            <span class="role-badge {{ $log->user->role ?? 'user' }}">
    
                                <i class="ph-light 
                                    @if(($log->user->role ?? '') === 'superadmin') ph-crown
                                    @elseif(($log->user->role ?? '') === 'admin') ph-shield
                                    @else ph-user
                                    @endif
                                "></i>

                                {{ ucfirst($log->user->role ?? 'User') }}

                            </span>

                            <span class="actor-name">
                                {{ $log->actor_name }}
                            </span>

                        </div>
                    </td>

                    <!-- TARGET -->
                    <td>

                        @if(in_array($log->action, [
                            'create_faq',
                            'update_faq',
                            'delete_faq'
                        ], true))

                            {{-- FAQ audit target --}}
                            <span class="target-user">
                                FAQ #{{ $log->faq_id }}
                            </span>

                            @elseif(in_array($log->action, [
    'create_category',
    'update_category',
    'delete_category'
], true))

    {{-- Category audit target --}}
    <span class="target-user">
        Category #{{ $log->category_id }}
    </span>

                            @elseif($log->action === 'delete_support_request')

    @php
        /*
         * The Support Request ID is stored inside the
         * old_values audit snapshot because UserLog does
         * not currently have a dedicated support_request_id
         * column.
         */
        $supportAuditData = $log->old_values ?? [];

        /*
         * Support older records where old_values may be
         * stored as a JSON string.
         */
        if (is_string($supportAuditData)) {
            $decoded = json_decode($supportAuditData, true);
            $supportAuditData = is_array($decoded)
                ? $decoded
                : [];
        }

        $supportRequestId =
            $supportAuditData['support_request_id'] ?? null;
    @endphp

    <span class="target-user">
        {{ $supportRequestId
            ? 'Support Request #' . $supportRequestId
            : 'Support Request'
        }}
    </span>

                        @elseif(in_array($log->action, [
                            'create_agency',
                            'update_agency',
                            'delete_agency'
                        ], true))

                            {{-- Agency audit target --}}
                            <span class="target-user">
                                Agency #{{ $log->agency_id }}
                            </span>

                        @elseif($log->action === 'invite_admin')

                        @php
                            /*
                            * The invited administrator does not have a users record yet.
                            * Therefore target_user_id is intentionally NULL.
                            *
                            * The invited email is stored in the audit snapshot instead.
                            */
                            $inviteData = $log->new_values ?? [];

                            /*
                            * Support older audit records where new_values
                            * may still be stored as a JSON string.
                            */
                            if (is_string($inviteData)) {
                                $decoded = json_decode($inviteData, true);
                                $inviteData = is_array($decoded) ? $decoded : [];
                            }

                            $inviteEmail = $inviteData['email'] ?? null;
                        @endphp

                        <div class="pending-target">

                            <span class="target-user">
                                Pending Admin
                            </span>

                            @if($inviteEmail)
                                <span class="target-email">
                                    {{ $inviteEmail }}
                                </span>
                            @endif

                        </div>

                    @elseif($log->target_user_id)

                        <span class="target-user">
                            {{ $log->targetUser->email ?? 'User #' . $log->target_user_id }}
                        </span>

                    @else

                        <span class="target-user">
                            System
                        </span>

                    @endif

                    </td>

                    <!-- ACTION -->
                    <td>
                        <span class="badge action {{ $log->action }}">
                            <i class="ph-light 
    @switch($log->action)

        {{-- AUTH --}}
        @case('login') ph-sign-in @break
        @case('logout') ph-sign-out @break
        @case('admin_login') ph-shield-check @break
        @case('admin_logout') ph-shield-slash @break

        {{-- VIEW / NAVIGATION --}}
        @case('view_map') ph-map-trifold @break
        @case('navigate') ph-compass @break
        @case('view_agency') ph-building @break
        @case('view_agencies') ph-buildings @break

        {{-- CREATE --}}
        @case('create_agency') ph-plus-circle @break
        @case('create_faq') ph-chat-centered-dots @break
        @case('create_category') ph-tag @break

        {{-- UPDATE --}}
        @case('update_agency') ph-pencil-simple @break
        @case('update_faq') ph-pencil-simple-line @break
        @case('update_category') ph-pencil-simple @break

        {{-- DELETE --}}
        @case('delete_agency') ph-trash @break
        @case('delete_faq') ph-trash @break
        @case('delete_support_request') ph-trash @break
        @case('delete_category') ph-trash @break

        {{-- ADMIN --}}
        @case('approve_admin') ph-check @break
        @case('promote_admin') ph-arrow-up-right @break
        @case('demote_admin') ph-arrow-down-right @break
        @case('delete_admin') ph-user-minus @break
        @case('invite_admin') ph-paper-plane-tilt @break

        {{-- DEFAULT --}}
        @default ph-circle

    @endswitch
"></i>

                            {{ $log->action_label }}
                        </span>
                    </td>

                    


                    <!-- CHANGE -->
<td class="audit-change-cell">

    @if(in_array($log->action, [
    'create_faq',
    'create_agency',
    'create_category'
], true))

        {{-- Creation actions have no previous database state. --}}
        <span class="change-status created">
    Created
    {{
        match (true) {
            str_contains($log->action, 'agency') => 'Agency',
            str_contains($log->action, 'faq') => 'FAQ',
            str_contains($log->action, 'category') => 'Category',
            default => 'Record',
        }
    }}
</span>

    @elseif(in_array($log->action, [
    'delete_faq',
    'delete_agency',
    'delete_support_request',
    'delete_category'
], true))

        {{-- Deletion actions represent the removal of an existing record. --}}
        <span class="change-status deleted">

    @switch($log->action)

        @case('delete_faq')
            Deleted FAQ
            @break

        @case('delete_agency')
            Deleted Agency
            @break

        @case('delete_category')
            Deleted Category
            @break

        @case('delete_support_request')
            Deleted Support Request
            @break

    @endswitch

</span>

    @elseif(in_array($log->action, [
    'update_faq',
    'update_agency',
    'update_category'
], true))

        @php
            /*
             * Retrieve the structured audit snapshots.
             *
             * UserLog normally casts these values into arrays,
             * but older records may still contain JSON strings.
             */
            $oldValues = $log->old_values ?? [];
            $newValues = $log->new_values ?? [];

            /*
             * Safely decode older JSON-string records.
             */
            if (is_string($oldValues)) {
                $decoded = json_decode($oldValues, true);
                $oldValues = is_array($decoded) ? $decoded : [];
            }

            if (is_string($newValues)) {
                $decoded = json_decode($newValues, true);
                $newValues = is_array($decoded) ? $decoded : [];
            }

            /*
             * Collect every field appearing in either snapshot.
             */
            $changedFields = array_unique(
                array_merge(
                    array_keys($oldValues),
                    array_keys($newValues)
                )
            );

            /*
             * Remove identifiers and metadata that should not
             * be presented as user-editable fields.
             */
            $changedFields = array_values(
                array_diff(
                    $changedFields,
                    [
                        'agency_id',
                        'faq_id',
                        'status'
                    ]
                )
            );

            /*
             * Convert database field names into readable labels.
             */
            $fieldLabels = collect($changedFields)
                ->map(function ($field) {
                    return ucwords(
                        str_replace('_', ' ', $field)
                    );
                });
        @endphp

        @if(empty($changedFields))

            <span class="change-status">
                No recorded changes
            </span>

        @else

            <div class="audit-summary">

                <span class="audit-summary-count">

                    {{ count($changedFields) }}

                    {{ count($changedFields) === 1 ? 'field' : 'fields' }}

                    changed

                </span>

                <span
                    class="audit-summary-fields"
                    title="{{ $fieldLabels->implode(' · ') }}"
                >

                    {{ $fieldLabels->take(2)->implode(' · ') }}

                    @if(count($changedFields) > 2)
                        · +{{ count($changedFields) - 2 }} more
                    @endif

                </span>

            </div>

        @endif

    @elseif(in_array($log->action, [
        'approve_admin',
        'promote_admin',
        'demote_admin'
    ], true))

        @php
            /*
             * Admin-management actions also use structured
             * old/new audit snapshots.
             */
            $oldValues = $log->old_values ?? [];
            $newValues = $log->new_values ?? [];

            /*
             * Safely support older JSON-string records.
             */
            if (is_string($oldValues)) {
                $decoded = json_decode($oldValues, true);
                $oldValues = is_array($decoded) ? $decoded : [];
            }

            if (is_string($newValues)) {
                $decoded = json_decode($newValues, true);
                $newValues = is_array($decoded) ? $decoded : [];
            }

            /*
             * Admin actions currently record one changed field:
             *
             * promote/demote → role
             * approve        → status
             */
            $field = array_key_first($oldValues)
                ?? array_key_first($newValues);

            $oldValue = $field !== null
                ? ($oldValues[$field] ?? null)
                : null;

            $newValue = $field !== null
                ? ($newValues[$field] ?? null)
                : null;

            /*
             * Convert technical values into readable labels.
             *
             * Example:
             * superadmin → Superadmin
             */
            $oldLabel = $oldValue !== null
                ? ucfirst(str_replace('_', ' ', (string) $oldValue))
                : 'No previous value';

            $newLabel = $newValue !== null
                ? ucfirst(str_replace('_', ' ', (string) $newValue))
                : 'No new value';

            /*
             * Describe the actual field that changed.
             */
            $changeLabel = match ($log->action) {
                'approve_admin' => 'Status changed',
                'promote_admin' => 'Role changed',
                'demote_admin' => 'Role changed',
                default => 'Admin updated',
            };
        @endphp

        <div class="audit-summary">

            <span class="audit-summary-count">
                {{ $changeLabel }}
            </span>

            <div class="change-box">

                <span class="old">
                    {{ $oldLabel }}
                </span>

                <i class="ph-light ph-arrow-right"></i>

                <span class="new">
                    {{ $newLabel }}
                </span>

            </div>

        </div>

    @elseif($log->action === 'delete_admin')

        <span class="change-status deleted">
            Deleted Admin
        </span>

    @elseif($log->action === 'invite_admin')

    @php
        /*
         * Retrieve the structured invitation audit data.
         */
        $inviteData = $log->new_values ?? [];

        /*
         * Support older records where new_values
         * may still be stored as a JSON string.
         */
        if (is_string($inviteData)) {
            $decoded = json_decode($inviteData, true);
            $inviteData = is_array($decoded) ? $decoded : [];
        }

        $inviteEmail = $inviteData['email'] ?? null;
    @endphp

    <div class="audit-summary">

        <span class="audit-summary-count">
            Invitation sent
        </span>

        <span
            class="audit-summary-fields"
            title="{{ $inviteEmail ?? 'Unknown email' }}"
        >
            {{ $inviteEmail ?? 'Unknown email' }}
        </span>

    </div>

    @else

        {{-- Fallback for unrelated audit records. --}}
        @if($log->old_value && $log->new_value)

            <div class="change-box">

                <span class="old">
                    {{ \Illuminate\Support\Str::limit(
                        $log->old_value,
                        32,
                        '...'
                    ) }}
                </span>

                <i class="ph-light ph-arrow-right"></i>

                <span class="new">
                    {{ \Illuminate\Support\Str::limit(
                        $log->new_value,
                        32,
                        '...'
                    ) }}
                </span>

            </div>

        @else

            <span class="change-status">
                System action
            </span>

        @endif

    @endif

</td>








                    <!-- CONTEXT -->
<td>

    @if($log->agency)

        {{-- Agency-related context --}}
        <span class="log-context agency-context">
            {{ $log->agency->agency_name }}
        </span>

    @elseif(in_array($log->action, [
    'invite_admin',
    'approve_admin',
    'promote_admin',
    'demote_admin',
    'delete_admin'
], true))

    {{-- Admin-management actions affect an administrator account. --}}
    <span class="log-context admin-context">
        Admin Account
    </span>

@else

    {{-- Other system/user activity has its own context. --}}
    <span class="log-context system-context">
        {{ ucfirst(str_replace('_', ' ', $log->page ?? 'System')) }}
    </span>

@endif

</td>

                    <!-- PAGE -->
                    <td>
                        {{ ucfirst(str_replace('_', ' ', $log->page)) }}
                    </td>

                    <!-- DATE -->
                    <td>
                        {{ $log->created_at->format('M d, Y H:i') }}
                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="5" class="empty">
                        No logs found.
                    </td>
                </tr>
                @endforelse

            </tbody>

        </table>

        <!-- FOOTER -->
        <div class="footer">

            <div class="result-info">
                Showing {{ $logs->firstItem() ?? 0 }} 
                to {{ $logs->lastItem() ?? 0 }} 
                of {{ $logs->total() }} results
            </div>

            <div class="pagination-modern">

                @if ($logs->onFirstPage())
                    <span class="arrow disabled">
                        <svg viewBox="0 0 24 24" width="14" height="14">
                            <path d="M15 6L9 12L15 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                @else
                    <a href="{{ $logs->previousPageUrl() }}" class="arrow">
                        <svg viewBox="0 0 24 24" width="14" height="14">
                            <path d="M15 6L9 12L15 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                @endif

                <span class="page-indicator">
                    Page {{ $logs->currentPage() }}
                </span>

                @if ($logs->hasMorePages())
                    <a href="{{ $logs->nextPageUrl() }}" class="arrow">
                        <svg viewBox="0 0 24 24" width="14" height="14">
                            <path d="M9 6L15 12L9 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                @else
                    <span class="arrow disabled">
                        <svg viewBox="0 0 24 24" width="14" height="14">
                            <path d="M9 6L15 12L9 18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                @endif

            </div>

        </div>

    </div>

</div>



<div id="logModal" class="log-modal">

    <div class="modal-content">

        <div class="modal-header">
            <span>Log Details</span>
            <button id="closeModal">&times;</button>
        </div>

        <div class="modal-body">

            <div class="modal-block">
                <div class="modal-label">Old Value</div>
                <div id="modalOld" class="modal-box old"></div>
            </div>

            <div class="modal-arrow">
                <i class="ph-light ph-arrow-right"></i>
            </div>

            <div class="modal-block">
                <div class="modal-label">New Value</div>
                <div id="modalNew" class="modal-box new"></div>
            </div>

        </div>

    </div>

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    const modal = document.getElementById('logModal');
    const modalOld = document.getElementById('modalOld');
    const modalNew = document.getElementById('modalNew');
    const closeBtn = document.getElementById('closeModal');

    /* ================= FORMAT FUNCTION ================= */
    function escapeHtml(value) {

    const div = document.createElement('div');

    div.textContent = String(value);

    return div.innerHTML;
}


function formatData(value) {

    /*
     * NULL means there was no previous/new state.
     */
    if (
        value === null ||
        value === undefined ||
        value === '' ||
        value === 'null'
    ) {
        return `
            <div class="data-value">
                No recorded data
            </div>
        `;
    }

    try {

        let parsed = JSON.parse(value);

        /*
         * Handle accidentally double-encoded JSON.
         */
        if (typeof parsed === 'string') {
            parsed = JSON.parse(parsed);
        }

        /*
         * Handle simple non-object values.
         */
        if (
            typeof parsed !== 'object' ||
            parsed === null ||
            Array.isArray(parsed)
        ) {
            return `
                <div class="data-value">
                    ${escapeHtml(parsed)}
                </div>
            `;
        }

        let html = '';

        Object.entries(parsed).forEach(([key, val]) => {

            const label = key
                .replaceAll('_', ' ')
                .replace(/\b\w/g, letter => letter.toUpperCase());

            const displayValue =
                val === null || val === ''
                    ? 'No value'
                    : val;

            html += `
                <div class="data-row">

                    <div class="data-label">
                        ${escapeHtml(label)}
                    </div>

                    <div class="data-value">
                        ${escapeHtml(displayValue)}
                    </div>

                </div>
            `;
        });

        return html;

    } catch {

        return `
            <div class="data-value">
                ${escapeHtml(value)}
            </div>
        `;
    }
}

    /* ================= OPEN MODAL ================= */
    document.querySelectorAll('.log-row').forEach(row => {

        row.addEventListener('click', () => {

            const oldVal = row.dataset.old;
            const newVal = row.dataset.new;

            modalOld.innerHTML = formatData(oldVal);

if (
    row.dataset.action === 'delete_faq' ||
    row.dataset.action === 'delete_agency' ||
    row.dataset.action === 'delete_category' ||
    row.dataset.action === 'delete_admin' ||
    row.dataset.action === 'delete_support_request'
) {

    /*
     * A deletion has no new database state.
     *
     * We display an explicit audit event instead of
     * showing an empty or misleading NULL value.
     */
    modalNew.innerHTML = `
        <div class="data-value">
            Data deleted
        </div>
    `;

} else if (
    row.dataset.action === 'create_faq' ||
    row.dataset.action === 'create_agency' ||
    row.dataset.action === 'create_category'
) {

    /*
     * A creation has no previous database state.
     */
    modalOld.innerHTML = `
        <div class="data-value">
            No previous data
        </div>
    `;

    /*
     * The new audit snapshot contains the created record.
     */
    modalNew.innerHTML = formatData(newVal);

} else {

    /*
     * Update and all other audit records show their
     * actual old/new snapshots.
     */
    modalNew.innerHTML = formatData(newVal);
}






            modal.classList.add('active');

            // 🔥 LOCK BACKGROUND SCROLL
            document.body.style.overflow = 'hidden';
        });

    });

    /* ================= CLOSE MODAL ================= */
    function closeModal(){
        modal.classList.remove('active');

        // 🔓 RESTORE SCROLL
        document.body.style.overflow = '';
    }

    closeBtn.addEventListener('click', closeModal);

    /* click outside */
    modal.addEventListener('click', (e) => {
        if(e.target === modal){
            closeModal();
        }
    });

    /* ESC key (PRO FEATURE) */
    document.addEventListener('keydown', (e) => {
        if(e.key === 'Escape'){
            closeModal();
        }
    });

});
</script>
@endpush