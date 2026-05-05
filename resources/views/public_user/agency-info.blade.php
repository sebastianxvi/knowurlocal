<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $agency->agency_name }}</title>

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />

    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('cssfiles/public_user/agency-info.css') }}">

    <link rel="stylesheet" href="{{ asset('cssfiles/public_user/chatbot.css') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>

<div class="back-floating">
    <button onclick="history.back()">
        <i class="fa-solid fa-chevron-left"></i>
    </button>
</div>

<main>
    <!-- COVER IMAGE -->
<div class="agency-cover">
    <img src="{{ $agency->profile_pic 
        ? asset('storage/' . $agency->profile_pic) 
        : asset('images/default-profile.png') }}">

    <div class="cover-text">
        <h2>{{ $agency->agency_name }}</h2>
        <p class="location">
            <i class="fa-solid fa-location-dot"></i>
            {{ $agency->agency_location }}
        </p>
    </div>
</div>

<div class="card">
    <h4>About</h4>
    <p>{{ $agency->agency_description }}</p>
</div>

<!-- MAP + CONTACT -->
<div class="map-contact">
    <div class="agency-map">
        
        <div class="map-wrapper">
            <div id="agencyMap"
                data-lat="{{ $agency->lat ?? 12.353984 }}"
                data-lng="{{ $agency->lng ?? 121.067504 }}">
            </div>

            <!-- Direction Button Overlay -->
            <a href="{{ route('navigate', $agency->id) }}" class="map-direction-btn">
                <i class="fa-solid fa-location-arrow"></i>
                Directions
            </a>
        </div>

    </div>

    <div class="agency-contact">
    <h4>Contact</h4>

    <div class="contact-item">
    <i class="fa-solid fa-phone"></i>

    <a href="tel:{{ $agency->agency_hotline }}">
        {{ $agency->agency_hotline }}
    </a>

    <button class="copy-btn" 
        onclick="copyText('{{ $agency->agency_hotline }}')">
        <i class="fa-regular fa-copy"></i>
    </button>
</div>

    @if($agency->agency_landline)
    <div class="contact-item">
        <i class="fa-solid fa-phone"></i>
        <span>{{ $agency->agency_landline }}</span>
    </div>
    @endif

    <div class="contact-item">
        <i class="fa-regular fa-envelope"></i>

        <a href="mailto:{{ $agency->agency_email }}">
            {{ $agency->agency_email }}
        </a>

        <button class="copy-btn" 
            onclick="copyText('{{ $agency->agency_email }}')">
            <i class="fa-regular fa-copy"></i>
        </button>
    </div>
</div>
</div>

<!-- SERVICES (placeholder if you add later) -->
{{-- <div class="card">
    <h4>Services</h4>
    <p>No services listed yet.</p>
</div> --}}

<!-- OFFICE HOURS -->
<div class="card">
    <h4>Office Hours</h4>
    <p>{{ $agency->office_hours }}</p>
</div>

    <!-- Chatbot placeholder -->
    <div id="chat-toggle">
        <img src="{{asset('images/chatbot-icon.png')}}">
    </div>
</main>

    <!-- overlay -->
    <div id="chat-overlay"></div>

    <!-- chatbot panel -->
    <div id="chatbot"
     data-agency="{{ $agency->id }}"
     data-agency-name="{{ $agency->agency_name }}">

        <div id="chat-container">
            <button id="drag-handle">
                ━━

                <span id="chat-close">
                    <i class="fa-solid fa-xmark"></i>
                </span>
            
            </button>

            <div id="chatbox">
                <div id="chat-header">
                    <img src="{{asset('images/logo.png')}}">
                    <p>Ask your questions about the offices of NGAs and NGOs of San Jose, Occidental Mindoro.</p>
                </div>
            </div>

            <div id="inputArea">
                <input type="text" id="message" class="chatbot-input" placeholder="Type a message...">
                <button onclick="sendMessage()" class="chatbot-btn">
                    <img src="{{asset('images/logo.png')}}">
                </button>
            </div>

        </div>
    </div>
    

    <script src="{{ asset('jsfiles/public_user/chatbot.js') }}"></script>





    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        console.log("Agency ID:", agencyId);
        var agencyMapEl = document.getElementById('agencyMap');
        var lat = parseFloat(agencyMapEl.dataset.lat);
        var lng = parseFloat(agencyMapEl.dataset.lng);

        var agencyMap = L.map('agencyMap', { zoomControl: false }).setView([lat, lng], 16);
        agencyMap.panBy([-10, -30]);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(agencyMap);

        var icon = L.icon({
                        iconUrl: "{{ asset('images/map-marker.png') }}",
                        iconSize: [40, 60],
                        iconAnchor: [30, 60],
                        popupAnchor: [0, -60]
                    });

        L.marker([lat, lng], {icon: icon}).addTo(agencyMap)
            // .bindPopup(`<strong>{{ $agency->agency_name }}</strong>`)
            // .openPopup();


            function copyText(text) {
                navigator.clipboard.writeText(text)
                    .then(() => {
                        alert("Copied!");
                    })
                    .catch(() => {
                        alert("Failed to copy");
                    });
            }



            let routingControl;
let watchId;

function startNavigation() {

    if (!navigator.geolocation) {
        alert("GPS not supported");
        return;
    }

    navigator.geolocation.getCurrentPosition(function(position) {

        const userLat = position.coords.latitude;
        const userLng = position.coords.longitude;

        // Remove old route if exists
        if (routingControl) {
            agencyMap.removeControl(routingControl);
        }

        routingControl = L.Routing.control({
            waypoints: [
                L.latLng(userLat, userLng),
                L.latLng(lat, lng) // agency location
            ],
            routeWhileDragging: false,
            addWaypoints: false,
            draggableWaypoints: false
        }).addTo(agencyMap);

        // 🔥 TRACK USER MOVEMENT (THIS IS THE MAGIC)
        watchId = navigator.geolocation.watchPosition(function(pos) {

            const newLat = pos.coords.latitude;
            const newLng = pos.coords.longitude;

            // Update route dynamically
            routingControl.setWaypoints([
                L.latLng(newLat, newLng),
                L.latLng(lat, lng)
            ]);

        });

    }, function() {
        alert("Please enable GPS");
    });
}
    </script>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

</body>
</html>