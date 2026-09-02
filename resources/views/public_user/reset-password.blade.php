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

    <title>KNOWURLOCAL | Create New Password</title>

    {{-- Phosphor Icons --}}
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    {{-- Inter font --}}
    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >

    {{-- Page-specific stylesheet --}}
    <link
        rel="stylesheet"
        href="{{ asset('cssfiles/public_user/reset-password.css') }}"
    >

    {{-- Shared modal stylesheet. --}}
<link
    rel="stylesheet"
    href="{{ asset('cssfiles/components/modal.css') }}"
>

</head>

<body>

    <main class="auth-page">

        <section class="auth-container">

            {{-- =================================================
                 BRAND PANEL
            ================================================== --}}

            <div class="auth-brand-panel">

                <div class="brand-content">

                    <div class="brand-mark">
                        KYL
                    </div>

                    <h1>
                        Secure your account.
                    </h1>

                    <p>
                        Create a new password for your
                        KNOWURLOCAL account and keep your
                        information protected.
                    </p>

                </div>

            </div>


            {{-- =================================================
                 FORM PANEL
            ================================================== --}}

            <div class="auth-form-panel">

                <div class="auth-form-wrapper">

                    <div class="form-header">

                        <div class="form-icon">

                            <i class="ph-light ph-lock-key-open"></i>

                        </div>

                        <h2>
                            Create new password
                        </h2>

                        <p>
                            Choose a strong password that you
                            haven't used before.
                        </p>

                    </div>


                    {{-- =================================================
                         VALIDATION ERRORS
                    ================================================== --}}

                    @if($errors->any())

                        <div class="message message-error">

                            <i class="ph-light ph-warning-circle"></i>

                            <div>

                                @foreach($errors->all() as $error)

                                    <div>
                                        {{ $error }}
                                    </div>

                                @endforeach

                            </div>

                        </div>

                    @endif


                    {{-- =================================================
                         RESET PASSWORD FORM
                    ================================================== --}}

                    <form
                        method="POST"
                        action="{{ route('password.update') }}"
                        id="resetPasswordForm"
                    >

                        @csrf


                        {{-- 
                            The email is intentionally NOT submitted
                            from the browser.

                            The controller already has the verified
                            email stored in the server-side session.
                        --}}


                        {{-- =================================================
                             NEW PASSWORD
                        ================================================== --}}

                        <div class="form-group">

                            <label for="password">
                                New password
                            </label>

                            <div class="password-input">

                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    autocomplete="new-password"
                                    required
                                >

                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-target="password"
                                    aria-label="Show password"
                                >

                                    <i class="ph-light ph-eye"></i>

                                </button>

                            </div>

                        </div>


                        {{-- =================================================
                             CONFIRM PASSWORD
                        ================================================== --}}

                        <div class="form-group">

                            <label for="password_confirmation">
                                Confirm new password
                            </label>

                            <div class="password-input">

                                <input
                                    type="password"
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    autocomplete="new-password"
                                    required
                                >

                                <button
                                    type="button"
                                    class="password-toggle"
                                    data-target="password_confirmation"
                                    aria-label="Show password"
                                >

                                    <i class="ph-light ph-eye"></i>

                                </button>

                            </div>

                        </div>


                        {{-- =================================================
                             PASSWORD REQUIREMENTS
                        ================================================== --}}

                        <div class="password-hint">

                            <i class="ph-light ph-shield-check"></i>

                            <span>
                                Use at least 8 characters.
                            </span>

                        </div>


                        {{-- =================================================
                             SUBMIT
                        ================================================== --}}

                        <button
                            type="submit"
                            class="primary-button"
                        >

                            <span>
                                Reset password
                            </span>

                            <i class="ph-light ph-arrow-right"></i>

                        </button>

                    </form>


                    {{-- =================================================
                         BACK TO LOGIN
                    ================================================== --}}

                    <a
                        href="{{ route('public.login') }}"
                        class="back-link"
                    >
                        <i class="ph-light ph-arrow-left"></i>

                        Back to login
                    </a>

                </div>

            </div>

        </section>

    </main>


    {{-- Existing modal system --}}
    @include('components.modal')

    {{-- Shared modal JavaScript. --}}
<script src="{{ asset('jsfiles/components/modal-system.js') }}"></script>


    <script>

        /*
         * Find every password visibility button.
         */
        const passwordToggles =
            document.querySelectorAll('.password-toggle');


        /*
         * Add the show/hide behavior to every
         * password visibility button.
         */
        passwordToggles.forEach(button => {

            button.addEventListener('click', function () {

                /*
                 * Read the ID of the password input
                 * controlled by this button.
                 */
                const targetId =
                    this.dataset.target;


                /*
                 * Find the corresponding password input.
                 */
                const input =
                    document.getElementById(targetId);


                /*
                 * Find the icon inside the button.
                 */
                const icon =
                    this.querySelector('i');


                /*
                 * Toggle between password and text.
                 */
                if (input.type === 'password') {

                    input.type = 'text';

                    icon.className =
                        'ph-light ph-eye-slash';

                    this.setAttribute(
                        'aria-label',
                        'Hide password'
                    );

                } else {

                    input.type = 'password';

                    icon.className =
                        'ph-light ph-eye';

                    this.setAttribute(
                        'aria-label',
                        'Show password'
                    );

                }

            });

        });

    </script>

</body>

</html>