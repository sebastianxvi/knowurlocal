<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>KNOWURLOCAL | About</title>


    <!-- =====================================================
         PHOSPHOR ICONS
         ===================================================== -->

    <script src="https://unpkg.com/phosphor-icons"></script>


    <!-- =====================================================
         NAVBAR STYLES
         ===================================================== -->

    <link
        rel="stylesheet"
        href="{{ asset('cssfiles/public_user/navbar.css') }}"
    >


    <!-- =====================================================
         ABOUT PAGE STYLES
         ===================================================== -->

    <link
        rel="stylesheet"
        href="{{ asset('cssfiles/public_user/about.css') }}"
    >


    <!-- =====================================================
         CSRF TOKEN
         ===================================================== -->

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >


    <!-- =====================================================
         NAVBAR JAVASCRIPT

         defer ensures the script executes after the HTML has
         been parsed, preventing DOM elements from being null.
         ===================================================== -->

    <script
        src="{{ asset('jsfiles/public_user/navbar.js') }}"
        defer
    ></script>


    <!-- =====================================================
         ABOUT PAGE JAVASCRIPT
         ===================================================== -->

    <script
        src="{{ asset('jsfiles/public_user/about.js') }}"
        defer
    ></script>

</head>


<body class="about-page-body">


    <!-- =====================================================
         PUBLIC NAVBAR

         hideSearch=true tells the shared navbar component
         not to render its search interface on this page.

         The navbar itself remains visible, including the
         burger/menu control.
         ===================================================== -->

    <x-public.navbar :hideSearch="true" />


    <!-- =====================================================
         MAIN ABOUT PAGE
         ===================================================== -->

    <main class="about-page">


        <!-- =================================================
             HERO
             ================================================= -->

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


                <!-- =============================================
                     HERO COPY
                     ============================================= -->

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

                        KNOWURLOCAL helps citizens find local government agencies and organizations, 
                        understand the services and information they need, 
                        and get answers to common questions before making the trip.

                    </p>


                    <div class="about-hero-actions">

                        <a
                            href="{{ url('/map') }}"
                            class="about-primary-button"
                        >

                            <i
                                class="
                                    ph-light
                                    ph-map-trifold
                                "
                            ></i>

                            <span>
                                Explore the Map
                            </span>

                        </a>


                        <a
                            href="#what-is"
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
                            ></i>

                        </a>

                    </div>

                </div>


                <!-- =============================================
                     INTERACTIVE HERO PREVIEW
                     ============================================= -->

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


                        <!-- =====================================
                             PREVIEW HEADER
                             ===================================== -->

                        <div class="about-preview-header">

                            <div
                                class="
                                    about-preview-dots
                                "
                            >

                                <span></span>
                                <span></span>
                                <span></span>

                            </div>


                            <span>
                                LOCAL INFORMATION
                            </span>

                        </div>


                        <!-- =====================================
                             SEARCH
                             ===================================== -->

                        <div
                            class="
                                about-preview-search
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-magnifying-glass
                                "
                            ></i>


                            <input
                                type="text"
                                id="aboutPreviewSearch"
                                placeholder="Find an agency..."
                                autocomplete="off"
                                aria-label="Search local information"
                            >

                        </div>


                        <!-- =====================================
                             SEARCH RESULTS
                             ===================================== -->

                        <div
                            class="
                                about-preview-results
                            "
                            id="aboutPreviewResults"
                        >


                            <!-- AGENCY -->

                            <button
                                type="button"
                                class="
                                    about-preview-row
                                "
                                data-preview-action="agency"
                            >

                                <div
                                    class="
                                        about-preview-icon
                                    "
                                >

                                    <i
                                        class="
                                            ph-light
                                            ph-buildings
                                        "
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


                            <!-- LOCATION -->

                            <button
                                type="button"
                                class="
                                    about-preview-row
                                "
                                data-preview-action="location"
                            >

                                <div
                                    class="
                                        about-preview-icon
                                    "
                                >

                                    <i
                                        class="
                                            ph-light
                                            ph-map-pin
                                        "
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


                        <!-- =====================================
                             EMPTY SEARCH
                             ===================================== -->

                        <div
                            class="
                                about-preview-empty
                            "
                            id="aboutPreviewEmpty"
                        >

                            <i
                                class="
                                    ph-light
                                    ph-magnifying-glass
                                "
                            ></i>

                            <span>
                                No matching information
                            </span>

                        </div>


                        <!-- =====================================
                             AGENCY DETAIL STATE
                             ===================================== -->

                        <div
                            class="
                                about-preview-detail
                            "
                            id="aboutPreviewAgency"
                        >

                            <button
                                type="button"
                                class="
                                    about-preview-back
                                "
                                data-preview-back
                            >

                                <i
                                    class="
                                        ph-light
                                        ph-arrow-left
                                    "
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
                                ></i>

                                <span>
                                    View agency information
                                </span>

                            </div>

                        </div>


                        <!-- =====================================
                             MAP STATE
                             ===================================== -->

                        <div
                            class="
                                about-preview-map
                            "
                            id="aboutPreviewMap"
                        >

                            <button
                                type="button"
                                class="
                                    about-preview-back
                                "
                                data-preview-back
                            >

                                <i
                                    class="
                                        ph-light
                                        ph-arrow-left
                                    "
                                ></i>

                                <span>
                                    Back
                                </span>

                            </button>


                            <div
                                class="
                                    about-preview-mini-map
                                "
                            >

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
                                    ></i>

                                </span>


                                <span
                                    class="
                                        about-mini-map-center
                                    "
                                >

                                    <i
                                        class="
                                            ph-light
                                            ph-navigation-arrow
                                        "
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


                        <!-- =====================================
                             STATUS
                             ===================================== -->

                        <div
                            class="
                                about-preview-status
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-check-circle
                                "
                            ></i>

                            <span>
                                Check before you go
                            </span>

                            <span
                                class="
                                    about-preview-status-dot
                                "
                                aria-hidden="true"
                            ></span>

                        </div>

                    </div>

                </div>

            </div>

        </section>



        <!-- =================================================
             WHAT IS KNOWURLOCAL?
             ================================================= -->

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


                <div
                    class="
                        about-introduction-grid
                    "
                >

                    <div
                        class="
                            about-introduction-copy
                            about-reveal
                        "
                        style="--reveal-delay: 80ms;"
                    >

                        <p
                            class="about-lead"
                        >

                            KNOWURLOCAL is an information-access
                            platform designed to help citizens of
                            San Jose, Occidental Mindoro find basic
                            information about local government
                            agencies and organizations.

                        </p>


                        <p>

                            Citizens may sometimes need to search
                            through different sources, contact an
                            office directly, or travel to a location
                            simply to ask a basic question.

                        </p>


                        <p>

                            The system provides a convenient starting point where users can explore agency information, 
                            services, locations, office hours, contact details, FAQs, and 
                            submit questions when the information they need is not already available.

                        </p>

                    </div>


                    <div
                        class="
                            about-information-card
                            about-reveal
                        "
                        style="--reveal-delay: 160ms;"
                    >

                        <div
                            class="
                                about-information-icon
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-buildings
                                "
                            ></i>

                        </div>


                        <span
                            class="about-card-label"
                        >
                            LOCAL INFORMATION
                        </span>


                        <h3>
                            Start with the information
                            you need.
                        </h3>


                        <p>

                            Explore available information from one
                            place before deciding where to go or
                            who to contact.

                        </p>


                        <div
                            class="
                                about-information-line
                            "
                        ></div>


                        <div
                            class="
                                about-information-meta
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-check-circle
                                "
                            ></i>

                            <span>
                                Designed for local citizens
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </section>



        <!-- =================================================
             WHY WE BUILT IT
             ================================================= -->

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


                <div
                    class="
                        about-problem-solution
                    "
                >


                    <!-- PROBLEM -->

                    <article
                        class="
                            about-story-card
                            about-reveal
                        "
                        style="--reveal-delay: 80ms;"
                    >

                        <div
                            class="
                                about-story-number
                            "
                        >
                            BEFORE
                        </div>


                        <div
                            class="
                                about-story-icon
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-map-pin
                                "
                            ></i>

                        </div>


                        <h3>
                            Finding basic information
                            can take a trip.
                        </h3>


                        <div
                            class="
                                about-story-flow
                            "
                        >

                            <span>
                                Question
                            </span>

                            <i
                                class="
                                    ph-light
                                    ph-arrow-down
                                "
                            ></i>

                            <span>
                                Search or contact
                            </span>

                            <i
                                class="
                                    ph-light
                                    ph-arrow-down
                                "
                            ></i>

                            <span>
                                Wait for a response
                            </span>

                            <i
                                class="
                                    ph-light
                                    ph-arrow-down
                                "
                            ></i>

                            <span>
                                Travel to the office
                            </span>

                        </div>

                    </article>


                    <!-- DIVIDER -->

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


                    <!-- SOLUTION -->

                    <article
                        class="
                            about-story-card
                            about-story-card-solution
                            about-reveal
                        "
                        style="--reveal-delay: 180ms;"
                    >

                        <div
                            class="
                                about-story-number
                            "
                        >
                            WITH KNOWURLOCAL
                        </div>


                        <div
                            class="
                                about-story-icon
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-check-circle
                                "
                            ></i>

                        </div>


                        <h3>
                            Start with information
                            before you go.
                        </h3>


                        <div
                            class="
                                about-story-flow
                            "
                        >

                            <span>
                                Search
                            </span>

                            <i
                                class="
                                    ph-light
                                    ph-arrow-down
                                "
                            ></i>

                            <span>
                                Check information
                            </span>

                            <i
                                class="
                                    ph-light
                                    ph-arrow-down
                                "
                            ></i>

                            <span>
                                Locate the office
                            </span>

                            <i
                                class="
                                    ph-light
                                    ph-arrow-down
                                "
                            ></i>

                            <span>
                                Go prepared
                            </span>

                        </div>

                    </article>

                </div>

            </div>

        </section>



        <!-- =================================================
             WHO IS IT FOR?
             ================================================= -->

        <section
            class="
                about-section
                about-audience
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
                        WHO IS IT FOR?
                    </span>

                    <h2>
                        Built around the people
                        who need local information.
                    </h2>

                    <p>

                        KNOWURLOCAL is designed to support different
                        people who need to understand where to go
                        and what to expect.

                    </p>

                </div>


                <div
                    class="
                        about-audience-grid
                    "
                >


                    <!-- RESIDENTS -->

                    <article
                        class="
                            about-audience-card
                            about-reveal
                        "
                        style="--reveal-delay: 0ms;"
                    >

                        <div
                            class="
                                about-audience-icon
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-house
                                "
                            ></i>

                        </div>


                        <span>
                            RESIDENTS
                        </span>


                        <h3>
                            People living in
                            San Jose
                        </h3>


                        <p>

                            Find agencies, services, locations,
                            office hours, and contact information
                            without having to search through
                            multiple sources first.

                        </p>

                    </article>



                    <!-- VISITORS -->

                    <article
                        class="
                            about-audience-card
                            about-reveal
                        "
                        style="--reveal-delay: 90ms;"
                    >

                        <div
                            class="
                                about-audience-icon
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-map-pin
                                "
                            ></i>

                        </div>


                        <span>
                            VISITORS
                        </span>


                        <h3>
                            People unfamiliar
                            with local offices
                        </h3>


                        <p>

                            Get a clearer idea of where an office
                            is located and what it can help with
                            before making the trip.

                        </p>

                    </article>



                    <!-- STUDENTS -->

                    <article
                        class="
                            about-audience-card
                            about-reveal
                        "
                        style="--reveal-delay: 180ms;"
                    >

                        <div
                            class="
                                about-audience-icon
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-student
                                "
                            ></i>

                        </div>


                        <span>
                            COMMUNITY MEMBERS
                        </span>


                        <h3>
                            People who need local government information
                        </h3>


                        <p>

                            Find agencies, services, requirements, locations, 
                            and other information needed before contacting or visiting an office.

                        </p>

                    </article>



                    <!-- COMMUNITY -->

                    <article
                        class="
                            about-audience-card
                            about-reveal
                        "
                        style="--reveal-delay: 270ms;"
                    >

                        <div
                            class="
                                about-audience-icon
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-users-three
                                "
                            ></i>

                        </div>


                        <span>
                            COMMUNITY MEMBERS
                        </span>


                        <h3>
                            People who simply
                            need an answer
                        </h3>


                        <p>

                            Check existing FAQs or submit a question
                            when the information they need is not
                            already available.

                        </p>

                    </article>

                </div>

            </div>

        </section>



        <!-- =================================================
             YOU MIGHT BE HERE BECAUSE...
             ================================================= -->

        <section
            class="
                about-section
                about-reasons
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
                        SOUND FAMILIAR?
                    </span>

                    <h2>
                        You might be here because...
                    </h2>

                    <p>

                        KNOWURLOCAL is built around the small
                        questions that can otherwise turn into
                        an unnecessary trip.

                    </p>

                </div>


                <div
                    class="
                        about-reasons-grid
                    "
                >


                    <article
                        class="
                            about-reason-card
                            about-reveal
                        "
                    >

                        <div
                            class="
                                about-reason-number
                            "
                        >
                            01
                        </div>

                        <div
                            class="
                                about-reason-icon
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-buildings
                                "
                            ></i>

                        </div>

                        <h3>
                            "I need to find an office."
                        </h3>

                        <p>
                            Find the local agency or organization that provides the service you need.
                        </p>

                    </article>


                    <article
                        class="
                            about-reason-card
                            about-reveal
                        "
                    >

                        <div
                            class="
                                about-reason-number
                            "
                        >
                            02
                        </div>

                        <div
                            class="
                                about-reason-icon
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-clock
                                "
                            ></i>

                        </div>

                        <h3>
                            "I want to know their hours."
                        </h3>

                        <p>
                            Check available office hours before
                            planning your visit.
                        </p>

                    </article>


                    <article
                        class="
                            about-reason-card
                            about-reveal
                        "
                    >

                        <div
                            class="
                                about-reason-number
                            "
                        >
                            03
                        </div>

                        <div
                            class="
                                about-reason-icon
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-list-checks
                                "
                            ></i>

                        </div>

                        <h3>
                            "What services do they offer?"
                        </h3>

                        <p>
                            Learn about available services before
                            going to an office.
                        </p>

                    </article>


                    <article
                        class="
                            about-reason-card
                            about-reveal
                        "
                    >

                        <div
                            class="
                                about-reason-number
                            "
                        >
                            04
                        </div>

                        <div
                            class="
                                about-reason-icon
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-chat-circle-text
                                "
                            ></i>

                        </div>

                        <h3>
                            "I have a question first."
                        </h3>

                        <p>
                            Check FAQs or submit an inquiry when
                            you need more information.
                        </p>

                    </article>

                </div>

            </div>

        </section>



        <!-- =================================================
             BEFORE YOU GO
             ================================================= -->

        <section
            class="
                about-section
                about-before
            "
        >

            <div class="about-container">


                <div
                    class="
                        about-before-heading
                        about-reveal
                    "
                >

                    <span class="about-section-label">
                        BEFORE YOU GO
                    </span>

                    <h2>
                        A few things worth checking first.
                    </h2>

                    <p>

                        Instead of making the trip first, get the
                        basic information you need beforehand.

                    </p>

                </div>


                <div
                    class="
                        about-check-grid
                    "
                >

                    <article
                        class="
                            about-check-card
                            about-reveal
                        "
                    >

                        <div
                            class="
                                about-check-top
                            "
                        >

                            <span>
                                01
                            </span>

                            <i
                                class="
                                    ph-light
                                    ph-clock
                                "
                            ></i>

                        </div>

                        <h3>
                            Is it open?
                        </h3>

                        <p>
                            Check the available office hours.
                        </p>

                    </article>


                    <article
                        class="
                            about-check-card
                            about-reveal
                        "
                    >

                        <div
                            class="
                                about-check-top
                            "
                        >

                            <span>
                                02
                            </span>

                            <i
                                class="
                                    ph-light
                                    ph-map-pin
                                "
                            ></i>

                        </div>

                        <h3>
                            Where is it?
                        </h3>

                        <p>
                            Locate the office on the interactive map.
                        </p>

                    </article>


                    <article
                        class="
                            about-check-card
                            about-reveal
                        "
                    >

                        <div
                            class="
                                about-check-top
                            "
                        >

                            <span>
                                03
                            </span>

                            <i
                                class="
                                    ph-light
                                    ph-list-checks
                                "
                            ></i>

                        </div>

                        <h3>
                            What do they offer?
                        </h3>

                        <p>
                            Review available services and information.
                        </p>

                    </article>


                    <article
                        class="
                            about-check-card
                            about-reveal
                        "
                    >

                        <div
                            class="
                                about-check-top
                            "
                        >

                            <span>
                                04
                            </span>

                            <i
                                class="
                                    ph-light
                                    ph-phone
                                "
                            ></i>

                        </div>

                        <h3>
                            Who can I contact?
                        </h3>

                        <p>
                            Find available contact information.
                        </p>

                    </article>

                </div>

            </div>

        </section>



        <!-- =================================================
             HOW IT HELPS
             ================================================= -->

        <section
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
                        HOW IT HELPS
                    </span>

                    <h2>
                        From searching to asking,
                        all in one place.
                    </h2>

                    <p>

                        The system follows the simple steps citizens
                        commonly take when looking for local information.

                    </p>

                </div>


                <div
                    class="
                        about-process-grid
                    "
                >

                    <article
                        class="
                            about-process-card
                            about-reveal
                        "
                    >

                        <div
                            class="
                                about-process-top
                            "
                        >

                            <div
                                class="
                                    about-process-icon
                                "
                            >

                                <i
                                    class="
                                        ph-light
                                        ph-magnifying-glass
                                    "
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


                    <article
                        class="
                            about-process-card
                            about-reveal
                        "
                    >

                        <div
                            class="
                                about-process-top
                            "
                        >

                            <div
                                class="
                                    about-process-icon
                                "
                            >

                                <i
                                    class="
                                        ph-light
                                        ph-map-pin
                                    "
                                ></i>

                            </div>

                            <span>
                                02
                            </span>

                        </div>

                        <h3>
                            Explore
                        </h3>

                        <p>
                            View its location and learn more about
                            the organization.
                        </p>

                    </article>


                    <article
                        class="
                            about-process-card
                            about-reveal
                        "
                    >

                        <div
                            class="
                                about-process-top
                            "
                        >

                            <div
                                class="
                                    about-process-icon
                                "
                            >

                                <i
                                    class="
                                        ph-light
                                        ph-list-checks
                                    "
                                ></i>

                            </div>

                            <span>
                                03
                            </span>

                        </div>

                        <h3>
                            Check
                        </h3>

                        <p>
                            Review services, requirements, office hours, 
                            contact information, and other available guidance before visiting.
                        </p>

                    </article>


                    <article
                        class="
                            about-process-card
                            about-reveal
                        "
                    >

                        <div
                            class="
                                about-process-top
                            "
                        >

                            <div
                                class="
                                    about-process-icon
                                "
                            >

                                <i
                                    class="
                                        ph-light
                                        ph-chat-circle-text
                                    "
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

                </div>

            </div>

        </section>



        <!-- =================================================
             WHAT YOU CAN FIND
             ================================================= -->

        <section
            class="
                about-section
                about-discover
            "
        >

            <div class="about-container">


                <div
                    class="
                        about-discover-grid
                    "
                >


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


                    <div
                        class="
                            about-discover-list
                        "
                    >

                        <article
                            class="
                                about-discover-item
                                about-reveal
                            "
                        >

                            <div
                                class="
                                    about-discover-icon
                                "
                            >

                                <i
                                    class="
                                        ph-light
                                        ph-buildings
                                    "
                                ></i>

                            </div>

                            <div>

                                <h3>
                                    Agency Information
                                </h3>

                                <p>
                                    View basic information about an agency or organization and the services it provides.
                                </p>

                            </div>

                        </article>


                        <article
                            class="
                                about-discover-item
                                about-reveal
                            "
                        >

                            <div
                                class="
                                    about-discover-icon
                                "
                            >

                                <i
                                    class="
                                        ph-light
                                        ph-map-pin
                                    "
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

                            <div
                                class="
                                    about-discover-icon
                                "
                            >

                                <i
                                    class="
                                        ph-light
                                        ph-list-checks
                                    "
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

                            <div
                                class="
                                    about-discover-icon
                                "
                            >

                                <i
                                    class="
                                        ph-light
                                        ph-clock
                                    "
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

                            <div
                                class="
                                    about-discover-icon
                                "
                            >

                                <i
                                    class="
                                        ph-light
                                        ph-phone
                                    "
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

                            <div
                                class="
                                    about-discover-icon
                                "
                            >

                                <i
                                    class="
                                        ph-light
                                        ph-chats
                                    "
                                ></i>

                            </div>

                            <div>

                                <h3>
                                    FAQs & Questions
                                </h3>

                                <p>
                                    Find answers to common agency-related questions or 
                                    submit your own question when the information you need is not already available.
                                </p>

                            </div>

                        </article>

                    </div>

                </div>

            </div>

        </section>



        <!-- =================================================
             HOW QUESTIONS WORK
             ================================================= -->

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

                        KNOWURLOCAL provides a way for users to
                        submit questions when the information they
                        need is not already covered by the available
                        FAQs.

                    </p>

                </div>


                <div
                    class="
                        about-question-flow
                    "
                >


                    <!-- STEP 1 -->

                    <article
                        class="
                            about-question-step
                            about-reveal
                        "
                    >

                        <span>
                            01
                        </span>

                        <div
                            class="
                                about-question-step-icon
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-chat-circle
                                "
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
                        class="
                            about-question-arrow
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


                    <!-- STEP 2 -->

                    <article
                        class="
                            about-question-step
                            about-reveal
                        "
                    >

                        <span>
                            02
                        </span>

                        <div
                            class="
                                about-question-step-icon
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-clock
                                "
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
                        class="
                            about-question-arrow
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


                    <!-- STEP 3 -->

                    <article
                        class="
                            about-question-step
                            about-reveal
                        "
                    >

                        <span>
                            03
                        </span>

                        <div
                            class="
                                about-question-step-icon
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-chat-circle-text
                                "
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



        <!-- =================================================
             VISIT JOURNEY
             ================================================= -->

        <section
            class="
                about-section
                about-journey
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
                        YOUR VISIT, SIMPLIFIED
                    </span>

                    <h2>
                        Don't start with the trip.
                        Start with the information.
                    </h2>

                </div>


                <div
                    class="
                        about-journey-track
                    "
                >

                    <div
                        class="
                            about-journey-step
                            about-reveal
                        "
                    >

                        <span>
                            01
                        </span>

                        <div
                            class="
                                about-journey-icon
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-magnifying-glass
                                "
                            ></i>

                        </div>

                        <h3>
                            Search
                        </h3>

                    </div>


                    <div
                        class="
                            about-journey-arrow
                        "
                    >

                        <i
                            class="
                                ph-light
                                ph-arrow-right
                            "
                        ></i>

                    </div>


                    <div
                        class="
                            about-journey-step
                            about-reveal
                        "
                    >

                        <span>
                            02
                        </span>

                        <div
                            class="
                                about-journey-icon
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-info
                                "
                            ></i>

                        </div>

                        <h3>
                            Check
                        </h3>

                    </div>


                    <div
                        class="
                            about-journey-arrow
                        "
                    >

                        <i
                            class="
                                ph-light
                                ph-arrow-right
                            "
                        ></i>

                    </div>


                    <div
                        class="
                            about-journey-step
                            about-reveal
                        "
                    >

                        <span>
                            03
                        </span>

                        <div
                            class="
                                about-journey-icon
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-map-pin
                                "
                            ></i>

                        </div>

                        <h3>
                            Locate
                        </h3>

                    </div>


                    <div
                        class="
                            about-journey-arrow
                        "
                    >

                        <i
                            class="
                                ph-light
                                ph-arrow-right
                            "
                        ></i>

                    </div>


                    <div
                        class="
                            about-journey-step
                            about-reveal
                        "
                    >

                        <span>
                            04
                        </span>

                        <div
                            class="
                                about-journey-icon
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-navigation-arrow
                                "
                            ></i>

                        </div>

                        <h3>
                            Go
                        </h3>

                    </div>

                </div>

            </div>

        </section>



        <!-- =================================================
             MAP FEATURE
             ================================================= -->

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

                    <div
                        class="
                            about-map-visual
                        "
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


                        <div
                            class="
                                about-map-center
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-navigation-arrow
                                "
                            ></i>

                        </div>

                    </div>


                    <div
                        class="
                            about-map-copy
                        "
                    >

                        <span
                            class="
                                about-section-label
                            "
                        >
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
                            href="{{ url('/map') }}"
                            class="
                                about-primary-button
                            "
                        >

                            <i
                                class="
                                    ph-light
                                    ph-map-trifold
                                "
                            ></i>

                            <span>
                                Open the Map
                            </span>

                        </a>

                    </div>

                </div>

            </div>

        </section>



        <!-- =================================================
             INFORMATION RELIABILITY
             ================================================= -->

        <section
            class="
                about-section
                about-reliability
            "
        >

            <div class="about-container">


                <div
                    class="
                        about-reliability-card
                        about-reveal
                    "
                >

                    <div
                        class="
                            about-reliability-icon
                        "
                    >

                        <i
                            class="
                                ph-light
                                ph-arrows-clockwise
                            "
                        ></i>

                    </div>


                    <div>

                        <span
                            class="
                                about-section-label
                            "
                        >
                            KEEPING INFORMATION USEFUL
                        </span>


                        <h2>
                            Information can change.
                        </h2>


                        <p>

                            Office hours, contact details, services,
                            and other information may change over time.
                            KNOWURLOCAL is intended to make information
                            easier to discover, while important or
                            time-sensitive details should still be
                            confirmed with the concerned agency or
                            organization.

                        </p>

                    </div>


                    <div
                        class="
                            about-reliability-points
                        "
                    >

                        <div>

                            <i
                                class="
                                    ph-light
                                    ph-clock
                                "
                            ></i>

                            <span>
                                Office hours
                            </span>

                        </div>


                        <div>

                            <i
                                class="
                                    ph-light
                                    ph-phone
                                "
                            ></i>

                            <span>
                                Contact details
                            </span>

                        </div>


                        <div>

                            <i
                                class="
                                    ph-light
                                    ph-list-checks
                                "
                            ></i>

                            <span>
                                Services
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </section>



        <!-- =================================================
             SCOPE
             ================================================= -->

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
                        KNOW THE SCOPE
                    </span>

                    <h2>
                        What KNOWURLOCAL is —
                        and isn't.
                    </h2>

                </div>


                <div
                    class="
                        about-scope-grid
                    "
                >


                    <!-- IS -->

                    <div
                        class="
                            about-scope-card
                            about-scope-positive
                            about-reveal
                        "
                    >

                        <div
                            class="
                                about-scope-heading
                            "
                        >

                            <div
                                class="
                                    about-scope-icon
                                "
                            >

                                <i
                                    class="
                                        ph-light
                                        ph-check
                                    "
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
                                ></i>

                                <span>
                                    A place to find agency services, locations, office hours, and contact information
                                </span>

                            </li>


                            <li>

                                <i
                                    class="
                                        ph-light
                                        ph-check-circle
                                    "
                                ></i>

                                <span>
                                    A starting point for checking common agency-related questions
                                </span>

                            </li>

                            <li>

                                <i
                                    class="
                                        ph-light
                                        ph-check-circle
                                    "
                                ></i>

                                <span>
                                    A way to submit questions when available information does not answer what you need
                                </span>

                            </li>

                        </ul>

                    </div>


                    <!-- ISN'T -->

                    <div
                        class="
                            about-scope-card
                            about-scope-negative
                            about-reveal
                        "
                    >

                        <div
                            class="
                                about-scope-heading
                            "
                        >

                            <div
                                class="
                                    about-scope-icon
                                "
                            >

                                <i
                                    class="
                                        ph-light
                                        ph-minus
                                    "
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



        <!-- =================================================
             FINAL NOTE
             ================================================= -->

        <section
            class="
                about-section
                about-information-note
            "
        >

            <div class="about-container">


                <div
                    class="
                        about-note-card
                        about-reveal
                    "
                >

                    <div
                        class="
                            about-note-icon
                        "
                    >

                        <i
                            class="
                                ph-light
                                ph-info
                            "
                        ></i>

                    </div>


                    <div>

                        <span
                            class="
                                about-section-label
                            "
                        >
                            A NOTE ABOUT INFORMATION
                        </span>


                        <h2>
                            Use the information as
                            your starting point.
                        </h2>


                        <p>

                            KNOWURLOCAL provides information intended
                            to help citizens locate and understand
                            available services. When an important
                            decision depends on current requirements,
                            schedules, or official instructions,
                            confirm the details directly with the
                            concerned agency or organization.

                        </p>

                    </div>

                </div>

            </div>

        </section>



        <!-- =================================================
             FINAL CTA
             ================================================= -->

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

                    <span
                        class="
                            about-section-label
                        "
                    >
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
                        href="{{ url('/map') }}"
                        class="
                            about-primary-button
                        "
                    >

                        <i
                            class="
                                ph-light
                                ph-map-trifold
                            "
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