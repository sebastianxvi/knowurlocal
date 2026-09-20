<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>KNOWURLOCAL | My Inquiries</title>

    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/phosphor-icons"></script>

    <!-- Global theme -->
    <link
        rel="stylesheet"
        href="{{ asset('cssfiles/theme.css') }}"
    >

    <!-- Page-specific styles -->
    <link
        rel="stylesheet"
        href="{{ asset('cssfiles/public_user/inquiries.css') }}"
    >

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
            <i
                class="ph-light ph-arrow-left"
                aria-hidden="true"
            ></i>
        </button>

        <div class="header-copy">

            <h1>My Inquiries</h1>

            <p>
                Track your submitted questions and responses
            </p>

        </div>

    </header>


    <!-- ================= FILTERS ================= -->

<div
    class="filter-bar"
    role="group"
    aria-label="Filter inquiries"
>

    <button
        type="button"
        class="filter-btn active"
        data-filter="answered"
        aria-pressed="true"
    >
        Answered
    </button>

    <button
        type="button"
        class="filter-btn"
        data-filter="pending"
        aria-pressed="false"
    >
        Pending
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

    <!-- =====================================================
         COLLAPSED INQUIRY HEADER
         ===================================================== -->

    <button
        type="button"
        class="inquiry-toggle"
        aria-expanded="false"
        aria-controls="inquiry-details-{{ $req->id }}"
    >

        <div class="inquiry-summary">

            <div class="card-header">

                <span class="status {{ $req->status }}">

                    @if($req->status === 'answered')

                        <i
                            class="ph-light ph-check"
                            aria-hidden="true"
                        ></i>

                    @else

                        <i
                            class="ph-light ph-clock"
                            aria-hidden="true"
                        ></i>

                    @endif

                    {{ ucfirst($req->status) }}

                    @if(
                        $req->status === 'answered' &&
                        is_null($req->answer_seen_at)
                    )

                        <span
                            class="unread-inquiry-dot"
                            aria-label="New response"
                            title="New response"
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


    <!-- =====================================================
         EXPANDABLE INQUIRY CONTENT
         ===================================================== -->

    <div
        class="inquiry-details"
        id="inquiry-details-{{ $req->id }}"
    >

        <div class="inquiry-details-inner">

            @if($req->status === 'answered')

                <!-- =================================================
                     ADMINISTRATOR RESPONSE
                     ================================================= -->

                <div class="answer-block">

                    <div class="answer-header">

                        <i
                            class="ph-light ph-chat-centered-text"
                            aria-hidden="true"
                        ></i>

                        <span>
                            Administrator response
                        </span>

                    </div>


                    <p class="answer">
                        {{ $req->answer }}
                    </p>


                    @if($req->answer_image)

                        <!-- =========================================
                             ATTACHED IMAGE
                             ========================================= -->

                        <div class="inquiry-image-block">

                            <div class="inquiry-image-header">

                                <i
                                    class="ph-light ph-image"
                                    aria-hidden="true"
                                ></i>

                                <span>
                                    Attached image
                                </span>

                            </div>


                            <button
                                type="button"
                                class="inquiry-image-preview-trigger"
                                data-image-preview
                                data-image-src="{{ asset('storage/' . $req->answer_image) }}"
                                aria-label="Open attached image in full view"
                            >

                                <img
                                    src="{{ asset('storage/' . $req->answer_image) }}"
                                    alt="Image attached by the administrator"
                                    class="inquiry-attached-image"
                                    loading="lazy"
                                >

                            </button>

                        </div>

                    @endif

                </div>

            @else

                <!-- =================================================
                     PENDING STATE
                     ================================================= -->

                <div
                    class="pending-message"
                    role="status"
                >

                    <i
                        class="ph-light ph-hourglass"
                        aria-hidden="true"
                    ></i>

                    <span>
                        Waiting for an administrator's response
                    </span>

                </div>

            @endif

        </div>

    </div>

</article>

        @empty

            <!-- ================= EMPTY STATE ================= -->

            <div class="empty-state" hidden>
    <div class="empty-icon" aria-hidden="true">
        <i class="ph-light ph-chat-circle-dots"></i>
    </div>

    <h2>No answered inquiries yet</h2>

    <p>
        Your submitted questions will appear here once an office responds.
    </p>

    <a href="{{ route('map') }}" class="empty-action">
        <i class="ph-light ph-paper-plane-tilt" aria-hidden="true"></i>
        Ask a question
    </a>
</div>

        @endforelse

    </main>

</div>


<!-- =====================================================
     IMAGE LIGHTBOX
     ===================================================== -->

<div
    class="image-lightbox"
    id="image-lightbox"
    role="dialog"
    aria-modal="true"
    aria-labelledby="image-lightbox-title"
    aria-hidden="true"
>

    <div class="image-lightbox-content">

        <button
            type="button"
            class="image-lightbox-close"
            id="image-lightbox-close"
            aria-label="Close image preview"
        >

            <i
                class="ph-light ph-x"
                aria-hidden="true"
            ></i>

        </button>


        <img
            src=""
            alt=""
            class="image-lightbox-image"
            id="image-lightbox-image"
        >

    </div>


    <span
        id="image-lightbox-title"
        hidden
    >
        Attached image preview
    </span>

</div>


<!-- Page JavaScript -->

<script src="{{ asset('jsfiles/public_user/inquiries.js') }}"></script>

</body> 

</html>