<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="theme-color"
        content="#f6f7fa"
    >

    <title>KNOWURLOCAL | About</title>


    {{-- =========================================================
         PHOSPHOR ICONS
         =========================================================
         The application uses Phosphor's light icon weight
         throughout the public interface.
    ========================================================== --}}

    <script src="https://unpkg.com/phosphor-icons"></script>


    {{-- =========================================================
         SHARED NAVBAR
         ========================================================== --}}

    <link
        rel="stylesheet"
        href="{{ asset('cssfiles/public_user/navbar.css') }}"
    >


    {{-- =========================================================
         ABOUT PAGE STYLES
         ========================================================== --}}

    <link
        rel="stylesheet"
        href="{{ asset('cssfiles/public_user/about.css') }}"
    >


    {{-- =========================================================
         CSRF TOKEN
         =========================================================
         Kept available for JavaScript requests that may require
         Laravel's CSRF protection.
    ========================================================== --}}

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >


    {{-- =========================================================
         SHARED NAVBAR JAVASCRIPT
         ========================================================== --}}

    <script
        src="{{ asset('jsfiles/public_user/navbar.js') }}"
        defer
    ></script>


    {{-- =========================================================
         ABOUT PAGE JAVASCRIPT
         ========================================================== --}}

    <script
        src="{{ asset('jsfiles/public_user/about.js') }}"
        defer
    ></script>

</head>


