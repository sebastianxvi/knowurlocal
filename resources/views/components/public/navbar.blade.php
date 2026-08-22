<header class="site-header">

    <div class="nav-group">

        <div class="search-area">

            {{-- SEARCH CAPSULE --}}
            <div class="logo-search">

                <div class="header-brand">
                    <span class="logo-short">KYL</span>
                    <span class="logo-full">KNOWURLOCAL</span>
                </div>

                @if(!isset($hideSearch))

                    <div class="search-form">

                        <input
                            type="text"
                            id="searchInput"
                            placeholder="Search agencies"
                        >

                        <button
                            type="button"
                            id="searchBtn"
                            aria-label="Search agencies"
                        >
                            <i class="ph-light ph-magnifying-glass"></i>
                        </button>

                        <div
                            id="searchResults"
                            class="search-results"
                        ></div>

                    </div>

                @endif

            </div>

            {{-- CATEGORY FILTERS --}}
            @if(!isset($hideSearch))

                <div
                    id="categoryFilters"
                    class="category-filters"
                    aria-label="Agency category filters"
                ></div>

            @endif

        </div>


        <button
    type="button"
    class="menu-toggle"
    id="menuToggle"
    aria-label="Open navigation menu"
>
    <i class="ph-light ph-list"></i>

    @if($hasUnreadInquiry ?? false)
        <span
            class="menu-notification-dot"
            aria-label="You have an unread inquiry response"
        ></span>
    @endif
</button>

        <nav class="nav-drawer" id="navDrawer">

            <h2 id="greet">
                Hi, {{ Auth::user()->first_name ?? 'User' }}!
            </h2>

            {{-- <a href="{{ url('home') }}" class="nav-link">
                <i class="ph-light ph-house"></i>
                Home
            </a> --}}

            {{-- <a href="{{ url('agencies') }}" class="nav-link">
                <i class="ph-light ph-buildings"></i>
                Agencies
            </a> --}}

            <a href="{{ url('map') }}" class="nav-link">
                <i class="ph-light ph-map-trifold"></i>
                Map
            </a>

            <a href="{{ url('about') }}" class="nav-link">
                <i class="ph-light ph-info"></i>
                About
            </a>

            <div class="nav-link account-wrapper">

                <div class="account-toggle" id="accountToggle">

    <span class="account-label">
        <i class="ph-light ph-user"></i>
        Account
    </span>

    @if($hasUnreadInquiry ?? false)
        <span
            class="account-notification-dot"
            aria-label="You have an unread inquiry response"
        ></span>
    @endif

    <i class="ph-light ph-caret-down account-chevron"></i>

</div>

                <div class="account-dropdown" id="accountDropdown">

                    <a
    href="{{ route('user.inquiries') }}"
    class="dropdown-item inquiry-link"
>
    <span>My Inquiries</span>

    @if($hasUnreadInquiry ?? false)
        <span
            class="inquiry-notification-dot"
            aria-label="You have an unread inquiry response"
        ></span>
    @endif
</a>

                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="dropdown-item logout">
                            Logout
                        </button>
                    </form>

                </div>

            </div>

        </nav>

    </div>

</header>