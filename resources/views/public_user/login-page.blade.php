<!DOCTYPE html>
<html lang="en">

<head>

    {{-- Basic document encoding. --}}
    <meta charset="UTF-8">

    {{-- Makes the layout adapt correctly to phones and tablets. --}}
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    {{-- Browser/page title. --}}
    <title>
        KNOWURLOCAL | Access Portal
    </title>


    {{-- =====================================================
         PHOSPHOR ICONS
         =====================================================

         KNOWURLOCAL uses lightweight, outline-style icons
         throughout the system.

         We use the Phosphor web library here so the login
         page matches the admin interface visually.
    ====================================================== --}}
    <script
        src="https://unpkg.com/@phosphor-icons/web"
    ></script>


    {{-- =====================================================
         FONT
         =====================================================

         Inter is used throughout the KNOWURLOCAL interface.
    ====================================================== --}}
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    {{-- Page-specific authentication styles. --}}
    <link
        rel="stylesheet"
        href="{{ asset('cssfiles/public_user/login.css') }}"
    >

    <link
    rel="stylesheet"
    href="{{ asset('cssfiles/components/modal.css') }}"
>
</head>


<body>


<div
    class="container"
    id="mainContainer"
>


    {{-- =====================================================
         REGISTER
         ===================================================== --}}

    <section
        class="form-side register-side"
        aria-label="Create a KNOWURLOCAL account"
    >

        <form
            method="POST"
            action="/register"
        >

            {{-- Laravel CSRF protection. --}}
            @csrf


            <div class="form">


                {{-- Form heading. --}}
                <div class="form-heading">

                    <span class="form-eyebrow">
                        GET STARTED
                    </span>

                    <h2>
                        Create account
                    </h2>

                    <p class="subtitle">
                        Create your KNOWURLOCAL account to get started.
                    </p>

                </div>


                {{-- =================================================
                     NAME
                     ================================================= --}}

                <div class="row">


                    <div class="input-group half">

                        <label
                            for="register-first-name"
                            class="sr-only"
                        >
                            First name
                        </label>

                        <i
                            class="ph-light ph-user"
                            aria-hidden="true"
                        ></i>

                        <input
                            id="register-first-name"
                            type="text"
                            name="first_name"
                            value="{{ old('first_name') }}"
                            required
                            autocomplete="given-name"
                            placeholder="First Name"
                        >

                    </div>


                    <div class="input-group half">

                        <label
                            for="register-last-name"
                            class="sr-only"
                        >
                            Last name
                        </label>

                        <i
                            class="ph-light ph-user"
                            aria-hidden="true"
                        ></i>

                        <input
                            id="register-last-name"
                            type="text"
                            name="last_name"
                            value="{{ old('last_name') }}"
                            required
                            autocomplete="family-name"
                            placeholder="Last Name"
                        >

                    </div>

                </div>


                {{-- =================================================
                     EMAIL
                     ================================================= --}}

                <div class="input-group">

                    <label
                        for="register-email"
                        class="sr-only"
                    >
                        Email address
                    </label>

                    <i
                        class="ph-light ph-envelope"
                        aria-hidden="true"
                    ></i>

                    <input
                        id="register-email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        placeholder="Email Address"
                    >

                </div>


                {{-- =================================================
                     PASSWORD
                     ================================================= --}}

                <div class="input-group">

                    <label
                        for="register-password"
                        class="sr-only"
                    >
                        Password
                    </label>

                    <i
                        class="ph-light ph-lock"
                        aria-hidden="true"
                    ></i>

                    <input
                        id="register-password"
                        type="password"
                        name="password"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        placeholder="Password"
                    >


                    {{-- Button is used instead of a clickable span
                         because this is an interactive control. --}}
                    <button
                        type="button"
                        class="toggle-password"
                        aria-label="Show password"
                    >

                        <i
                            class="ph-light ph-eye"
                            aria-hidden="true"
                        ></i>

                    </button>

                </div>


                {{-- =================================================
                     PASSWORD CONFIRMATION
                     ================================================= --}}

                <div class="input-group">

                    <label
                        for="register-password-confirmation"
                        class="sr-only"
                    >
                        Confirm password
                    </label>

                    <i
                        class="ph-light ph-lock"
                        aria-hidden="true"
                    ></i>

                    <input
                        id="register-password-confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        minlength="8"
                        autocomplete="new-password"
                        placeholder="Confirm Password"
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        aria-label="Show password"
                    >

                        <i
                            class="ph-light ph-eye"
                            aria-hidden="true"
                        ></i>

                    </button>

                </div>


                {{-- =================================================
                     TERMS
                     ================================================= --}}

                <label class="checkbox-group">

                    <input
                        type="checkbox"
                        required
                    >

                    <span>

                        I agree to the

                        <a
                            href="/terms"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Terms
                        </a>

                        and

                        <a
                            href="/privacy"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Privacy Policy
                        </a>

                    </span>

                </label>


                {{-- Registration submit button. --}}
                <button
                    type="submit"
                    class="btn"
                >
                    Create Account
                </button>


                {{-- Switch to login mode. --}}
                <p class="switch">

                    Already have an account?

                    <button
                        type="button"
                        class="switch-button"
                        onclick="toggleAuth()"
                    >
                        Sign in
                    </button>

                </p>

            </div>

        </form>

    </section>



    {{-- =====================================================
         LOGIN
         ===================================================== --}}

    <section
        class="form-side login-side"
        aria-label="Log in to KNOWURLOCAL"
    >

        <form
            method="POST"
            action="/login"
        >

            {{-- Laravel CSRF protection. --}}
            @csrf


            <div class="form">


                {{-- Form heading. --}}
                <div class="form-heading">

                    <span class="form-eyebrow">
                        WELCOME BACK
                    </span>

                    <h2>
                        Welcome back
                    </h2>

                    <p class="subtitle">
                        Sign in to access your KNOWURLOCAL account.
                    </p>

                </div>


                {{-- =================================================
                     EMAIL
                     ================================================= --}}

                <div class="input-group">

                    <label
                        for="login-email"
                        class="sr-only"
                    >
                        Email address
                    </label>

                    <i
                        class="ph-light ph-envelope"
                        aria-hidden="true"
                    ></i>

                    <input
                        id="login-email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        placeholder="Email Address"
                    >

                </div>


                {{-- =================================================
                     PASSWORD
                     ================================================= --}}

                <div class="input-group">

                    <label
                        for="login-password"
                        class="sr-only"
                    >
                        Password
                    </label>

                    <i
                        class="ph-light ph-lock"
                        aria-hidden="true"
                    ></i>

                    <input
                        id="login-password"
                        type="password"
                        name="password"
                        required
                        minlength="8"
                        autocomplete="current-password"
                        placeholder="Password"
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        aria-label="Show password"
                    >

                        <i
                            class="ph-light ph-eye"
                            aria-hidden="true"
                        ></i>

                    </button>

                </div>


                {{-- Login button. --}}
                <button
                    type="submit"
                    class="btn"
                >
                    Sign in
                </button>


                {{-- Switch to registration mode. --}}
                <p class="switch">

                    New to KNOWURLOCAL?

                    <button
                        type="button"
                        class="switch-button"
                        onclick="toggleAuth()"
                    >
                        Create Account
                    </button>

                </p>

            </div>

        </form>

    </section>



    {{-- =====================================================
         BRAND / INFORMATION PANEL
         ===================================================== --}}

    <aside class="overlay-panel">


        {{-- =================================================
             LOGIN PANEL
             ================================================= --}}

        <div class="panel-content login-content active">

            <span class="brand-mark">
                KYL
            </span>

            <span class="panel-eyebrow">
                KNOWURLOCAL
            </span>

            <h1>
                Local services,
                <span>simplified.</span>
            </h1>

            <p>
                Find government offices, discover services,
                navigate locations, and get answers without
                unnecessary trips.
            </p>


            <ul class="features">

                <li>

                    <span class="feature-icon">

                        <i
                            class="ph-light ph-buildings"
                            aria-hidden="true"
                        ></i>

                    </span>

                    <span>
                        Agencies
                    </span>

                </li>


                <li>

                    <span class="feature-icon">

                        <i
                            class="ph-light ph-map-pin"
                            aria-hidden="true"
                        ></i>

                    </span>

                    <span>
                        Maps
                    </span>

                </li>


                <li>

                    <span class="feature-icon">

                        <i
                            class="ph-light ph-robot"
                            aria-hidden="true"
                        ></i>

                    </span>

                    <span>
                        Chatbot
                    </span>

                </li>

            </ul>

        </div>



        {{-- =================================================
             REGISTER PANEL
             ================================================= --}}

        <div class="panel-content register-content">

            <span class="brand-mark">
                KYL
            </span>

            <span class="panel-eyebrow">
                KNOWURLOCAL
            </span>

            <h1>
                Get started
                <span>in seconds.</span>
            </h1>

            <p>
                Create your account and make accessing local
                government information easier.
            </p>


            <ul class="features">

                <li>

                    <span>
                        Find
                    </span>

                    <span class="feature-icon">

                        <i
                            class="ph-light ph-magnifying-glass"
                            aria-hidden="true"
                        ></i>

                    </span>

                </li>


                <li>

                    <span>
                        Ask
                    </span>

                    <span class="feature-icon">

                        <i
                            class="ph-light ph-chats"
                            aria-hidden="true"
                        ></i>

                    </span>

                </li>


                <li>

                    <span>
                        Navigate
                    </span>

                    <span class="feature-icon">

                        <i
                            class="ph-light ph-navigation-arrow"
                            aria-hidden="true"
                        ></i>

                    </span>

                </li>

            </ul>

        </div>

    </aside>


