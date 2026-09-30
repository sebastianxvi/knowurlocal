@php
    $latestResponse = $req->latestResponse;

    $statusLabels = [
        'pending' => 'Waiting for response',
        'awaiting_confirmation' => 'Response available',
        'needs_follow_up' => 'Follow-up needed',
        'answered' => 'Resolved',
    ];

    $statusIcons = [
        'pending' => 'ph-clock',
        'awaiting_confirmation' => 'ph-question',
        'needs_follow_up' => 'ph-arrow-counter-clockwise',
        'answered' => 'ph-check',
    ];
@endphp

<article
    class="inquiry-card"
    data-id="{{ $req->id }}"
    data-status="{{ $req->status }}"
    data-answer-seen="{{ $req->answer_seen_at ? '1' : '0' }}"
>
    <button
        type="button"
        class="inquiry-toggle"
        aria-expanded="false"
        aria-controls="inquiry-details-{{ $req->id }}"
    >
        <div class="inquiry-summary">

            <div class="inquiry-card-topline">

                <span class="inquiry-id">
                    #{{ $req->id }}
                </span>

                <span class="inquiry-status-badge {{ $req->status }}">
                    <i
                        class="ph-light {{ $statusIcons[$req->status] ?? 'ph-clock' }}"
                        aria-hidden="true"
                    ></i>

                    {{ $statusLabels[$req->status] ?? ucfirst(str_replace('_', ' ', $req->status)) }}
                </span>

                @if (
                    $req->status === 'awaiting_confirmation' && is_null($req->answer_seen_at) ||
                    ($req->status === 'answered' && is_null($req->answer_seen_at))
                )
                    <span
                        class="inquiry-unread-badge"
                        data-unread-response
                        title="New response"
                        aria-label="New response"
                    >
                        New
                    </span>
                @endif

                @if ($req->created_at)
                    <time
                        datetime="{{ $req->created_at->toISOString() }}"
                        class="inquiry-date"
                    >
                        {{ $req->created_at->format('M d, Y') }}
                    </time>
                @endif

            </div>

            <div class="inquiry-card-content">

                <p class="inquiry-question">
                    {{ $req->question }}
                </p>

                @if ($req->agency)
                    <span class="inquiry-agency">
                        <i class="ph-light ph-buildings" aria-hidden="true"></i>
                        {{ $req->agency->agency_name }}
                    </span>
                @endif

            </div>

        </div>

        <span class="inquiry-chevron" aria-hidden="true">
            <i class="ph-light ph-caret-down"></i>
        </span>
    </button>


    <div
        id="inquiry-details-{{ $req->id }}"
        class="inquiry-details"
        hidden
        aria-hidden="true"
    >
        <div class="inquiry-details-inner">

            <section class="inquiry-question-full">

                <div class="inquiry-detail-heading">
                    <i class="ph-light ph-quotes" aria-hidden="true"></i>
                    <span>Your Question</span>
                </div>

                <p>
                    {{ $req->question }}
                </p>

            </section>


            @if ($latestResponse)

                <section class="official-response">

                    <div class="official-response-header">

                        <div class="official-response-title">

                            <i
                                class="ph-light ph-check-circle"
                                aria-hidden="true"
                            ></i>

                            <span>Official Response</span>

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
                        {{-- Match the chatbot attachment order: images, answer text, file/link lines, then QR. --}}
                        @foreach ($latestResponse->components->where('type', 'image') as $component)
                            @if ($component->attachment_url)
                                <div class="response-component response-component-image">
                                    @if ($component->label)
                                        <span class="response-component-label">{{ $component->label }}</span>
                                    @endif
                                    <button type="button" class="response-image-trigger" data-image-url="{{ $component->attachment_url }}" aria-label="View response image">
                                        <img src="{{ $component->attachment_url }}" alt="{{ $component->label ?: 'Official response image' }}" loading="lazy">
                                    </button>
                                </div>
                            @endif
                        @endforeach

                        @foreach ($latestResponse->components->where('type', 'text') as $component)
                            <div class="response-component response-component-text"><p>{{ $component->content }}</p></div>
                        @endforeach

                        <div class="response-attachment-links" aria-label="Response files and links">
                            @foreach ($latestResponse->components->where('type', 'file') as $component)
                                @if ($component->attachment_url)
                                    <a class="response-attachment-link response-file-link" href="{{ $component->attachment_url }}" download>
                                        <i class="ph-light ph-file-arrow-down" aria-hidden="true"></i>
                                        <span>{{ $component->label ?: 'Download file' }}</span>
                                        <i class="ph-light ph-download-simple response-attachment-trailing" aria-hidden="true"></i>
                                    </a>
                                @endif
                            @endforeach

                            @foreach ($latestResponse->components->where('type', 'link') as $component)
                                @php $linkScheme = strtolower((string) parse_url((string) $component->content, PHP_URL_SCHEME)); @endphp
                                @if (in_array($linkScheme, ['http', 'https'], true))
                                    <a class="response-attachment-link response-plain-link" href="{{ $component->content }}" target="_blank" rel="noopener noreferrer">
                                        <span>{{ $component->label ?: $component->content }}</span>
                                        <i class="ph-light ph-arrow-up-right" aria-hidden="true"></i>
                                    </a>
                                @endif
                            @endforeach
                        </div>

                        @foreach ($latestResponse->components->where('type', 'qr_code') as $component)
                            @php $qrScheme = strtolower((string) parse_url((string) $component->content, PHP_URL_SCHEME)); @endphp
                            @if (in_array($qrScheme, ['http', 'https'], true))
                                <div class="response-component response-component-qr">
                                    <div class="response-qr-heading">
                                        <i class="ph-light ph-qr-code" aria-hidden="true"></i>
                                        <span>{{ $component->label ?: 'QR code' }}</span>
                                    </div>
                                    <div class="response-qr-code" data-qr-value="{{ $component->content }}" role="img" aria-label="{{ $component->label ?: 'Scannable QR code' }}"></div>
                                    <a class="response-qr-open" href="{{ $component->content }}" target="_blank" rel="noopener noreferrer">Open destination <i class="ph-light ph-arrow-up-right" aria-hidden="true"></i></a>
                                </div>
                            @endif
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

                    <div class="inquiry-pending-icon" aria-hidden="true">
                        <i class="ph-light ph-clock"></i>
                    </div>

                    <div>
                        <strong>Waiting for an official response</strong>

                        <p>
                            The office has not submitted a response to this inquiry yet.
                        </p>
                    </div>

                </section>

            @endif

        </div>
    </div>
</article>
