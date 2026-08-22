<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <link
    rel="stylesheet"
    href="{{ asset('cssfiles/components/modal.css') }}"
>

    <title>KNOWURLOCAL | OTP Verification</title>


    <style>

        /* =====================================================
           DESIGN SYSTEM
           ===================================================== */

        :root {
            --blue-main: #1F3A5F;
            --red-main: #f06277;
            --red-hover: #e2556a;
            --bg-color: #e9edf3;

            --text-main: #1f2937;
            --text-muted: #6b7280;

            --border-light: #e5e7eb;
        }


        /* =====================================================
           RESET
           ===================================================== */

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }


        /* =====================================================
           BODY
           ===================================================== */

        body {
            min-height: 100vh;

            background: var(--bg-color);

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 16px;
        }


        /* =====================================================
           CONTAINER
           ===================================================== */

        .otp-container {
            width: 100%;
        }


        /* =====================================================
           CARD
           ===================================================== */

        .otp-card {
            width: 100%;

            background: #fff;

            padding: 28px 20px;

            border-radius: 18px;

            border: 1px solid var(--border-light);

            box-shadow:
                0 10px 25px rgba(0, 0, 0, 0.05);

            text-align: center;

            animation: fadeUp 0.4s ease;
        }


        /* =====================================================
           HEADER
           ===================================================== */

        .otp-card h2 {
            font-size: 18px;
            color: var(--blue-main);
            margin-bottom: 6px;
        }


        .otp-card p {
            font-size: 12px;
            color: var(--text-muted);
        }


        /* =====================================================
           OTP INPUTS
           ===================================================== */

        .otp-inputs {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin: 22px 0;
        }


        .otp-inputs input {
            width: 40px;
            height: 50px;

            font-size: 16px;
            font-weight: 600;

            text-align: center;

            border-radius: 12px;

            border: 1px solid var(--border-light);

            background: #fff;

            color: var(--text-main);

            outline: none;

            transition: all 0.2s ease;
        }


        /* =====================================================
           OTP INPUT FOCUS
           ===================================================== */

        .otp-inputs input:focus {
            border-color: var(--blue-main);

            box-shadow:
                0 0 0 2px rgba(31, 58, 95, 0.15);

            transform: translateY(-2px);
        }


        /* =====================================================
           BUTTON
           ===================================================== */

        .btn {
            width: 100%;

            padding: 12px;

            border: none;

            border-radius: 12px;

            background: var(--blue-main);

            color: #fff;

            font-size: 13px;

            font-weight: 600;

            cursor: pointer;

            transition: 0.2s;
        }


        .btn:active {
            transform: scale(0.97);
        }


        /* =====================================================
           RESEND
           ===================================================== */

        .resend {
            margin-top: 14px;

            font-size: 12px;

            color: var(--text-muted);
        }


        .resend-button {

    border: none;

    padding: 0;

    background: transparent;

    color: var(--red-main);

    cursor: pointer;

    font: inherit;

    font-weight: 600;

    transition:
        color 0.2s ease,
        opacity 0.2s ease;
}


.resend-button:hover {

    color: var(--red-hover);

}


.resend-button:disabled {

    color: var(--text-muted);

    cursor: not-allowed;

    opacity: 0.7;
}


        /* =====================================================
           ANIMATION
           ===================================================== */

        @keyframes fadeUp {

            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }

        }


        /* =====================================================
           TABLET
           ===================================================== */

        @media (min-width: 640px) {

            .otp-container {
                max-width: 380px;
            }

            .otp-card {
                padding: 32px 26px;
            }

            .otp-card h2 {
                font-size: 20px;
            }

            .otp-card p {
                font-size: 13px;
            }

            .otp-inputs input {
                height: 56px;
                font-size: 18px;
            }

            .btn {
                font-size: 14px;
                padding: 13px;
            }

        }


        /* =====================================================
           DESKTOP
           ===================================================== */

        @media (min-width: 1024px) {

            body {
                padding: 0;
            }

            .otp-container {
                max-width: 420px;
            }

            .otp-card {
                padding: 40px 32px;
                border-radius: 20px;
            }

            .otp-inputs {
                gap: 10px;
            }

            .otp-inputs input {
                height: 60px;
            }

            .btn:hover {
                background: #162c48;

                transform: translateY(-1px);

                box-shadow:
                    0 8px 18px rgba(31, 58, 95, 0.2);
            }

        }

    </style>

