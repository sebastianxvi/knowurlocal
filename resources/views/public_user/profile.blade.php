<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My Profile | KNOWURLOCAL</title>
    <link rel="stylesheet" href="{{ asset('cssfiles/public_user/typography.css') }}?v={{ filemtime(public_path('cssfiles/public_user/typography.css')) }}">
    <link rel="stylesheet" href="{{ asset('cssfiles/public_user/navbar.css') }}?v={{ filemtime(public_path('cssfiles/public_user/navbar.css')) }}">
    <link rel="stylesheet" href="{{ asset('cssfiles/public_user/profile.css') }}?v={{ filemtime(public_path('cssfiles/public_user/profile.css')) }}">
    <link rel="stylesheet" href="{{ asset('cssfiles/components/modal.css') }}?v={{ filemtime(public_path('cssfiles/components/modal.css')) }}">
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/light/style.css">
</head>
<body class="profile-page">
    <x-public.navbar :hideSearch="true" />
    <main class="profile-shell">
        <section class="profile-hero" aria-label="Your profile">
            <div class="profile-cover" aria-hidden="true">
                <div class="profile-cover-orb profile-cover-orb-one"></div>
                <div class="profile-cover-orb profile-cover-orb-two"></div>
                <span class="profile-cover-mark">KNOWURLOCAL</span>
            </div>
            <div class="profile-identity">
                <div class="profile-picture-wrap">
                    <div class="profile-picture" id="profilePicture">
                        @if($user->profile_picture)
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->profile_picture) }}" alt="Profile picture" id="profilePictureImage">
                        @else
                            <span id="profilePictureInitials">{{ mb_strtoupper(mb_substr($user->first_name ?: 'U', 0, 1) . mb_substr($user->last_name ?: '', 0, 1)) }}</span>
                        @endif
                    </div>
                    <button type="button" class="profile-picture-edit" id="chooseProfilePicture" aria-label="Change profile picture" title="Change profile picture">
                        <i class="ph-light ph-camera" aria-hidden="true"></i>
                    </button>
                    <input type="file" id="profilePictureInput" accept="image/jpeg,image/png,image/webp" hidden>
                </div>
                <div class="profile-identity-details">
                    <h1>{{ $user->first_name }} {{ $user->last_name }}</h1>
                    <p>{{ $user->email }}</p>
                    <div class="profile-identity-subline"><i class="ph-light ph-user-circle" aria-hidden="true"></i> KNOWURLOCAL public user</div>
                    <div class="profile-actions-wrap">
                        <button type="button" class="profile-more-button" id="profileMoreButton" aria-label="Profile options" title="Profile options" aria-expanded="false">
                            <i class="ph-light ph-dots-three-vertical" aria-hidden="true"></i>
                        </button>
                        <div class="profile-actions-menu" id="profileActionsMenu" hidden>
                            <button type="button" data-open-profile-modal="name"><i class="ph-light ph-user" aria-hidden="true"></i> Edit name</button>
                            <button type="button" data-open-profile-modal="email"><i class="ph-light ph-at" aria-hidden="true"></i> Edit email</button>
                            <button type="button" data-open-profile-modal="passwordRequest"><i class="ph-light ph-lock-key" aria-hidden="true"></i> Change password</button>
                        </div>
                    </div>
                </div>
                <div class="profile-identity-edit-hint" aria-hidden="true"><i class="ph-light ph-pencil-simple"></i></div>
            </div>
        </section>

        <div class="profile-content">
            <div class="profile-feedback-intro">
                <div>
                    <p class="profile-eyebrow">YOUR FEEDBACK</p>
                    <h2>My Agency Evaluations <span>{{ $evaluations->count() }}</span></h2>
                    <p>Revisit your agency evaluations and update your feedback when needed.</p>
                </div>
            </div>
        <section class="profile-evaluations-section">
            <div class="profile-section-heading" hidden>
                <div>
                    <p class="profile-eyebrow">YOUR FEEDBACK</p>
                    <h2>My Agency Evaluations <span>{{ $evaluations->count() }}</span></h2>
                    <p>Choose an evaluation to open its agency and make changes.</p>
                </div>
            </div>

            @if($evaluations->isEmpty())
                <div class="profile-evaluations-empty">
                    <div class="profile-empty-icon"><i class="ph-light ph-star" aria-hidden="true"></i></div>
                    <h3>No evaluations yet</h3>
                    <p>When you share feedback about an agency, it will appear here.</p>
                    <a href="{{ route('map') }}">Explore agencies <i class="ph-light ph-arrow-up-right" aria-hidden="true"></i></a>
                </div>
            @else
                <div class="profile-evaluations-list">
                    @foreach($evaluations as $evaluation)
                        <article class="profile-evaluation-card">
                            <div class="profile-evaluation-main">
                                <div class="profile-agency-avatar">
                                    @if($evaluation->agency?->agency_image)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($evaluation->agency->agency_image) }}" alt="" loading="lazy">
                                    @else
                                        <i class="ph-light ph-buildings" aria-hidden="true"></i>
                                    @endif
                                </div>
                                <div class="profile-evaluation-copy">
                                    <h3>{{ $evaluation->agency?->agency_name ?? 'Agency unavailable' }}</h3>
                                    <div class="profile-evaluation-stars" aria-label="{{ $evaluation->rating }} out of 5 stars">{{ str_repeat('★', $evaluation->rating) }}<span>{{ str_repeat('☆', 5 - $evaluation->rating) }}</span></div>
                                    <p class="profile-evaluation-comment">{{ $evaluation->comment ?: 'No written comment.' }}</p>
                                    <div class="profile-evaluation-meta">
                                        <span><i class="ph-light ph-calendar-blank" aria-hidden="true"></i> Updated {{ $evaluation->updated_at?->format('M j, Y') }}</span>
                                        <span class="profile-status profile-status-{{ \Illuminate\Support\Str::slug($evaluation->status) }}">{{ ucfirst(str_replace('_', ' ', $evaluation->status)) }}</span>
                                    </div>
                                </div>
                            </div>
                            @if($evaluation->agency && $evaluation->status === 'published')
                                <a class="profile-evaluation-action" href="{{ route('map', ['agency_id' => $evaluation->agency_id, 'edit_evaluation' => 1]) }}" aria-label="Open {{ $evaluation->agency?->agency_name }} evaluation" title="Open evaluation">
                                    <i class="ph-light ph-arrow-up-right" aria-hidden="true"></i>
                                </a>
                            @elseif($evaluation->agency)
                                <span class="profile-evaluation-action is-disabled" title="This evaluation is currently under review">Temporarily unavailable</span>
                            @else
                                <span class="profile-evaluation-action is-disabled">Agency unavailable</span>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
            </div>
    </main>


    <div class="profile-modal-backdrop" id="profileModalBackdrop" hidden>
        <section class="profile-modal" role="dialog" aria-modal="true" aria-labelledby="profileModalTitle">
            <header class="profile-modal-header">
                <div><p class="profile-eyebrow">PROFILE SETTINGS</p><h2 id="profileModalTitle">Edit profile</h2><p id="profileModalDescription">Update your account information.</p></div>
                <button type="button" class="profile-modal-close" id="profileModalClose" aria-label="Close"><i class="ph-light ph-x"></i></button>
            </header>

            <form id="profileNameForm" class="profile-modal-form" hidden>
                <label>First name<input name="first_name" required maxlength="100" value="{{ $user->first_name }}"></label>
                <label>Last name<input name="last_name" required maxlength="100" value="{{ $user->last_name }}"></label>
                <label>Current password <small>Confirm it's you</small><input type="password" name="current_password" required autocomplete="current-password"></label>
                <p class="profile-modal-error" role="alert" hidden></p>
                <footer><button type="button" class="profile-modal-cancel" data-close-profile-modal>Cancel</button><button type="submit" class="profile-modal-save">Save name</button></footer>
            </form>

            <form id="profileEmailForm" class="profile-modal-form" hidden>
                <label>Email address<input type="email" name="email" required maxlength="255" value="{{ $user->email }}"></label>
                <p class="profile-email-note"><i class="ph-light ph-shield-check"></i> Your current password is required to save this change.</p>
                <label>Current password <small>Confirm it's you</small><input type="password" name="current_password" required autocomplete="current-password"></label>
                <p class="profile-modal-error" role="alert" hidden></p>
                <footer><button type="button" class="profile-modal-cancel" data-close-profile-modal>Cancel</button><button type="submit" class="profile-modal-save">Save email</button></footer>
            </form>

            <form id="profilePasswordRequestForm" class="profile-modal-form" hidden>
                <p class="profile-password-explainer">We'll send a one-time verification code to <strong>{{ $user->email }}</strong>. The code expires after 10 minutes.</p>
                <label>Current password<input type="password" name="current_password" required autocomplete="current-password"></label>
                <p class="profile-modal-error" role="alert" hidden></p>
                <footer><button type="button" class="profile-modal-cancel" data-close-profile-modal>Cancel</button><button type="submit" class="profile-modal-save">Send verification code</button></footer>
            </form>

            <form id="profilePasswordChangeForm" class="profile-modal-form" hidden>
                <p class="profile-password-explainer">Enter the code sent to your email and choose a new password.</p>
                <label>6-digit verification code<input name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autocomplete="one-time-code"></label>
                <label>New password<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
                <label>Confirm new password<input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password"></label>
                <label>Current password<input type="password" name="current_password" required autocomplete="current-password"></label>
                <p class="profile-modal-error" role="alert" hidden></p>
                <footer><button type="button" class="profile-modal-cancel" data-close-profile-modal>Cancel</button><button type="submit" class="profile-modal-save">Change password</button></footer>
            </form>
        </section>
    </div>

    @include('components.modal')
    <script src="{{ asset('jsfiles/components/modal-system.js') }}?v={{ filemtime(public_path('jsfiles/components/modal-system.js')) }}"></script>
    <script src="{{ asset('jsfiles/public_user/profile.js') }}?v={{ filemtime(public_path('jsfiles/public_user/profile.js')) }}"></script>
</body>
</html>
