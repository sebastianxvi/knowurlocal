<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Navigate to {{ $agency->agency_name }}</title>

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Routing -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css"/>
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

<!-- Phosphor Icons (light weight as per your system) -->
<script src="https://unpkg.com/phosphor-icons"></script>

<link rel="stylesheet" href="{{ asset('cssfiles/public_user/navigation.css') }}">
</head>

<body>

<div class="nav-pill">

    <button class="pill-back" onclick="window.location.href='/map'">
        <i class="ph-light ph-arrow-left"></i>
    </button>

    <div class="pill-text">
        <span class="pill-sub">Navigating to</span>

        <span class="pill-name">
            {{ $agency->agency_abbreviation ?? $agency->agency_name }}
        </span>
    </div>

</div>

<!-- ROUTE BUTTON -->
<!-- ROUTE CONTROL -->
<div class="route-toggle">

    <!--
        Main directions button.

        This remains available when the contextual
        hint is not being displayed.
    -->
    <button
        id="openRoute"
        aria-label="View directions"
    >
        <i class="ph-light ph-path"></i>
    </button>


    <!--
        Contextual route hint.

        The entire hint is clickable and opens
        the directions panel for the selected mode.
    -->
    <button 
    class="route-hint" 
    id="routeHint" 
    type="button" 
    aria-label="View selected route directions"
    tabindex="-1"
>

        <i
            class="ph-light ph-navigation-arrow"
            id="routeHintIcon"
        ></i>


        <span class="route-hint-text">

            <strong id="routeHintTitle">
                View route
            </strong>

            <span id="routeHintSubtitle">
                See directions
            </span>

        </span>

    </button>

</div>


<!-- DIRECTIONS MODAL -->
<div class="route-modal" id="routeModal">

    <div class="route-modal-content">

        <div class="route-modal-header">

    <div class="route-mode-header">

        <div class="route-mode-icon" id="routeModeIcon">
            <i class="ph-light ph-car"></i>
        </div>

        <div class="route-mode-text">

            <span id="routeModeName">
                Car directions
            </span>

            <span id="routeModeSummary">
                Calculating route...
            </span>

        </div>

    </div>

    <button id="closeRoute" aria-label="Close directions">
        <i class="ph-light ph-x"></i>
    </button>

</div>

        <div id="routeContainer"></div>

    </div>

</div>

<!-- MAP -->
<div
    id="navMap"
    data-lat="{{ $agency->lat }}"
    data-lng="{{ $agency->lng }}"
    data-name="{{ $agency->agency_name }}"
></div>


<!-- TRAVEL TIME SUMMARY -->
<div class="travel-time-card" id="travelTimeCard">

    <div class="travel-time-header">
        <div>
            <span class="travel-time-label">Estimated travel time</span>
            <span class="travel-time-subtext">
                Based on your current location
            </span>
        </div>

        <i class="ph-light ph-clock"></i>
    </div>

    <div class="travel-time-options">

        <!-- WALKING -->
        <div
    class="travel-mode"
    data-mode="walking"
    role="button"
    tabindex="0"
    aria-pressed="false"
    aria-label="Select walking route"
>
            <div class="travel-mode-icon">
                <i class="ph-light ph-person-simple-walk"></i>
            </div>

            <div class="travel-mode-info">
                <span class="travel-mode-name">Walking</span>
                <span class="travel-mode-time">Calculating...</span>
            </div>
        </div>

        <!-- BICYCLE -->
        <div
    class="travel-mode"
    data-mode="cycling"
    role="button"
    tabindex="0"
    aria-pressed="false"
    aria-label="Select bicycle route"
>
            <div class="travel-mode-icon">
                <i class="ph-light ph-bicycle"></i>
            </div>

            <div class="travel-mode-info">
                <span class="travel-mode-name">Bicycle</span>
                <span class="travel-mode-time">Calculating...</span>
            </div>
        </div>

        <!-- CAR -->
        <div
    class="travel-mode"
    data-mode="driving"
    role="button"
    tabindex="0"
    aria-pressed="false"
    aria-label="Select car route"
>
            <div class="travel-mode-icon">
                <i class="ph-light ph-car"></i>
            </div>

            <div class="travel-mode-info">
                <span class="travel-mode-name">Car</span>
                <span class="travel-mode-time">Calculating...</span>
            </div>
        </div>

    </div>

</div>

<script src="{{ asset('jsfiles/public_user/navigation.js') }}" defer></script>

</body>
</html>