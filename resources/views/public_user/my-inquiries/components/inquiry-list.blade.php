<section class="inquiries-toolbar" aria-label="Inquiry filters">

    <div class="inquiries-search-row">

        <div class="inquiries-search" role="search">

            <i
                class="ph-light ph-magnifying-glass"
                aria-hidden="true"
            ></i>

            <label
                for="inquiry-search"
                class="sr-only"
            >
                Search your inquiries
            </label>

            <input
                id="inquiry-search"
                type="search"
                data-inquiry-search
                placeholder="Search questions, agencies, or #number"
                autocomplete="off"
                spellcheck="false"
            >

            <button
                type="button"
                class="inquiries-search-clear"
                data-clear-inquiry-search
                aria-label="Clear inquiry search"
                hidden
            >
                <i class="ph-light ph-x" aria-hidden="true"></i>
            </button>

        </div>

    </div>


    <div class="inquiries-filter-picker" data-inquiry-filter-picker>

        <button
            type="button"
            class="inquiries-filter-trigger"
            data-inquiry-filter-trigger
            aria-expanded="false"
            aria-controls="inquiry-filter-drawer"
        >
            <span class="inquiries-filter-trigger-leading">
                <i class="ph-light ph-funnel" aria-hidden="true"></i>
                <span>Filter inquiries</span>
            </span>
            <span class="inquiries-filter-trigger-selection">
                <span data-inquiry-filter-label>Needs Attention</span>
                <strong data-inquiry-filter-trigger-count>0</strong>
                <span
                    class="inquiries-filter-unread-badge inquiries-filter-trigger-unread"
                    data-filter-unread-count
                    data-filter="needs_attention"
                    data-filter-unread-initial="{{ $unreadNeedsAttentionCount ?? 0 }}"
                    @if(($unreadNeedsAttentionCount ?? 0) <= 0) hidden @endif
                    aria-label="{{ ($unreadNeedsAttentionCount ?? 0) }} new {{ ($unreadNeedsAttentionCount ?? 0) === 1 ? 'response' : 'responses' }} needing attention"
                >{{ ($unreadNeedsAttentionCount ?? 0) > 99 ? '99+' : ($unreadNeedsAttentionCount ?? 0) }}</span>
                <i class="ph-light ph-caret-down" aria-hidden="true"></i>
            </span>
        </button>

        <div
            id="inquiry-filter-drawer"
            class="inquiries-filter-drawer"
            data-inquiry-filter-drawer
            hidden
        >
<div
                class="inquiries-filter-tabs"
                role="group"
                aria-label="Filter inquiries"
            >

                <button
                    type="button"
                    class="inquiries-filter-tab active"
                    data-filter="needs_attention"
                    aria-pressed="true"
                >
                    <i class="ph-light ph-warning-circle" aria-hidden="true"></i>
                    <span>Needs Attention</span>
                    <strong data-filter-count>0</strong>
                    <span
                        class="inquiries-filter-unread-badge"
                        data-filter-unread-count
                        data-filter="needs_attention"
                        data-filter-unread-initial="{{ $unreadNeedsAttentionCount ?? 0 }}"
                        @if(($unreadNeedsAttentionCount ?? 0) <= 0) hidden @endif
                        aria-label="{{ ($unreadNeedsAttentionCount ?? 0) }} new {{ ($unreadNeedsAttentionCount ?? 0) === 1 ? 'response' : 'responses' }} needing attention"
                    >{{ ($unreadNeedsAttentionCount ?? 0) > 99 ? '99+' : ($unreadNeedsAttentionCount ?? 0) }}</span>
                </button>

                <button type="button" class="inquiries-filter-tab" data-filter="pending" aria-pressed="false">
                    <i class="ph-light ph-clock" aria-hidden="true"></i>
                    <span>Pending</span>
                    <strong data-filter-count>0</strong>
                </button>

                <button type="button" class="inquiries-filter-tab" data-filter="follow_up" aria-pressed="false">
                    <i class="ph-light ph-arrow-counter-clockwise" aria-hidden="true"></i>
                    <span>Follow-up</span>
                    <strong data-filter-count>0</strong>
                </button>

                <button type="button" class="inquiries-filter-tab" data-filter="answered" aria-pressed="false">
                    <i class="ph-light ph-check-circle" aria-hidden="true"></i>
                    <span>Answered</span>
                    <strong data-filter-count>0</strong>
                </button>

                <button type="button" class="inquiries-filter-tab" data-filter="trashed" aria-pressed="false">
                    <i class="ph-light ph-trash" aria-hidden="true"></i>
                    <span>Trashed</span>
                    <strong data-filter-count>0</strong>
                    <span
                        class="inquiries-filter-unread-badge"
                        data-filter-unread-count
                        data-filter="trashed"
                        data-filter-unread-initial="{{ $unreadTrashCount ?? 0 }}"
                        @if(($unreadTrashCount ?? 0) <= 0) hidden @endif
                        aria-label="{{ ($unreadTrashCount ?? 0) }} new trashed {{ ($unreadTrashCount ?? 0) === 1 ? 'inquiry' : 'inquiries' }}"
                    >{{ ($unreadTrashCount ?? 0) > 99 ? '99+' : ($unreadTrashCount ?? 0) }}</span>
                </button>

            </div>
        </div>
    </div>

</section>

<section class="inquiries-section" aria-labelledby="inquiries-section-title">

    <div class="inquiries-section-heading">

        <div>
            <span class="inquiries-section-eyebrow">All submissions</span>

            <h2 id="inquiries-section-title">
                Your inquiries
            </h2>
        </div>

        <div
            class="inquiries-section-meta"
            data-inquiry-result-meta
            aria-live="polite"
        >
            {{ $requests->count() }} {{ Str::plural('inquiry', $requests->count()) }}
        </div>

    </div>

    <main class="inquiries-list">

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

    </main>

</section>
