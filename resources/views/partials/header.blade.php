<header class="topbar">
    <div class="header-left">
        <button
            type="button"
            class="admin-mobile-menu"
            data-admin-shell-toggle
            aria-label="Open navigation"
            aria-expanded="false"
        >
            <i class="ph-light ph-list" aria-hidden="true"></i>
        </button>

        <div class="header-heading">
            <div class="header-context">
                KNOWURLOCAL <span aria-hidden="true">/</span> Admin Workspace
            </div>

            <h1 class="page-title">@yield('page-title')</h1>

            @hasSection('page-subtitle')
                <p class="page-subtitle">@yield('page-subtitle')</p>
            @endif
        </div>
    </div>

    <div class="header-right">
        <a
            href="{{ route('admin.support.requests') }}"
            class="header-quick-action {{ request()->routeIs('admin.support.requests') ? 'active' : '' }}"
            title="Support requests"
            aria-label="Open support requests"
        >
            <i class="ph-light ph-chat-circle-dots" aria-hidden="true"></i>
            <span
                class="header-quick-badge js-support-pending-badge"
                hidden
                aria-live="polite"
            >0</span>
        </a>
    </div>
</header>
