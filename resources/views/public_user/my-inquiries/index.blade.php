<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/knowurlocal-kyl-logo.svg') }}?v=3" sizes="any">
    <link rel="alternate icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=3">


    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <meta
        name="user-id"
        content="{{ auth()->id() }}"
    >

    <meta
        name="broadcast-auth-endpoint"
        content="{{ url('/broadcasting/auth') }}"
    >
    <meta name="realtime-driver" content="{{ config('broadcasting.default') }}">
    <meta name="reverb-app-key" content="{{ config('broadcasting.connections.reverb.key') }}">
    <meta name="reverb-host" content="{{ config('broadcasting.connections.reverb.options.host') }}">
    <meta name="reverb-port" content="{{ config('broadcasting.connections.reverb.options.port') }}">
    <meta name="reverb-scheme" content="{{ config('broadcasting.connections.reverb.options.scheme') }}">
    <meta name="realtime-debug" content="{{ app()->environment('local') ? 'true' : 'false' }}">


    <title>KNOWURLOCAL | My Inquiries</title>

    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/phosphor-icons@1.4.2"></script>

    <!-- Global theme -->
    <link
        rel="stylesheet"
        href="{{ asset('cssfiles/theme.css') }}"
    >

    <!-- My Inquiries styles -->
    <link
        rel="stylesheet"
        href="{{ asset('cssfiles/public_user/my-inquiries/index.css') }}?v={{ md5_file(public_path('cssfiles/public_user/my-inquiries/index.css')) }}&release=ticket-subject-expanded-20261119"
    >

    <link rel="stylesheet" href="{{ asset('cssfiles/public_user/typography.css') }}?v=20261104-field-focus-reset&build={{ file_exists(public_path('cssfiles/public_user/typography.css')) ? filemtime(public_path('cssfiles/public_user/typography.css')) : time() }}">
    <link rel="stylesheet" href="{{ asset('cssfiles/components/image-placeholders.css') }}?v={{ file_exists(public_path('cssfiles/components/image-placeholders.css')) ? filemtime(public_path('cssfiles/components/image-placeholders.css')) : time() }}">
</head>

<body>

<div class="inquiries-page">

    @include(
        'public_user.my-inquiries.components.inquiry-list',
        ['requests' => $requests]
    )

    @include(
        'public_user.my-inquiries.components.create-ticket-modal',
        ['agencies' => $agencies]
    )

</div>


@include(
    'public_user.my-inquiries.components.image-lightbox'
)

<div class="response-qr-lightbox" id="response-qr-lightbox" role="dialog" aria-modal="true" aria-labelledby="response-qr-lightbox-title" aria-hidden="true">
    <div class="response-qr-lightbox-panel">
        <button type="button" class="response-qr-lightbox-close" aria-label="Close QR code preview"><i class="ph-light ph-x" aria-hidden="true"></i></button>
        <div class="response-qr-lightbox-heading"><i class="ph-light ph-qr-code" aria-hidden="true"></i><h2 id="response-qr-lightbox-title">QR code</h2></div>
        <div class="response-qr-lightbox-code response-qr-code" data-qr-value="" role="img" aria-label="Scannable QR code"></div>
        <a class="response-qr-lightbox-destination" href="#" target="_blank" rel="noopener noreferrer">Open destination <i class="ph-light ph-arrow-up-right" aria-hidden="true"></i></a>
    </div>
</div>

@include('components.modal')
<link rel="stylesheet" href="{{ asset('cssfiles/components/modal.css') }}?v={{ filemtime(public_path('cssfiles/components/modal.css')) }}">
<script src="{{ asset('jsfiles/components/modal-system.js') }}?v={{ filemtime(public_path('jsfiles/components/modal-system.js')) }}"></script>


<!-- Laravel Echo / Reverb -->
@vite('resources/js/echo.js')

<!-- Local QR generator shared with the chatbot attachment renderer -->
<script src="{{ asset('jsfiles/public_user/qrcode-core.js') }}?v={{ filemtime(public_path('jsfiles/public_user/qrcode-core.js')) }}"></script>
<!-- Load the QR viewer independently so slow mobile module imports cannot leave QR buttons inert. -->
<script
    type="module"
    src="{{ asset('jsfiles/public_user/my-inquiries/components/qr-viewer.js') }}?v={{ filemtime(public_path('jsfiles/public_user/my-inquiries/components/qr-viewer.js')) }}-mobile-qr-square-3"
></script>

<!-- Page JavaScript -->
<script
    type="module"
    src="{{ asset('jsfiles/public_user/my-inquiries/index.js') }}?v={{ md5_file(public_path('jsfiles/public_user/my-inquiries/index.js')) }}&release=ticket-subject-expanded-20261119"
></script>

</body>

</html>