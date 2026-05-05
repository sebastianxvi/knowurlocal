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
                    <th>Agency</th>
                    <th>Page</th>
                    <th>Date</th>
                </tr>
            </thead>

            <tbody>

                @forelse($logs as $log)
                <tr class="log-row"
                    data-old='@json($log->old_value)'
                    data-new='@json($log->new_value)'
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

                    <td>
                        @if($log->target_user_id)
                            <span class="target-user">
                                {{ $log->targetUser->email ?? '-' }}
                            </span>
                        @else
                            -
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

        {{-- UPDATE --}}
        @case('update_agency') ph-pencil-simple @break
        @case('update_faq') ph-pencil-simple-line @break

        {{-- DELETE --}}
        @case('delete_agency') ph-trash @break
        @case('delete_faq') ph-trash @break

        {{-- ADMIN --}}
        @case('approve_admin') ph-check @break
        @case('promote_admin') ph-arrow-up-right @break
        @case('demote_admin') ph-arrow-down-right @break
        @case('delete_admin') ph-user-minus @break

        {{-- DEFAULT --}}
        @default ph-circle

    @endswitch
"></i>

                            {{ $log->action_label }}
                        </span>
                    </td>

                    <td>
    @if($log->old_value && $log->new_value)

        @php
            $old = $log->old_value;
            $new = $log->new_value;

            // detect role change
            $isRole = str_contains(strtolower($old), 'role') || str_contains(strtolower($new), 'role');

            // detect JSON-like content
            $isJson = str_contains($old, '{') || str_contains($new, '{');
        @endphp

        <div class="change-box">

            @if($isRole)

                <!-- ✅ SHOW ROLE -->
                <span class="old">{{ $old }}</span>

                <i class="ph-light ph-arrow-right"></i>

                <span class="new">{{ $new }}</span>

            @elseif($isJson)

                <!-- 🔒 HIDE JSON COMPLETELY -->
                <span class="old">...</span>

                <i class="ph-light ph-arrow-right"></i>

                <span class="new">...</span>

            @else

                <!-- fallback (normal short text) -->
                <span class="old">
                    {{ strlen($old) > 15 ? substr($old,0,15).'...' : $old }}
                </span>

                <i class="ph-light ph-arrow-right"></i>

                <span class="new">
                    {{ strlen($new) > 15 ? substr($new,0,15).'...' : $new }}
                </span>

            @endif

        </div>

    @else
        -
    @endif
</td>

                    <!-- AGENCY -->
                    <td>
                        {{ $log->agency->agency_name ?? '-' }}
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
    function formatData(value){

        try{
            let parsed = JSON.parse(value);

            // 🔒 handle double-encoded JSON
            if (typeof parsed === "string") {
                parsed = JSON.parse(parsed);
            }

            // fallback for non-object
            if (typeof parsed !== "object" || parsed === null || Array.isArray(parsed)) {
                return `<div class="data-value">${value}</div>`;
            }

            let html = '';

            Object.entries(parsed).forEach(([key, val]) => {

                const label = key
                    .replaceAll('_',' ')
                    .replace(/\b\w/g, l => l.toUpperCase());

                html += `
                    <div class="data-row">
                        <div class="data-label">${label}</div>
                        <div class="data-value">${val}</div>
                    </div>
                `;
            });

            return html;

        } catch {
            return `<div class="data-value">${value}</div>`;
        }
    }

    /* ================= OPEN MODAL ================= */
    document.querySelectorAll('.log-row').forEach(row => {

        row.addEventListener('click', () => {

            const oldVal = row.dataset.old;
            const newVal = row.dataset.new;

            modalOld.innerHTML = formatData(oldVal);
            modalNew.innerHTML = formatData(newVal);

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