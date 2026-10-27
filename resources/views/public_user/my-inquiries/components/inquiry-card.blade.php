@php
    $latestResponse = $req->latestResponse;
    $displayStatus = $req->trashed() ? 'trashed' : $req->status;

    $statusLabels = [
        'pending' => 'Waiting for response',
        'awaiting_confirmation' => 'Response available',
        'needs_follow_up' => 'Follow-up needed',
        'answered' => 'Resolved',
        'trashed' => 'Trashed by administration',
    ];

    $statusIcons = [
        'pending' => 'ph-clock',
        'awaiting_confirmation' => 'ph-question',
        'needs_follow_up' => 'ph-arrow-counter-clockwise',
        'answered' => 'ph-check',
        'trashed' => 'ph-trash',
    ];
@endphp

<article
    class="inquiry-card {{ $req->trashed() ? 'is-trashed' : '' }}"
    data-id="{{ $req->id }}"
    data-agency-id="{{ $req->agency_id }}"
    data-status="{{ $displayStatus }}"
    data-answer-seen="{{ $req->answer_seen_at ? '1' : '0' }}"
>
    <button
        type="button"
        class="inquiry-toggle"
        aria-expanded="false"
        aria-controls="inquiry-details-{{ $req->id }}"
    >
        <div class="inquiry-summary">
            <div class="messenger-avatar" aria-hidden="true">
                @if ($req->agency && $req->agency->agency_image)
                    <img src="{{ Storage::disk('agency_images')->url($req->agency->agency_image) }}" alt="" loading="lazy" onerror="this.hidden=true; this.nextElementSibling.hidden=false">
                    <span class="messenger-avatar-fallback" hidden>{{ strtoupper(mb_substr($req->agency->agency_abbreviation ?: $req->agency->agency_name, 0, 2)) }}</span>
                @else
                    <span class="messenger-avatar-fallback">{{ strtoupper(mb_substr(optional($req->agency)->agency_abbreviation ?: (optional($req->agency)->agency_name ?? 'AG'), 0, 2)) }}</span>
                @endif
            </div>
            <div class="messenger-row-copy">
                <div class="messenger-row-heading">
                    <span class="messenger-agency-name">{{ optional($req->agency)->agency_abbreviation ?: (optional($req->agency)->agency_name ?? 'Agency') }}</span>
                    <time class="messenger-row-time" datetime="{{ optional($req->trashed() ? $req->created_at : ($req->answered_at ?: $req->updated_at ?: $req->created_at))->toISOString() }}">{{ optional($req->trashed() ? $req->created_at : ($req->answered_at ?: $req->updated_at ?: $req->created_at))->format('M j') }}</time>
                </div>
                <p class="messenger-row-preview">{{ $req->trashed() ? 'Ticket moved to trash by administration' : ($latestResponse ? \Illuminate\Support\Str::limit(strip_tags((string) ($latestResponse->response_text ?? $latestResponse->response ?? $req->answer ?? 'Official response')), 54) : \Illuminate\Support\Str::limit($req->question, 54)) }}</p>
                <div class="messenger-row-meta">
                    <span class="inquiry-status-badge {{ $displayStatus }}">{{ $statusLabels[$displayStatus] ?? ucfirst(str_replace('_', ' ', $displayStatus)) }}</span>
                    <span class="inquiry-id">#{{ $req->id }}</span>
                </div>
            </div>
            <div class="inquiry-card-topline messenger-legacy-topline">

                <span class="inquiry-id">
                    #{{ $req->id }}
                </span>

                <span class="inquiry-status-badge {{ $displayStatus }}">
                    <i
                        class="ph-light {{ $statusIcons[$displayStatus] ?? 'ph-clock' }}"
                        aria-hidden="true"
                    ></i>

                    {{ $statusLabels[$displayStatus] ?? ucfirst(str_replace('_', ' ', $displayStatus)) }}
                </span>

                @if (
                    !$req->trashed() &&
                    (
                        ($req->status === 'awaiting_confirmation' && is_null($req->answer_seen_at)) ||
                        ($req->status === 'answered' && is_null($req->answer_seen_at))
                    )
                )
                    <span
                        class="inquiry-unread-badge"
                        data-unread-response
                        data-unread-type="response"
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

            <section class="inquiry-question-full" @if($req->trashed()) hidden @endif>

                <div class="inquiry-detail-heading">
                    <i class="ph-light ph-quotes" aria-hidden="true"></i>
                    <span>Your Question</span>
                </div>

                <p>
                    {{ $req->question }}
                </p>

            </section>

            @php($reopenMessageRendered = false)
            @if ($req->trashed())

                @if ($req->trash_reason)
                    <p class="inquiry-trash-reason">
                        <strong>Reason from administration:</strong>
                        {{ $req->trash_reason }}
                    </p>
                @endif

            @elseif ($latestResponse)

                @foreach (($req->responses ?? collect([$latestResponse]))->sortBy(fn ($response) => $response->forwarded_at ?? $response->created_at) as $historicalResponse)
                    @if (!$reopenMessageRendered && !$req->trashed() && filled(trim((string) $req->reopen_message)) && (($req->reopen_message_at && (($historicalResponse->forwarded_at ?? $historicalResponse->created_at) >= $req->reopen_message_at)) || (!$req->reopen_message_at && in_array($req->status, ['awaiting_confirmation', 'answered'], true) && (string) $historicalResponse->id === (string) optional($latestResponse)->id)))
                        <section class="citizen-follow-up citizen-reopen-message" aria-label="Your ticket reopening message">
                            <div class="citizen-follow-up-header">
                                <span><i class="ph-light ph-chat-circle-text" aria-hidden="true"></i> Your follow-up · Ticket reopened</span>
                                @if ($req->reopen_message_at)<time datetime="{{ $req->reopen_message_at->toISOString() }}">{{ $req->reopen_message_at->format('M d, Y · g:i A') }}</time>@endif
                            </div>
                            <p>{{ $req->reopen_message }}</p>
                        </section>
                        @php($reopenMessageRendered = true)
                    @endif
                <section class="official-response" data-response-id="{{ $historicalResponse->id }}">

                    <div class="official-response-header">

                        <div class="official-response-title">

                            <i
                                class="ph-light ph-check-circle"
                                aria-hidden="true"
                            ></i>

                            <span>Official Response</span>

                        </div>

                        @if ($historicalResponse->forwarded_at)
                            <time
                                datetime="{{ $historicalResponse->forwarded_at->toISOString() }}"
                                class="response-date"
                            >
                                {{ $historicalResponse->forwarded_at->format('M d, Y') }}
                            </time>
                        @endif

                    </div>


                    <div class="response-components">
                        {{-- Match the chatbot attachment order: images, answer text, file/link lines, then QR. --}}
                        @if ($historicalResponse->components->where('type', 'image')->isNotEmpty())
                        <div class="response-image-grid" aria-label="Response images">
                        @foreach ($historicalResponse->components->where('type', 'image') as $component)
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
                        </div>
                        @endif

                        @foreach ($historicalResponse->components->where('type', 'text') as $component)
                            <div class="response-component response-component-text"><p>{{ $component->content }}</p></div>
                        @endforeach

                        <div class="response-attachment-links" aria-label="Response files and links">
                            @foreach ($historicalResponse->components->where('type', 'file') as $component)
                                @if ($component->attachment_url)
                                    <a class="response-resource-card is-file" href="{{ $component->attachment_url }}" download aria-label="Download {{ $component->label ?: 'attachment' }}">
                                        <span class="response-resource-icon"><i class="ph-light ph-file-arrow-down" aria-hidden="true"></i></span>
                                        <span class="response-resource-copy">
                                            <span class="response-resource-title">{{ $component->label ?: 'Download attachment' }}</span>
                                            <span class="response-resource-detail">File attachment</span>
                                        </span>
                                        <i class="ph-light ph-download-simple response-resource-action" aria-hidden="true"></i>
                                    </a>
                                @endif
                            @endforeach

                            <?php foreach ($historicalResponse->components->where('type', 'link') as $component): ?>
                                <?php $linkScheme = strtolower((string) parse_url((string) $component->content, PHP_URL_SCHEME)); ?>
                                <?php if (in_array($linkScheme, ['http', 'https'], true)): ?>
                                    <a class="response-resource-card is-link" href="{{ $component->content }}" target="_blank" rel="noopener noreferrer" aria-label="Open {{ $component->label ?: $component->content }}">
                                        <span class="response-resource-icon"><i class="ph-light ph-globe" aria-hidden="true"></i></span>
                                        <span class="response-resource-copy">
                                            <span class="response-resource-title">{{ $component->label ?: $component->content }}</span>
                                            <span class="response-resource-detail">{{ parse_url($component->content, PHP_URL_HOST) ?: $component->content }}</span>
                                        </span>
                                        <i class="ph-light ph-arrow-up-right response-resource-action" aria-hidden="true"></i>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>

                        <?php foreach ($historicalResponse->components->where('type', 'qr_code') as $component) {
                            $qrScheme = strtolower((string) parse_url((string) $component->content, PHP_URL_SCHEME));
                            if (in_array($qrScheme, ['http', 'https'], true)) {
                        ?>
                                <div class="response-component response-component-qr">
                                    <div class="response-qr-heading">
                                        <i class="ph-light ph-qr-code" aria-hidden="true"></i>
                                        <span><?= e($component->label ?: 'QR code') ?></span>
                                    </div>
                                    <div class="response-qr-code" data-qr-value="<?= e($component->content) ?>" role="img" aria-label="<?= e($component->label ?: 'Scannable QR code') ?>"></div>
                                    <a class="response-qr-open" href="<?= e($component->content) ?>" target="_blank" rel="noopener noreferrer">Open destination <i class="ph-light ph-arrow-up-right" aria-hidden="true"></i></a>
                                </div>
                        <?php
                            }
                        } ?>
                    </div>

                </section>
                    @if ($historicalResponse->follow_up_reason)
                        <section class="citizen-follow-up" aria-label="Your follow-up message">
                            <div class="citizen-follow-up-header">
                                <span><i class="ph-light ph-chat-circle-text" aria-hidden="true"></i> Your follow-up</span>
                                @if ($historicalResponse->responded_at)
                                    <time datetime="{{ $historicalResponse->responded_at->toISOString() }}">{{ $historicalResponse->responded_at->format('M d, Y · g:i A') }}</time>
                                @endif
                            </div>
                            <p>{{ $historicalResponse->follow_up_reason }}</p>
                        </section>
                    @endif

                    {{-- Attach the satisfaction prompt to the newest official response,
                         rather than leaving it detached at the bottom of the ticket. --}}
                    @if ((string) $historicalResponse->id === (string) optional($latestResponse)->id && $req->status === 'awaiting_confirmation')
                        @include(
                            'public_user.my-inquiries.components.response-confirmation',
                            ['req' => $req]
                        )
                    @endif
                @endforeach

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

            @if (!$reopenMessageRendered && !$req->trashed() && filled(trim((string) $req->reopen_message)))
                <section class="citizen-follow-up citizen-reopen-message" aria-label="Your ticket reopening message">
                    <div class="citizen-follow-up-header">
                        <span><i class="ph-light ph-chat-circle-text" aria-hidden="true"></i> Your follow-up · Ticket reopened</span>
                        @if ($req->reopen_message_at)<time datetime="{{ $req->reopen_message_at->toISOString() }}">{{ $req->reopen_message_at->format('M d, Y · g:i A') }}</time>@endif
                    </div>
                    <p>{{ $req->reopen_message }}</p>
                </section>
            @endif

        </div>
    </div>
</article>
