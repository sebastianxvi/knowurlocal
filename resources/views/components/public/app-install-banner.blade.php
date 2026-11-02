{{-- Site-wide, dismissible PWA install banner. Kept in normal document flow. --}}
<link rel="stylesheet" href="{{ asset('cssfiles/public_user/app-install-banner.css') }}?v=4">
<aside class="kyl-app-install-banner" data-app-install-banner aria-label="Open KNOWURLOCAL app" hidden>
    <div class="kyl-app-install-banner-inner">
        <div class="kyl-app-install-banner-icon" aria-hidden="true">
            <img src="{{ asset('images/knowurlocal-kyl-logo.svg') }}" alt="">
        </div>
        <div class="kyl-app-install-banner-copy">
            <p class="kyl-app-install-banner-title">Open KNOWURLOCAL</p>
            <p class="kyl-app-install-banner-description">Quick access to local agencies and services.</p>
            <p class="kyl-app-install-banner-help" data-app-install-help hidden></p>
        </div>
        <button type="button" class="kyl-app-install-banner-action" data-app-install-action>
            <i class="ph-light ph-download-simple" aria-hidden="true"></i>
            <span>Install App</span>
        </button>
        <button type="button" class="kyl-app-install-banner-dismiss" data-app-install-dismiss aria-label="Dismiss app install banner" title="Dismiss">
            <i class="ph-light ph-x" aria-hidden="true"></i>
        </button>
    </div>
</aside>
<script src="{{ asset('jsfiles/public_user/app-install-banner.js') }}?v=4" defer></script>
