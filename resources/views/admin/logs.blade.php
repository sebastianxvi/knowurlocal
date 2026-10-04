@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/theme.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/logs.css') }}">
@endpush


@section('title', 'KNOWURLOCAL | ' . ucfirst(auth()->user()->role) . ' Module')

@section('page-title', 'Activity Logs')
@section('page-subtitle', 'Review a complete audit trail of user and administrator activity')

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
    <section class="support-filter-toolbar admin-filter-toolbar" aria-label="Activity log filters">
        <form method="GET" class="support-filter-form">
            <div class="support-filter-field support-search-field">
                <label for="logs-search" class="sr-only">Search logs</label>
                <i class="ph-light ph-magnifying-glass" aria-hidden="true"></i>
                <input type="search" id="logs-search" name="search" value="{{ request('search') }}" placeholder="Search user or action..." autocomplete="off">
            </div>

            <div class="support-filter-field">
                <label for="logs-action" class="sr-only">Filter by action</label>
                <i class="ph-light ph-funnel" aria-hidden="true"></i>
                <select name="action" id="logs-action">
                    <option value="">All Actions</option>
                    @php
                        $groupedActions = $availableActions->groupBy(
                            fn ($action) => config("activity_logs.actions.{$action}.group", 'Other Activity')
                        );
                    @endphp
                    @foreach($groupedActions as $group => $groupActions)
                        <optgroup label="{{ $group }}">
                            @foreach($groupActions as $action)
                                <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                    {{ config("activity_logs.actions.{$action}.label", ucfirst(str_replace('_',' ', $action))) }}
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            <div class="support-filter-field">
                <label for="logs-date" class="sr-only">Filter by date</label>
                <i class="ph-light ph-calendar-blank" aria-hidden="true"></i>
                <select name="date" id="logs-date">
                    <option value="">All Dates</option>
                    @foreach($availableDates as $date)
                        <option value="{{ $date }}" {{ request('date') == $date ? 'selected' : '' }}>
                            {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="support-filter-field">
                <label for="logs-sort" class="sr-only">Sort logs</label>
                <i class="ph-light ph-arrows-down-up" aria-hidden="true"></i>
                <select name="sort" id="logs-sort">
                    <option value="desc" {{ request('sort', 'desc') == 'desc' ? 'selected' : '' }}>Newest First</option>
                    <option value="asc" {{ request('sort') == 'asc' ? 'selected' : '' }}>Oldest First</option>
                </select>
            </div>

            <input type="hidden" name="role" value="{{ request('role') }}">

            <button type="submit" class="support-filter-submit admin-icon-button" aria-label="Apply filters" title="Apply filters">
                <i class="ph-light ph-sliders-horizontal" aria-hidden="true"></i>
                <span class="sr-only">Filter</span>
            </button>
        </form>

        <nav class="support-dataset-tabs admin-filter-tabs" aria-label="Activity log actor">
            <a href="{{ route('admin.logs', array_merge(request()->all(), ['role' => ''])) }}"
               class="support-dataset-tab {{ request('role') == '' ? 'is-active' : '' }}"
               aria-current="{{ request('role') == '' ? 'page' : 'false' }}">
                <span>All</span>
            </a>
            <a href="{{ route('admin.logs', array_merge(request()->all(), ['role' => 'admin'])) }}"
               class="support-dataset-tab {{ request('role') == 'admin' ? 'is-active' : '' }}"
               aria-current="{{ request('role') == 'admin' ? 'page' : 'false' }}">
                <span>Admin</span>
            </a>
            <a href="{{ route('admin.logs', array_merge(request()->all(), ['role' => 'user'])) }}"
               class="support-dataset-tab {{ request('role') == 'user' ? 'is-active' : '' }}"
               aria-current="{{ request('role') == 'user' ? 'page' : 'false' }}">
                <span>User</span>
            </a>
        </nav>
    </section>

    <!-- TABLE -->
    @include('admin.components.list-result-meta', [
    'count' => $logs->total(),
    'label' => 'log',
])

<div class="table-wrapper">

        <table class="table">

            <thead>
                <tr>
                    <th class="col-user">User</th>
                    <th class="col-target">Target</th>
                    <th class="col-action">Activity</th>
                    <th class="col-change">Changes</th>
                    <th class="col-page">Source</th>
                    <th class="col-date">Time</th>
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
                        $log->audit_old_data,
                        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
                    )'
                    data-new='@json(
                        $log->audit_new_data,
                        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
                    )'
                    data-description="{{ $log->description }}"
                    data-actor="{{ $log->actor_name }}"
                    data-role="{{ $log->role ?? 'user' }}"
                    data-target="{{ $log->log_target_name && $log->log_target !== 'System' ? $log->log_target_name . ' (' . $log->log_target . ')' : ($log->log_target_name ?? 'System') }}"
                    data-page="{{ $log->page_label }}"
                    data-ip="{{ $log->ip_address ?? 'Not recorded' }}"
                    data-device="{{ $log->device ?? 'Not recorded' }}"
                    data-group="{{ $log->action_group }}"
                >

                    <!-- USER -->
                    <td class="col-user">
                        <div class="actor-cell">

                            <span class="role-badge {{ $log->role ?? 'user' }}">

                                <i class="ph-light
                                    @if(($log->role ?? '') === 'superadmin')
                                        ph-crown
                                    @elseif(($log->role ?? '') === 'admin')
                                        ph-shield
                                    @else
                                        ph-user
                                    @endif
                                "></i>

                                <span>
                                    {{ ucfirst($log->role ?? 'user') }}
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

        @php
            $targetLabel = match ($log->action) {

                'login',
                'logout',
                'admin_login',
                'admin_logout',
                'session_expired'
                    => 'Authentication',

                default
                    => 'System',
            };
        @endphp

        <div class="target-cell">

            <span class="target-name system-target">
                {{ $targetLabel }}
            </span>

        </div>

    @endif

