
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    {{-- Laravel CSRF token --}}
    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    {{-- Laravel broadcasting authentication endpoint --}}
    <meta
    name="broadcast-auth-endpoint"
    content="{{ url('/broadcasting/auth') }}"
        >
        <meta name="realtime-driver" content="{{ config('broadcasting.default') }}"
>
    <meta name="reverb-app-key" content="{{ config('broadcasting.connections.reverb.key') }}">
    <meta name="reverb-host" content="{{ config('broadcasting.connections.reverb.options.host') }}">
    <meta name="reverb-port" content="{{ config('broadcasting.connections.reverb.options.port') }}">
    <meta name="reverb-scheme" content="{{ config('broadcasting.connections.reverb.options.scheme') }}">
    <meta name="realtime-debug" content="{{ app()->environment('local') ? 'true' : 'false' }}">


    <title>
        @yield('title', 'Admin')
    </title>


    <!-- FONT -->

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap"
        rel="stylesheet"
    >


    <!-- ICONS -->

    <script src="https://unpkg.com/@phosphor-icons/web@2.1.1"></script>


    <!-- LEAFLET -->

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet/dist/leaflet.css"
    >


    <!-- GLOBAL CSS -->

    <link
        rel="stylesheet"
        href="{{ asset('cssfiles/admin/admin.css') }}"
    >

    <link
        rel="stylesheet"
        href="{{ asset('cssfiles/components/modal.css') }}"
    >


    <!-- VITE / LARAVEL ECHO -->

    @vite(['resources/js/app.js'])


    <!-- PAGE CSS -->

    @stack('styles')

    {{-- Shared admin design system is loaded last so page-specific
         styles can refine it without creating unrelated visual systems. --}}
    <link
        rel="stylesheet"
        href="{{ asset('cssfiles/admin/admin-design-system.css') }}"
    >


</head>


<body
    data-admin-page="@yield('admin-page')"
    data-admin-user-id="{{ auth()->id() }}"
>
<div class="admin-drawer-backdrop" data-admin-shell-close aria-hidden="true"></div>

<div class="layout">


    {{-- SIDEBAR --}}

    @include('partials.sidebar')


    {{-- MAIN --}}

    <main class="main">


        {{-- HEADER --}}

        @include('partials.header')


        {{-- PAGE CONTENT --}}

        <div class="content">

            @yield('content')

        </div>


    </main>


</div>



<!-- ================= GLOBAL MODAL ================= -->

{{-- This MUST be before scripts so JS can access it. --}}

@include('components.modal')



<!-- ================= GLOBAL SCRIPTS ================= -->

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>


{{-- MODAL SYSTEM --}}

<script src="{{ asset('jsfiles/components/modal-system.js') }}"></script>
<script>
    // Shared feedback for every admin route; the modal system consumes each message once.
    window.__FLASH_SUCCESS__ = @json(session('success'));
    window.__FLASH_ERROR__ = @json(session('error'));
    window.__FLASH_WARNING__ = @json(session('warning'));
    window.__FLASH_INFO__ = @json(session('info'));
</script>

<script src="{{ asset('jsfiles/admin/admin-shell.js') }}"></script>
<script src="{{ asset('jsfiles/admin/admin-notifications.js') }}"></script>



<!-- ================= PAGE SCRIPTS ================= -->

@stack('scripts')



</body>

</html>