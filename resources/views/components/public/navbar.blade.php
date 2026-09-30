<header class="site-header">

    <div class="nav-group">

        {{-- =====================================================
             SEARCH AREA
             ===================================================== --}}
        <div class="search-area">

            <div class="logo-search">

                {{-- BRAND --}}
                <a
                    href="{{ route('map') }}"
                    class="header-brand"
                    aria-label="KNOWURLOCAL Map"
                >
                    <span class="logo-short">KYL</span>
                    <span class="logo-full">KNOWURLOCAL</span>
                </a>


                {{-- SEARCH --}}
                @if(!isset($hideSearch))

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

                        <div
                            id="searchResults"
                            class="search-results"
                            role="listbox"
                            aria-label="Agency search results"
                        ></div>

                    </div>

                @endif

            </div>


            {{-- =================================================
                 CATEGORY FILTERS
                 ================================================= --}}
            @if(!isset($hideSearch))

                <div
                    id="categoryFilters"
                    class="category-filters"
                    aria-label="Agency category filters"
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
                data-inquiry-notification-badge
                data-count="{{ $unreadInquiryCount ?? 0 }}"
                aria-live="polite"
                aria-atomic="true"
                @if(($unreadInquiryCount ?? 0) <= 0) hidden @endif
            >{{ ($unreadInquiryCount ?? 0) > 99 ? '99+' : ($unreadInquiryCount ?? 0) }}</span>

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
                <i
                    class="ph-light ph-map-trifold"
                    aria-hidden="true"
                ></i>

                <span>Map</span>
            </a>


            {{-- =================================================
                 ABOUT
                 ================================================= --}}
            <a
                href="{{ url('about') }}"
                class="nav-link {{ request()->is('about') ? 'active' : '' }}"
            >
                <i
                    class="ph-light ph-info"
                    aria-hidden="true"
                ></i>

                <span>About</span>
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

                        <i
                            class="ph-light ph-user"
                            aria-hidden="true"
                        ></i>

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


                    <i
                        class="ph-light ph-caret-down account-chevron"
                        aria-hidden="true"
                    ></i>

                </div>


                {{-- =================================================
                     ACCOUNT DROPDOWN
                     ================================================= --}}
                <div
                    class="account-dropdown"
                    id="accountDropdown"
                >

                    <a
                        href="{{ route('user.inquiries') }}"
                        class="dropdown-item inquiry-link"
                    >
                        <span class="dropdown-item-label">

                            <i
                                class="ph-light ph-chats-circle"
                                aria-hidden="true"
                            ></i>

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
                            <i
                                class="ph-light ph-sign-out"
                                aria-hidden="true"
                            ></i>

                            <span>Sign out</span>
                        </button>

                    </form>

                </div>

            </div>

        </nav>

    </div>

</header>