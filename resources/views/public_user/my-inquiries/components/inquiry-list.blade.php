<section class="inquiries-section messenger-inquiries" aria-label="Your agency conversations">
    <aside class="messenger-sidebar" aria-label="Ticket conversations">
        <div class="messenger-sidebar-topbar">
            <button type="button" onclick="history.back()" class="messenger-back-button" aria-label="Back to map">
                <i class="ph-light ph-arrow-left" aria-hidden="true"></i>
                <span>Back to map</span>
            </button>
        </div>
        <div class="messenger-sidebar-heading">
            <div>
                <span class="inquiries-section-eyebrow">Your conversations</span>
                <h2>User Tickets</h2>
            </div>
            <div class="messenger-sidebar-heading-actions">
                <span class="messenger-chat-count" data-inquiry-result-meta>{{ $requests->count() }}</span>
                <button type="button" class="messenger-new-ticket-button" data-ticket-modal-open
                    aria-label="Create a new ticket" title="Create a new ticket">
                    <i class="ph-light ph-plus" aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <div class="messenger-sidebar-search inquiries-search" role="search">
            <i class="ph-light ph-magnifying-glass" aria-hidden="true"></i>
            <label for="inquiry-search" class="sr-only">Search agencies and tickets</label>
            <input id="inquiry-search" type="search" data-inquiry-search
                placeholder="Search agencies or tickets..." autocomplete="off" spellcheck="false">
            <button type="button" class="inquiries-search-clear" data-clear-inquiry-search
                aria-label="Clear search" hidden>
                <i class="ph-light ph-x" aria-hidden="true"></i>
            </button>
        </div>
        <div class="messenger-chat-list" data-messenger-chat-list>


        @forelse($requests as $req)

            @include(
                'public_user.my-inquiries.components.inquiry-card',
                ['req' => $req]
            )

        @empty

            @include(
                'public_user.my-inquiries.components.empty-state'
            )

        @endforelse

        </div>
    </aside>
    <section class="messenger-conversation" aria-label="Selected ticket conversation" data-messenger-conversation>
        <div class="messenger-welcome" data-messenger-welcome>
            <div class="messenger-welcome-icon"><i class="ph-light ph-chat-circle-dots" aria-hidden="true"></i></div>
            <h2>Your conversations</h2>
            <p>Select an agency on the left to view your ticket and official responses.</p>
        </div>
        <div class="messenger-conversation-content" data-messenger-conversation-content hidden></div>
    </section>
</section>
