<section class="inquiries-toolbar" aria-label="Inquiry filters">

    <div class="inquiries-filter-tabs" role="group" aria-label="Filter inquiries">

        <button
            type="button"
            class="inquiries-filter-tab active"
            data-filter="needs_attention"
            aria-pressed="true"
        >
            <i class="ph-light ph-seal-question" aria-hidden="true"></i>
            <span>Needs Attention</span>
        </button>

        <button
            type="button"
            class="inquiries-filter-tab"
            data-filter="pending"
            aria-pressed="false"
        >
            <i class="ph-light ph-clock" aria-hidden="true"></i>
            <span>Pending</span>
        </button>

        <button
            type="button"
            class="inquiries-filter-tab"
            data-filter="follow_up"
            aria-pressed="false"
        >
            <i class="ph-light ph-arrow-counter-clockwise" aria-hidden="true"></i>
            <span>Follow-up</span>
        </button>

        <button
            type="button"
            class="inquiries-filter-tab"
            data-filter="answered"
            aria-pressed="false"
        >
            <i class="ph-light ph-check-circle" aria-hidden="true"></i>
            <span>Answered</span>
        </button>

    </div>

</section>


<section class="inquiries-section" aria-labelledby="inquiries-section-title">

    <div class="inquiries-section-heading">

        <div>
            <span class="inquiries-section-eyebrow">Your activity</span>

            <h2 id="inquiries-section-title">
                Inquiry history
            </h2>
        </div>

        <div class="inquiries-section-meta">
            <i class="ph-light ph-list-dashes" aria-hidden="true"></i>
            <span>
                {{ $requests->count() }}
                {{ Str::plural('inquiry', $requests->count()) }}
            </span>
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