</head>


<body>


    <!-- =====================================================
         OTP CARD
         ===================================================== -->

    <div class="otp-container">

        <div class="otp-card">

            <h2>
                Email Verification
            </h2>

            <p>
                Enter the 6-digit code sent to your email
            </p>


            <!-- =================================================
                 OTP FORM
                 ================================================= -->

            <form
                method="POST"
                action="/verify-otp"
            >

                @csrf


                <!-- Email associated with verification -->
                <input
                    type="hidden"
                    name="email"
                    value="{{ request('email') }}"
                >


                <!-- Combined six-digit OTP -->
                <input
                    type="hidden"
                    name="otp"
                    id="otp"
                >


                <!-- =================================================
                     OTP INPUTS
                     ================================================= -->

                <div class="otp-inputs">

                    <input
                        type="text"
                        maxlength="1"
                        inputmode="numeric"
                        aria-label="OTP digit 1"
                    >

                    <input
                        type="text"
                        maxlength="1"
                        inputmode="numeric"
                        aria-label="OTP digit 2"
                    >

                    <input
                        type="text"
                        maxlength="1"
                        inputmode="numeric"
                        aria-label="OTP digit 3"
                    >

                    <input
                        type="text"
                        maxlength="1"
                        inputmode="numeric"
                        aria-label="OTP digit 4"
                    >

                    <input
                        type="text"
                        maxlength="1"
                        inputmode="numeric"
                        aria-label="OTP digit 5"
                    >

                    <input
                        type="text"
                        maxlength="1"
                        inputmode="numeric"
                        aria-label="OTP digit 6"
                    >

                </div>


                <!-- =================================================
                     VERIFY
                     ================================================= -->

                <button
                    type="submit"
                    class="btn"
                >
                    Verify
                </button>


                <!-- =================================================
                     RESEND
                     ================================================= -->

                <p class="resend">

    Didn't receive code?

    <button
        type="button"
        id="resend-otp"
        class="resend-button"
    >
        Resend
    </button>

</p>

            </form>

        </div>

    </div>


    <!-- =====================================================
         GLOBAL ALERT MODAL
         ===================================================== -->

    @include('components.modal')


    <!-- =====================================================
         GLOBAL ALERT MODAL SYSTEM
         ===================================================== -->

    <script src="{{ asset('jsfiles/components/modal-system.js') }}"></script>


    <script>

