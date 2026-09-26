<div class="topbar">

    <div class="header-left">
        <h1 class="page-title">@yield('page-title')</h1>
        <p class="page-subtitle">@yield('page-subtitle')</p>
    </div>

    <div class="header-right">

        {{-- <div class="notification">
            <i class="ph-light ph-bell"></i>
            <span class="notif-dot"></span>
        </div> --}}

        <div class="profile-text">

            @php
                $user = auth()->user();
                $roleLabel = $user->role === 'superadmin' ? 'Superadmin' : 'Admin';
            @endphp

            <span class="greeting">
                Hello, <strong>{{ $roleLabel }}</strong>
            </span>

            <span class="username">
                {{ $user->first_name }}
            </span>

        </div>

    </div>

</div>