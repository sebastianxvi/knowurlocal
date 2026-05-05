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
<div class="route-toggle">
    <button id="openRoute">
        <i class="ph-light ph-path"></i>
    </button>
</div>

<!-- MODAL -->
<div class="route-modal" id="routeModal">
    <div class="route-modal-content">

        <div class="route-modal-header">
            <span>Directions</span>
            <button id="closeRoute">&times;</button>
        </div>

        <div id="routeContainer"></div>

    </div>
</div>

<!-- MAP -->
<div id="navMap"
     data-lat="{{ $agency->lat }}"
     data-lng="{{ $agency->lng }}"
     data-name="{{ $agency->agency_name }}">
</div>

<div class="sheet" id="sheet">

    <div class="sheet-handle" id="sheetHandle"></div>

    <div class="sheet-content">

    <!-- COVER -->
    <div class="agency-cover">
        <img src="{{ $agency->agency_image 
            ? asset('storage/' . $agency->agency_image) 
            : asset('images/default-agency.png') }}">
    </div>

    <div class="sheet-inner">
        <div class="cover-text">

    <!-- NAME + ABBR -->
    <div class="agency-header">
        <h2>{{ $agency->agency_name }}</h2>

        @if($agency->agency_abbreviation)
        <span class="agency-abbr">
            {{ $agency->agency_abbreviation }}
        </span>
        @endif
    </div>

    <!-- LOCATION -->
    <p class="location">
        {{ $agency->agency_location }}
    </p>

    <!-- TYPE -->
    @if($agency->type)
    <p class="agency-type">
        <i class="ph-light ph-buildings"></i>
        {{ $agency->type->name ?? 'Unknown Type' }}
    </p>
    @endif

</div>
    

    <!-- ABOUT -->
    @if($agency->agency_description)
    <div class="card">
        <h4>About</h4>
        <p>{{ $agency->agency_description }}</p>
    </div>
    @endif

    <!-- CONTACT -->
    <div class="card">
    <h4>Contact</h4>

    @if($agency->agency_hotline)
    <div class="contact-item">
        <i class="ph-light ph-phone"></i>

        <a href="tel:{{ $agency->agency_hotline }}">
            {{ $agency->agency_hotline }}
        </a>

        <button class="copy-btn" data-copy="{{ $agency->agency_hotline }}">
            <i class="ph-light ph-copy"></i>
        </button>
    </div>
    @endif

    @if($agency->agency_landline)
    <div class="contact-item">
        <i class="ph-light ph-device-mobile"></i>

        <span>{{ $agency->agency_landline }}</span>

        <button class="copy-btn" data-copy="{{ $agency->agency_landline }}">
            <i class="ph-light ph-copy"></i>
        </button>
    </div>
    @endif

    @if($agency->agency_email)
    <div class="contact-item">
        <i class="ph-light ph-envelope"></i>

        <a href="mailto:{{ $agency->agency_email }}">
            {{ $agency->agency_email }}
        </a>

        <button class="copy-btn" data-copy="{{ $agency->agency_email }}">
            <i class="ph-light ph-copy"></i>
        </button>
    </div>
    @endif

    @if($agency->agency_website)
    <div class="contact-item">
        <i class="ph-light ph-globe"></i>

        <a href="{{ $agency->agency_website }}" target="_blank">
            Official Website link
        </a>

        <button class="copy-btn" data-copy="{{ $agency->agency_website }}">
            <i class="ph-light ph-copy"></i>
        </button>
    </div>
    @endif

    @if($agency->agency_fb)
    <div class="contact-item">
        <i class="ph-light ph-facebook-logo"></i>

        <a href="{{ $agency->agency_fb }}" target="_blank">
            Facebook Page
        </a>

        <button class="copy-btn" data-copy="{{ $agency->agency_fb }}">
            <i class="ph-light ph-copy"></i>
        </button>
    </div>
    @endif

</div>

    <!-- OFFICE HOURS -->
    @if($agency->office_hours)
    <div class="card">
        <h4>Office Hours</h4>
        <p>{{ $agency->office_hours }}</p>
    </div>
    @endif

</div>
</div>
</div>

<script src="{{ asset('jsfiles/public_user/navigation.js') }}" defer></script>

</body>
</html>