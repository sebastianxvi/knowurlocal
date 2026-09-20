<!DOCTYPE html>
<html lang="en">

<head>

    {{-- =========================================================
         DOCUMENT META
         ========================================================= --}}

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="KNOWURLOCAL helps San Jose, Occidental Mindoro citizens discover government agencies and local organizations, understand their services, navigate to their offices, and ask questions before making the trip."
    >

    <meta
        name="theme-color"
        content="#f8faf8"
    >

    <title>
        KNOWURLOCAL - Know Where To Go. Know What To Ask.
    </title>


    {{-- =========================================================
         PHOSPHOR ICONS
         ========================================================= --}}

    <link
        rel="stylesheet"
        href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/light/style.css"
    >


    {{-- =========================================================
         LANDING PAGE CSS
         ========================================================= --}}

    <link
        rel="stylesheet"
        href="{{ asset('cssfiles/public_user/landing.css') }}"
    >

</head>


<body>

    {{-- =========================================================
         LANDING PAGE ROOT

         data-landing-page is required by landing.js.
         The JavaScript intentionally stops if this element
         cannot be found.
         ========================================================= --}}

    <div
        class="landing-page"
        data-landing-page
    >


        {{-- =====================================================
             HEADER
             ===================================================== --}}

        <header
            class="landing-header"
            id="landing-header"
            data-landing-header
        >

            <div class="landing-header-inner">


                {{-- =================================================
                     BRAND
                     ================================================= --}}

                <a
                    href="{{ route('landing') }}"
                    class="landing-brand"
                    aria-label="KNOWURLOCAL home"
                >
                    KNOWURLOCAL
                </a>


                {{-- =================================================
                     DESKTOP NAVIGATION
                     ================================================= --}}

                <nav
                    class="landing-nav"
                    aria-label="Primary navigation"
                >

                    <a
                        href="#discover"
                        class="landing-nav-link"
                        data-nav-target="discover"
                    >
                        Discover
                    </a>

                    <a
                        href="#know"
                        class="landing-nav-link"
                        data-nav-target="know"
                    >
                        Know
                    </a>

                    <a
                        href="#navigate"
                        class="landing-nav-link"
                        data-nav-target="navigate"
                    >
                        Navigate
                    </a>

                    <a
                        href="#ask"
                        class="landing-nav-link"
                        data-nav-target="ask"
                    >
                        Ask
                    </a>

                </nav>


                {{-- =================================================
                     HEADER ACTIONS
                     ================================================= --}}

                <div class="landing-header-actions">

                    <a
                        href="{{ route('public.login') }}"
                        class="landing-login"
                    >
                        Sign in
                    </a>

                    <a
                        href="{{ route('public.login', ['register' => 1]) }}"
                        class="landing-button landing-button-primary"
                    >
                        Get started

                        <i class="ph-light ph-arrow-up-right"></i>
                    </a>

                </div>


                {{-- =================================================
                     MOBILE MENU BUTTON
                     ================================================= --}}

                <button
                    type="button"
                    class="landing-menu-toggle"
                    id="landing-menu-toggle"
                    data-menu-toggle
                    aria-label="Open navigation menu"
                    aria-expanded="false"
                    aria-controls="landing-mobile-menu"
                >
                    <i class="ph-light ph-list"></i>
                </button>

            </div>


            {{-- =================================================
                 MOBILE MENU
                 ================================================= --}}

            <div
                class="landing-mobile-menu"
                id="landing-mobile-menu"
                data-mobile-menu
                aria-hidden="true"
            >

                <a
                    href="#discover"
                    class="landing-mobile-link"
                    data-mobile-link
                >
                    Discover
                </a>

                <a
                    href="#know"
                    class="landing-mobile-link"
                    data-mobile-link
                >
                    Know
                </a>

                <a
                    href="#navigate"
                    class="landing-mobile-link"
                    data-mobile-link
                >
                    Navigate
                </a>

                <a
                    href="#ask"
                    class="landing-mobile-link"
                    data-mobile-link
                >
                    Ask
                </a>

                <a
                    href="{{ route('public.login') }}"
                    class="landing-mobile-link landing-mobile-cta"
                    data-mobile-link
                >
                    Sign in
                </a>

            </div>

        </header>



        {{-- =====================================================
             JOURNEY ROUTE

             JavaScript controls this SVG.

             IMPORTANT:
             The JS uses a normalized SVG coordinate system of:

                 width  = 100
                 height = 1000

             Therefore the SVG viewBox must match that system.
             ===================================================== --}}

        <div
            class="landing-journey"
            id="landing-route"
            data-journey
            aria-hidden="true"
        >

            <svg
                class="landing-route"
                id="landing-route-svg"
                data-route
                viewBox="0 0 100 1000"
                preserveAspectRatio="none"
            >

                <path
                    class="landing-route-base"
                    id="landing-route-base"
                    data-route-base
                    fill="none"
                    d=""
                ></path>

                <path
                    class="landing-route-progress"
                    id="landing-route-progress"
                    data-route-progress
                    fill="none"
                    d=""
                ></path>

            </svg>


            {{-- =================================================
                 JOURNEY MARKER
                 ================================================= --}}

            <div
                class="landing-route-marker"
                id="landing-route-marker"
                data-route-marker
            >

                <span class="landing-route-marker-ring"></span>

                <span class="landing-route-marker-core">
                    <i class="ph-light ph-arrow-down"></i>
                </span>

            </div>

        </div>



        {{-- =====================================================
             MAIN CONTENT
             ===================================================== --}}

        <main class="landing-main">


            {{-- =================================================
                 HERO
                 ================================================= --}}

            <section
                class="landing-hero"
                id="hero"
                data-hero
                data-route-milestone="discover"
            >

                <div
                    class="landing-container landing-hero-inner"
                >


                    {{-- =================================================
                         HERO COPY
                         ================================================= --}}

                    <div
                        class="landing-hero-copy"
                        data-reveal="left"
                    >

                        <div class="landing-eyebrow">

                            <span class="landing-eyebrow-dot"></span>

                            LOCAL INFORMATION, SIMPLIFIED

                        </div>


                        <h1 class="landing-hero-title">

                            Know where to go.

                            <span>
                                Know what to ask.
                            </span>

                        </h1>


                        <p class="landing-hero-description">

                            Find the government agencies and local
                            organizations you need in San Jose,
                            Occidental Mindoro - understand what they
                            offer, know when to visit, and get answers
                            before you make the trip.

                        </p>


                        <div class="landing-hero-actions">

                            <a
                                href="#discover"
                                class="landing-button landing-button-primary"
                            >
                                Explore local services

                                <i class="ph-light ph-arrow-down"></i>
                            </a>


                            <a
                                href="#ask"
                                class="landing-button landing-button-secondary"
                            >
                                <i class="ph-light ph-chats"></i>

                                Ask a question
                            </a>

                        </div>

                    </div>



                    {{-- =================================================
                         HERO VISUAL
                         ================================================= --}}

                    <div
                        class="landing-hero-visual"
                        data-reveal="right"
                    >

                        <div class="landing-hero-orbit">

                            <span
                                class="landing-orbit-dot landing-orbit-dot-one"
                            ></span>

                            <span
                                class="landing-orbit-dot landing-orbit-dot-two"
                            ></span>

                            <span
                                class="landing-orbit-dot landing-orbit-dot-three"
                            ></span>

                        </div>


                        <div class="landing-hero-preview">


                            {{-- PREVIEW HEADER --}}

                            <div class="landing-preview-header">

                                <div class="landing-preview-status">

                                    <span class="landing-status-dot"></span>

                                    KNOWURLOCAL

                                </div>

                                <i class="ph-light ph-sparkle"></i>

                            </div>


                            {{-- QUESTION --}}

                            <div
                                class="landing-preview-question"
                                data-reveal="up"
                                data-reveal-delay="1"
                            >

                                <span class="landing-preview-label">
                                    YOU
                                </span>

                                <p>
                                    Where can I get help with
                                    government documents?
                                </p>

                            </div>


                            {{-- ANSWER --}}

                            <div
                                class="landing-preview-answer"
                                data-reveal="up"
                                data-reveal-delay="2"
                            >

                                <span class="landing-preview-label">
                                    KNOWURLOCAL
                                </span>

                                <p>
                                    Explore nearby agencies, check
                                    their services and office hours,
                                    then view their location before
                                    visiting.
                                </p>

                            </div>


                            {{-- PREVIEW ACTION --}}

                            <div
                                class="landing-preview-actions"
                                data-reveal="up"
                                data-reveal-delay="3"
                            >

                                <span>
                                    FIND THE RIGHT OFFICE
                                </span>

                                <i class="ph-light ph-arrow-up-right"></i>

                            </div>

                        </div>

                    </div>

                </div>

            </section>



            {{-- =================================================
                 DISCOVER
                 ================================================= --}}

            <section
                class="landing-section landing-discover"
                id="discover"
                data-route-milestone="discover"
            >

                <div
                    class="landing-container landing-section-inner"
                >


                    {{-- COPY --}}

                    <div
                        class="landing-section-copy"
                        data-reveal="left"
                    >

                        <span class="landing-section-kicker">

                            <span>
                                01
                            </span>

                            DISCOVER

                        </span>


                        <h2 class="landing-section-title">

                            Start with
                            what you need.

                        </h2>


                        <p class="landing-section-description">

                            Instead of searching through scattered
                            pages or asking around, begin with the
                            type of organization you are looking for.

                        </p>

                    </div>



                    {{-- PANEL --}}

                    <div
                        class="landing-discover-panel"
                        data-reveal="right"
                    >

                        <span class="landing-panel-overline">
                            EXPLORE
                        </span>


                        <div class="landing-panel-heading">

                            <div>

                                <h3>
                                    What are you looking for?
                                </h3>

                            </div>

                            <i class="ph-light ph-arrow-up-right"></i>

                        </div>


                        <div class="landing-discover-options">


                            {{-- GOVERNMENT AGENCIES --}}

                            <a
                                href="{{ route('map') }}"
                                class="landing-discover-option"
                                data-reveal="right"
                                data-reveal-delay="1"
                            >

                                <div class="landing-option-icon">

                                    <i class="ph-light ph-buildings"></i>

                                </div>


                                <div class="landing-option-content">

                                    <strong>
                                        Government agencies
                                    </strong>

                                    <small>
                                        Find national and local
                                        government offices and the
                                        services they provide.
                                    </small>

                                </div>


                                <i class="ph-light ph-arrow-up-right"></i>

                            </a>



                            {{-- LOCAL ORGANIZATIONS --}}

                            <a
                                href="{{ route('map') }}"
                                class="landing-discover-option"
                                data-reveal="right"
                                data-reveal-delay="2"
                            >

                                <div class="landing-option-icon">

                                    <i class="ph-light ph-hand-heart"></i>

                                </div>


                                <div class="landing-option-content">

                                    <strong>
                                        Local organizations
                                    </strong>

                                    <small>
                                        Explore NGOs and community
                                        organizations serving San Jose.
                                    </small>

                                </div>


                                <i class="ph-light ph-arrow-up-right"></i>

                            </a>

                        </div>

                    </div>

                </div>

            </section>



            {{-- =================================================
                 KNOW
                 ================================================= --}}

            <section
                class="landing-section landing-know"
                id="know"
                data-route-milestone="know"
            >

                <div
                    class="landing-container landing-section-inner landing-section-inner-reverse"
                >


                    {{-- COPY --}}

                    <div
                        class="landing-section-copy"
                        data-reveal="right"
                    >

                        <span class="landing-section-kicker">

                            <span>
                                02
                            </span>

                            KNOW

                        </span>


                        <h2 class="landing-section-title">

                            Know before
                            you go.

                        </h2>


                        <p class="landing-section-description">

                            Get the practical information you need
                            before stepping outside your home -
                            from services and schedules to contact
                            details.

                        </p>

                    </div>



                    {{-- INFORMATION PANEL --}}

                    <div
                        class="landing-information-panel"
                        data-reveal="left"
                    >

                        <span class="landing-panel-overline">
                            ORGANIZATION DETAILS
                        </span>


                        <div class="landing-information-heading">

                            <div>

                                <h3>
                                    Everything in one place.
                                </h3>

                            </div>

                            <i class="ph-light ph-info"></i>

                        </div>


                        <div class="landing-information-list">


                            {{-- SERVICES --}}

                            <div
                                class="landing-information-item"
                                data-reveal="up"
                                data-reveal-delay="1"
                            >

                                <div class="landing-information-icon">

                                    <i class="ph-light ph-list-checks"></i>

                                </div>


                                <div>

                                    <strong>
                                        Services offered
                                    </strong>

                                    <p>
                                        Understand what an office or
                                        organization can help you with.
                                    </p>

                                </div>

                            </div>



                            {{-- OFFICE HOURS --}}

                            <div
                                class="landing-information-item"
                                data-reveal="up"
                                data-reveal-delay="2"
                            >

                                <div class="landing-information-icon">

                                    <i class="ph-light ph-clock"></i>

                                </div>


                                <div>

                                    <strong>
                                        Office hours
                                    </strong>

                                    <p>
                                        Check schedules before making
                                        the trip.
                                    </p>

                                </div>

                            </div>



                            {{-- CONTACT --}}

                            <div
                                class="landing-information-item"
                                data-reveal="up"
                                data-reveal-delay="3"
                            >

                                <div class="landing-information-icon">

                                    <i class="ph-light ph-phone"></i>

                                </div>


                                <div>

                                    <strong>
                                        Contact information
                                    </strong>

                                    <p>
                                        Find available phone numbers,
                                        email addresses, and other
                                        details.
                                    </p>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </section>



            {{-- =================================================
                 NAVIGATE
                 ================================================= --}}

            <section
                class="landing-section landing-navigate"
                id="navigate"
                data-route-milestone="navigate"
            >

                <div
                    class="landing-container landing-section-inner"
                >


                    {{-- COPY --}}

                    <div
                        class="landing-section-copy"
                        data-reveal="left"
                    >

                        <span class="landing-section-kicker">

                            <span>
                                03
                            </span>

                            NAVIGATE

                        </span>


                        <h2 class="landing-section-title">

                            Know exactly
                            where to go.

                        </h2>


                        <p class="landing-section-description">

                            View an organization's location on the
                            map and use navigation features to make
                            your journey easier.

                        </p>


                        <a
    href="{{ route('map') }}"
    class="landing-inline-link"