<body class="about-page-body">


    {{-- =========================================================
         PUBLIC NAVBAR
         =========================================================
         Search is intentionally hidden because this page is
         informational rather than an agency discovery page.
    ========================================================== --}}

    <x-public.navbar :hideSearch="true" />


    <main class="about-page">


        {{-- =====================================================
             01. HERO
             ====================================================== --}}

        <section class="about-hero">

            <div
                class="about-hero-decoration"
                aria-hidden="true"
            >

                <span
                    class="
                        about-soft-orb
                        about-soft-orb-one
                    "
                ></span>

                <span
                    class="
                        about-soft-orb
                        about-soft-orb-two
                    "
                ></span>

                <span
                    class="
                        about-soft-dot
                        about-soft-dot-one
                    "
                ></span>

                <span
                    class="
                        about-soft-dot
                        about-soft-dot-two
                    "
                ></span>

            </div>


            <div class="about-container about-hero-grid">


                {{-- HERO COPY --}}

                <div
                    class="
                        about-hero-content
                        about-reveal
                    "
                >

                    <div class="about-eyebrow">

                        <i
                            class="
                                ph-light
                                ph-map-pin
                            "
                            aria-hidden="true"
                        ></i>

                        <span>
                            San Jose, Occidental Mindoro
                        </span>

                    </div>


                    <h1>
                        Know where to go.
                        <span>
                            Before you go.
                        </span>
                    </h1>


                    <p class="about-hero-description">

                        KNOWURLOCAL helps citizens discover
                        local government agencies and organizations,
                        understand the information they need,
                        and get answers to common questions
                        before making the trip.

                    </p>


                    <div class="about-hero-actions">

                        <a
                            href="{{ route('map') }}"
                            class="about-primary-button"
                        >

                            <i
                                class="
                                    ph-light
                                    ph-map-trifold
                                "
                                aria-hidden="true"
                            ></i>

                            <span>
                                Explore the Map
                            </span>

                        </a>


                        <a
                            href="#how-it-works"
                            class="about-secondary-button"
                        >

                            <span>
                                See how it works
                            </span>

                            <i
                                class="
                                    ph-light
                                    ph-arrow-down
                                "
                                aria-hidden="true"
                            ></i>

                        </a>

                    </div>

                </div>


                {{-- =================================================
                     INTERACTIVE PRODUCT PREVIEW
                     ================================================== --}}

                <div
                    class="
                        about-hero-preview
                        about-reveal
                    "
                    style="--reveal-delay: 160ms;"
                >

                    <div
                        class="about-preview-window"
                        id="aboutPreview"
                    >


                        {{-- PREVIEW HEADER --}}

                        <div class="about-preview-header">

                            <div class="about-preview-dots">

                                <span></span>
                                <span></span>
                                <span></span>

                            </div>

                            <span>
                                LOCAL INFORMATION
                            </span>

                        </div>


                        {{-- SEARCH --}}

                        <div class="about-preview-search">

                            <i
                                class="
                                    ph-light
                                    ph-magnifying-glass
                                "
                                aria-hidden="true"
                            ></i>

                            <input
                                type="text"
                                id="aboutPreviewSearch"
                                placeholder="Find an agency..."
                                autocomplete="off"
                                aria-label="Search local information"
                            >

                        </div>


                        {{-- SEARCH RESULTS --}}

                        <div
                            class="about-preview-results"
                            id="aboutPreviewResults"
                        >

                            <button
                                type="button"
                                class="about-preview-row"
                                data-preview-action="agency"
                            >

                                <div class="about-preview-icon">

                                    <i
                                        class="
                                            ph-light
                                            ph-buildings
                                        "
                                        aria-hidden="true"
                                    ></i>

                                </div>

                                <div>

                                    <strong>
                                        Agency information
                                    </strong>

                                    <small>
                                        Services · Hours · Contact
                                    </small>

                                </div>

                                <i
                                    class="
                                        ph-light
                                        ph-arrow-up-right
                                    "
                                    aria-hidden="true"
                                ></i>

                            </button>


                            <button
                                type="button"
                                class="about-preview-row"
                                data-preview-action="location"
                            >

                                <div class="about-preview-icon">

                                    <i
                                        class="
                                            ph-light
                                            ph-map-pin
                                        "
                                        aria-hidden="true"
                                    ></i>

                                </div>

                                <div>

                                    <strong>
                                        Office location
                                    </strong>

                                    <small>
                                        Find it on the local map
                                    </small>

                                </div>

                                <i
                                    class="
                                        ph-light
                                        ph-arrow-up-right
                                    "
                                    aria-hidden="true"
                                ></i>

                            </button>

                        </div>


                        {{-- EMPTY SEARCH STATE --}}

                        <div
                            class="about-preview-empty"
                            id="aboutPreviewEmpty"
                        >

                            <i
                                class="
                                    ph-light
                                    ph-magnifying-glass
                                "
                                aria-hidden="true"
                            ></i>

                            <span>
                                No matching information
                            </span>

                        </div>


                        {{-- AGENCY DETAIL STATE --}}

                        <div
                            class="about-preview-detail"
                            id="aboutPreviewAgency"
                        >

                            <button
                                type="button"
                                class="about-preview-back"
                                data-preview-back
                            >

                                <i
                                    class="
                                        ph-light
                                        ph-arrow-left
                                    "
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Back
                                </span>

                            </button>


                            <div
                                class="
                                    about-preview-detail-heading
                                "
                            >

                                <div
                                    class="
                                        about-preview-detail-icon
                                    "
                                >

                                    <i
                                        class="
                                            ph-light
                                            ph-buildings
                                        "
                                        aria-hidden="true"
                                    ></i>

                                </div>

                                <div>

                                    <strong>
                                        Local Agency
                                    </strong>

                                    <small>
                                        San Jose, Occidental Mindoro
                                    </small>

                                </div>

                            </div>


                            <div
                                class="
                                    about-preview-detail-grid
                                "
                            >

                                <div>

                                    <span>
                                        SERVICES
                                    </span>

                                    <strong>
                                        Available services
                                    </strong>

                                </div>

                                <div>

                                    <span>
                                        OFFICE HOURS
                                    </span>

                                    <strong>
                                        Check before visiting
                                    </strong>

                                </div>

                            </div>


                            <div
                                class="
                                    about-preview-detail-link
                                "
                            >

                                <i
                                    class="
                                        ph-light
                                        ph-arrow-up-right
                                    "
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    View agency information
                                </span>

                            </div>

                        </div>


                        {{-- MAP STATE --}}

                        <div
                            class="about-preview-map"
                            id="aboutPreviewMap"
                        >

                            <button
                                type="button"
                                class="about-preview-back"
                                data-preview-back
                            >

                                <i
                                    class="
                                        ph-light
                                        ph-arrow-left
                                    "
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Back
                                </span>

                            </button>


                            <div class="about-preview-mini-map">

                                <span
                                    class="
                                        about-mini-road
                                        about-mini-road-one
                                    "
                                ></span>

                                <span
                                    class="
                                        about-mini-road
                                        about-mini-road-two
                                    "
                                ></span>

                                <span
                                    class="
                                        about-mini-road
                                        about-mini-road-three
                                    "
                                ></span>


                                <span
                                    class="
                                        about-mini-pin
                                        about-mini-pin-one
                                    "
                                >

                                    <i
                                        class="
                                            ph-light
                                            ph-map-pin
                                        "
                                        aria-hidden="true"
                                    ></i>

                                </span>


                                <span
                                    class="
                                        about-mini-pin
                                        about-mini-pin-two
                                    "
                                >

                                    <i
                                        class="
                                            ph-light
                                            ph-map-pin
                                        "
                                        aria-hidden="true"
                                    ></i>

                                </span>


                                <span
                                    class="
                                        about-mini-pin
                                        about-mini-pin-three
                                    "
                                >

                                    <i
                                        class="
                                            ph-light
                                            ph-map-pin
                                        "
                                        aria-hidden="true"
                                    ></i>

                                </span>


                                <span class="about-mini-map-center">

                                    <i
                                        class="
                                            ph-light
                                            ph-navigation-arrow
                                        "
                                        aria-hidden="true"
                                    ></i>

                                </span>

                            </div>


                            <div
                                class="
                                    about-preview-map-caption
                                "
                            >

                                <strong>
                                    Offices around San Jose
                                </strong>

                                <small>
                                    Explore locations on the
                                    interactive map
                                </small>

                            </div>

                        </div>


                        {{-- PREVIEW STATUS --}}

                        <div class="about-preview-status">

                            <i
                                class="
                                    ph-light
                                    ph-check-circle
                                "
                                aria-hidden="true"
                            ></i>

                            <span>
                                Check before you go
                            </span>

                            <span
                                class="about-preview-status-dot"
                                aria-hidden="true"
                            ></span>

                        </div>

                    </div>

                </div>

            </div>

        </section>



        {{-- =====================================================
             02. WHAT IS KNOWURLOCAL?
             ====================================================== --}}

        <section
            id="what-is"
            class="
                about-section
                about-introduction
            "
        >

            <div class="about-container">

                <div
                    class="
                        about-section-heading
                        about-reveal
                    "
                >

                    <span class="about-section-label">
                        ABOUT THE SYSTEM
                    </span>

                    <h2>
                        Local information,
                        made easier to find.
                    </h2>

                </div>


                <div class="about-introduction-grid">


                    <div
                        class="
                            about-introduction-copy
                            about-reveal
                        "
                        style="--reveal-delay: 80ms;"
                    >

                        <p class="about-lead">

                            KNOWURLOCAL is an information-access
                            platform designed to help citizens of
                            San Jose, Occidental Mindoro find basic
                            information about local government
                            agencies and organizations.

                        </p>


                        <p>

                            Instead of searching through different
                            sources or traveling to an office simply
                            to ask a basic question, citizens can
                            start by checking the information
                            available through the system.

                        </p>


                        <p>

                            Agency information, services, locations,
                            office hours, contact details, FAQs,
                            and submitted questions are brought
                            together in one place.

                        </p>

                    </div>


                    <div
                        class="
                            about-information-card
                            about-reveal
                        "
                        style="--reveal-delay: 160ms;"
                    >

                        <div class="about-information-icon">

                            <i
                                class="
                                    ph-light
                                    ph-buildings
                                "
                                aria-hidden="true"
                            ></i>

                        </div>

                        <span class="about-card-label">
                            LOCAL INFORMATION
                        </span>

                        <h3>
                            Start with the information
                            you need.
                        </h3>

                        <p>

                            Explore available information from
                            one place before deciding where to go
                            or who to contact.

                        </p>

                        <div class="about-information-line"></div>

                        <div class="about-information-meta">

                            <i
                                class="
                                    ph-light
                                    ph-check-circle
                                "
                                aria-hidden="true"
                            ></i>

                            <span>
                                Designed for local citizens
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </section>



        {{-- =====================================================
             03. WHY WE BUILT IT
             ====================================================== --}}

        <section
            class="
                about-section
                about-why
            "
        >

            <div class="about-container">

                <div
                    class="
                        about-section-heading
                        about-reveal
                    "
                >

                    <span class="about-section-label">
                        WHY WE BUILT IT
                    </span>

                    <h2>
                        A simple question
                        shouldn't require a trip.
                    </h2>

                    <p>

                        KNOWURLOCAL was built around a common
                        information problem: sometimes citizens
                        need to travel, call, search, or wait for
                        a response just to find a basic answer.

                    </p>

                </div>


                <div class="about-problem-solution">


                    {{-- BEFORE --}}

                    <article
                        class="
                            about-story-card
                            about-reveal
                        "
                        style="--reveal-delay: 80ms;"
                    >

                        <div class="about-story-number">
                            BEFORE
                        </div>

                        <div class="about-story-icon">

                            <i
                                class="
                                    ph-light
                                    ph-map-pin
                                "
                                aria-hidden="true"
                            ></i>

                        </div>

                        <h3>
                            Finding basic information
                            can take a trip.
                        </h3>

                        <div class="about-story-flow">

                            <span>
                                Question
                            </span>

                            <i
                                class="
                                    ph-light
                                    ph-arrow-down
                                "
                                aria-hidden="true"
                            ></i>

                            <span>
                                Search or contact
                            </span>

                            <i
                                class="
                                    ph-light
                                    ph-arrow-down
                                "
                                aria-hidden="true"
                            ></i>

                            <span>
                                Wait for a response
                            </span>

                            <i
                                class="
                                    ph-light
                                    ph-arrow-down
                                "
                                aria-hidden="true"
                            ></i>

                            <span>
                                Travel to the office
                            </span>

                        </div>

                    </article>


                    {{-- DIVIDER --}}

                    <div
                        class="
                            about-story-divider
                            about-reveal
                        "
                        aria-hidden="true"
                    >

                        <i
                            class="
                                ph-light
                                ph-arrow-right
                            "
                        ></i>

                    </div>


                    {{-- WITH KNOWURLOCAL --}}

                    <article
                        class="
                            about-story-card
                            about-story-card-solution
                            about-reveal
                        "
                        style="--reveal-delay: 180ms;"
                    >

                        <div class="about-story-number">
                            WITH KNOWURLOCAL
                        </div>

                        <div class="about-story-icon">

                            <i
                                class="
                                    ph-light
                                    ph-check-circle
                                "
                                aria-hidden="true"
                            ></i>

                        </div>

                        <h3>
                            Start with information
                            before you go.
                        </h3>

                        <div class="about-story-flow">

                            <span>
                                Search
                            </span>

                            <i
                                class="
                                    ph-light
                                    ph-arrow-down
                                "
                                aria-hidden="true"
                            ></i>

                            <span>
                                Check information
                            </span>

                            <i
                                class="
                                    ph-light
                                    ph-arrow-down
                                "
                                aria-hidden="true"
                            ></i>

                            <span>
                                Locate the office
                            </span>

                            <i
                                class="
                                    ph-light
                                    ph-arrow-down
                                "
                                aria-hidden="true"
                            ></i>

                            <span>
                                Go prepared
                            </span>

                        </div>

                    </article>

                </div>

            </div>

        </section>



        {{-- =====================================================
             04. HOW IT WORKS
             ====================================================== --}}

        <section
            id="how-it-works"
            class="
                about-section
                about-how
            "
        >

            <div class="about-container">

                <div
                    class="
                        about-section-heading
                        about-section-heading-centered
                        about-reveal
                    "
                >

                    <span class="about-section-label">
                        HOW IT WORKS
                    </span>

                    <h2>
                        Find. Know. Navigate. Ask. Go.
                    </h2>

                    <p>

                        KNOWURLOCAL follows the simple journey
                        citizens can take when looking for
                        local information.

                    </p>

                </div>


                <div class="about-process-grid">


                    {{-- FIND --}}

                    <article
                        class="
                            about-process-card
                            about-reveal
                        "
                        style="--reveal-delay: 0ms;"
                    >

                        <div class="about-process-top">

                            <div class="about-process-icon">

                                <i
                                    class="
                                        ph-light
                                        ph-magnifying-glass
                                    "
                                    aria-hidden="true"
                                ></i>

                            </div>

                            <span>
                                01
                            </span>

                        </div>

                        <h3>
                            Find
                        </h3>

                        <p>
                            Search for an agency or organization
                            available in San Jose.
                        </p>

                    </article>


                    {{-- KNOW --}}

                    <article
                        class="
                            about-process-card
                            about-reveal
                        "
                        style="--reveal-delay: 80ms;"
                    >

                        <div class="about-process-top">

                            <div class="about-process-icon">

                                <i
                                    class="
                                        ph-light
                                        ph-info
                                    "
                                    aria-hidden="true"
                                ></i>

                            </div>

                            <span>
                                02
                            </span>

                        </div>

                        <h3>
                            Know
                        </h3>

                        <p>
                            Review services, office hours,
                            contact details, and available
                            agency information.
                        </p>

                    </article>


                    {{-- NAVIGATE --}}

                    <article
                        class="
                            about-process-card
                            about-reveal
                        "
                        style="--reveal-delay: 160ms;"
                    >

                        <div class="about-process-top">

                            <div class="about-process-icon">

                                <i
                                    class="
                                        ph-light
                                        ph-map-pin
                                    "
                                    aria-hidden="true"
                                ></i>

                            </div>

                            <span>
                                03
                            </span>

                        </div>

                        <h3>
                            Navigate
                        </h3>

                        <p>
                            Locate the office on the interactive
                            map and plan where you need to go.
                        </p>

                    </article>


                    {{-- ASK --}}

                    <article
                        class="
                            about-process-card
                            about-reveal
                        "
                        style="--reveal-delay: 240ms;"
                    >

                        <div class="about-process-top">

                            <div class="about-process-icon">

                                <i
                                    class="
                                        ph-light
                                        ph-chat-circle-text
                                    "
                                    aria-hidden="true"
                                ></i>

                            </div>

                            <span>
                                04
                            </span>

                        </div>

                        <h3>
                            Ask
                        </h3>

                        <p>
                            Check FAQs or submit your own question
                            when you need more help.
                        </p>

                    </article>


                    {{-- GO --}}

                    <article
                        class="
                            about-process-card
                            about-reveal
                        "
                        style="--reveal-delay: 320ms;"
                    >

                        <div class="about-process-top">

                            <div class="about-process-icon">

                                <i
                                    class="
                                        ph-light
                                        ph-navigation-arrow
                                    "
                                    aria-hidden="true"
                                ></i>

                            </div>

                            <span>
                                05
                            </span>

                        </div>

                        <h3>
                            Go
                        </h3>

                        <p>
                            Visit with the information you need
                            already in hand.
                        </p>

                    </article>

                </div>

            </div>

        </section>



        {{-- =====================================================
             05. WHAT YOU CAN FIND
             ====================================================== --}}

        <section
            class="
                about-section
                about-discover
            "
        >

            <div class="about-container">

                <div class="about-discover-grid">


                    <div
                        class="
                            about-discover-heading
                            about-reveal
                        "
                    >

                        <span class="about-section-label">
                            WHAT YOU CAN FIND
                        </span>

                        <h2>
                            Know more
                            before you visit.
                        </h2>

                        <p>

                            The system brings together the basic
                            details citizens commonly need when
                            looking for a local agency or organization.

                        </p>

                    </div>


                    <div class="about-discover-list">


                        <article
                            class="
                                about-discover-item
                                about-reveal
                            "
                        >

                            <div class="about-discover-icon">

                                <i
                                    class="
                                        ph-light
                                        ph-buildings
                                    "
                                    aria-hidden="true"
                                ></i>

                            </div>

                            <div>

                                <h3>
                                    Agency Information
                                </h3>

                                <p>
                                    View basic information about
                                    an agency or organization and
                                    the services it provides.
                                </p>

                            </div>

                        </article>


                        <article
                            class="
                                about-discover-item
                                about-reveal
                            "
                        >

                            <div class="about-discover-icon">

                                <i
                                    class="
                                        ph-light
                                        ph-map-pin
                                    "
                                    aria-hidden="true"
                                ></i>

                            </div>

                            <div>

                                <h3>
                                    Location
                                </h3>

                                <p>
                                    Find where an office is located
                                    and use the map to plan your visit.
                                </p>

                            </div>

                        </article>


                        <article
                            class="
                                about-discover-item
                                about-reveal
                            "
                        >

                            <div class="about-discover-icon">

                                <i
                                    class="
                                        ph-light
                                        ph-list-checks
                                    "
                                    aria-hidden="true"
                                ></i>

                            </div>

                            <div>

                                <h3>
                                    Services
                                </h3>

                                <p>
                                    Check available services before
                                    contacting or visiting an office.
                                </p>

                            </div>

                        </article>


                        <article
                            class="
                                about-discover-item
                                about-reveal
                            "
                        >

                            <div class="about-discover-icon">

                                <i
                                    class="
                                        ph-light
                                        ph-clock
                                    "
                                    aria-hidden="true"
                                ></i>

                            </div>

                            <div>

                                <h3>
                                    Office Hours
                                </h3>

                                <p>
                                    Check available office hours
                                    before planning a visit.
                                </p>

                            </div>

                        </article>


                        <article
                            class="
                                about-discover-item
                                about-reveal
                            "
                        >

                            <div class="about-discover-icon">

                                <i
                                    class="
                                        ph-light
                                        ph-phone
                                    "
                                    aria-hidden="true"
                                ></i>

                            </div>

                            <div>

                                <h3>
                                    Contact Information
                                </h3>

                                <p>
                                    Find available contact details
                                    for reaching an agency.
                                </p>

                            </div>

                        </article>


                        <article
                            class="
                                about-discover-item
                                about-reveal
                            "
                        >

                            <div class="about-discover-icon">

                                <i
                                    class="
                                        ph-light
                                        ph-chats
                                    "
                                    aria-hidden="true"
                                ></i>

                            </div>

                            <div>

                                <h3>
                                    FAQs & Questions
                                </h3>

                                <p>
                                    Find answers to common agency-related
                                    questions or submit your own question
                                    when the information you need is not
                                    already available.
                                </p>

                            </div>

                        </article>

                    </div>

                </div>

            </div>

        </section>



        {{-- =====================================================
             06. QUESTIONS / HELPDESK
             ====================================================== --}}

        <section
            class="
                about-section
                about-questions
            "
        >

            <div class="about-container">

                <div
                    class="
                        about-section-heading
                        about-reveal
                    "
                >

                    <span class="about-section-label">
                        HAVE A QUESTION?
                    </span>

                    <h2>
                        When the answer isn't
                        already there, ask.
                    </h2>

                    <p>

                        KNOWURLOCAL provides a way for users
                        to submit questions when the information
                        they need is not already covered by
                        available FAQs.

                    </p>

                </div>


                <div class="about-question-flow">


                    <article
                        class="
                            about-question-step
                            about-reveal
                        "
                    >

                        <span>
                            01
                        </span>

                        <div class="about-question-step-icon">

                            <i
                                class="
                                    ph-light
                                    ph-chat-circle
                                "
                                aria-hidden="true"
                            ></i>

                        </div>

                        <h3>
                            Ask
                        </h3>

                        <p>
                            Submit the question you need answered.
                        </p>

                    </article>


                    <div
                        class="about-question-arrow"
                        aria-hidden="true"
                    >

                        <i
                            class="
                                ph-light
                                ph-arrow-right
                            "
                        ></i>

                    </div>


                    <article
                        class="
                            about-question-step
                            about-reveal
                        "
                    >

                        <span>
                            02
                        </span>

                        <div class="about-question-step-icon">

                            <i
                                class="
                                    ph-light
                                    ph-clock
                                "
                                aria-hidden="true"
                            ></i>

                        </div>

                        <h3>
                            Track
                        </h3>

                        <p>
                            Keep track of your submitted inquiry.
                        </p>

                    </article>


                    <div
                        class="about-question-arrow"
                        aria-hidden="true"
                    >

                        <i
                            class="
                                ph-light
                                ph-arrow-right
                            "
                        ></i>

                    </div>


                    <article
                        class="
                            about-question-step
                            about-reveal
                        "
                    >

                        <span>
                            03
                        </span>

                        <div class="about-question-step-icon">

                            <i
                                class="
                                    ph-light
                                    ph-chat-circle-text
                                "
                                aria-hidden="true"
                            ></i>

                        </div>

                        <h3>
                            Receive
                        </h3>

                        <p>
                            Review the response when it becomes available.
                        </p>

                    </article>

                </div>


                <div
                    class="
                        about-question-note
                        about-reveal
                    "
                >

                    <i
                        class="
                            ph-light
                            ph-info
                        "
                        aria-hidden="true"
                    ></i>

                    <p>

                        Questions and responses are intended to
                        provide information and guidance. Official
                        agency requirements and decisions should
                        still be confirmed with the concerned office.

                    </p>

                </div>

            </div>

        </section>



        {{-- =====================================================
             07. MAP FEATURE
             ====================================================== --}}

        <section
            class="
                about-section
                about-map-feature
            "
        >

            <div class="about-container">

                <div
                    class="
                        about-map-card
                        about-reveal
                    "
                >


                    {{-- MAP VISUAL --}}

                    <div
                        class="about-map-visual"
                        aria-hidden="true"
                    >

                        <span
                            class="
                                about-map-road
                                about-road-one
                            "
                        ></span>

                        <span
                            class="
                                about-map-road
                                about-road-two
                            "
                        ></span>

                        <span
                            class="
                                about-map-road
                                about-road-three
                            "
                        ></span>

                        <span
                            class="
                                about-map-road
                                about-road-four
                            "
                        ></span>


                        <div
                            class="
                                about-map-pin
                                about-map-pin-one
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-map-pin
                                "
                            ></i>

                        </div>


                        <div
                            class="
                                about-map-pin
                                about-map-pin-two
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-map-pin
                                "
                            ></i>

                        </div>


                        <div
                            class="
                                about-map-pin
                                about-map-pin-three
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-map-pin
                                "
                            ></i>

                        </div>


                        <div class="about-map-center">

                            <i
                                class="
                                    ph-light
                                    ph-navigation-arrow
                                "
                            ></i>

                        </div>

                    </div>


                    {{-- MAP COPY --}}

                    <div class="about-map-copy">

                        <span class="about-section-label">
                            LOCAL MAP
                        </span>

                        <h2>
                            Find agencies
                            around San Jose.
                        </h2>

                        <p>

                            Use the interactive map to explore
                            agency locations and get a better idea
                            of where you need to go before making
                            the trip.

                        </p>

                        <a
                            href="{{ route('map') }}"
                            class="about-primary-button"
                        >

                            <i
                                class="
                                    ph-light
                                    ph-map-trifold
                                "
                                aria-hidden="true"
                            ></i>

                            <span>
                                Open the Map
                            </span>

                        </a>

                    </div>

                </div>

            </div>

        </section>



        {{-- =====================================================
             08. INFORMATION & SCOPE
             ====================================================== --}}

        <section
            class="
                about-section
                about-scope
            "
        >

            <div class="about-container">


                <div
                    class="
                        about-section-heading
                        about-reveal
                    "
                >

                    <span class="about-section-label">
                        INFORMATION & SCOPE
                    </span>

                    <h2>
                        Use KNOWURLOCAL
                        as your starting point.
                    </h2>

                    <p>

                        KNOWURLOCAL makes local information easier
                        to discover, but important requirements,
                        schedules, and official decisions should
                        still be confirmed with the concerned office.

                    </p>

                </div>


                <div class="about-scope-grid">


                    {{-- WHAT IT IS --}}

                    <div
                        class="
                            about-scope-card
                            about-scope-positive
                            about-reveal
                        "
                    >

                        <div class="about-scope-heading">

                            <div class="about-scope-icon">

                                <i
                                    class="
                                        ph-light
                                        ph-check
                                    "
                                    aria-hidden="true"
                                ></i>

                            </div>

                            <h3>
                                KNOWURLOCAL is
                            </h3>

                        </div>


                        <ul>

                            <li>

                                <i
                                    class="
                                        ph-light
                                        ph-check-circle
                                    "
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    A local information and helpdesk platform
                                </span>

                            </li>


                            <li>

                                <i
                                    class="
                                        ph-light
                                        ph-check-circle
                                    "
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    A way to discover agencies
                                    and organizations
                                </span>

                            </li>


                            <li>

                                <i
                                    class="
                                        ph-light
                                        ph-check-circle
                                    "
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    A place to find services,
                                    locations, office hours,
                                    and contact information
                                </span>

                            </li>


                            <li>

                                <i
                                    class="
                                        ph-light
                                        ph-check-circle
                                    "
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    A starting point for common
                                    agency-related questions
                                </span>

                            </li>


                            <li>

                                <i
                                    class="
                                        ph-light
                                        ph-check-circle
                                    "
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    A way to submit questions when
                                    available information does not
                                    answer what you need
                                </span>

                            </li>

                        </ul>

                    </div>


                    {{-- WHAT IT IS NOT --}}

                    <div
                        class="
                            about-scope-card
                            about-scope-negative
                            about-reveal
                        "
                    >

                        <div class="about-scope-heading">

                            <div class="about-scope-icon">

                                <i
                                    class="
                                        ph-light
                                        ph-minus
                                    "
                                    aria-hidden="true"
                                ></i>

                            </div>

                            <h3>
                                KNOWURLOCAL isn't
                            </h3>

                        </div>


                        <ul>

                            <li>

                                <i
                                    class="
                                        ph-light
                                        ph-minus-circle
                                    "
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    The government agency itself
                                </span>

                            </li>


                            <li>

                                <i
                                    class="
                                        ph-light
                                        ph-minus-circle
                                    "
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    A replacement for official
                                    agency communication
                                </span>

                            </li>


                            <li>

                                <i
                                    class="
                                        ph-light
                                        ph-minus-circle
                                    "
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    A guarantee that information
                                    never changes
                                </span>

                            </li>


                            <li>

                                <i
                                    class="
                                        ph-light
                                        ph-minus-circle
                                    "
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    A substitute for official
                                    requirements or decisions
                                </span>

                            </li>

                        </ul>

                    </div>

                </div>

            </div>

        </section>



        {{-- =====================================================
             09. FINAL CTA
             ====================================================== --}}

        <section
            class="
                about-section
                about-cta
            "
        >

            <div class="about-container">

                <div
                    class="
                        about-cta-content
                        about-reveal
                    "
                >

                    <span class="about-section-label">
                        START EXPLORING
                    </span>

                    <h2>
                        Know where to go
                        before you go.
                    </h2>

                    <p>

                        Explore agencies and organizations around
                        San Jose through the KNOWURLOCAL map.

                    </p>

                    <a
                        href="{{ route('map') }}"
                        class="about-primary-button"
                    >

                        <i
                            class="
                                ph-light
                                ph-map-trifold
                            "
                            aria-hidden="true"
                        ></i>

                        <span>
                            Explore the Map
                        </span>

                    </a>

                </div>

            </div>

        </section>


    </main>

</body>

</html>