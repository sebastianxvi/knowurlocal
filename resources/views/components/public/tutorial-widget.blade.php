@php
    // A successful login regenerates Laravel's session ID. Using a one-way
    // fingerprint lets the launcher dismissal reset on the next login.
    $tutorialSessionKey = hash('sha256', session()->getId());
@endphp

<link rel="stylesheet" href="{{ asset('cssfiles/public_user/tutorial-widget.35cff928e356.css') }}">
<div class="kul-tutorial" data-tutorial-widget data-tutorial-session="{{ $tutorialSessionKey }}">
    <div class="kul-tutorial-launcher" data-tutorial-launcher>
        <button class="kul-tutorial-open" type="button" aria-label="Watch KNOWURLOCAL tutorial"
                title="Watch tutorial" aria-haspopup="dialog" aria-controls="kulTutorialDialog">
            <i class="ph-light ph-play-circle" aria-hidden="true"></i>
        </button>
        <button class="kul-tutorial-dismiss" type="button" aria-label="Hide tutorial button" title="Hide tutorial button">
            <i class="ph-light ph-x" aria-hidden="true"></i>
        </button>
    </div>

    <div class="kul-tutorial-dialog" id="kulTutorialDialog" role="dialog" aria-modal="true"
         aria-labelledby="kulTutorialTitle" aria-hidden="true" hidden>
        <div class="kul-tutorial-backdrop" data-tutorial-close></div>
        <section class="kul-tutorial-panel" role="document">
            <header class="kul-tutorial-header">
                <div>
                    <p class="kul-tutorial-eyebrow">KNOWURLOCAL GUIDE</p>
                    <h2 id="kulTutorialTitle">How to use KNOWURLOCAL</h2>
                </div>
                <button class="kul-tutorial-modal-close" type="button" aria-label="Close tutorial video" data-tutorial-close>
                    <i class="ph-light ph-x" aria-hidden="true"></i>
                </button>
            </header>
            <div class="kul-tutorial-video-wrap">
                <video
                    data-tutorial-video
                    title="KNOWURLOCAL tutorial video"
                    controls
                    playsinline
                    preload="metadata"
                    controlslist="nodownload noplaybackrate"
                    aria-label="KNOWURLOCAL tutorial video">
                    <source src="{{ asset('videos/knowurlocal%20introduction.mp4') }}" type="video/mp4">
                    Your browser does not support HTML5 video playback.
                </video>
            </div>
        </section>
    </div>
</div>
<script src="{{ asset('jsfiles/public_user/tutorial-widget.006f92111f3a.js') }}" defer></script>
