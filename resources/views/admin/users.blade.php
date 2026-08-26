@extends('layouts.admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('cssfiles/theme.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/table.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/form-system.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/admin-modules.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/admin/users.css') }}">
@endpush

@section('title', 'KNOWURLOCAL | ' . ucfirst(auth()->user()->role) . ' Module')

@section('page-title', 'User Management')
@section('page-subtitle', 'View and manage registered users')

@section('content')

<div class="admin-page">

    {{-- ================= STATUS TABS ================= --}}
<div class="admin-status-tabs">

    {{-- ACTIVE --}}
    <a
        href="{{ route(
            'admin.users',
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


    {{-- DEACTIVATED --}}
    <a
        href="{{ route(
            'admin.users',
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

    <!-- ================= FILTER ================= -->
    <div class="filter-card">

        <form method="GET" action="{{ route('admin.users') }}" class="filter-bar">

            <!-- SEARCH -->
            <input 
                type="text" 
                name="search"
                placeholder="Search user..."
                value="{{ request('search') }}"
            >

            <!-- SORT -->
            <select name="sort">
                <option value="desc" {{ request('sort') == 'desc' ? 'selected' : '' }}>Newest First</option>
                <option value="asc" {{ request('sort') == 'asc' ? 'selected' : '' }}>Oldest First</option>
            </select>

            {{-- PRESERVE CURRENT STATUS TAB --}}
            <input
                type="hidden"
                name="status"
                value="{{ request('status') }}"
            >

            <button type="submit">Filter</button>

        </form>

    </div>

    <!-- ================= TABLE ================= -->
    <div class="table-wrapper">

        <table class="table">

            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Date Registered</th>

                    @if(auth()->user()->role === 'superadmin')
                        <th>Action</th>
                    @endif
                </tr>
            </thead>

            <tbody>

                @forelse($users as $user)
                <tr
                    class="user-row"

                    data-id="{{ $user->id }}"

                    data-name="{{ $user->first_name }} {{ $user->last_name }}"

                    data-email="{{ $user->email }}"

                    data-status="{{ $user->status }}"

                    data-last-login="{{ $user->last_login_at
                        ? $user->last_login_at->format('M d, Y, g:i A')
                        : 'Never'
                    }}"

                    data-date="{{ $user->created_at->format('M d, Y') }}"
                >
                    <!-- USER -->
                    <td>
                        <div class="actor-cell">
                            <span class="actor-name">
                                {{ $user->first_name }} {{ $user->last_name }}
                            </span>
                        </div>
                    </td>

                    <!-- EMAIL -->
                    <td>{{ $user->email }}</td>

                    <!-- ROLE -->
                    <td>
                        <span class="role-badge {{ $user->role }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>

                    <!-- STATUS -->
                    <td>
                        <span class="badge {{ $user->status ?? 'active' }}">
                            {{ ucfirst($user->status ?? 'active') }}
                        </span>
                    </td>

                    <!-- LAST LOGIN -->
                    <td>
                        @if($user->last_login_at)
                            {{ $user->last_login_at->format('M d, Y, g:i A') }}
                        @else
                            <span style="color:var(--text-muted);">
                                Never
                            </span>
                        @endif
                    </td>


                    <!-- DATE REGISTERED -->
                    <td>
                        {{ $user->created_at->format('M d, Y') }}
                    </td>

                    @if(auth()->user()->role === 'superadmin')

    <td class="tablebtn">

        @php
            /*
             * Prevent the currently authenticated account
             * from being modified or deleted.
             */
            $isSelf = $user->id === auth()->id();
        @endphp


        {{-- =================================================
             ACTIVE USER
             ================================================= --}}
        @if($user->status === 'active')

            @if(!$isSelf)

                <form
                    method="POST"
                    action="{{ route('admin.users.deactivate', $user->id) }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="btn btn-danger deactivate-user-btn"
                    >
                        <i class="ph-light ph-user-minus"></i>
                        Deactivate
                    </button>

                </form>

            @else

                <span style="font-size:12px; color:var(--text-muted);">
                    —
                </span>

            @endif


        {{-- =================================================
             DEACTIVATED USER
             ================================================= --}}
        @elseif($user->status === 'deactivated')

            @if(!$isSelf)

                {{-- REACTIVATE --}}
                <form
                    method="POST"
                    action="{{ route('admin.users.reactivate', $user->id) }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="btn btn-primary reactivate-user-btn"
                    >
                        <i class="ph-light ph-user-check"></i>
                        Reactivate
                    </button>

                </form>


                {{-- PERMANENT DELETE --}}
                <form
                    method="POST"
                    action="{{ route('admin.users.delete', $user->id) }}"
                >
                    @csrf
                    @method('DELETE')

                    <button
                        type="submit"
                        class="btn btn-danger delete-user-btn"
                    >
                        <i class="ph-light ph-trash"></i>
                        Delete Permanently
                    </button>

                </form>

            @endif

        @endif

    </td>