</td>

                    
                    <!-- ACTION -->
                    <td>
                        <span class="badge action {{ $log->action }}" title="{{ $log->action_group }}">
                            <i class="ph-light {{ $log->action_icon }}" aria-hidden="true"></i>
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
         * Retrieve the old and new audit snapshots.
         *
         * UserLog normally casts these values into arrays,
         * but older records may still contain JSON strings.
         */
        $oldValues = $log->old_values ?? [];
        $newValues = $log->new_values ?? [];


        /*
         * Decode old JSON snapshots when necessary.
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
         * Decode new JSON snapshots when necessary.
         */
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
         * Retrieve the image values.
         *
         * array_key_exists() is important here because the
         * new image may intentionally exist with a NULL value.
         */
        $oldImage = $oldValues['image'] ?? null;

        $newImage = array_key_exists('image', $newValues)
            ? $newValues['image']
            : null;


        /*
         * Detect intentional image removal.
         *
         * Example:
         *
         * Old image: faqs/example.png
         * New image: null
         *
         * This means the image was removed.
         */
        $imageWasRemoved =
            !empty($oldImage) &&
            empty($newImage);


        /*
         * Collect all fields from both snapshots.
         */
        $changedFields = array_unique(
            array_merge(
                array_keys($oldValues),
                array_keys($newValues)
            )
        );


        /*
         * If the image was removed but the new snapshot
         * did not contain the image key, manually add it.
         */
        if ($imageWasRemoved) {

            $changedFields[] = 'image';

        }


        /*
         * Remove fields that are not user-editable fields.
         */
        $changedFields = array_values(
            array_diff(
                array_unique($changedFields),
                [
                    'agency_id',
                    'faq_id',
                    'status'
                ]
            )
        );


        /*
         * Convert field names into readable labels.
         *
         * image becomes Image instead of Image Path.
         */
        $fieldLabels = collect($changedFields)
            ->map(function ($field) {

                if ($field === 'image') {
                    return 'Image';
                }

                return ucwords(
                    str_replace('_', ' ', $field)
                );

            });
    @endphp


    @if($imageWasRemoved)

        <div class="audit-summary">

            <span class="audit-summary-count">
                Image removed
            </span>

            <span
                class="audit-summary-fields"
                title="FAQ image was removed"
            >
                FAQ image was removed
            </span>

        </div>


    @elseif(empty($changedFields))

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

    @if(in_array($log->action, [
        'login',
        'logout',
        'admin_login',
        'admin_logout',
        'session_expired'
    ], true))

        <span class="change-status">
            @switch($log->action)

                @case('login')
                @case('admin_login')
                    Signed in
                    @break

                @case('logout')
                @case('admin_logout')
                    Signed out
                    @break

                @case('session_expired')
                    Session expired
                    @break

            @endswitch
        </span>

    @elseif($log->action === 'forward_support_response')

        @php
            $forwardedData = $log->audit_new_data;
            $forwardedResponse = is_array($forwardedData)
                ? ($forwardedData['official_response'] ?? null)
                : null;
            $componentCount = is_array($forwardedResponse)
                ? count($forwardedResponse['components'] ?? [])
                : 0;
        @endphp

        <span
            class="change-status"
            title="{{ $componentCount }} response component(s) forwarded"
        >
            Response forwarded
            @if($componentCount > 0)
                · {{ $componentCount }} component{{ $componentCount === 1 ? '' : 's' }}
            @endif
        </span>

    @elseif($log->old_value && $log->new_value)

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

        <span class="change-status" title="{{ $log->description ?? $log->action_label }}">
            {{ $log->description ?: $log->action_label }}
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



