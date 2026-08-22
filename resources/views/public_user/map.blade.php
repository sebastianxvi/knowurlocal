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


    <!-- Chatbot placeholder -->
    <div id="chat-toggle">
        <img src="{{asset('images/chatbot-icon.png')}}">
    </div>



    <!-- overlay -->
    <div id="chat-overlay"></div>

    <!-- chatbot panel -->
    <div id="chatbot">

        <div id="chat-container">
            <button id="drag-handle">
                ━━

                <span id="chat-close">
                    <i class="ph-light ph-x"></i>
                </span>
            </button>

            

            <div id="chatbox">
                <div id="chat-header">

                    <div class="chat-top-row">

                        <div class="chat-greeting">
                            <h2>
                                Hello, {{ Auth::user()->first_name ?? 'User' }}!
                            </h2>
                            <p class="chat-subtext">
                                What do you have in mind?
                            </p>
                        </div>

                        <button id="ask-human-btn">
                            <i class="ph-light ph-chat-circle-text"></i>
                            Talk to human
                        </button>

                    </div>

                    <!-- suggestion slider -->
                    <div id="chat-suggestions"></div>

                </div>
            </div>

            <div id="inputArea">
                <input type="text" id="message" class="chatbot-input" placeholder="Type a message...">
                <button
    type="button"
    class="chatbot-btn"
    aria-label="Send message"
>
    <i class="ph-light ph-paper-plane-tilt"></i>
</button>
            </div>

        </div>
    </div>

    <div id="image-modal">
        <span id="image-close">
            <i class="ph-light ph-x"></i>
        </span>

        <img id="modal-img" src="" alt="Preview">
    </div>

    
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        window.APP_CONFIG = {
            navigateBaseUrl: "{{ url('/navigate') }}",
            markerIcon: "{{ asset('images/map-marker.png') }}"
        };
    </script>

    <script src="{{ asset('jsfiles/public_user/navbar.js')}}"></script>
    <script src="{{ asset('jsfiles/public_user/map.js') }}" defer></script>
    <script src="{{ asset('jsfiles/public_user/chatbot.js') }}"></script>

    

    

</body>
</html>