@endif

                </tr>
                @empty
                <tr>
                    <td colspan="5" class="empty">
                        No users found.
                    </td>
                </tr>
                @endforelse

            </tbody>

        </table>
        </div>

        <!-- ================= FOOTER ================= -->
        <div class="footer">

            <span class="result-info">
                Showing {{ $users->firstItem() ?? 0 }} 
                to {{ $users->lastItem() ?? 0 }} 
                of {{ $users->total() }} results
            </span>

            <div class="pagination-modern">

                @if ($users->onFirstPage())
                    <span class="arrow disabled">
                        <i class="ph-light ph-caret-left"></i>
                    </span>
                @else
                    <a href="{{ $users->previousPageUrl() }}" class="arrow">
                        <i class="ph-light ph-caret-left"></i>
                    </a>
                @endif

                <span class="page-indicator">
                    Page {{ $users->currentPage() }}
                </span>

                @if ($users->hasMorePages())
                    <a href="{{ $users->nextPageUrl() }}" class="arrow">
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




<!-- =========================================================
     USER DETAILS / INQUIRIES MODAL
     ========================================================= -->

<div
    id="user-modal-back"
    class="back"
    aria-hidden="true"
>
    <div
        class="modal user-details-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="user-modal-title"
    >

        <!-- ================= HEADER ================= -->

        <div class="modal-header">

            <h2 id="user-modal-title">
                User Overview
            </h2>

            <div class="modal-actions">

                <button
                    type="button"
                    onclick="closeUserModal()"
                    class="btn-cancel"
                >
                    Close
                </button>

            </div>

        </div>


        <!-- ================= BODY ================= -->

        <div class="user-modal-body">

            <!-- ================= USER INFO ================= -->

            <section class="user-profile-section">

                <div class="user-profile-main">

                    <div class="user-profile-icon">
                        <i class="ph-light ph-user"></i>
                    </div>

                    <div class="user-profile-copy">

                        <h3 id="user_name_display">
                            —
                        </h3>

                        <p id="user_email_display">
                            —
                        </p>

                    </div>

                </div>


                <!-- ACCOUNT STATUS -->

                <div
                    id="user_status_display"
                    class="user-account-status"
                >

                    <i class="ph-light ph-circle"></i>

                    <span>
                        —
                    </span>

                </div>

            </section>


            <!-- ================= ACCOUNT META ================= -->

            <section class="user-meta-grid">

                <!-- LAST LOGIN -->

                <div class="user-meta-item">

                    <span class="user-meta-label">
                        Last Login
                    </span>

                    <strong id="user_last_login">
                        —
                    </strong>

                </div>


                <!-- DATE REGISTERED -->

                <div class="user-meta-item">

                    <span class="user-meta-label">
                        Date Registered
                    </span>

                    <strong id="user_date">
                        —
                    </strong>

                </div>

            </section>


            <!-- ================= INQUIRY SECTION ================= -->

            <section class="user-inquiries-section">

                <div class="user-section-heading">

                    <div>

                        <span class="user-section-eyebrow">
                            Activity
                        </span>

                        <h3>
                            User Inquiries
                        </h3>

                    </div>

                </div>


                <!-- ================= INQUIRY SUMMARY ================= -->

                <div class="inquiry-summary">

                    <!-- TOTAL -->

                    <div class="inquiry-stat">

                        <span class="inquiry-stat-label">
                            Total
                        </span>

                        <strong id="inquiry-total">
                            0
                        </strong>

                    </div>


                    <!-- ANSWERED -->

                    <div class="inquiry-stat">

                        <span class="inquiry-stat-label">
                            Answered
                        </span>

                        <strong id="inquiry-answered">
                            0
                        </strong>

                    </div>


                    <!-- PENDING -->

                    <div class="inquiry-stat">

                        <span class="inquiry-stat-label">
                            Pending
                        </span>

                        <strong id="inquiry-pending">
                            0
                        </strong>

                    </div>

                </div>


                <!-- ================= INQUIRY LIST ================= -->

                <div id="user-inquiries-container">

                    <div class="user-inquiries-loading">
                        Loading inquiries...
                    </div>

                </div>

            </section>

        </div>

    </div>
