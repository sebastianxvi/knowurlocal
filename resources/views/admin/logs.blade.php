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
                    <th class="col-user">User</th>
                    <th class="col-target">Target</th>
                    <th class="col-action">Action</th>
                    <th class="col-change">Changes</th>
                    <th class="col-page">Page</th>
                    <th class="col-date">Date</th>
                </tr>
            </thead>

            <tbody>

                @forelse($logs as $log)

                {{-- @php
    /*
     * Resolve the human-readable label for THIS log entry.
     *
     * This must be inside the loop because $log only exists
     * after @forelse creates the current log record.
     */
    $displayActionLabel = $actionLabels[$log->action]
        ?? ucfirst(
            str_replace('_', ' ', $log->action)
        );
@endphp --}}

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
                    <td class="col-user">
                        <div class="actor-cell">

                            <span class="role-badge {{ $log->user->role ?? 'user' }}">

                                <i class="ph-light
                                    @if(($log->user->role ?? '') === 'superadmin')
                                        ph-crown
                                    @elseif(($log->user->role ?? '') === 'admin')
                                        ph-shield
                                    @else
                                        ph-user
                                    @endif
                                "></i>

                                <span>
                                    {{ ucfirst($log->user->role ?? 'User') }}
                                </span>

                            </span>

                            <span
                                class="actor-name"
                                title="{{ $log->actor_name }}"
                            >
                                {{ $log->actor_name }}
                            </span>

                        </div>
                    </td>

                    <!-- TARGET -->

                    <td class="col-target">

                        @if(
                            $log->log_target_name &&
                            $log->log_target !== 'System'
                        )

                            <div
                                class="target-cell"
                                title="{{ $log->log_target_name }}"
                            >

                                <span class="target-name">
                                    {{ $log->log_target_name }}
                                </span>

                                @if($log->log_target !== $log->log_target_name)

                                    <span class="target-meta">
                                        {{ $log->log_target }}
                                    </span>

                                @endif

                            </div>

                        @else

                            <div class="target-cell">

                                <span class="target-name system-target">
                                    System
                                </span>

                            </div>

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

                {{-- PUBLIC USER ACTIVITY --}}
                @case('view_map') ph-map-trifold @break
                @case('view_agencies') ph-buildings @break
                @case('view_agency') ph-building @break
                @case('search_agency') ph-magnifying-glass @break
                @case('get_directions') ph-navigation-arrow @break
                @case('contact_agency') ph-address-book @break
                @case('filter_category') ph-funnel-simple @break
                @case('navigate') ph-compass @break

                {{-- CREATE --}}
                @case('create_agency') ph-plus-circle @break
                @case('create_faq') ph-chat-centered-dots @break
                @case('create_category') ph-tag @break

                {{-- UPDATE --}}
                @case('update_agency') ph-pencil-simple @break
                @case('update_faq') ph-pencil-simple-line @break
                @case('update_category') ph-pencil-simple @break

                {{-- AGENCY LIFECYCLE --}}
                @case('trash_agency') ph-trash @break
                @case('restore_agency') ph-arrow-counter-clockwise @break
                @case('force_delete_agency') ph-trash-simple @break

                {{-- FAQ LIFECYCLE --}}
                @case('delete_faq') ph-trash @break
                @case('restore_faq') ph-arrow-counter-clockwise @break
                @case('force_delete_faq') ph-trash-simple @break

                {{-- CATEGORY LIFECYCLE --}}
                @case('delete_category') ph-trash @break
                @case('restore_category') ph-arrow-counter-clockwise @break
                @case('force_delete_category') ph-trash-simple @break

                {{-- OTHER DELETE ACTIONS --}}
                @case('delete_agency') ph-trash @break
                @case('delete_support_request') ph-trash @break
                @case('restore_support_request') ph-arrow-counter-clockwise @break
                @case('force_delete_support_request') ph-trash-simple @break
                

                {{-- ADMIN --}}
                @case('approve_admin') ph-check @break
                @case('promote_admin') ph-arrow-up-right @break
                @case('demote_admin') ph-arrow-down-right @break
                @case('deactivate_admin') ph-user-minus @break
                @case('reactivate_admin') ph-user-check @break
                @case('delete_admin') ph-user-minus @break
                @case('invite_admin') ph-paper-plane-tilt @break

                {{-- PUBLIC USER MANAGEMENT --}}
                @case('deactivate_user') ph-user-minus @break
                @case('reactivate_user') ph-user-check @break
                @case('delete_user') ph-trash @break

                {{-- DEFAULT --}}
                @default ph-lightning

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
    /*
     * FAQ lifecycle
     */
    'delete_faq',
    'restore_faq',
    'force_delete_faq',

    /*
     * Agency lifecycle
     */
    'delete_agency',
    'trash_agency',
    'restore_agency',
    'force_delete_agency',

    /*
     * Category lifecycle
     */
    'delete_category',
    'restore_category',
    'force_delete_category',

    /*
 * Support Request lifecycle
 */
