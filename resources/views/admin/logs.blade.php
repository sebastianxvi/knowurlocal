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
        @case('create_agency')
            ph-plus-circle
            @break

        @case('create_faq')
            ph-chat-centered-dots
            @break

        @case('create_category')
            ph-tag
            @break


        {{-- UPDATE --}}
        @case('update_agency')
            ph-pencil-simple
            @break

        @case('update_faq')
            ph-pencil-simple-line
            @break

        @case('update_category')
            ph-pencil-simple
            @break


        {{-- AGENCY LIFECYCLE --}}
        @case('trash_agency')
            ph-trash
            @break

        @case('restore_agency')
            ph-arrow-counter-clockwise
            @break

        @case('force_delete_agency')
            ph-trash-simple
            @break


        {{-- DELETE --}}
        @case('delete_agency')
            ph-trash
            @break

        @case('delete_faq')
            ph-trash
            @break

        @case('delete_support_request')
            ph-trash
            @break

        @case('delete_category')
            ph-trash
            @break

        {{-- ADMIN --}}
        @case('approve_admin') ph-check @break
        @case('promote_admin') ph-arrow-up-right @break
        @case('demote_admin') ph-arrow-down-right @break
        @case('delete_admin') ph-user-minus @break
        @case('invite_admin') ph-paper-plane-tilt @break

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
    'delete_faq',
    'delete_agency',
    'delete_support_request',
    'delete_category',
    'trash_agency',
    'restore_agency',
    'force_delete_agency'
], true))

        {{-- Deletion actions represent the removal of an existing record. --}}
        <span class="change-status {{ 
    $log->action === 'restore_agency'
        ? 'created'
        : 'deleted'
}}">

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

        @case('trash_agency')
            Agency Trashed
            @break

        @case('restore_agency')
            Agency Restored
            @break

        @case('force_delete_agency')
            Agency Permanently Deleted
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
                row.dataset.action === 'delete_agency' ||
                row.dataset.action === 'delete_category' ||
                row.dataset.action === 'delete_admin' ||
                row.dataset.action === 'delete_support_request' ||
                row.dataset.action === 'trash_agency' ||
                row.dataset.action === 'force_delete_agency'
            ) {

                modalNew.innerHTML = `
                    <div class="data-value">

                        ${
                            row.dataset.action === 'trash_agency'
                                ? 'Agency moved to trash'
                                : row.dataset.action === 'force_delete_agency'
                                    ? 'Agency permanently deleted'
                                    : 'Data deleted'
                        }

                    </div>
                `;

            } else if (
                row.dataset.action === 'restore_agency'
            ) {

                modalOld.innerHTML = `
                    <div class="data-value">
                        Agency was in trash
                    </div>
                `;

                modalNew.innerHTML = `
                    <div class="data-value">
                        Agency restored to active records
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