>
    Open the map

    <i class="ph-light ph-arrow-up-right"></i>
</a>

                    </div>



                    {{-- LOCATION PANEL --}}

                    <div
                        class="landing-location-panel"
                        data-reveal="scale"
                    >


                        {{-- MAP --}}

                        <div class="landing-location-map">

                            <div class="landing-map-grid"></div>


                            {{-- DECORATIVE ROADS --}}

                            <span
                                class="landing-map-road landing-map-road-one"
                            ></span>

                            <span
                                class="landing-map-road landing-map-road-two"
                            ></span>

                            <span
                                class="landing-map-road landing-map-road-three"
                            ></span>


                            {{-- LOCATION MARKER --}}

                            <div class="landing-map-marker">

                                <span></span>

                                <i class="ph-light ph-map-pin"></i>

                            </div>

                        </div>


                        {{-- LOCATION DETAILS --}}

                        <div class="landing-location-details">

                            <span class="landing-panel-overline">
                                LOCATION
                            </span>


                            <h3>
                                San Jose,
                                Occidental Mindoro
                            </h3>


                            <p>
                                Find participating government agencies
                                and local organizations throughout
                                the municipality.
                            </p>


                            <a
                                href="{{ route('map') }}"
                                class="landing-location-link"
                            >

                                View locations

                                <i class="ph-light ph-arrow-up-right"></i>

                            </a>

                        </div>

                    </div>

                </div>

            </section>



            {{-- =================================================
                 ASK
                 ================================================= --}}

            <section
                class="landing-section landing-ask"
                id="ask"
                data-route-milestone="ask"
            >

                <div
                    class="landing-container landing-section-inner"
                >


                    {{-- COPY --}}

                    <div
                        class="landing-section-copy"
                        data-reveal="left"
                    >

                        <span class="landing-section-kicker">

                            <span>
                                04
                            </span>

                            ASK

                        </span>


                        <h2 class="landing-section-title">

                            Have a question?
                            Ask before you travel.

                        </h2>


                        <p class="landing-section-description">

                            Get answers to common questions and submit
                            your own questions when the information you
                            need is not already available.

                        </p>


                        <a
                            href="{{ route('map', ['open' => 'chat']) }}"
                            class="landing-button landing-button-primary"
                        >

                            Open the assistant

                            <i class="ph-light ph-arrow-up-right"></i>

                        </a>

                    </div>



                    {{-- CHAT PREVIEW --}}

                    <div
                        class="landing-chat-preview"
                        data-reveal="scale"
                    >


                        {{-- CHAT HEADER --}}

                        <div class="landing-chat-header">

                            <div class="landing-chat-profile">

                                <div class="landing-chat-avatar">

                                    <i class="ph-light ph-sparkle"></i>

                                </div>


                                <div>

                                    <strong>
                                        KNOWURLOCAL Assistant
                                    </strong>

                                    <span>
                                        Information support
                                    </span>

                                </div>

                            </div>


                            {{-- <span class="landing-chat-online">

                                <span></span>

                                Online

                            </span> --}}

                        </div>



                        {{-- CHAT MESSAGES --}}

                        <div class="landing-chat-messages">


                            {{-- USER MESSAGE --}}

                            <div
                                class="landing-chat-message landing-chat-message-user"
                                data-chat-reveal
                            >

                                <span class="landing-chat-message-label">
                                    YOU
                                </span>

                                <p>
                                    Where can I ask about the
                                    requirements for a service?
                                </p>

                            </div>



                            {{-- BOT MESSAGE --}}

                            <div
                                class="landing-chat-message landing-chat-message-bot"
                                data-chat-reveal
                            >

                                <span class="landing-chat-message-label">
                                    KNOWURLOCAL
                                </span>

                                <p>
                                    You can search for the organization
                                    first, then view its services and
                                    available contact information.
                                </p>

                            </div>

                        </div>



                        {{-- CHAT INPUT --}}

                        <div class="landing-chat-input">

                            <span>
                                Ask a question...
                            </span>

                            <i class="ph-light ph-arrow-up"></i>

                        </div>

                    </div>

                </div>

            </section>



            {{-- =================================================
                 ARRIVE
                 ================================================= --}}

            <section
                class="landing-arrive"
                id="arrive"
                data-route-milestone="arrive"
            >

                <div
                    class="landing-container landing-arrive-inner"
                >

                    <div
                        class="landing-arrive-content"
                        data-reveal="up"
                    >

                        <span class="landing-section-kicker">
                            05 - ARRIVE
                        </span>


                        <h2 class="landing-arrive-title">

                            Less guessing.

                            <span>
                                More knowing.
                            </span>

                        </h2>


                        <p class="landing-arrive-description">

                            KNOWURLOCAL helps you make informed
                            decisions before leaving home - so when
                            you arrive, you already know where to go
                            and what to ask.

                        </p>


                        <div
                            class="landing-arrive-actions"
                            data-reveal="up"
                            data-reveal-delay="2"
                        >

                           <a
    href="{{ route('map') }}"
    class="landing-button landing-button-primary"
