<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="KNOWURLOCAL administrator account registration"
    >

    <title>KNOWURLOCAL | Admin Registration</title>


    <!-- =====================================================
         INTER FONT
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
         SHARED AUTHENTICATION CSS
         ===================================================== -->

    <link
        rel="stylesheet"
        href="{{ asset('cssfiles/public_user/login.css') }}"
    >


    <!-- =====================================================
         ADMIN REGISTRATION-SPECIFIC CSS
         ===================================================== -->

    <link
        rel="stylesheet"
        href="{{ asset('cssfiles/admin/admin-registration.css') }}"
    >


    <!-- =====================================================
         GLOBAL ALERT MODAL
         ===================================================== -->

    <link
        rel="stylesheet"
        href="{{ asset('cssfiles/components/modal.css') }}"
    >

</head>


<body>


<!-- =========================================================
     AUTHENTICATION CONTAINER

     This intentionally uses the same .container class as
     the public and admin login pages.

     This allows login.css to control the shared layout.
     ========================================================= -->

<div class="container admin-registration">


    <!-- =====================================================
         ADMIN INFORMATION PANEL
         ===================================================== -->

    <div class="overlay-panel">


        <div class="panel-content active">


            <!-- BRAND MARK -->

            <div class="brand-mark">

                <i class="ph-light ph-shield-check"></i>

            </div>


            <!-- PANEL EYEBROW -->

            <span class="panel-eyebrow">
                ADMINISTRATION
            </span>


            <!-- PANEL TITLE -->

            <h1>
                KNOWURLOCAL
            </h1>


            <!-- PANEL DESCRIPTION -->

            <p>

                Manage local information and help keep
                KNOWURLOCAL accurate, useful, and up to date.

            </p>


            <!-- =================================================
                 ADMIN FEATURES
                 ================================================= -->

            <ul class="features">


                <li>

                    <span class="feature-icon">

                        <i class="ph-light ph-buildings"></i>

                    </span>

                    <span>
                        Manage agencies
                    </span>

                </li>


                <li>

                    <span class="feature-icon">

                        <i class="ph-light ph-chat-centered-text"></i>

                    </span>

                    <span>
                        Monitor inquiries
                    </span>

                </li>


                <li>

                    <span class="feature-icon">

                        <i class="ph-light ph-chart-line-up"></i>

                    </span>

                    <span>
                        Review analytics
                    </span>

                </li>


            </ul>


            <!-- =================================================
                 SECURITY NOTE

                 This is registration-specific, so it gets its
                 own class instead of modifying the shared
                 .features component.
                 ================================================= -->

            <div class="admin-registration-security">

                <i class="ph-light ph-lock-key"></i>

                <span>
                    Administrative access is invite-only.
                </span>

            </div>


        </div>

    </div>



    <!-- =====================================================
         REGISTRATION FORM SIDE

         We reuse .form-side and .login-side because the
         registration page uses the same right-side layout
         as the login page.
         ===================================================== -->

    <div class="form-side login-side">


        <form
            method="POST"
            action="{{ route('admin.register') }}"
            id="adminRegistrationForm"
        >

            @csrf


            <div class="form">


                <!-- =================================================
                     FORM HEADER
                     ================================================= -->

                <div class="form-heading">

                    <span class="form-eyebrow">
                        ADMIN ACCESS
                    </span>

                    <h2>
                        Create your account
                    </h2>

                    <p class="subtitle">
                        Complete your account details to continue.
                    </p>

                </div>


                <!-- =================================================
                     INVITATION TOKEN

                     The token comes from the server-generated
                     invitation URL.

                     It must remain hidden from the interface.
                     ================================================= -->

                <input
                    type="hidden"
                    name="token"
                    value="{{ $token }}"
                >


                <!-- =================================================
                     NAME ROW

                     .row and .input-group.half already exist
                     in login.css, so we reuse them.
                     ================================================= -->

                <div class="row">


                    <!-- FIRST NAME -->

                    <div class="input-group half">

                        <i class="ph-light ph-user"></i>

                        <input
                            type="text"
                            name="first_name"
                            value="{{ old('first_name') }}"
                            autocomplete="given-name"
                            maxlength="255"
                            required
                            placeholder="First Name"
                        >

                    </div>


                    <!-- LAST NAME -->

                    <div class="input-group half">

                        <i class="ph-light ph-user"></i>

                        <input
                            type="text"
                            name="last_name"
                            value="{{ old('last_name') }}"
                            autocomplete="family-name"
                            maxlength="255"
                            required
                            placeholder="Last Name"
                        >

                    </div>


                </div>



                <!-- =================================================
                     INVITED EMAIL

                     readonly prevents the administrator from
                     changing the invitation email.

                     The server must still validate this value
                     against the invitation token.
                     ================================================= -->

                <div class="input-group admin-registration-locked">

                    <i class="ph-light ph-envelope-simple"></i>

                    <input
                        type="email"
                        name="email"
                        value="{{ $email }}"
                        readonly
                        aria-readonly="true"
                        autocomplete="email"
                    >


                    <span
                        class="admin-registration-lock"
                        title="Invitation email"
                    >

                        <i class="ph-light ph-lock-key"></i>

                    </span>

                </div>


                <!-- INVITATION EMAIL EXPLANATION -->

                <div class="admin-registration-field-hint">

                    <i class="ph-light ph-info"></i>

                    <span>
                        This email address is tied to your administrator invitation.
                    </span>

                </div>



                <!-- =================================================
                     PASSWORD
                     ================================================= -->

                <div class="input-group">

                    <i class="ph-light ph-lock-key"></i>

                    <input
                        type="password"
                        name="password"
                        id="adminPassword"
                        autocomplete="new-password"
                        minlength="8"
                        required
                        placeholder="Password"
                    >


                    <button
                        type="button"
                        class="toggle-password"
                        id="toggleAdminPassword"
                        aria-label="Show password"
                    >

                        <i class="ph-light ph-eye"></i>

                    </button>

                </div>



                <!-- =================================================
                     CONFIRM PASSWORD
                     ================================================= -->

                <div class="input-group">

                    <i class="ph-light ph-lock-key"></i>

                    <input
                        type="password"
                        name="password_confirmation"
                        id="adminPasswordConfirmation"
                        autocomplete="new-password"
                        minlength="8"
                        required
                        placeholder="Confirm Password"
                    >


                    <button
                        type="button"
                        class="toggle-password"
                        id="toggleAdminPasswordConfirmation"
                        aria-label="Show password"
                    >

                        <i class="ph-light ph-eye"></i>

                    </button>

                </div>


                <!-- PASSWORD REQUIREMENT -->

                <div class="admin-registration-password-hint">

                    <i class="ph-light ph-shield-check"></i>

                    <span>
                        Use at least 8 characters for your password.
                    </span>

                </div>



                <!-- =================================================
                     SUBMIT
                     ================================================= -->

                <button
                    type="submit"
                    class="btn"
                    id="adminRegistrationSubmit"
                >

                    Create Admin Account

                </button>



                {{-- <!-- =================================================
                     BACK TO ADMIN LOGIN
                     ================================================= -->

                <div class="switch">

                    Already have an admin account?

                    <a
                        href="{{ route('admin.login') }}"
                        class="admin-registration-login-link"
                    >
                        Back to Login
                    </a>

                </div> --}}


            </div>

        </form>

    </div>

