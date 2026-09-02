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

    <title>KNOWURLOCAL | Verify Your Account</title>

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
        href="{{ asset('cssfiles/public_user/password-reset-otp.css') }}"
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

            {{-- ================= BRAND PANEL ================= --}}
            <div class="auth-brand-panel">

                <div class="brand-content">

                    <div class="brand-mark">
                        KYL
                    </div>

                    <h1>
                        Verify your account.
                    </h1>

                    <p>
                        Enter the verification code sent to your
                        email address to continue resetting your
                        password.
                    </p>

                </div>

            </div>


            {{-- ================= FORM PANEL ================= --}}
            <div class="auth-form-panel">

                <div class="auth-form-wrapper">

                    <div class="form-header">

                        <div class="form-icon">
                            <i class="ph-light ph-shield-check"></i>
                        </div>

                        <h2>
                            Enter verification code
                        </h2>

                        <p>
                            We sent a 6-digit verification code to
                            your email address.
                        </p>

                    </div>


                    {{-- ================= SUCCESS MESSAGE ================= --}}
                    @if(session('success'))

                        <div class="message message-success">

                            <i class="ph-light ph-check-circle"></i>

                            <span>
                                {{ session('success') }}
                            </span>

                        </div>

                    @endif


                    {{-- ================= ERROR MESSAGE ================= --}}
                    @if($errors->has('otp'))

                        <div class="message message-error">

                            <i class="ph-light ph-warning-circle"></i>

                            <span>
                                {{ $errors->first('otp') }}
                            </span>

                        </div>

                    @endif


                    {{-- ================= OTP FORM ================= --}}
                    <form
                        id="passwordResetOtpForm"
                        method="POST"
                        action="{{ route('password.otp.verify') }}"
                    >

                        @csrf

                        {{-- 
                            This hidden input stores the complete
                            six-digit OTP before form submission.
                        --}}
                        <input
                            type="hidden"
                            name="otp"
                            id="otp"
                        >


                        {{-- ================= OTP INPUTS ================= --}}
                        <div
                            class="otp-container"
                            id="otpContainer"
                        >

                            @for($i = 0; $i < 6; $i++)

                                <input
                                    type="text"
                                    class="otp-input"
                                    maxlength="1"
                                    inputmode="numeric"
                                    autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                                    aria-label="Verification digit {{ $i + 1 }}"
                                >

                            @endfor

                        </div>


                        {{-- ================= VALIDATION ERROR ================= --}}
                        @if($errors->has('email'))

                            <div class="message message-error">

                                <i class="ph-light ph-warning-circle"></i>

                                <span>
                                    {{ $errors->first('email') }}
                                </span>

                            </div>

                        @endif


                        {{-- ================= VERIFY BUTTON ================= --}}
                        <button
                            type="submit"
                            class="primary-button"
                            id="verifyButton"
                        >

                            <span>
                                Verify code
                            </span>

                            <i class="ph-light ph-arrow-right"></i>

                        </button>

                    </form>


                    {{-- ================= RESEND ================= --}}
                    <div class="resend-section">

                        <p>
                            Didn't receive the code?
                        </p>

                        <button
                            type="button"
                            id="resendButton"
                            class="resend-button"
                            data-url="{{ route('password.otp.resend') }}"
                            {{ $resendRemaining > 0 ? 'disabled' : '' }}
                        >

                            <i class="ph-light ph-arrow-clockwise"></i>

                            <span id="resendText">

                                @if($resendRemaining > 0)

                                    Resend in {{ $resendRemaining }}s

                                @else

                                    Resend code

                                @endif

                            </span>

                        </button>

                    </div>


                    {{-- ================= BACK ================= --}}
                    <a
                        href="{{ route('password.request') }}"
                        class="back-link"
                    >

                        <i class="ph-light ph-arrow-left"></i>

                        Back to forgot password

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
         * Collect all six OTP input boxes.
         * querySelectorAll returns a NodeList containing
         * every element with the .otp-input class.
         */
        const otpInputs = document.querySelectorAll('.otp-input');


        /*
         * The hidden input is submitted to Laravel.
         * The visible inputs are only used for the UI.
         */
        const otpHiddenInput = document.getElementById('otp');


        /*
         * The actual form that submits the verification code.
         */
        const otpForm = document.getElementById('passwordResetOtpForm');


        /*
         * Prevent non-numeric characters from being entered.
         */
        otpInputs.forEach((input, index) => {

            input.addEventListener('input', function () {

                /*
                 * Remove every character except numbers.
                 */
                this.value = this.value.replace(/\D/g, '');


                /*
                 * Move focus to the next input after
                 * entering a digit.
                 */
                if (this.value && index < otpInputs.length - 1) {

                    otpInputs[index + 1].focus();

                }


                updateHiddenOtp();

                autoSubmitOtp();

            });


            /*
             * Allow the Backspace key to move the user
             * back to the previous OTP field.
             */
            input.addEventListener('keydown', function (event) {

                if (
                    event.key === 'Backspace' &&
                    !this.value &&
                    index > 0
                ) {

                    otpInputs[index - 1].focus();

                }

            });


            /*
             * Handle pasting a complete six-digit OTP.
             */
            input.addEventListener('paste', function (event) {

                event.preventDefault();

                const pasted = (
                    event.clipboardData ||
                    window.clipboardData
                )
                    .getData('text')
                    .replace(/\D/g, '')
                    .slice(0, 6);


                pasted.split('').forEach((digit, pastedIndex) => {

                    if (otpInputs[pastedIndex]) {

                        otpInputs[pastedIndex].value = digit;

                    }

                });


                updateHiddenOtp();

                autoSubmitOtp();

            });

        });


        /*
         * Combine the six visible OTP fields into
         * one six-digit value.
         */
        function updateHiddenOtp() {

            let value = '';

            otpInputs.forEach(input => {

                value += input.value;

            });

            otpHiddenInput.value = value;

        }


        /*
         * Submit automatically once all six digits
         * have been entered.
         */
        function autoSubmitOtp() {

            if (otpHiddenInput.value.length === 6) {

                otpForm.submit();

            }

        }


        /*
         * Resend OTP functionality.
         */
        const resendButton =
            document.getElementById('resendButton');

        const resendText =
            document.getElementById('resendText');


        /*
         * Read the server-generated resend endpoint.
         * This prevents the browser from constructing
         * the URL manually.
         */
        const resendUrl =
            resendButton.dataset.url;


        /*
         * Server-side cooldown value passed by Laravel.
         */
        let resendRemaining =
            {{ (int) $resendRemaining }};


        /*
         * Update the countdown every second.
         */
        function startResendCountdown() {

            if (resendRemaining <= 0) {

                resendButton.disabled = false;

                resendText.textContent = 'Resend code';

                return;

            }


            resendButton.disabled = true;

            resendText.textContent =
                `Resend in ${resendRemaining}s`;


            resendRemaining--;


            setTimeout(
                startResendCountdown,
                1000
            );

        }


        /*
         * Start the initial server-controlled countdown.
         */
        startResendCountdown();


        /*
         * Request a new OTP from Laravel.
         */
        resendButton.addEventListener('click', async function () {

            if (resendButton.disabled) {
                return;
            }


            resendButton.disabled = true;

            resendText.textContent = 'Sending...';


            try {

                const response = await fetch(
                    resendUrl,
                    {
                        method: 'POST',

                        headers: {

                            'X-CSRF-TOKEN':
                                document
                                    .querySelector(
                                        'meta[name="csrf-token"]'
                                    )
                                    ?.getAttribute('content'),

                            'Accept':
                                'application/json',

                        },

                        credentials: 'same-origin',

                    }
                );


                const data = await response.json();


                if (!response.ok || !data.success) {

                    throw new Error(
                        data.message ||
                        'Unable to resend the verification code.'
                    );

                }


                /*
                 * Laravel has successfully generated and
                 * sent a new OTP.
                 */
                resendRemaining = 60;

                startResendCountdown();


                if (typeof showAlertModal === 'function') {

                    showAlertModal(
                        'Verification code sent',
                        data.message
                    );

                }

            } catch (error) {

                resendButton.disabled = false;

                resendText.textContent = 'Resend code';


                if (typeof showAlertModal === 'function') {

                    showAlertModal(
                        'Unable to resend code',
                        error.message
                    );

                }

            }

        });


        /*
         * Focus the first OTP field when the page loads.
         */
        if (otpInputs.length > 0) {

            otpInputs[0].focus();

        }

    </script>

</body>

</html>