</div>

@include('components.modal')

<script src="{{ asset('jsfiles/components/modal-system.js') }}"></script>


<script>

/*
 * =========================================================
 * AUTH MODE SWITCHING
 * =========================================================
 *
 * Switches between the login and registration forms.
 *
 * The actual visual transition is controlled by login.css.
 */
function toggleAuth() {

    const container =
        document.getElementById("mainContainer");

    container.classList.toggle("is-register");

    document
        .querySelector(".login-content")
        .classList.toggle("active");

    document
        .querySelector(".register-content")
        .classList.toggle("active");
}



/*
 * =========================================================
 * PASSWORD VISIBILITY
 * =========================================================
 *
 * Allows the user to temporarily reveal a password.
 *
 * The password value is never stored or copied by this
 * script.
 */
document
    .querySelectorAll(".toggle-password")
    .forEach(toggle => {

        toggle.addEventListener("click", () => {

            const input =
                toggle.previousElementSibling;

            const icon =
                toggle.querySelector("i");

            const showingPassword =
                input.type === "text";


            if (showingPassword) {

                /*
                 * Hide the password.
                 */
                input.type = "password";

                /*
                 * Restore the normal eye icon.
                 */
                icon.classList.replace(
                    "ph-eye-slash",
                    "ph-eye"
                );

                toggle.setAttribute(
                    "aria-label",
                    "Show password"
                );

            } else {

                /*
                 * Temporarily reveal the password.
                 */
                input.type = "text";

                /*
                 * Show the eye-slash icon.
                 */
                icon.classList.replace(
                    "ph-eye",
                    "ph-eye-slash"
                );

                toggle.setAttribute(
                    "aria-label",
                    "Hide password"
                );

            }

        });

    });



