<!-- ================= FILTERS ================= -->

<div
    class="filter-bar"
    role="group"
    aria-label="Filter inquiries"
>

    {{-- NEEDS ATTENTION --}}

    <button
        type="button"
        class="filter-btn active"
        data-filter="needs_attention"
        aria-pressed="true"
    >
        Needs Attention
    </button>


    {{-- PENDING --}}

    <button
        type="button"
        class="filter-btn"
        data-filter="pending"
        aria-pressed="false"
    >
        Pending
    </button>


    {{-- FOLLOW-UP --}}

    <button
        type="button"
        class="filter-btn"
        data-filter="follow_up"
        aria-pressed="false"
    >
        Follow-up
    </button>


    {{-- ANSWERED --}}

    <button
        type="button"
        class="filter-btn"
        data-filter="answered"
        aria-pressed="false"
    >
        Answered
    </button>

</div>


<!-- ================= INQUIRIES ================= -->

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