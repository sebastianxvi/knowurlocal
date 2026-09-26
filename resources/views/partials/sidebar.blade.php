<aside class="sidebar">

    <div>
        <div class="logo">KNOWURLOCAL</div>

        <nav>

            <!-- Dashboard -->
            <a href="{{ route('admin.dashboard') }}"
               class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="ph-light ph-house"></i>
                Dashboard
            </a>

            <!-- Analytics -->
            <a href="{{ route('admin.analytics') }}"
               class="{{ request()->routeIs('admin.analytics') ? 'active' : '' }}">
                <i class="ph-light ph-chart-line-up"></i>
                <span class="nav-label">Analytics</span>
            </a>

           <!-- Agencies -->
            <a href="{{ route('admin.nga') }}"
            class="{{ request()->routeIs('admin.nga') ? 'active' : '' }}">
                <i class="ph-light ph-buildings"></i>
                NGA & NGO
            </a>

            <!-- Categories -->
            <a href="{{ route('admin.categories') }}"
            class="{{ request()->routeIs('admin.categories') ? 'active' : '' }}">
                <i class="ph-light ph-tag"></i>
                Categories
            </a>

            <!-- FAQ -->
            <a href="{{ route('faqs.index') }}"
               class="{{ request()->routeIs('faqs.index') ? 'active' : '' }}">
                <i class="ph-light ph-chat-centered-text"></i>
                FAQ
            </a>

            @auth
            @if(in_array(auth()->user()->role, ['admin', 'superadmin']))

            <a href="{{ route('admin.support.requests') }}"
            class="{{ request()->routeIs('admin.support.requests') ? 'active' : '' }}">
                <i class="ph-light ph-chat-circle"></i>
                <span class="nav-label">Support Requests</span>
                <span
                    class="nav-badge nav-badge-danger {{ ($unreadSupportRequests ?? 0) > 0 ? '' : 'is-hidden' }}"
                    id="support-request-badge"
                    data-count="{{ $unreadSupportRequests ?? 0 }}"
                    aria-label="{{ $unreadSupportRequests ?? 0 }} new support requests"
                >{{ ($unreadSupportRequests ?? 0) > 99 ? '99+' : ($unreadSupportRequests ?? 0) }}</span>
            </a>

            @endif
            @endauth

            @auth
            @if(auth()->user()->role === 'superadmin')

            <a href="{{ route('admin.admins') }}"
            class="{{ request()->routeIs('admin.admins') ? 'active' : '' }}">
                <i class="ph-light ph-users"></i>
                Admins
            </a>

            @endif
            @endauth

            @auth
            @if(in_array(auth()->user()->role, ['admin', 'superadmin']))

            <a href="{{ route('admin.users') }}"
            class="{{ request()->routeIs('admin.users') ? 'active' : '' }}">
                <i class="ph-light ph-user"></i>
                Users
            </a>

            @endif
            @endauth

            <!-- LOGS GROUP -->
            <div class="nav-group">

                <div class="nav-parent" title="Logs">
                    <i class="ph-light ph-clock-counter-clockwise"></i>
                    Logs
                </div>

                <div class="nav-children">

                    <!-- Activity Logs -->
                    <a href="{{ route('admin.logs') }}"
                       class="{{ request()->routeIs('admin.logs') ? 'active' : '' }}">
                        Activity Logs
                    </a>

                    <!-- Chatbot Logs -->
                    <a href="{{ route('admin.chatbot.logs') }}"
                       class="{{ request()->routeIs('admin.chatbot.logs') ? 'active' : '' }}">
                        Chatbot Logs
                    </a>

                </div>

            </div>

        </nav>
    </div>

    <!-- LOGOUT -->
<div class="logout">
    <form action="{{ route('logout') }}" method="POST">
        @csrf

        <button type="submit" class="logout-btn">
            <i class="ph-light ph-sign-out"></i>
            Logout
        </button>
    </form>
</div>

</aside>