/*
 * =========================================================
 * REGISTRATION VALIDATION
 * =========================================================
 *
 * These checks provide immediate feedback before the
 * registration request reaches Laravel.
 *
 * IMPORTANT:
 * These checks are NOT the security boundary.
 * Laravel must still validate everything server-side.
 */
const registerForm =
    document.querySelector(".register-side form");


if (registerForm) {

    registerForm.addEventListener(
        "submit",
        function (event) {

            const email =
                registerForm.querySelector(
                    'input[name="email"]'
                );

            const password =
                registerForm.querySelector(
                    'input[name="password"]'
                );

            const confirm =
                registerForm.querySelector(
                    'input[name="password_confirmation"]'
                );


            /*
             * =================================================
             * EMAIL
             * =================================================
             */
            const emailValid =
                /^[^\s@]+@[^\s@]+\.[^\s@]+$/
                    .test(email.value.trim());


            if (!emailValid) {

                event.preventDefault();

                showAlertModal({

                    title: "Check your email",

                    text:
                        "Please enter a valid email address before continuing.",

                    icon: "!",

                    variant: "danger",

                    confirmText: "OK",

                    showCancel: false

                });

                email.focus();

                return;
            }


            /*
             * =================================================
             * PASSWORD LENGTH
             * =================================================
             */
            if (password.value.length < 8) {

                event.preventDefault();

                showAlertModal({

                    title: "Password is too short",

                    text:
                        "Your password must contain at least 8 characters. Please choose a stronger password.",

                    icon: "!",

                    variant: "danger",

                    confirmText: "OK",

                    showCancel: false

                });

                password.focus();

                return;
            }


            /*
             * =================================================
             * PASSWORD CONFIRMATION
             * =================================================
             */
            if (password.value !== confirm.value) {

                event.preventDefault();

                showAlertModal({

                    title: "Passwords don't match",

                    text:
                        "The passwords you entered are different. Please check both fields and try again.",

                    icon: "!",

                    variant: "danger",

                    confirmText: "OK",

                    showCancel: false

                });

                confirm.focus();

                return;
            }

        }
    );

}