</div>
</div>

@endsection

@push('scripts')

<script>
document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("user-modal-back");

    const nameInput = document.getElementById("user_name");
    const dateInput = document.getElementById("user_date");
    const container = document.getElementById("user-inquiries-container");

    function openUserModal(data){

    /*
     * Open the modal.
     */
    modal.classList.remove("hidden");

    modal.style.display = "flex";


    /*
     * Force the browser to recognize the initial
     * hidden state before starting the transition.
     */
    void modal.offsetWidth;


    /*
     * Start the modal entrance animation.
     */
    modal.classList.add("active");


    /*
     * Populate the account identity.
     */
    document.getElementById(
        "user_name_display"
    ).textContent = data.name;


    document.getElementById(
        "user_email_display"
    ).textContent = data.email;


    /*
     * Populate the account status.
     */
    const statusElement =
        document.getElementById(
            "user_status_display"
        );


    const statusText =
        statusElement.querySelector(
            "span"
        );


    const statusIcon =
        statusElement.querySelector(
            "i"
        );


    statusText.textContent =
        data.status === "deactivated"
            ? "Deactivated"
            : "Active";


    /*
     * Change only the semantic class.
     *
     * CSS will handle the actual appearance.
     */
    statusElement.classList.toggle(
        "deactivated",
        data.status === "deactivated"
    );


    statusElement.classList.toggle(
        "active",
        data.status === "active"
    );


    /*
     * Use contextual Phosphor Light icons.
     */
    statusIcon.className =
        data.status === "deactivated"
            ? "ph-light ph-user-minus"
            : "ph-light ph-user-check";


    /*
     * Populate account metadata.
     */
    document.getElementById(
        "user_last_login"
    ).textContent =
        data.lastLogin;


    document.getElementById(
        "user_date"
    ).textContent =
        data.date;


    /*
     * Reset inquiry counters while the request
     * is loading.
     */
    document.getElementById(
        "inquiry-total"
    ).textContent = "—";


    document.getElementById(
        "inquiry-answered"
    ).textContent = "—";


    document.getElementById(
        "inquiry-pending"
    ).textContent = "—";


    /*
     * Load this user's inquiries.
     */
    loadInquiries(data.id);
}

    async function loadInquiries(userId){

        container.innerHTML = `<p style="font-size:12px;">Loading...</p>`;

        try{
            const res = await fetch(`{{ url('/admin/users') }}/${userId}/inquiries`);
            const data = await res.json();

            renderInquiries(data.logs);

        }catch(err){
            container.innerHTML = `<p style="color:red;">Failed to load</p>`;
        }
    }

    function escapeUserText(value){

    /*
     * Convert null/undefined into an empty string.
     */
    const text =
        String(value ?? "");


    /*
     * Escape characters that have special meaning
     * inside HTML.
     */
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

    function renderInquiries(logs){

    /*
     * Make sure the response is actually an array.
     *
     * This prevents malformed API responses from breaking
     * the modal.
     */
    if (!Array.isArray(logs)) {

        logs = [];

    }


    /*
     * Calculate inquiry statistics.
     */
    const total =
        logs.length;


    const answered =
        logs.filter(
            log => log.status === "answered"
        ).length;


    const pending =
        logs.filter(
            log => log.status === "pending"
        ).length;


    /*
     * Update the summary.
     */
    document.getElementById(
        "inquiry-total"
    ).textContent = total;


    document.getElementById(
        "inquiry-answered"
    ).textContent = answered;


    document.getElementById(
        "inquiry-pending"
    ).textContent = pending;


    /*
     * Display an empty state when the user
     * has never submitted an inquiry.
     */
    if (!logs.length) {

        container.innerHTML = `
            <div class="user-inquiries-empty">

                <i class="ph-light ph-chat-circle-dots"></i>

                <strong>
                    No inquiries yet
                </strong>

                <span>
                    This user has not submitted
                    any questions.
                </span>

            </div>
        `;

        return;
    }


    /*
     * Build the inquiry list.
     */
    let html = "";


    logs.forEach(log => {

        /*
         * Determine whether the inquiry has
         * already received an answer.
         */
        const isAnswered =
            log.status === "answered";


        /*
         * Escape question/answer text before inserting
         * it into innerHTML.
         *
         * The API response contains user-generated
         * content, so it must never be inserted directly.
         */
        const question =
            escapeUserText(
                log.question
            );


        const answer =
            escapeUserText(
                log.answer || ""
            );


        /*
         * Format the inquiry date.
         */
        const date =
            new Date(
                log.created_at
            ).toLocaleDateString(
                undefined,
                {
                    month: "short",
                    day: "numeric",
                    year: "numeric"
                }
            );


        html += `

            <article class="user-inquiry-card">

                <!-- HEADER -->

                <div class="user-inquiry-header">

                    <div
                        class="user-inquiry-status
                        ${isAnswered ? "answered" : "pending"}"
                    >

                        <i class="ph-light
                            ${
                                isAnswered
                                    ? "ph-check-circle"
                                    : "ph-clock"
                            }
                        "></i>

                        <span>
                            ${
                                isAnswered
                                    ? "Answered"
                                    : "Pending"
                            }
                        </span>

                    </div>


                    <time>
                        ${date}
                    </time>

                </div>


                <!-- QUESTION -->

                <div class="user-inquiry-question">

                    ${question}

                </div>


                <!-- ANSWER -->

                ${
                    isAnswered

                    ? `
                        <div class="user-inquiry-answer">

                            ${answer}

                        </div>
                    `

                    : `
                        <div class="user-inquiry-pending">

                            <i class="ph-light ph-clock"></i>

                            <span>
                                Waiting for response
                            </span>

                        </div>
                    `
                }

            </article>
        `;
    });


    /*
     * Insert the finished inquiry list.
     */
    container.innerHTML = html;
}

    window.closeUserModal = function(){
        modal.classList.remove("active");

        setTimeout(() => {
            modal.style.display = "none";
        }, 200);
    };

    /* ================= ROW CLICK (FAQ STYLE) ================= */
    document.addEventListener("click", function (e) {

        const row = e.target.closest(".user-row");
        if (!row) return;

        if (e.target.closest("button") || e.target.closest("form")) return;

        openUserModal({

    id:
        row.dataset.id,

    name:
        row.dataset.name,

    email:
        row.dataset.email,

    status:
        row.dataset.status,

    lastLogin:
        row.dataset.lastLogin,

    date:
        row.dataset.date

});

    });

        // =========================================================
    // USER ACCOUNT LIFECYCLE CONFIRMATION
    // =========================================================

    /*
     * Handle account-management actions through the existing
     * KNOWURLOCAL alert modal system.
     *
     * The original Laravel form remains responsible for
     * actually performing the action.
     *
     * This means:
     *
     * 1. JavaScript only asks for confirmation.
     * 2. Laravel still receives the original POST/DELETE request.
     * 3. Laravel middleware/controller still handles authorization.
     * 4. CSRF protection remains unchanged.
     */
    document.addEventListener("submit", function (e) {

        /*
         * Find only forms belonging to user lifecycle actions.
         *
         * We intentionally target the form itself rather than
         * relying on button clicks. This also makes the behavior
         * work for keyboard-submitted forms.
         */
        const form = e.target.closest(
            "form:has(.deactivate-user-btn), " +
            "form:has(.reactivate-user-btn), " +
            "form:has(.delete-user-btn)"
        );


        /*
         * Ignore every other form on the page.
         */
        if (!form) {
            return;
        }


        /*
         * Prevent the browser from submitting immediately.
         *
         * The request will only continue after confirmation.
         */
        e.preventDefault();


        /*
         * Identify which action belongs to this form.
         */
        const deactivateBtn =
            form.querySelector(
                ".deactivate-user-btn"
            );


        const reactivateBtn =
            form.querySelector(
                ".reactivate-user-btn"
            );


        const deleteBtn =
            form.querySelector(
                ".delete-user-btn"
            );


        /*
         * Determine the affected user's name from the
         * table row containing the form.
         */
        const row =
            form.closest(".user-row");


        const userName =
            row?.dataset.name ||
            "this user";


        /*
         * Configuration for the confirmation modal.
         */
        let config = null;


        // =====================================================
        // DEACTIVATE
        // =====================================================

        if (deactivateBtn) {

            config = {

                title:
                    "Deactivate User",

                text:
                    `Are you sure you want to deactivate ${userName}? The account will remain in the system and can be reactivated later.`,

                icon:
                    "!",

                variant:
                    "danger",

                confirmText:
                    "Deactivate"

            };

        }


        // =====================================================
        // REACTIVATE
        // =====================================================

        else if (reactivateBtn) {

            config = {

                title:
                    "Reactivate User",

                text:
                    `Are you sure you want to reactivate ${userName}? This will restore the account's active status.`,

                icon:
                    "✓",

                variant:
                    "success",

                confirmText:
                    "Reactivate"

            };

        }


        // =====================================================
        // PERMANENT DELETE
        // =====================================================

        else if (deleteBtn) {

            config = {

                title:
                    "Delete User Permanently",

                text:
                    `This permanently deletes ${userName}'s account and cannot be undone. Are you sure you want to continue?`,

                icon:
                    "!",

                variant:
                    "danger",

                confirmText:
                    "Delete Permanently"

            };

        }


        /*
         * Safety guard.
         *
         * If somehow a matching form exists without a known
         * action button, do not submit it automatically.
         */
        if (!config) {

            console.error(
                "Unknown user lifecycle action."
            );

            return;
        }


        /*
         * Prevent accidental repeated submissions while the
         * confirmation dialog is open.
         */
        if (
            form.dataset.confirming === "true"
        ) {
            return;
        }


        form.dataset.confirming = "true";


        /*
         * Reuse the existing KNOWURLOCAL alert modal.
         */
        showAlertModal({

            title:
                config.title,

            text:
                config.text,

            icon:
                config.icon,

            variant:
                config.variant,

            confirmText:
                config.confirmText,

            showCancel:
                true,


            /*
             * Only submit the original form after the
             * administrator explicitly confirms.
             */
            onConfirm: () => {

                /*
                 * Keep the original Laravel form intact.
                 *
                 * This preserves:
                 *
                 * - CSRF protection
                 * - HTTP method spoofing
                 * - Laravel route handling
                 * - server-side authorization
                 */
                form.submit();

            }

        });

    });

    // =========================================================
// SESSION SUCCESS / ERROR ALERT
// =========================================================

/*
 * Laravel stores the result of the previous action
 * in the session flash data.
 *
 * Blade exposes that message to JavaScript only when
 * a message actually exists.
 */
@if(session('success'))

    showAlertModal({

        /*
         * Short title describing the result.
         */
        title: "Action Successful",

        /*
         * Use Laravel's escaped session value.
         *
         * json_encode safely transfers the string from
         * PHP into JavaScript.
         */
        text: @json(session('success')),

        /*
         * Success icon handled by the existing modal system.
         */
        icon: "✓",

        /*
         * Use the existing success styling.
         */
        variant: "success",

        /*
         * No confirmation is necessary for a result message.
         */
        confirmText: "OK",

        /*
         * Hide the Cancel button.
         */
        showCancel: false

    });

@endif


@if(session('error'))

    showAlertModal({

        /*
         * Error title.
         */
        title: "Action Failed",

        /*
         * Safely transfer the Laravel flash message.
         */
        text: @json(session('error')),

        /*
         * Existing warning/error icon.
         */
        icon: "!",

        /*
         * Use the existing danger styling.
         */
        variant: "danger",

        /*
         * Simple acknowledgement button.
         */
        confirmText: "OK",

        /*
         * This is informational, not a confirmation.
         */
        showCancel: false

    });

@endif

});
</script>

@endpush