</div>



<!-- =========================================================
     GLOBAL ALERT MODAL

     We use the exact same modal component used elsewhere.
     ========================================================= -->

@include('components.modal')


<!-- =========================================================
     GLOBAL ALERT MODAL SYSTEM
     ========================================================= -->

<script src="{{ asset('jsfiles/components/modal-system.js') }}"></script>



<script>

document.addEventListener("DOMContentLoaded", () => {


    /*
     * =====================================================
     * PASSWORD VISIBILITY TOGGLE
     * =====================================================
     *
     * Both password fields use the same shared
     * .toggle-password styling from login.css.
     *
     * We only provide the behavior here.
     */

    function setupPasswordToggle(inputId, buttonId) {

        const input =
            document.getElementById(inputId);

        const button =
            document.getElementById(buttonId);


        if (!input || !button) {
            return;
        }


        button.addEventListener("click", () => {

            const icon =
                button.querySelector("i");


            if (input.type === "password") {

                input.type = "text";

                icon.classList.replace(
                    "ph-eye",
                    "ph-eye-slash"
                );

                button.setAttribute(
                    "aria-label",
                    "Hide password"
                );

            } else {

                input.type = "password";

                icon.classList.replace(
                    "ph-eye-slash",
                    "ph-eye"
                );

                button.setAttribute(
                    "aria-label",
                    "Show password"
                );

            }

        });

    }


    setupPasswordToggle(
        "adminPassword",
        "toggleAdminPassword"
    );


    setupPasswordToggle(
        "adminPasswordConfirmation",
        "toggleAdminPasswordConfirmation"
    );



    /*
     * =====================================================
     * REGISTRATION VALIDATION
     * =====================================================
     *
     * This validation improves the user experience.
     *
     * Laravel remains the authoritative validator.
     */

    const form =
        document.getElementById(
            "adminRegistrationForm"
        );


    if (!form) {
        return;
    }


    form.addEventListener("submit", (event) => {

        const password =
            document.getElementById(
                "adminPassword"
            );

        const confirmation =
            document.getElementById(
                "adminPasswordConfirmation"
            );


        /*
         * Check minimum password length.
         */

        if (password.value.length < 8) {

            event.preventDefault();

            showAlertModal({

                title: "Password too short",

                text:
                    "Your password must contain at least 8 characters.",

                icon: "!",

                variant: "danger",

                confirmText: "OK",

                showCancel: false

            });

            password.focus();

            return;
        }


        /*
         * Check password confirmation.
         */

        if (
            password.value !==
            confirmation.value
        ) {

            event.preventDefault();

            showAlertModal({

                title: "Passwords do not match",

                text:
                    "Please make sure both password fields contain the same password.",

                icon: "!",

                variant: "danger",

                confirmText: "OK",

                showCancel: false

            });

            confirmation.focus();

            return;
        }

    });



    /*
     * =====================================================
     * SERVER-SIDE VALIDATION ERRORS
     * =====================================================
     *
     * Laravel's validation remains authoritative.
     *
     * We simply present the first returned error using
     * KNOWURLOCAL's existing alert modal.
     */

    @if ($errors->any())

        showAlertModal({

            title: "Registration failed",

            text: @json($errors->first()),

            icon: "!",

            variant: "danger",

            confirmText: "OK",

            showCancel: false

        });

    @endif

});

</script>


</body>
</html>