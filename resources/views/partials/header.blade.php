<header class="topbar">
    <div class="header-left">
        <button
            type="button"
            class="admin-shell-toggle"
            data-admin-shell-toggle
            aria-label="Toggle navigation"
            aria-expanded="true"
        >
            <i class="ph-light ph-sidebar-simple" aria-hidden="true"></i>
        </button>
        <span class="header-context">KNOWURLOCAL · Admin</span>
        <div class="header-title-row">
            <h1 class="page-title">@yield('page-title')</h1>
            @if(($adminPendingSupportCount ?? 0) > 0 && !request()->routeIs('admin.support.requests'))
                <span class="header-alert-pill"><i class="ph-light ph-bell"></i>{{ $adminPendingSupportCount }} pending</span>
            @endif
        </div>
        @hasSection('page-subtitle')<p class="page-subtitle">@yield('page-subtitle')</p>@endif
    </div>
    <div class="header-right">
        <a href="{{ route('admin.support.requests') }}" class="header-quick-action {{ request()->routeIs('admin.support.requests') ? 'active' : '' }}" title="Support requests">
            <i class="ph-light ph-chat-circle-dots"></i>
            <span class="header-quick-badge js-support-pending-badge" {{ ($adminPendingSupportCount ?? 0) < 1 ? 'hidden' : '' }}>{{ ($adminPendingSupportCount ?? 0) > 99 ? '99+' : ($adminPendingSupportCount ?? 0) }}</span>
        </a>
        <div class="profile-text">
            @php $user = auth()->user(); $roleLabel = $user->role === 'superadmin' ? 'Superadmin' : 'Admin'; @endphp
            <span class="greeting">{{ $roleLabel }}</span>
            <span class="username">{{ $user->first_name }}</span>
        </div>
        <span class="header-avatar">{{ strtoupper(substr($user->first_name ?? 'A', 0, 1)) }}</span>
    </div>
</header>
