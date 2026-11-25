@php
    $tutorialVideoId = '1886zanQdGeGfm3qRrBYQlJIIrobAg2ay';
    // A successful login regenerates Laravel's session ID. Using a one-way
    // fingerprint lets the launcher dismissal reset on the next login.
    $tutorialSessionKey = hash('sha256', session()->getId());
@endphp

<link rel="stylesheet" href="{{ asset('cssfiles/public_user/tutorial-widget.357f5af01c0a.css') }}">
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
                <iframe
                    data-tutorial-frame
                    title="KNOWURLOCAL tutorial video"
                    data-src="https://drive.google.com/file/d/{{ $tutorialVideoId }}/preview"
                    allow="autoplay; encrypted-media; fullscreen; picture-in-picture"
                    allowfullscreen
                    referrerpolicy="strict-origin-when-cross-origin">
                </iframe>
            </div>
        </section>
    </div>
</div>
<script src="{{ asset('jsfiles/public_user/tutorial-widget.473fa873d2d7.js') }}" defer></script>