'delete_support_request',
'restore_support_request',
'force_delete_support_request'
], true))

    @php
        /*
         * Determine whether this is a restoration action.
         *
         * Restoring a record is not destructive, so it uses
         * the same positive visual style as creation actions.
         */
        $isRestore = in_array($log->action, [
    'restore_faq',
    'restore_agency',
    'restore_category',
    'restore_support_request'
], true);

        /*
         * Convert the internal action code into a clear
         * administrator-facing description.
         */
        $changeLabel = match ($log->action) {

    /*
     * FAQ
     */
    'delete_faq'
        => 'FAQ moved to trash',

    'restore_faq'
        => 'FAQ restored',

    'force_delete_faq'
        => 'FAQ permanently deleted',


    /*
     * Agency
     */
    'delete_agency'
        => 'Agency deleted',

    'trash_agency'
        => 'Agency moved to trash',

    'restore_agency'
        => 'Agency restored',

    'force_delete_agency'
        => 'Agency permanently deleted',


    /*
     * Category
     */
    'delete_category'
        => 'Category moved to trash',

    'restore_category'
        => 'Category restored',

    'force_delete_category'
        => 'Category permanently deleted',


    /*
 * Support Request
 */
'delete_support_request'
    => 'Support Request moved to trash',

'restore_support_request'
    => 'Support Request restored',

'force_delete_support_request'
    => 'Support Request permanently deleted',

    default
        => 'Record updated',
};
    @endphp

    <span class="change-status {{ $isRestore ? 'created' : 'deleted' }}">
        {{ $changeLabel }}
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
    'demote_admin',
    'deactivate_admin',
    'reactivate_admin',

    /*
     * PUBLIC USER MANAGEMENT
     */
    'deactivate_user',
    'reactivate_user'
], true))

    @php
        /*
         * Admin Management logs store the exact value
         * before and after the action.
         *
         * Role actions:
         * promote / demote → role
         *
         * Status actions:
         * approve / deactivate / reactivate → status
         */
        $oldValues = $log->old_values ?? [];
        $newValues = $log->new_values ?? [];


        /*
         * Support older logs where the audit snapshots
         * were stored as JSON strings.
         */
        if (is_string($oldValues)) {

            $decoded = json_decode(
                $oldValues,
                true
            );

            $oldValues = is_array($decoded)
                ? $decoded
                : [];
        }


        if (is_string($newValues)) {

            $decoded = json_decode(
                $newValues,
                true
            );

            $newValues = is_array($decoded)
                ? $decoded
                : [];
        }


        /*
         * Determine which field actually changed.
         *
         * Example:
         *
         * ['role' => 'superadmin']
         *
         * gives us:
         *
         * $field = 'role'
         */
        $field = array_key_first($oldValues)
            ?? array_key_first($newValues);


        /*
         * Get the value before the action.
         */
        $oldValue = $field !== null
            ? ($oldValues[$field] ?? null)
            : null;


        /*
         * Get the value after the action.
         */
        $newValue = $field !== null
            ? ($newValues[$field] ?? null)
            : null;


        /*
         * Convert database values into readable text.
         *
         * superadmin → Superadmin
         * deactivated → Deactivated
         */
        $oldLabel = $oldValue !== null
            ? ucfirst(
                str_replace(
                    '_',
                    ' ',
                    (string) $oldValue
                )
            )
            : 'No previous value';


        $newLabel = $newValue !== null
            ? ucfirst(
                str_replace(
                    '_',
                    ' ',
                    (string) $newValue
                )
            )
            : 'No new value';


        /*
         * The heading is based on the field that changed.
         *
         * role   → Role changed
         * status → Status changed
         */
        $changeLabel = match ($field) {

            'role' =>
                'Role changed',

            'status' =>
                'Status changed',

            default =>
                'Admin updated',
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

        @elseif(in_array($log->action, [
    'search_agency',
    'view_agency',
    'get_directions',
    'contact_agency',
    'filter_category'
], true))

    <span class="change-status">

        @switch($log->action)

            @case('search_agency')
                Searched for agency
                @break

            @case('view_agency')
                Viewed agency details
                @break

            @case('get_directions')
                Requested directions to agency
                @break

            @case('contact_agency')
                {{ $log->description ?? 'Contacted agency' }}
                @break

            @case('filter_category')
                Applied category filter
                @break

        @endswitch

    </span>

    @elseif(in_array($log->action, [
    'delete_admin',
    'delete_user'
], true))

    @php
        /*
         * Retrieve the status that existed immediately
         * before the account was permanently deleted.
         *
         * The deletion log stores this snapshot because
         * the actual User record no longer exists afterward.
         */
        $oldValues = $log->old_values ?? [];

        /*
         * Support older logs where the snapshot may still
         * be stored as a JSON string.
         */
        if (is_string($oldValues)) {

            $decoded = json_decode(
                $oldValues,
                true
            );

            $oldValues = is_array($decoded)
                ? $decoded
                : [];
        }


        /*
         * Determine the user's previous account status.
         *
         * If unavailable, use a neutral historical label
         * rather than inventing a state.
         */
        $oldStatus =
            $oldValues['status'] ?? null;


        $oldStatusLabel =
            $oldStatus !== null
                ? ucfirst(
                    str_replace(
                        '_',
                        ' ',
                        (string) $oldStatus
                    )
                )
                : 'Previous status';
    @endphp


    <div class="audit-summary">

        <span class="audit-summary-count">
            Status changed
        </span>

        <div class="change-box">

            <span class="old">
                {{ $oldStatusLabel }}
            </span>

            <i class="ph-light ph-arrow-right"></i>

            <span class="new">
                Deleted
            </span>

        </div>

    </div>

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




                    <!-- PAGE -->
                    <td
                        class="col-page page-cell"
                        title="{{ $log->page_label }}"
                    >
                        {{ $log->page_label }}
                    </td>

                    <!-- DATE -->
                    <td class="col-date">

                        <div class="date-cell">

                            <span>
                                {{ $log->created_at->format('M d, Y') }}
                            </span>

                            <span>
                                {{ $log->created_at->format('H:i') }}
                            </span>

                        </div>

                    </td>

                </tr>
                @empty
                <tr>
                    <td colspan="6" class="empty">
                        No logs found.
                    </td>
                </tr>
                @endforelse

            </tbody>

        </table>
        </div>

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


    /* =========================================================
       HTML ESCAPING
       ========================================================= */

    function escapeHtml(value) {

        /*
         * Create a temporary DOM element.
         *
         * textContent treats the supplied value as plain text,
         * not executable HTML.
         *
         * This protects the audit modal against stored XSS when
         * displaying database-controlled values.
         */
        const div = document.createElement('div');

        div.textContent = String(value ?? '');

        /*
         * Returning innerHTML gives us the safely escaped
         * representation that can be inserted using innerHTML.
         */
        return div.innerHTML;
    }


    /* =========================================================
       READABLE FIELD LABEL
       ========================================================= */

    function formatLabel(key) {

        /*
         * Convert database-style field names:
         *
         * agency_name
         *
         * into:
         *
         * Agency Name
         */
        return String(key)
            .replaceAll('_', ' ')
            .replace(/\b\w/g, letter => letter.toUpperCase());
    }


    /* =========================================================
       CONTACT VALUE FORMATTER
       ========================================================= */

    function formatContacts(contacts) {

        /*
         * Make sure we actually received an array.
         *
         * This prevents malformed audit data from breaking
         * the entire modal.
         */
        if (!Array.isArray(contacts) || contacts.length === 0) {

            return `
                <div class="data-value">
                    No contact information
                </div>
            `;
        }


        /*
         * Create one visual block for every contact.
         */
        return contacts.map(contact => {

            /*
             * Safely retrieve the contact properties.
             *
             * The backend snapshot currently provides:
             *
             * type
             * type_slug
             * label
             * value
             * is_primary
             * sort_order
             */
            const type =
                contact?.type ||
                contact?.type_slug ||
                'Contact';

            const label =
                contact?.label ||
                '';

            const value =
                contact?.value ??
                'No value';

            const isPrimary =
                contact?.is_primary === true ||
                contact?.is_primary === 1 ||
                contact?.is_primary === '1';


            /*
             * Build the optional custom label.
             */
            const labelHtml = label
                ? `
                    <div class="contact-audit-label">
                        ${escapeHtml(label)}
                    </div>
                `
                : '';


            /*
             * Primary status is represented visually but
             * remains text-based for accessibility.
             */
            const primaryHtml = isPrimary
                ? `
                    <span class="contact-audit-primary">
                        Primary
                    </span>
                `
                : '';


            /*
             * Every contact becomes its own compact card.
             */
            return `
                <div class="contact-audit-item">

                    <div class="contact-audit-header">

                        <span class="contact-audit-type">
                            ${escapeHtml(type)}
                        </span>

                        ${primaryHtml}

                    </div>

                    ${labelHtml}

                    <div class="contact-audit-value">
                        ${escapeHtml(value)}
                    </div>

                </div>
            `;

        }).join('');
    }


    /* =========================================================
       GENERIC OBJECT FORMATTER
       ========================================================= */

    function formatObject(object) {

        /*
         * Object.entries() converts an object into
         * [key, value] pairs that we can safely iterate.
         */
        return Object.entries(object).map(([key, value]) => {

            const label = formatLabel(key);


            /*
             * CONTACTS GET THEIR OWN SPECIAL FORMAT.
             *
             * This is the important fix for:
             *
             * [object Object],[object Object]
             */
            if (key === 'contacts' && Array.isArray(value)) {

                return `
                    <div class="data-row">

                        <div class="data-label">
                            ${escapeHtml(label)}
                        </div>

                        <div class="contact-audit-list">
                            ${formatContacts(value)}
                        </div>

                    </div>
                `;
            }


            /*
             * Nested arrays/objects other than contacts should
             * still be displayed safely instead of becoming
             * [object Object].
             */
            if (
                Array.isArray(value) ||
                (
                    typeof value === 'object' &&
                    value !== null
                )
            ) {

                return `
                    <div class="data-row">

                        <div class="data-label">
                            ${escapeHtml(label)}
                        </div>

                        <div class="data-value">

                            ${formatNestedValue(value)}

                        </div>

                    </div>
                `;
            }


            /*
             * Normal scalar database values.
             */
            const displayValue =
                value === null ||
                value === undefined ||
                value === ''
                    ? 'No value'
                    : value;


            return `
                <div class="data-row">

                    <div class="data-label">
                        ${escapeHtml(label)}
                    </div>

                    <div class="data-value">
                        ${escapeHtml(displayValue)}
                    </div>

                </div>
            `;

        }).join('');
    }


    /* =========================================================
       NESTED VALUE FORMATTER
       ========================================================= */

    function formatNestedValue(value) {

        /*
         * Arrays are formatted one item at a time.
         */
        if (Array.isArray(value)) {

            return value.map(item => {

                /*
                 * Nested objects should be represented as
                 * structured JSON rather than [object Object].
                 */
                if (
                    typeof item === 'object' &&
                    item !== null
                ) {

                    return `
                        <div class="nested-object">
                            ${formatObject(item)}
                        </div>
                    `;
                }


                return `
                    <div class="data-value">
                        ${escapeHtml(item)}
                    </div>
                `;

            }).join('');
        }


        /*
         * Nested object.
         */
        if (
            typeof value === 'object' &&
            value !== null
        ) {

            return `
                <div class="nested-object">
                    ${formatObject(value)}
                </div>
            `;
        }


        /*
         * Scalar fallback.
         */
        return escapeHtml(
            value === null ||
            value === undefined ||
            value === ''
                ? 'No value'
                : value
        );
    }


    /* =========================================================
       MAIN AUDIT DATA FORMATTER
       ========================================================= */

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

            let parsed = value;


            /*
             * data-* attributes always arrive from HTML as strings.
             *
             * Therefore JSON must normally be decoded here.
             */
            if (typeof parsed === 'string') {

                parsed = JSON.parse(parsed);


                /*
                 * Handle accidentally double-encoded JSON.
                 */
                if (typeof parsed === 'string') {

                    try {
                        parsed = JSON.parse(parsed);
                    } catch {
                        /*
                         * If the second parse fails, the first
                         * decoded string is still usable.
                         */
                    }
                }
            }


            /*
             * Handle simple scalar values.
             */
            if (
                typeof parsed !== 'object' ||
                parsed === null
            ) {

                return `
                    <div class="data-value">
                        ${escapeHtml(parsed)}
                    </div>
                `;
            }


            /*
             * A top-level array can occur in older audit records.
             *
             * Format it safely instead of converting it into
             * comma-separated [object Object] values.
             */
            if (Array.isArray(parsed)) {

                /*
                 * If the array looks like a contact collection,
                 * render it using the specialized contact UI.
                 */
                if (
                    parsed.length === 0 ||
                    parsed.every(item =>
                        item &&
                        typeof item === 'object' &&
                        (
                            'value' in item ||
                            'type' in item ||
                            'type_slug' in item
                        )
                    )
                ) {

                    return `
                        <div class="contact-audit-list">
                            ${formatContacts(parsed)}
                        </div>
                    `;
                }


                /*
                 * Generic array fallback.
                 */
                return formatNestedValue(parsed);
            }


            /*
             * Normal structured audit object.
             */
            return formatObject(parsed);

        } catch (error) {

            /*
             * Never allow malformed historical audit data to
             * break the modal.
             *
             * Fall back to escaped plain text.
             */
            return `
                <div class="data-value">
                    ${escapeHtml(value)}
                </div>
            `;
        }
    }


    /* =========================================================
       OPEN MODAL
       ========================================================= */

    document.querySelectorAll('.log-row').forEach(row => {

        row.addEventListener('click', () => {

            const oldVal = row.dataset.old;
            const newVal = row.dataset.new;


            /*
             * Render the previous state first.
             */
            modalOld.innerHTML = formatData(oldVal);


            /*
             * Destructive actions don't have a meaningful
             * "new database state".
             */
            if (
                row.dataset.action === 'delete_faq' ||
                row.dataset.action === 'force_delete_faq' ||

                row.dataset.action === 'delete_agency' ||
                row.dataset.action === 'trash_agency' ||
                row.dataset.action === 'force_delete_agency' ||

                row.dataset.action === 'delete_category' ||
                row.dataset.action === 'force_delete_category' ||

                row.dataset.action === 'delete_admin' ||
                row.dataset.action === 'delete_support_request' ||
                row.dataset.action === 'force_delete_support_request'
            ) {

                modalNew.innerHTML = `
                    <div class="data-value">

                        ${
                            {
                                delete_faq: 'FAQ moved to trash',
                                force_delete_faq: 'FAQ permanently deleted',

                                delete_agency: 'Agency deleted',
                                trash_agency: 'Agency moved to trash',
                                force_delete_agency: 'Agency permanently deleted',

                                delete_category: 'Category moved to trash',
                                force_delete_category: 'Category permanently deleted',

                                delete_admin: 'Admin deleted',
                                delete_support_request: 'Support Request moved to trash',
                                force_delete_support_request: 'Support Request permanently deleted'
                            }[row.dataset.action] || 'Data deleted'
                        }

                    </div>
                `;

            } else if (
    row.dataset.action === 'restore_agency' ||
    row.dataset.action === 'restore_faq' ||
    row.dataset.action === 'restore_category' ||
    row.dataset.action === 'restore_support_request'
) {

    const recordName =
    row.dataset.action === 'restore_faq'
        ? 'FAQ'
        : row.dataset.action === 'restore_category'
            ? 'Category'
            : row.dataset.action === 'restore_support_request'
                ? 'Support Request'
                : 'Agency';

    modalOld.innerHTML = `
        <div class="data-value">
            ${recordName} was in trash
        </div>
    `;

    modalNew.innerHTML = `
        <div class="data-value">
            ${recordName} restored to active records
        </div>
    `;
            } else if (
                row.dataset.action === 'create_faq' ||
                row.dataset.action === 'create_agency' ||
                row.dataset.action === 'create_category'
            ) {

                modalOld.innerHTML = `
                    <div class="data-value">
                        No previous data
                    </div>
                `;

                modalNew.innerHTML = formatData(newVal);

            } else {

                /*
                 * Normal update action.
                 */
                modalNew.innerHTML = formatData(newVal);
            }


            /*
             * Display the modal.
             */
            modal.classList.add('active');


            /*
             * Prevent the page behind the modal from scrolling.
             */
            document.body.style.overflow = 'hidden';

        });

    });


    /* =========================================================
       CLOSE MODAL
       ========================================================= */

    function closeModal() {

        modal.classList.remove('active');

        /*
         * Restore normal page scrolling.
         */
        document.body.style.overflow = '';
    }


    closeBtn.addEventListener(
        'click',
        closeModal
    );


    /* =========================================================
       CLOSE WHEN CLICKING THE BACKDROP
       ========================================================= */

    modal.addEventListener('click', event => {

        /*
         * Only close when the actual backdrop is clicked.
         *
         * Clicking inside the modal content does nothing.
         */
        if (event.target === modal) {
            closeModal();
        }

    });


    /* =========================================================
       CLOSE WITH ESCAPE
       ========================================================= */

    document.addEventListener('keydown', event => {

        if (event.key === 'Escape') {
            closeModal();
        }

    });

});
</script>
@endpush