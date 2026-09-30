<!DOCTYPE html>
<html lang="en">

<head>

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
        href="{{ asset('cssfiles/public_user/my-inquiries/index.css') }}?v={{ filemtime(public_path('cssfiles/public_user/my-inquiries/index.css')) }}"
    >

</head>

<body>

<div class="inquiries-page">

    @include('public_user.my-inquiries.components.header')

    @include(
        'public_user.my-inquiries.components.inquiry-list',
        ['requests' => $requests]
    )

</div>


@include(
    'public_user.my-inquiries.components.image-lightbox'
)


<!-- Laravel Echo / Ably -->
@vite('resources/js/echo.js')

<!-- Local QR generator shared with the chatbot attachment renderer -->
<script src="{{ asset('jsfiles/public_user/qrcode-core.js') }}?v={{ filemtime(public_path('jsfiles/public_user/qrcode-core.js')) }}"></script>

<!-- Page JavaScript -->
<script
    type="module"
    src="{{ asset('jsfiles/public_user/my-inquiries/index.js') }}?v={{ filemtime(public_path('jsfiles/public_user/my-inquiries/index.js')) }}"
></script>

</body>

</html>