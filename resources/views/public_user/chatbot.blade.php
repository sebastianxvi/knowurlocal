<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot</title>

    <link rel="stylesheet" href="{{ asset('cssfiles/public_user/chatbot.css') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    
    <!-- floating button -->
    <div id="chat-toggle">💬</div>

    <!-- overlay -->
    <div id="chat-overlay"></div>

    <!-- chatbot panel -->
    <div id="chatbot">

        <div id="chat-container">
            <button id="drag-handle">━━</button>

            <button id="chat-close">
                <i class="fa-solid fa-xmark"></i>
            </button>
            
            <div id="chatbox">
                <div id="chat-header">
                    <img src="{{asset('images/logo.png')}}">
                    <p>Need help with an agency service? Ask about requirements, procedures, office hours, and more.</p>
                </div>
            </div>

            <button
                type="button"
                id="chat-send"
                class="chatbot-btn"
                aria-label="Send message"
            >
                <img
                    src="{{ asset('images/logo.png') }}"
                    alt=""
                    aria-hidden="true"
                >
            </button>

        </div>
    </div>
    

    <script src="{{ asset('jsfiles/public_user/chatbot.js') }}"></script>
</body>
</html>