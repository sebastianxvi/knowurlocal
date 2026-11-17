<x-public.app-install-banner />

<header class="site-header">

    <div class="nav-group">

        {{-- =====================================================
             SEARCH AREA
             ===================================================== --}}
        <div class="search-area{{ request()->routeIs('map') && !isset($hideSearch) ? '' : ' search-area--brand-only' }}">

            <div class="logo-search{{ request()->routeIs('map') && !isset($hideSearch) ? '' : ' logo-search--brand-only' }}">

                {{-- BRAND --}}
                <a
                    href="{{ route('map') }}"
                    class="header-brand"
                    aria-label="KNOWURLOCAL Map"
                >
                    <img
                        src="{{ asset('images/knowurlocal-kyl-logo.svg') }}"
                        alt=""
                        class="header-brand-icon"
                        aria-hidden="true"
                        onerror="this.hidden=true; this.parentElement.classList.add('header-brand-logo-missing');"
                    >
                    <span class="logo-short header-brand-fallback">KYL</span>
                    <span class="logo-full header-brand-fallback">KNOWURLOCAL</span>
                </a>


                {{-- SEARCH --}}
                @if(request()->routeIs('map') && !isset($hideSearch))

                    <div class="search-form">

                        <input
                            type="search"
                            id="searchInput"
                            placeholder="Search agencies"
                            autocomplete="off"
                            spellcheck="false"
                            aria-label="Search agencies"
                        >

                        <button
                            type="button"
                            id="searchClearBtn"
                            class="search-clear-btn"
                            aria-label="Clear search"
                            title="Clear search"
                            hidden
                        >
                            <i
                                class="ph-light ph-x"
                                aria-hidden="true"
                            ></i>
                        </button>

                        <button
                            type="button"
                            id="searchBtn"
                            aria-label="Search agencies"
                            title="Search agencies"
                        >
                            <i
                                class="ph-light ph-magnifying-glass"
                                aria-hidden="true"
                            ></i>
                        </button>

                    </div>

                    <div
                        id="searchResults"
                        class="search-results"
                        role="listbox"
                        aria-label="Agency search results"
                    ></div>

                @endif

            </div>


            {{-- =================================================
                 ORGANIZATION TYPE + SERVICE CATEGORY FILTERS
                 ================================================= --}}
            @if(request()->routeIs('map') && !isset($hideSearch))

                <div
                    id="agencyTypeFilters"
                    class="agency-type-filters"
                    role="group"
                    aria-label="Organization type filters"
                ></div>

                <div
                    id="categoryFilters"
                    class="category-filters"
                    role="group"
                    aria-label="Service category filters"
                ></div>

            @endif

        </div>


        {{-- =====================================================
             MOBILE MENU TOGGLE
             ===================================================== --}}
        <button
            type="button"
            class="menu-toggle"
            id="menuToggle"
            aria-label="Open navigation menu"
            aria-controls="navDrawer"
            aria-expanded="false"
        >

            <i
                class="ph-light ph-list"
                aria-hidden="true"
            ></i>

            <span
                class="menu-notification-badge"
                data-user-notification-badge
                data-count="{{ $unreadNotificationCount ?? 0 }}"
                aria-live="polite"
                aria-atomic="true"
                @if(($unreadNotificationCount ?? 0) <= 0) hidden @endif
            >{{ ($unreadNotificationCount ?? 0) > 99 ? '99+' : ($unreadNotificationCount ?? 0) }}</span>

        </button>


        {{-- =====================================================
             NAVIGATION
             ===================================================== --}}
        <nav
            class="nav-drawer"
            id="navDrawer"
            aria-label="Main navigation"
        >

            {{-- MOBILE GREETING --}}
            <h2 id="greet">
                Hi, {{ Auth::user()->first_name ?? 'User' }}!
            </h2>


            {{-- =================================================
                 MAP
                 ================================================= --}}
            <a
                href="{{ route('map') }}"
                class="nav-link {{ request()->routeIs('map') ? 'active' : '' }}"
            >
                <span>Map</span>
            </a>


            {{-- =================================================
                 ABOUT
                 ================================================= --}}
            <a
                href="{{ url('about') }}"
                class="nav-link {{ request()->is('about') ? 'active' : '' }}"
            >
                <span>About</span>
            </a>


            {{-- =====================================================
                 NOTIFICATIONS
                 ===================================================== --}}
            <a
                href="{{ route('user.notifications.index') }}"
                class="nav-link notification-nav-link {{ request()->routeIs('user.notifications.*') ? 'active' : '' }}"
                aria-label="Notifications"
                title="Notifications"
            >
                <span>Notifications</span>
                <span
                    class="user-notification-nav-badge"
                    data-user-notification-badge
                    data-count="{{ $unreadNotificationCount ?? 0 }}"
                    aria-live="polite"
                    aria-atomic="true"
                    @if(($unreadNotificationCount ?? 0) <= 0) hidden @endif
                >{{ ($unreadNotificationCount ?? 0) > 99 ? '99+' : ($unreadNotificationCount ?? 0) }}</span>
            </a>


            {{-- =================================================
                 ACCOUNT
                 ================================================= --}}
            <div class="account-wrapper">

                <div
                    class="account-toggle"
                    id="accountToggle"
                    role="button"
                    tabindex="0"
                    aria-controls="accountDropdown"
                    aria-expanded="false"
                >

                    <span class="account-label">

                        <span>Account</span>

                    </span>


                    <span
                        class="account-notification-badge"
                        data-inquiry-notification-badge
                        data-count="{{ $unreadInquiryCount ?? 0 }}"
                        aria-live="polite"
                        aria-atomic="true"
                        @if(($unreadInquiryCount ?? 0) <= 0) hidden @endif
                    >{{ ($unreadInquiryCount ?? 0) > 99 ? '99+' : ($unreadInquiryCount ?? 0) }}</span>



                </div>


                {{-- =================================================
                     ACCOUNT DROPDOWN
                     ================================================= --}}
                <div
                    class="account-dropdown"
                    id="accountDropdown"
                >

                    <a
                        href="{{ route('user.profile') }}"
                        class="dropdown-item profile-link {{ request()->routeIs('user.profile') ? 'active' : '' }}"
                    >
                        <span class="dropdown-item-content">
                            <span class="dropdown-item-label">
                                <span>My Profile</span>
                            </span>
                        </span>
                    </a>

                    <a
                        href="{{ route('user.inquiries') }}"
                        class="dropdown-item inquiry-link {{ request()->routeIs('user.inquiries') ? 'active' : '' }}"
                    >
                        <span class="dropdown-item-content">
                            <span class="dropdown-item-label">
                                <span>My Inquiries</span>
                            </span>

                            <span
                            class="inquiry-notification-badge"
                            data-inquiry-notification-badge
                            data-count="{{ $unreadInquiryCount ?? 0 }}"
                            aria-live="polite"
                            aria-atomic="true"
                            @if(($unreadInquiryCount ?? 0) <= 0) hidden @endif
                            >{{ ($unreadInquiryCount ?? 0) > 99 ? '99+' : ($unreadInquiryCount ?? 0) }}</span>
                        </span>
                    </a>


                    <form
                        action="{{ route('logout') }}"
                        method="POST"
                    >

                        @csrf

                        <button
                            type="submit"
                            class="dropdown-item logout"
                        >
                            <span class="dropdown-item-content">
                                <span>Sign out</span>
                            </span>
                        </button>

                    </form>

                </div>

            </div>

        </nav>

    </div>

</header>

{{-- Shared confirmation modal used by the public sign-out action. --}}
@include('components.modal')
<script src="{{ asset('jsfiles/components/modal-system.js') }}?v={{ file_exists(public_path('jsfiles/components/modal-system.js')) ? filemtime(public_path('jsfiles/components/modal-system.js')) : time() }}"></script>
