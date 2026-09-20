<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map</title>

    <!-- icons -->
    <script src="https://unpkg.com/phosphor-icons"></script>

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

    <!-- Leaflet MarkerCluster CSS -->
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"
>

<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"
>   

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('cssfiles/public_user/navbar.css')}}">

    <link rel="stylesheet" href="{{ asset('cssfiles/public_user/map.css') }}">

    <link rel="stylesheet" href="{{ asset('cssfiles/public_user/chatbot.css') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>

    <x-public.navbar />

    <!-- Map Container (fills entire page) -->
    <div id="map"></div>

    <!-- ================= AGENCY DETAILS ================= -->

<aside
    id="agencyDetails"
    class="agency-details"
    aria-hidden="true"
>

    <!-- PANEL HEADER -->

    <div class="agency-details-header">

        <div class="agency-details-actions">

            <!-- Navigate -->
            <a
                id="agencyNavigate"
                class="agency-action-button agency-navigate-button"
                href="#"
                aria-label="Navigate to this agency"
                title="Navigate to this agency"
            >
                <i class="ph-light ph-navigation-arrow"></i>

                <span class="agency-navigate-label">
                    Navigate
                </span>
            </a>

            <!-- Close -->
            <button
                id="agencyDetailsClose"
                class="agency-action-button agency-close-button"
                type="button"
                aria-label="Close agency details"
                title="Close"
            >
                <i class="ph-light ph-x"></i>
            </button>

        </div>

    </div>


    <!-- AGENCY CONTENT -->

    <div class="agency-details-content">

        <!-- COVER -->

        <div class="agency-details-cover">

            <img
                id="agencyDetailsImage"
                src="{{ asset('images/default-agency.png') }}"
                alt=""
            >

        </div>


        <!-- BASIC INFORMATION -->

        <div class="agency-details-main">

            <div class="agency-details-title-row">

                <div>

                    <h2 id="agencyDetailsName">
                        Agency Name
                    </h2>

                    <div class="agency-details-badges">

                        <span
                            id="agencyDetailsAbbreviation"
                            class="agency-details-abbr"
                        ></span>

                        <span
                            id="agencyDetailsCategory"
                            class="agency-details-category"
                        ></span>

                    </div>

                </div>

            </div>


            <!-- LOCATION -->

            <div
                id="agencyDetailsLocation"
                class="agency-detail-location"
            >
                <i class="ph-light ph-map-pin"></i>

                <span></span>
            </div>


            <!-- TYPE -->

            <div
                id="agencyDetailsType"
                class="agency-detail-meta"
            >
                <i class="ph-light ph-buildings"></i>

                <span></span>
            </div>


            <section
    id="agencyOfficeHeadSection"
    class="agency-detail-section"
    hidden
>
    <h3>Office Head</h3>

    <div class="agency-office-head">

        <div class="agency-office-head-item">
            <div class="agency-office-head-icon">
                <i class="ph-light ph-user"></i>
            </div>

            <div class="agency-office-head-content">
                <span class="agency-office-head-label">
                    Name
                </span>

                <p id="agencyDetailsOfficeHeadName"></p>
            </div>
        </div>

        <div class="agency-office-head-item">
            <div class="agency-office-head-icon">
                <i class="ph-light ph-identification-card"></i>
            </div>

            <div class="agency-office-head-content">
                <span class="agency-office-head-label">
                    Position
                </span>

                <p id="agencyDetailsOfficeHeadPosition"></p>
            </div>
        </div>

    </div>
</section>


            <!-- ABOUT -->

            <section
                id="agencyAboutSection"
                class="agency-detail-section"
            >

                <h3>About</h3>

                <p id="agencyDetailsDescription"></p>

            </section>


            <!-- SERVICES -->

            <section
                id="agencyServicesSection"
                class="agency-detail-section"
            >

                <h3>Services</h3>

                <p id="agencyDetailsServices"></p>

            </section>


            <!-- OFFICE HOURS -->

            <section
                id="agencyHoursSection"
                class="agency-detail-section"
            >

                <h3>Office Hours</h3>

                <p id="agencyDetailsHours"></p>

            </section>


            <!-- CONTACT -->

<section
    id="agencyContactSection"
    class="agency-detail-section"
>

    <h3>Contact</h3>

    <div
        id="agencyDetailsContacts"
        class="agency-contact-list"
    ></div>

</section>

        </div>

    </div>

</aside>


    <!-- =========================================================
     KNOWURLOCAL CHATBOT
     ========================================================= -->