document.addEventListener("DOMContentLoaded", function () {

    /*
     * =====================================================
     * ELEMENT REFERENCES
     * =====================================================
     *
     * Retrieve all required elements after the DOM has
     * finished loading.
     */

    const resendButton =
        document.getElementById("resend-otp");

    const emailInput =
        document.querySelector(
            'input[name="email"]'
        );

    const inputs =
        document.querySelectorAll(
            ".otp-inputs input"
        );

    const form =
        document.querySelector(
            ".otp-card form"
        );

    const hiddenInput =
        document.getElementById("otp");

        /*
 * =====================================================
 * SERVER-SIDE RESEND COOLDOWN
 * =====================================================
 */

    /*
 * =====================================================
 * START RESEND COUNTDOWN
 * =====================================================
 */
function startResendCountdown(seconds) {

    /*
     * Nothing to count down.
     */
    if (seconds <= 0) {

        resendButton.disabled = false;

        resendButton.textContent = "Resend";

        return;
    }


    /*
     * Disable the button while the cooldown is active.
     */
    resendButton.disabled = true;


    /*
     * Display the initial remaining time.
     */
    resendButton.textContent =
        `Resend in ${seconds}s`;


    /*
     * Count down once every second.
     */
    const countdown =
        setInterval(function () {

            seconds--;


            /*
             * Update the button text.
             */
            resendButton.textContent =
                `Resend in ${seconds}s`;


            /*
             * Re-enable the button when the cooldown
             * reaches zero.
             */
            if (seconds <= 0) {

                clearInterval(countdown);

                resendButton.disabled = false;

                resendButton.textContent = "Resend";

            }

        }, 1000);
}

/*
 * Restore any cooldown that already exists on the
 * server-side session.
 *
 * This handles page reloads caused by incorrect OTPs
 * or other validation errors.
 */
const serverResendSeconds =
    Number(@json($resendRemaining ?? 0));

startResendCountdown(serverResendSeconds);


    /*
     * =====================================================
     * OTP INPUT BEHAVIOR
     * =====================================================
     */

    inputs.forEach(function (input, index) {

        /*
         * Only allow numeric characters and automatically
         * move to the next OTP field.
         */
        input.addEventListener(
    "input",
    function () {

        /*
         * Allow only one numeric digit.
         *
         * This keeps the OTP fields predictable and
         * prevents non-numeric characters from entering
         * the verification value.
         */
        input.value =
            input.value
                .replace(/\D/g, "")
                .slice(0, 1);


        /*
         * Move the user's cursor to the next OTP field
         * after successfully entering a digit.
         */
        if (
            input.value.length === 1 &&
            index < inputs.length - 1
        ) {

            inputs[index + 1].focus();

        }


        /*
         * =================================================
         * AUTO SUBMIT
         * =================================================
         *
         * Check whether all six OTP fields contain a
         * digit.
         */
        const complete =
            Array.from(inputs).every(
                function (field) {
                    return field.value.length === 1;
                }
            );


        /*
         * If all six fields are complete, construct the
         * complete OTP and submit the form automatically.
         */
        if (complete) {

            let otp = "";


            inputs.forEach(
                function (field) {
                    otp += field.value;
                }
            );


            /*
             * Put the complete OTP into the hidden field
             * that Laravel receives.
             */
            hiddenInput.value = otp;


            /*
             * Submit the form programmatically.
             *
             * requestSubmit() is used instead of
             * form.submit() because it preserves the
             * normal submit event and validation flow.
             */
            form.requestSubmit();

        }

    }
);


        /*
         * Move backwards when Backspace is pressed
         * on an empty field.
         */
        input.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key === "Backspace" &&
                    index > 0 &&
                    input.value === ""
                ) {

                    inputs[index - 1].focus();

                }

            }
        );

    });


    /*
     * =====================================================
     * OTP FORM SUBMISSION
     * =====================================================
     */

    form.addEventListener(
        "submit",
        function (event) {

            let otp = "";


            /*
             * Combine the six individual inputs into
             * one six-digit value.
             */
            inputs.forEach(function (input) {

                otp += input.value.trim();

            });


            /*
             * Store the complete OTP in the hidden field
             * that Laravel receives.
             */
            hiddenInput.value = otp;


            /*
             * Stop the request if the user has not
             * entered all six digits.
             */
            if (otp.length !== 6) {

                event.preventDefault();

                showAlertModal({

                    title:
                        "Incomplete verification code",

                    text:
                        "Please enter all 6 digits of the verification code before continuing.",

                    icon:
                        "!",

                    variant:
                        "danger",

                    confirmText:
                        "OK",

                    showCancel:
                        false

                });

            }

        }
    );


    /*
     * =====================================================
     * RESEND OTP
     * =====================================================
     */

    resendButton.addEventListener(
        "click",
        async function () {

            /*
             * Prevent another request while this request
             * is already being processed.
             */
            if (resendButton.disabled) {
                return;
            }


            /*
             * Get the email from the hidden field.
             */
            const email =
                emailInput.value.trim();


            /*
             * Make sure an email actually exists.
             */
            if (!email) {

                showAlertModal({

                    title:
                        "Verification unavailable",

                    text:
                        "We could not determine the email address being verified. Please start the registration process again.",

                    icon:
                        "!",

                    variant:
                        "danger",

                    confirmText:
                        "OK",

                    showCancel:
                        false

                });

                return;
            }


            /*
             * Disable the button immediately to prevent
             * accidental double-clicks.
             */
            resendButton.disabled = true;

            resendButton.textContent =
                "Sending...";


            try {

                /*
                 * Send the email to Laravel.
                 *
                 * Laravel generates the new OTP and sends
                 * the appropriate email based on the trusted
                 * EmailVerification record.
                 */
                const response =
                    await fetch(
                        "{{ route('otp.resend') }}",
                        {
                            method: "POST",

                            headers: {

                                "Content-Type":
                                    "application/json",

                                "Accept":
                                    "application/json",

                                "X-CSRF-TOKEN":
                                    "{{ csrf_token() }}"

                            },

                            body:
                                JSON.stringify({
                                    email: email
                                })

                        }
                    );


                /*
                 * Convert Laravel's response into JSON.
                 */
                const data =
                    await response.json();


                /*
                 * Handle HTTP errors such as validation
                 * failures or throttling.
                 */
                if (!response.ok) {

                    let message =
                        "We could not send a new verification code.";


                    if (data.message) {

                        message =
                            data.message;

                    }


                    /*
                     * Handle Laravel validation errors.
                     */
                    if (
                        data.errors &&
                        data.errors.email
                    ) {

                        message =
                            data.errors.email[0];

                    }


                    if (
                        data.errors &&
                        data.errors.otp
                    ) {

                        message =
                            data.errors.otp[0];

                    }


                    throw new Error(message);

                }


                /*
                 * =================================================
                 * SUCCESS MODAL
                 * =================================================
                 */

                showAlertModal({

                    title:
                        "Verification code sent",

                    text:
                        "A new verification code has been sent to your email address. The previous code is no longer valid.",

                    icon:
                        "✓",

                    variant:
                        "success",

                    confirmText:
                        "OK",

                    showCancel:
                        false

                });


                /*
                 * =================================================
                 * RESEND COOLDOWN
                 * =================================================
                 *
                 * This is primarily a user-experience feature.
                 * Server-side throttling remains the actual
                 * security protection.
                 */

                startResendCountdown(60);

            } catch (error) {

                /*
                 * =================================================
                 * RESEND ERROR
                 * =================================================
                 */

                showAlertModal({

                    title:
                        "Unable to resend code",

                    text:
                        error.message ||
                        "Something went wrong while sending the verification code. Please try again.",

                    icon:
                        "!",

                    variant:
                        "danger",

                    confirmText:
                        "OK",

                    showCancel:
                        false

                });


                /*
                 * Allow the user to try again after an error.
                 */
                resendButton.disabled =
                    false;

                resendButton.textContent =
                    "Resend";

            }

        }
    );

});



/*
 * =====================================================
 * SERVER-SIDE OTP ERROR
 * =====================================================
 *
 * Laravel redirects back to this page when the submitted
 * OTP is incorrect, expired, or otherwise invalid.
 *
 * The error is displayed through the existing global
 * alert modal instead of the browser's native alert().
 */

@if ($errors->any())

document.addEventListener(
    "DOMContentLoaded",
    function () {

        showAlertModal({

            title:
                "Verification failed",

            text:
                @json($errors->first()),

            icon:
                "!",

            variant:
                "danger",

            confirmText:
                "OK",

            showCancel:
                false

        });

    }
);

@endif

</script>

</body>

</html>