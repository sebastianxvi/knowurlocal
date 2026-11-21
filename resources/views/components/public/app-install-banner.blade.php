{{-- Platform-aware app distribution entry point shared across the public website. --}}
<link rel="stylesheet" href="{{ asset('cssfiles/public_user/app-install-banner.css') }}?v={{ substr(hash_file('sha256', public_path('cssfiles/public_user/app-install-banner.css')), 0, 16) }}">
<aside class="kyl-app-install-banner" data-app-install-banner data-android-apk-url="{{ route('android.app.download') }}" data-android-apk-status-url="{{ route('android.app.download-status') }}" aria-label="Open KNOWURLOCAL app" hidden>
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

<dialog class="kyl-install-dialog" data-kyl-install-dialog aria-labelledby="kyl-install-dialog-title">
    <div class="kyl-install-dialog-card">
        <button type="button" class="kyl-install-dialog-close" data-kyl-install-close aria-label="Close installation instructions">
            <i class="ph-light ph-x" aria-hidden="true"></i>
        </button>
        <div class="kyl-install-dialog-icon" aria-hidden="true">
            <img src="{{ asset('images/knowurlocal-kyl-logo.svg') }}" alt="">
        </div>
        <p class="kyl-install-dialog-eyebrow">KNOWURLOCAL ON YOUR DEVICE</p>
        <h2 id="kyl-install-dialog-title" data-kyl-install-title>Add KNOWURLOCAL to your device</h2>
        <p class="kyl-install-dialog-description" data-kyl-install-description></p>
        <ol class="kyl-install-dialog-steps" data-kyl-install-steps></ol>
        <p class="kyl-install-dialog-note" data-kyl-install-note></p>
        <button type="button" class="kyl-install-dialog-primary" data-kyl-install-done>Got it</button>
    </div>
</dialog>

<script src="{{ asset('jsfiles/public_user/app-install-banner.js') }}?v={{ substr(hash_file('sha256', public_path('jsfiles/public_user/app-install-banner.js')), 0, 16) }}"></script>
