<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Admin')</title>

<!-- FONT -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

<!-- ICONS -->
<script src="https://unpkg.com/@phosphor-icons/web"></script>

<!-- LEAFLET -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

<!-- GLOBAL CSS -->
<link rel="stylesheet" href="{{ asset('cssfiles/admin/admin.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/components/modal.css') }}">

<!-- PAGE CSS -->
@stack('styles')

</head>
<body>

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

<!-- ================= GLOBAL MODAL (CRITICAL POSITION) ================= -->
{{-- This MUST be before scripts so JS can access it --}}
@include('components.modal')

<!-- ================= GLOBAL SCRIPTS ================= -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

{{-- 🔥 MODAL SYSTEM (LOAD FIRST BEFORE ANY PAGE JS) --}}
<script src="{{ asset('jsfiles/components/modal-system.js') }}"></script>

<!-- ================= PAGE SCRIPTS ================= -->
@stack('scripts')

</body>
</html>