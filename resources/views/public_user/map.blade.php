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
                <button onclick="sendMessage()" class="chatbot-btn">
                    <img src="{{asset('images/logo.png')}}">
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