>
    Explore organizations
    <i class="ph-light ph-arrow-up-right"></i>
</a>

<a
    href="{{ route('map', ['open' => 'chat']) }}"
    class="landing-arrive-secondary"
>
    Ask a question
    <i class="ph-light ph-arrow-up-right"></i>
</a>

                        </div>

                    </div>

                </div>

            </section>

        </main>



        {{-- =====================================================
             FOOTER
             ===================================================== --}}

        <footer class="landing-footer">

            <div class="landing-container landing-footer-inner">


                {{-- BRAND --}}

                <div
                    class="landing-footer-brand"
                    data-reveal="up"
                >

                    <div class="landing-footer-wordmark">
                        KNOWURLOCAL
                    </div>

                    <p>
                        Local information made easier
                        for San Jose.
                    </p>

                </div>



                {{-- LINKS --}}

                <div
                    class="landing-footer-links"
                    data-reveal="up"
                    data-reveal-delay="1"
                >

                    <a href="#discover">
                        Discover
                    </a>

                    <a href="#know">
                        Know
                    </a>

                    <a href="#navigate">
                        Navigate
                    </a>

                    <a href="#ask">
                        Ask
                    </a>

                    <a href="{{ route('public.login') }}">
                        Sign in
                    </a>

                </div>



                {{-- META --}}

                <div
                    class="landing-footer-meta"
                    data-reveal="up"
                    data-reveal-delay="2"
                >

                    <span>
                        SAN JOSE, OCCIDENTAL MINDORO
                    </span>

                    <span>
                        © {{ date('Y') }} KNOWURLOCAL
                    </span>

                </div>

            </div>

        </footer>


    </div>



    {{-- =========================================================
         LANDING PAGE JAVASCRIPT
         
         defer ensures the script executes after HTML parsing.
         The JavaScript itself also contains a DOM-ready fallback.
         ========================================================= --}}

    <script
        src="{{ asset('jsfiles/public_user/landing.js') }}"
        defer
    ></script>

</body>

</html>