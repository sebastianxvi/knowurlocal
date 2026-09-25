@php
    $latestResponse = $req->latestResponse;
@endphp

<article
    class="inquiry-card"
    data-id="{{ $req->id }}"
    data-status="{{ $req->status }}"
>
    <button
        type="button"
        class="inquiry-toggle"
        aria-expanded="false"
        aria-controls="inquiry-details-{{ $req->id }}"
    >
        <div class="inquiry-summary">

            <div class="inquiry-status-icon {{ $req->status }}">

                @switch($req->status)

                    @case('pending')

                        <i
                            class="ph-light ph-clock"
                            aria-hidden="true"
                        ></i>

                        @break

                    @case('awaiting_confirmation')

                        <i
                            class="ph-light ph-seal-question"
                            aria-hidden="true"
                        ></i>

                        @break

                    @case('needs_follow_up')

                        <i
                            class="ph-light ph-arrow-counter-clockwise"
                            aria-hidden="true"
                        ></i>

                        @break

                    @case('answered')

                        <i
                            class="ph-light ph-check"
                            aria-hidden="true"
                        ></i>

                        @break

                    @default

                        <i
                            class="ph-light ph-clock"
                            aria-hidden="true"
                        ></i>

                @endswitch

            </div>

            <div class="inquiry-summary-content">

                <div class="inquiry-meta">

                    <span class="inquiry-status">
                        @switch($req->status)

                            @case('pending')
                                Waiting for response
                                @break

                            @case('awaiting_confirmation')
                                Response available
                                @break

                            @case('needs_follow_up')
                                Follow-up needed
                                @break

                            @case('answered')
                                Resolved
                                @break

                            @default
                                {{ ucfirst(str_replace('_', ' ', $req->status)) }}

                        @endswitch
                    </span>

                    @if ($req->created_at)
                        <time
                            datetime="{{ $req->created_at->toISOString() }}"
                            class="inquiry-date"
                        >
                            {{ $req->created_at->format('M d, Y') }}
                        </time>
                    @endif

                </div>

                <p class="inquiry-question">
                    {{ $req->question }}
                </p>

            </div>

            <i
                class="ph-light ph-caret-down inquiry-chevron"
                aria-hidden="true"
            ></i>

        </div>
    </button>


    <div
        id="inquiry-details-{{ $req->id }}"
        class="inquiry-details"
        hidden
    >

        <div class="inquiry-question-full">

            <span class="inquiry-section-label">
                Your Question
            </span>

            <p>
                {{ $req->question }}
            </p>

        </div>


        @if ($latestResponse)

            <section class="official-response">

                <div class="official-response-header">

                    <div class="official-response-title">

                        <i
                            class="ph-light ph-seal-check"
                            aria-hidden="true"
                        ></i>

                        <span>
                            Official Response
                        </span>

                    </div>

                    @if ($latestResponse->forwarded_at)

                        <time
                            datetime="{{ $latestResponse->forwarded_at->toISOString() }}"
                            class="response-date"
                        >
                            {{ $latestResponse->forwarded_at->format('M d, Y') }}
                        </time>

                    @endif

                </div>


                <div class="response-components">

                    @foreach ($latestResponse->components as $component)

                        @switch($component->type)

                            @case('text')

                                <div class="response-component response-component-text">

                                    <p>
                                        {{ $component->content }}
                                    </p>

                                </div>

                                @break


                            @case('image')

                                @if ($component->attachment_url)

                                    <div class="response-component response-component-image">

                                        @if ($component->label)
                                            <span class="response-component-label">
                                                {{ $component->label }}
                                            </span>
                                        @endif

                                        <button
                                            type="button"
                                            class="response-image-trigger"
                                            data-image-url="{{ $component->attachment_url }}"
                                            aria-label="View response image"
                                        >
                                            <img
                                                src="{{ $component->attachment_url }}"
                                                alt="{{ $component->label ?: 'Official response image' }}"
                                                loading="lazy"
                                            >
                                        </button>

                                    </div>

                                @endif

                                @break


                            @case('file')

                                @if ($component->attachment_url)

                                    <div class="response-component response-component-file">

                                        <i
                                            class="ph-light ph-file"
                                            aria-hidden="true"
                                        ></i>

                                        <div class="response-file-info">

                                            @if ($component->label)
                                                <span class="response-component-label">
                                                    {{ $component->label }}
                                                </span>
                                            @endif

                                            <a
                                                href="{{ $component->attachment_url }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                View document
                                            </a>

                                        </div>

                                    </div>

                                @endif

                                @break


                            @case('link')

                                <div class="response-component response-component-link">

                                    <i
                                        class="ph-light ph-link"
                                        aria-hidden="true"
                                    ></i>

                                    <div class="response-link-info">

                                        @if ($component->label)
                                            <span class="response-component-label">
                                                {{ $component->label }}
                                            </span>
                                        @endif

                                        <a
                                            href="{{ $component->content }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            {{ $component->content }}
                                        </a>

                                    </div>

                                </div>

                                @break


                            @case('qr_code')

                                <div class="response-component response-component-qr">

                                    <i
                                        class="ph-light ph-qr-code"
                                        aria-hidden="true"
                                    ></i>

                                    <div class="response-qr-info">

                                        @if ($component->label)
                                            <span class="response-component-label">
                                                {{ $component->label }}
                                            </span>
                                        @endif

                                        <a
                                            href="{{ $component->content }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            Open QR destination
                                        </a>

                                    </div>

                                </div>

                                @break

                        @endswitch

                    @endforeach

                </div>

            </section>

            @if ($req->status === 'awaiting_confirmation')
                @include(
                    'public_user.my-inquiries.components.response-confirmation',
                    ['req' => $req]
                )
            @endif

        @else

            <section class="inquiry-pending-message">

                <i
                    class="ph-light ph-clock"
                    aria-hidden="true"
                ></i>

                <div>
                    <strong>
                        Waiting for an official response
                    </strong>

                    <p>
                        The office has not submitted a response to this inquiry yet.
                    </p>
                </div>

            </section>

        @endif

    </div>
</article>