<!-- Floating chatbot launcher -->
<button
    type="button"
    id="chat-toggle"
    class="chat-toggle"
    aria-label="Open KNOWURLOCAL Helpdesk"
    title="Open KNOWURLOCAL Helpdesk"
>
    <span
        class="chat-toggle-icon"
        aria-hidden="true"
    >
        <i class="ph-light ph-sparkle"></i>
    </span>

    <span
        class="chat-toggle-label"
        aria-hidden="true"
    >
        KNOWURLOCAL Helpdesk
    </span>
</button>


<!-- Chatbot backdrop -->
<div
    id="chat-overlay"
    aria-hidden="true"
></div>


<!-- Chatbot panel -->
<section
    id="chatbot"
    aria-label="KNOWURLOCAL Assistant"
>

    <div id="chat-container">

        <!-- =================================================
             CHAT HEADER
             ================================================= -->

        <header id="chat-header">

            <div class="chat-assistant-identity">

                <div class="chat-assistant-icon">
                    <i
                        class="ph-light ph-sparkle"
                        aria-hidden="true"
                    ></i>
                </div>

                <div class="chat-assistant-copy">

                    <h2>
                        KNOWURLOCAL Assistant
                    </h2>

                    <p>
                        Information support
                    </p>

                </div>

            </div>


            <!-- Header actions -->

            <div class="chat-header-actions">

                <button
                    type="button"
                    id="ask-human-btn"
                    aria-label="Send a ticket"
                    title="Send a ticket"
                >
                    <i
                        class="ph-light ph-ticket"
                        aria-hidden="true"
                    ></i>

                    <span>
                        Send a ticket
                    </span>
                </button>


                <button
                    type="button"
                    id="chat-close"
                    aria-label="Close assistant"
                    title="Close assistant"
                >
                    <i
                        class="ph-light ph-x"
                        aria-hidden="true"
                    ></i>
                </button>

            </div>

        </header>


        <!-- =================================================
             CONVERSATION
             ================================================= -->

        <div id="chatbox">

            <!-- Initial assistant message -->

            <div class="message bot chatbot-welcome">

                <div class="chat-message-content">

                    <span class="chat-message-label">
                        KNOWURLOCAL
                    </span>

                    <div class="bubble">
                        You can search for the organization first,
                        then view its services and available contact
                        information.
                    </div>

                </div>

            </div>


            <!-- Dynamic FAQ suggestions -->

            <div id="chat-suggestions"></div>

        </div>


        <!-- =================================================
             MOBILE SUPPORT ACTION
             ================================================= -->

        <div class="chat-mobile-ticket">

            <button
                type="button"
                class="chat-mobile-ticket-btn"
                id="ask-human-mobile"
            >
                <i
                    class="ph-light ph-ticket"
                    aria-hidden="true"
                ></i>

                <span>
                    Send a ticket
                </span>
            </button>

        </div>


        <!-- =================================================
             MESSAGE INPUT
             ================================================= -->

        <div id="inputArea">

            <input
                type="text"
                id="message"
                class="chatbot-input"
                placeholder="Ask a question..."
                autocomplete="off"
                maxlength="1000"
                aria-label="Ask KNOWURLOCAL a question"
            >

            <button
                type="button"
                class="chatbot-btn"
                aria-label="Send message"
                title="Send message"
            >
                <i
                    class="ph-light ph-arrow-up"
                    aria-hidden="true"
                ></i>
            </button>

        </div>

    </div>

</section>


<!-- =========================================================
     FAQ IMAGE MODAL
     ========================================================= -->

<div
    id="image-modal"
    aria-hidden="true"
>

    <button
        type="button"
        id="image-close"
        aria-label="Close image preview"
        title="Close image preview"
    >
        <i
            class="ph-light ph-x"
            aria-hidden="true"
        ></i>
    </button>

    <img
        id="modal-img"
        src=""
        alt="FAQ image preview"
    >

</div>

    
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <!-- Leaflet MarkerCluster JS -->
<script
    src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"
></script>

    <script>
        window.APP_CONFIG = {
            navigateBaseUrl: "{{ url('/navigate') }}",
            markerIcon: "{{ asset('images/map-marker.png') }}"
        };
    </script>

    <script src="{{ asset('jsfiles/public_user/navbar.js') }}" defer></script>
<script src="{{ asset('jsfiles/public_user/map.js') }}" defer></script>
<script src="{{ asset('jsfiles/public_user/chatbot.js') }}" defer></script>

    

    

</body>
</html>