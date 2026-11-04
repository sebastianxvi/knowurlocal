<div class="legal-modal-backdrop" id="registrationLegalModal" hidden>
    <section class="legal-modal" role="dialog" aria-modal="true" aria-labelledby="registrationLegalModalTitle" aria-describedby="registrationLegalModalDescription">
        <header class="legal-modal-header">
            <div class="legal-modal-heading">
                <span class="legal-modal-kicker">KNOWURLOCAL · ACCOUNT AGREEMENT</span>
                <h2 id="registrationLegalModalTitle">Terms &amp; Privacy</h2>
                <p id="registrationLegalModalDescription">Review the information that governs your account and how the system handles your data.</p>
            </div>
            <button type="button" class="legal-modal-close" data-legal-close aria-label="Close Terms and Privacy Policy">
                <i class="ph ph-x" aria-hidden="true"></i>
            </button>
        </header>
        <nav class="legal-modal-tabs" aria-label="Legal documents">
            <button type="button" class="legal-modal-tab is-active" data-legal-tab="terms" aria-selected="true">Terms of Service</button>
            <button type="button" class="legal-modal-tab" data-legal-tab="privacy" aria-selected="false">Privacy Policy</button>
        </nav>
        <div class="legal-modal-content" tabindex="0" aria-label="Legal document content">
            @include('components.legal-documents')
        </div>
        <footer class="legal-modal-footer">
            <span class="legal-modal-updated"><i class="ph ph-shield-check" aria-hidden="true"></i> Effective November 5, 2026</span>
        </footer>
    </section>
</div>
