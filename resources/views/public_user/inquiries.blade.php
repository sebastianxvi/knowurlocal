<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>KNOWURLOCAL | My Inquiries</title>

    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/phosphor-icons"></script>

    <!-- Global theme -->
    <link rel="stylesheet" href="{{ asset('cssfiles/theme.css') }}">

    <!-- Page-specific styles -->
    <link rel="stylesheet" href="{{ asset('cssfiles/public_user/inquiries.css') }}">

</head>

<body>

<div class="inquiries-page">

    <!-- ================= TOP BAR ================= -->
    <header class="inquiries-header">

        <button
            type="button"
            onclick="history.back()"
            class="back-btn"
            aria-label="Go back"
        >
            <i class="ph-light ph-arrow-left"></i>
        </button>

        <div class="header-copy">
            <h1>My Inquiries</h1>
            <p>Track your submitted questions and responses</p>
        </div>

    </header>


    <!-- ================= FILTERS ================= -->
    <div class="filter-bar" role="group" aria-label="Filter inquiries">

        <button
            type="button"
            class="filter-btn active"
            data-filter="all"
        >
            All
        </button>

        <button
            type="button"
            class="filter-btn"
            data-filter="pending"
        >
            Pending
        </button>

        <button
            type="button"
            class="filter-btn"
            data-filter="answered"
        >
            Answered
        </button>

    </div>


    <!-- ================= INQUIRIES ================= -->
    <main class="inquiries-list">

        @forelse($requests as $req)

            <article
    class="inquiry-card"
    data-id="{{ $req->id }}"
    data-status="{{ $req->status }}"
>

    <!-- ================= COLLAPSED HEADER ================= -->

    <button
        type="button"
        class="inquiry-toggle"
        aria-expanded="false"
    >

        <div class="inquiry-summary">

            <div class="card-header">

                <span class="status {{ $req->status }}">

    @if($req->status === 'answered')
        <i class="ph-light ph-check"></i>
    @else
        <i class="ph-light ph-clock"></i>
    @endif

    {{ ucfirst($req->status) }}

    @if(
        $req->status === 'answered' &&
        is_null($req->answer_seen_at)
    )
        <span
            class="unread-inquiry-dot"
            aria-label="New response"
        ></span>
    @endif

</span>

                <time
                    datetime="{{ $req->created_at->toIso8601String() }}"
                    class="inquiry-date"
                >
                    {{ $req->created_at->format('M d, Y') }}
                </time>

            </div>


            <p class="question-preview">
                {{ $req->question }}
            </p>

        </div>


        <i
            class="ph-light ph-caret-down inquiry-chevron"
            aria-hidden="true"
        ></i>

    </button>


    <!-- ================= EXPANDABLE CONTENT ================= -->

    <div class="inquiry-details">

    <div class="inquiry-details-inner">


        @if($req->status === 'answered')

            <div class="answer-block">

                <div class="answer-header">
                    <i class="ph-light ph-chat-centered-text"></i>
                    <span>Administrator response</span>
                </div>

                <p class="answer">
                    {{ $req->answer }}
                </p>

            </div>

        @else

            <div class="pending-message">

                <i class="ph-light ph-hourglass"></i>

                <span>
                    Waiting for an administrator's response
                </span>

            </div>

        @endif

    </div>

</div>

</article>

        @empty

            <div class="empty-state">

                <div class="empty-icon">
                    <i class="ph-light ph-chat-circle-dots"></i>
                </div>

                <h2>No inquiries yet</h2>

                <p>
                    Ask a question and track the response here.
                </p>

                <a
                    href="{{ route('map') }}"
                    class="ask-btn"
                >
                    <i class="ph-light ph-paper-plane-tilt"></i>
                    Ask a question
                </a>

            </div>

        @endforelse

    </main>

</div>
<script src="{{ asset('jsfiles/public_user/inquiries.js') }}"></script>
</body>
</html>