<div id="logModal" class="log-modal" role="dialog" aria-modal="true" aria-labelledby="logModalTitle">
    <div class="modal-content">
        <div class="modal-header">
            <div>
                <span id="logModalGroup" class="modal-kicker">Activity</span>
                <h3 id="logModalTitle">Log Details</h3>
            </div>
            <button id="closeModal" type="button" aria-label="Close log details">&times;</button>
        </div>

        <div class="modal-body">
            <div id="modalSummary" class="log-detail-summary"></div>

            <div class="log-detail-grid">
                <div class="log-detail-item"><span>Actor</span><strong id="modalActor"></strong></div>
                <div class="log-detail-item"><span>Target</span><strong id="modalTarget"></strong></div>
                <div class="log-detail-item"><span>Page</span><strong id="modalPage"></strong></div>
                <div class="log-detail-item"><span>Role</span><strong id="modalRole"></strong></div>
                <div class="log-detail-item"><span>IP Address</span><strong id="modalIp"></strong></div>
                <div class="log-detail-item log-detail-item-wide"><span>Device</span><strong id="modalDevice"></strong></div>
            </div>

            <div class="log-state-grid">
                <div class="modal-block">
                    <div class="modal-label">Previous State</div>
                    <div id="modalOld" class="modal-box old"></div>
                </div>
                <div class="modal-block">
                    <div class="modal-label">Resulting State</div>
                    <div id="modalNew" class="modal-box new"></div>
                </div>
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
    const modalSummary = document.getElementById('modalSummary');
    const modalGroup = document.getElementById('logModalGroup');
    const modalTitle = document.getElementById('logModalTitle');
    const modalActor = document.getElementById('modalActor');
    const modalTarget = document.getElementById('modalTarget');
    const modalPage = document.getElementById('modalPage');
    const modalRole = document.getElementById('modalRole');
    const modalIp = document.getElementById('modalIp');
    const modalDevice = document.getElementById('modalDevice');
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
       OFFICIAL SUPPORT RESPONSE FORMATTER
       ========================================================= */

    function formatOfficialResponse(response, counterpart = {}) {

        if (!response || typeof response !== 'object') {
            return `
                <div class="data-value">
                    No response recorded
                </div>
            `;
        }

        const status = response.status || 'Unknown';
        const forwardedAt = response.forwarded_at || 'Not recorded';
        const respondedAt = response.responded_at;
        const counterpartRespondedAt = counterpart?.responded_at;
        const reason = response.follow_up_reason;
        const counterpartReason = counterpart?.follow_up_reason;
        const components = Array.isArray(response.components)
            ? response.components
            : [];

        const componentHtml = components.length
            ? components.map((component, index) => {
                const type = component?.type || 'component';
                const label = component?.label || '';
                const isAttachment = component?.attachment === true;
                const content = component?.content;

                let valueHtml;

                if (isAttachment) {
                    valueHtml = `
                        <div class="response-attachment">
                            <i class="ph-light ph-paperclip" aria-hidden="true"></i>
                            Private ${escapeHtml(type)} attachment
                        </div>
                    `;
                } else if (content === null || content === undefined || content === '') {
                    valueHtml = '<div class="data-value">No value</div>';
                } else {
                    valueHtml = `
                        <div class="data-value audit-long-text">
                            ${escapeHtml(content)}
                        </div>
                    `;
                }

                return `
                    <div class="response-component">
                        <div class="response-component-header">
                            <span>${index + 1}. ${escapeHtml(type)}</span>
                            ${label ? `<span class="response-component-label">${escapeHtml(label)}</span>` : ''}
                        </div>
                        ${valueHtml}
                    </div>
                `;
            }).join('')
            : `
                <div class="data-value">
                    No response components recorded
                </div>
            `;

        const respondedAtHtml =
            respondedAt || counterpartRespondedAt
                ? `
                    <div class="data-row">
                        <div class="data-label">Citizen Responded At</div>
                        <div class="data-value">${escapeHtml(respondedAt || 'Not recorded')}</div>
                    </div>
                `
                : '';

        const reasonHtml =
            reason || counterpartReason
                ? `
                    <div class="data-row">
                        <div class="data-label">Follow-up Reason</div>
                        <div class="data-value audit-long-text">${escapeHtml(reason || 'Not recorded')}</div>
                    </div>
                `
                : '';

        return `
            <div class="official-response-audit">
                <div class="data-row">
                    <div class="data-label">Response Status</div>
                    <div class="data-value">${escapeHtml(status)}</div>
                </div>

                <div class="data-row">
                    <div class="data-label">Forwarded At</div>
                    <div class="data-value">${escapeHtml(forwardedAt)}</div>
                </div>

                ${respondedAtHtml}

                ${reasonHtml}

                <div class="data-row">
                    <div class="data-label">Response Components</div>
                    <div class="response-components-list">
                        ${componentHtml}
                    </div>
                </div>
            </div>
        `;
    }


    /* =========================================================
       GENERIC OBJECT FORMATTER
       ========================================================= */

    function hasAuditValue(value) {
        return !(
            value === null ||
            value === undefined ||
            value === ''
        );
    }


    function formatObject(object, counterpart = {}) {

        const hasStructuredResponse =
            !!object?.official_response ||
            !!counterpart?.official_response;

        return Object.entries(object).filter(([key, value]) => {

            /*
             * The structured response tables are now authoritative for
             * official responses. The legacy answer fields are still
             * supported for older/manual reply records, but must not be
             * shown beside a structured response.
             */
            if (
                hasStructuredResponse &&
                ['answer', 'answer_image'].includes(key)
            ) {
                return false;
            }

            const counterpartValue = counterpart?.[key];

            /*
             * Do not display fields that are empty on BOTH sides.
             *
             * This removes misleading "No value" rows for fields that are
             * simply not applicable to this lifecycle event, while still
             * preserving a cleared value when it changed from populated
             * -> empty.
             */
            return hasAuditValue(value) || hasAuditValue(counterpartValue);

        }).map(([key, value]) => {

            const label = formatLabel(key);
            const counterpartValue = counterpart?.[key];

            /*
             * Official support responses have their own renderer because
             * the actual answer is a sequence of typed components.
             */
            if (key === 'official_response') {
                return `
                    <div class="data-row">
                        <div class="data-label">${escapeHtml(label)}</div>
                        ${formatOfficialResponse(value, counterpartValue)}
                    </div>
                `;
            }


            /*
             * CONTACTS GET THEIR OWN SPECIAL FORMAT.
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
             * Nested arrays/objects retain their structure and compare
             * against the corresponding value from the opposite state.
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
                            ${formatNestedValue(value, counterpartValue)}
                        </div>
                    </div>
                `;
            }


            /*
             * A null/empty value is meaningful when the opposite state
             * contains a value: it means the field was cleared or was not
             * yet recorded at that point in the lifecycle.
             */
            const displayValue = hasAuditValue(value)
                ? value
                : 'Not recorded';

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

    function formatNestedValue(value, counterpart = {}) {

        if (Array.isArray(value)) {
            return value.map((item, index) => {

                const counterpartItem = Array.isArray(counterpart)
                    ? counterpart[index]
                    : {};

                if (
                    typeof item === 'object' &&
                    item !== null
                ) {
                    return `
                        <div class="nested-object">
                            ${formatObject(item, counterpartItem || {})}
                        </div>
                    `;
                }

                return `
                    <div class="data-value">
                        ${escapeHtml(
                            hasAuditValue(item)
                                ? item
                                : 'Not recorded'
                        )}
                    </div>
                `;

            }).join('');
        }


        if (
            typeof value === 'object' &&
            value !== null
        ) {
            return `
                <div class="nested-object">
                    ${formatObject(value, counterpart || {})}
                </div>
            `;
        }


        return escapeHtml(
            hasAuditValue(value)
                ? value
                : 'Not recorded'
        );
    }


    /* =========================================================
       MAIN AUDIT DATA FORMATTER
       ========================================================= */

    function parseAuditValue(value) {

        if (
            value === null ||
            value === undefined ||
            value === '' ||
            value === 'null'
        ) {
            return null;
        }

        if (typeof value !== 'string') {
            return value;
        }

        try {
            let parsed = JSON.parse(value);

            /*
             * Handle accidentally double-encoded JSON from older records.
             */
            if (typeof parsed === 'string') {
                try {
                    parsed = JSON.parse(parsed);
                } catch {
                    // Keep the first decoded value.
                }
            }

            return parsed;
        } catch {
            return value;
        }
    }


    function formatData(value, counterpart = null) {

        const parsed = parseAuditValue(value);
        const parsedCounterpart = parseAuditValue(counterpart);

        /*
         * NULL means there was no previous/new state.
         */
        if (
            parsed === null ||
            parsed === undefined ||
            parsed === ''
        ) {
            return `
                <div class="data-value">
                    No recorded data
                </div>
            `;
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
         */
        if (Array.isArray(parsed)) {

            if (parsed.length === 0) {
                return `
                    <div class="data-value">
                        No recorded data
                    </div>
                `;
            }


            /*
             * If the array looks like a contact collection,
             * render it using the specialized contact UI.
             */
            if (
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


            return formatNestedValue(
                parsed,
                Array.isArray(parsedCounterpart)
                    ? parsedCounterpart
                    : []
            );
        }


        /*
         * Empty objects are equivalent to an empty audit payload.
         */
        if (Object.keys(parsed).length === 0) {
            return `
                <div class="data-value">
                    No recorded data
                </div>
            `;
        }

        return formatObject(
            parsed,
            parsedCounterpart && typeof parsedCounterpart === 'object'
                ? parsedCounterpart
                : {}
        );
    }


    /* =========================================================
       OPEN MODAL
       ========================================================= */

    document.querySelectorAll('.log-row').forEach(row => {

        row.addEventListener('click', () => {

            const oldVal = row.dataset.old;
            const newVal = row.dataset.new;

            const actionLabel = row.querySelector('.badge.action')?.textContent?.trim() || row.dataset.action;
            const description = row.dataset.description?.trim() || 'No additional description recorded.';

            modalGroup.textContent = row.dataset.group || 'Activity';
            modalTitle.textContent = actionLabel;
            modalSummary.textContent = description;
            modalActor.textContent = row.dataset.actor || 'Unknown actor';
            modalTarget.textContent = row.dataset.target || 'System';
            modalPage.textContent = row.dataset.page || 'System';
            modalRole.textContent = row.dataset.role || 'Unknown';
            modalIp.textContent = row.dataset.ip || 'Not recorded';
            modalDevice.textContent = row.dataset.device || 'Not recorded';


            /*
             * Render the previous state first.
             */
            modalOld.innerHTML = formatData(oldVal, newVal);


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

                modalNew.innerHTML = formatData(newVal, oldVal);

            } else {

                /*
                 * Normal update action.
                 */
                modalNew.innerHTML = formatData(newVal, oldVal);
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