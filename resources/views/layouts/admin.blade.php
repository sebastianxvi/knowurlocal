
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



<!-- ================= GLOBAL MODAL ================= -->

{{-- This MUST be before scripts so JS can access it. --}}

@include('components.modal')



<!-- ================= GLOBAL SCRIPTS ================= -->

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>


{{-- MODAL SYSTEM --}}

<script src="{{ asset('jsfiles/components/modal-system.js') }}"></script>



<!-- ================= GLOBAL REALTIME NOTIFICATIONS ================= -->


<script>
(function () {
    const badge = document.getElementById('support-request-badge');
    if (!badge) return;

    const renderBadge = (count) => {
        const safeCount = Math.max(0, Number(count) || 0);
        badge.dataset.count = String(safeCount);
        badge.textContent = safeCount > 99 ? '99+' : String(safeCount);
        badge.classList.toggle('is-hidden', safeCount === 0);
        badge.setAttribute('aria-label', `${safeCount} new support requests`);
    };

    const addOne = () => renderBadge((Number(badge.dataset.count) || 0) + 1);

    let attempts = 0;
    const subscribe = () => {
        if (!window.Echo) {
            if (attempts++ < 30) setTimeout(subscribe, 250);
            return;
        }

        try {
            window.Echo.private('admin.support-requests')
                .listen('.support.request.created', (event) => {
                    // The Support Requests page acknowledges the queue server-side.
                    // Other admin pages increment their local notification count.
                    if (!window.location.pathname.startsWith('/admin/support-requests')) {
                        addOne();
                    }
                })
                .listen('.support.request.assigned', (event) => {
                    const row = document.querySelector(`[data-ticket-id=\"${event.id}\"]`);
                    const select = row?.querySelector('.collaboration-assign-select');
                    if (select) {
                        select.value = event.admin_id ? String(event.admin_id) : '';
                    }
                });
        } catch (error) {
            console.warn('Admin support notification subscription failed.', error);
        }
    };

    subscribe();
})();
</script>

<!-- ================= PAGE SCRIPTS ================= -->

@stack('scripts')



</body>

</html>