@if (session('success'))

    showAlertModal({

        title: "Email verified",

        text: @json(session('success')),

        icon: "✓",

        variant: "success",

        confirmText: "OK",

        showCancel: false

    });

@endif


/*
 * =========================================================
 * LARAVEL SERVER-SIDE ERRORS
 * =========================================================
 *
 * Laravel remains responsible for authentication and
 * validation.
 *
 * The original server message is kept immutable.
 * A separate display message is used when we want to
 * provide a friendlier explanation to the user.
 */
@if ($errors->any())

    /*
     * Keep Laravel's original message unchanged.
     *
     * const is intentional because this value should
     * never be modified by the frontend.
     */
    const serverError =
        {{ Illuminate\Support\Js::from($errors->first()) }};


    /*
     * This is the message that will actually be displayed
     * inside the alert modal.
     *
     * Initially it contains Laravel's original message.
     */
    let displayMessage = serverError;


    /*
     * Default modal title.
     */
    let errorTitle =
        "Unable to continue";


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
            "Verify your email";

        displayMessage =
            "Please verify your email address before signing in. " +
            "Check your inbox for the verification email.";

    }


    /*
     * =====================================================
     * INVALID LOGIN CREDENTIALS
     * =====================================================
     *
     * Keep the message generic.
     *
     * We intentionally do not reveal whether the email
     * address exists in the database.
     */
    else if (
        serverError
            .toLowerCase()
            .includes("invalid credentials")
        ||
        serverError
            .toLowerCase()
            .includes("credentials")
    ) {

        errorTitle =
            "Sign-in failed";

        displayMessage =
            "The email address or password you entered is incorrect. " +
            "Please check your credentials and try again.";

    }


    /*
     * =====================================================
     * EXISTING ACCOUNT
     * =====================================================
     */
    else if (
        serverError
            .toLowerCase()
            .includes("already been taken")
        ||
        serverError
            .toLowerCase()
            .includes("already exists")
    ) {

        errorTitle =
            "Account already exists";

        displayMessage =
            "An account with this email address already exists. " +
            "Please sign in instead.";

    }


    /*
     * =====================================================
     * DISPLAY MODAL
     * =====================================================
     *
     * The modal receives the processed display message,
     * while the original Laravel error remains untouched.
     */
    showAlertModal({

        title: errorTitle,

        text: displayMessage,

        icon: "!",

        variant: "danger",

        confirmText: "OK",

        showCancel: false

    });

@endif

</script>


</body>

</html>