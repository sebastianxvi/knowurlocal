<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>KNOWURLOCAL | Admin Login</title>


    <!-- =====================================================
         INTER
         ===================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >


    <!-- =====================================================
         PHOSPHOR ICONS
         ===================================================== -->

    <script src="https://unpkg.com/phosphor-icons"></script>


    <!-- =====================================================
         SHARED AUTHENTICATION STYLES
         ===================================================== -->

    <link
        rel="stylesheet"
        href="{{ asset('cssfiles/public_user/login.css') }}"
    >


    <!-- =====================================================
         GLOBAL ALERT MODAL STYLES
         ===================================================== -->

    <link
        rel="stylesheet"
        href="{{ asset('cssfiles/components/modal.css') }}"
    >

</head>


<body>


<div class="container">


    <!-- =====================================================
         ADMIN INFORMATION PANEL
         ===================================================== -->

    <div class="overlay-panel">

        <div class="panel-content active">

            <div class="brand-mark">

                <i class="ph-light ph-shield-check"></i>

            </div>


            <span class="panel-eyebrow">
                ADMINISTRATION
            </span>


            <h1>
                KNOWURLOCAL
            </h1>


            <p>
                Manage local information,
                agencies, chatbot services,
                and platform analytics from one place.
            </p>


            <ul class="features">

                <li>

                    <span class="feature-icon">
                        <i class="ph-light ph-buildings"></i>
                    </span>

                    <span>
                        Agencies
                    </span>

                </li>


                <li>

                    <span class="feature-icon">
                        <i class="ph-light ph-chat-centered-text"></i>
                    </span>

                    <span>
                        Chatbot
                    </span>

                </li>


                <li>

                    <span class="feature-icon">
                        <i class="ph-light ph-chart-line-up"></i>
                    </span>

                    <span>
                        Analytics
                    </span>

                </li>

            </ul>

        </div>

    </div>


    <!-- =====================================================
         ADMIN LOGIN
         ===================================================== -->

    <div class="form-side login-side">

        <form
            method="POST"
            action="{{ route('login.submit') }}"
        >

            @csrf


            <div class="form">


                <!-- =================================================
                     FORM HEADER
                     ================================================= -->

                <div class="form-heading">

                    <span class="form-eyebrow">
                        ADMIN PORTAL
                    </span>

                    <h2>
                        Welcome back
                    </h2>

                    <p class="subtitle">
                        Sign in to continue to the
                        KNOWURLOCAL administration panel.
                    </p>

                </div>


                <!-- =================================================
                     EMAIL
                     ================================================= -->

                <div class="input-group">

                    <i class="ph-light ph-envelope-simple"></i>

                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        required
                        placeholder="Email Address"
                    >

                </div>


                <!-- =================================================
                     PASSWORD
                     ================================================= -->

                <div class="input-group">

                    <i class="ph-light ph-lock-key"></i>

                    <input
                        type="password"
                        name="password"
                        id="password"
                        autocomplete="current-password"
                        required
                        placeholder="Password"
                    >


                    <button
                        type="button"
                        class="toggle-password"
                        aria-label="Show password"
                    >

                        <i class="ph-light ph-eye"></i>

                    </button>

                </div>


                <!-- =================================================
                     LOGIN BUTTON
                     ================================================= -->

                <button
                    type="submit"
                    class="btn"
                >

                    Sign in

                </button>


                <!-- =================================================
                     SECURITY MESSAGE
                     ================================================= -->

                <div class="switch">

                    <i class="ph-light ph-shield-check"></i>

                    Authorized administrators only

                </div>

            </div>

        </form>

    </div>

</div>


<!-- =========================================================
     GLOBAL ALERT MODAL
     ========================================================= -->

@include('components.modal')


<!-- =========================================================
     ALERT MODAL SYSTEM
     ========================================================= -->

<script src="{{ asset('jsfiles/components/modal-system.js') }}"></script>


<script>

document.addEventListener(
    "DOMContentLoaded",
    function () {


        /*
         * =====================================================
         * PASSWORD VISIBILITY
         * =====================================================
         */

        const passwordInput =
            document.getElementById("password");

        const togglePassword =
            document.querySelector(".toggle-password");


        if (
            passwordInput &&
            togglePassword
        ) {

            togglePassword.addEventListener(
                "click",
                function () {

                    const icon =
                        togglePassword.querySelector("i");


                    if (
                        passwordInput.type ===
                        "password"
                    ) {

                        passwordInput.type =
                            "text";

                        icon.classList.replace(
                            "ph-eye",
                            "ph-eye-slash"
                        );

                        togglePassword.setAttribute(
                            "aria-label",
                            "Hide password"
                        );

                    } else {

                        passwordInput.type =
                            "password";

                        icon.classList.replace(
                            "ph-eye-slash",
                            "ph-eye"
                        );

                        togglePassword.setAttribute(
                            "aria-label",
                            "Show password"
                        );

                    }

                }
            );

        }


        /*
         * =====================================================
         * LOGIN ERROR MODAL
         * =====================================================
         *
         * Authentication errors originate from Laravel.
         *
         * The server remains responsible for deciding whether
         * authentication succeeds or fails. JavaScript only
         * presents the resulting message.
         */

        @if ($errors->any())

    /*
     * Laravel provides the original server-side error.
     *
     * We do not modify the server response itself.
     * We only choose a more helpful presentation for
     * the administrator.
     */
    const serverError =
        {{ Illuminate\Support\Js::from($errors->first()) }};


    /*
     * Default values.
     *
     * If a future authentication error is introduced,
     * the original Laravel message will still be shown.
     */
    let errorTitle =
        "Unable to sign in";

    let errorMessage =
        serverError;


    /*
     * =====================================================
     * EMAIL VERIFICATION
     * =====================================================
     */

    if (
        serverError
            .toLowerCase()
            .includes("verify your email")
    ) {

        errorTitle =
            "Email verification required";

        errorMessage =
            "Your email address has not been verified yet. " +
            "Please complete the email verification process " +
            "before signing in.";

    }


    /*
     * =====================================================
     * ADMIN APPROVAL
     * =====================================================
     */

    else if (
        serverError
            .toLowerCase()
            .includes("awaiting approval")
        ||
        serverError
            .toLowerCase()
            .includes("not yet approved")
    ) {

        errorTitle =
            "Approval pending";

        errorMessage =
            "Your admin account has been verified successfully " +
            "but is still awaiting approval from an authorized " +
            "administrator. You will be able to sign in once " +
            "your account has been approved.";

    }


    /*
     * =====================================================
     * INVALID CREDENTIALS
     * =====================================================
     */

    else if (
        serverError
            .toLowerCase()
            .includes("invalid credentials")
    ) {

        errorTitle =
            "Sign-in failed";

        errorMessage =
            "The email address or password you entered is incorrect. " +
            "Please check your credentials and try again.";

    }


    /*
     * =====================================================
     * DISPLAY MODAL
     * =====================================================
     */

    showAlertModal({

        title:
            errorTitle,

        text:
            errorMessage,

        icon:
            "!",

        variant:
            "danger",

        confirmText:
            "OK",

        showCancel:
            false

    });

@endif


        /*
         * =====================================================
         * SUCCESS MODAL
         * =====================================================
         */

        @if (session('success'))

            showAlertModal({

                title:
                    "Success",

                text:
                    @json(session('success')),

                icon:
                    "✓",

                variant:
                    "success",

                confirmText:
                    "OK",

                showCancel:
                    false

            });

        @endif

    }
);

</script>


</body>

</html>