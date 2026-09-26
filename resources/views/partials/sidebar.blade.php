<aside class="sidebar" aria-label="Administrator navigation">
    <button type="button" class="sidebar-mobile-close" data-admin-shell-close aria-label="Close navigation">
        <i class="ph-light ph-x" aria-hidden="true"></i>
    </button>

    <div class="sidebar-main">
        <a href="{{ route('admin.dashboard') }}" class="sidebar-brand" aria-label="KNOWURLOCAL Dashboard">
            <span class="sidebar-brand-copy">
                <strong>KNOWURLOCAL</strong>
                <small>Admin workspace</small>
            </span>
        </a>

        <nav class="admin-nav">
            <span class="nav-section-label">Workspace</span>

            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="ph-light ph-house"></i><span>Dashboard</span>
            </a>

            <a href="{{ route('admin.analytics') }}" class="{{ request()->routeIs('admin.analytics') ? 'active' : '' }}">
                <i class="ph-light ph-chart-line-up"></i><span>Analytics</span>
            </a>

            <a href="{{ route('admin.nga') }}" class="{{ request()->routeIs('admin.nga') ? 'active' : '' }}">
                <i class="ph-light ph-buildings"></i><span>NGA &amp; NGO</span>
            </a>

            <a href="{{ route('admin.categories') }}" class="{{ request()->routeIs('admin.categories') ? 'active' : '' }}">
                <i class="ph-light ph-tag"></i><span>Categories</span>
            </a>

            <a href="{{ route('faqs.index') }}" class="{{ request()->routeIs('faqs.index') ? 'active' : '' }}">
                <i class="ph-light ph-chat-centered-text"></i><span>FAQ</span>
            </a>

            <span class="nav-section-label nav-section-label-spaced">Operations</span>

            @if(auth()->check() && in_array(auth()->user()->role, ['admin','superadmin']))
                <a href="{{ route('admin.support.requests') }}" class="{{ request()->routeIs('admin.support.requests') ? 'active' : '' }}">
                    <i class="ph-light ph-chat-circle"></i><span>Support Requests</span>
                    <span
                        class="sidebar-notification js-support-pending-badge"
                        hidden
                        aria-live="polite"
                    >0</span>
                </a>
            @endif

            @if(auth()->check() && auth()->user()->role === 'superadmin')
                <a href="{{ route('admin.admins') }}" class="{{ request()->routeIs('admin.admins') ? 'active' : '' }}">
                    <i class="ph-light ph-users-three"></i><span>Admins</span>
                </a>
            @endif

            @if(auth()->check() && in_array(auth()->user()->role, ['admin','superadmin']))
                <a href="{{ route('admin.users') }}" class="{{ request()->routeIs('admin.users') ? 'active' : '' }}">
                    <i class="ph-light ph-user"></i><span>Users</span>
                </a>
            @endif

            <span class="nav-section-label nav-section-label-spaced">Audit</span>

            <a href="{{ route('admin.logs') }}" class="{{ request()->routeIs('admin.logs') ? 'active' : '' }}">
                <i class="ph-light ph-clock-counter-clockwise"></i><span>Activity Logs</span>
            </a>

            <a href="{{ route('admin.chatbot.logs') }}" class="{{ request()->routeIs('admin.chatbot.logs') ? 'active' : '' }}">
                <i class="ph-light ph-chats-circle"></i><span>Chatbot Logs</span>
            </a>
        </nav>
    </div>

    <div class="sidebar-footer">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="logout-btn">
                <i class="ph-light ph-sign-out"></i>
                <span>Sign out</span>
            </button>
        </form>
    </div>
</aside>
