<!DOCTYPE html>
<html lang="en">

<head>

    {{-- Defines the character encoding used by the page. --}}
    <meta charset="UTF-8">

    {{-- Makes the authentication page responsive on smaller screens. --}}
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    {{-- Browser tab title. --}}
    <title>
        KNOWURLOCAL | Recover Account
    </title>


    {{-- =====================================================
         PHOSPHOR ICONS
         =====================================================

         Uses the same lightweight icon library and weight
         as the existing KNOWURLOCAL authentication interface.
    ====================================================== --}}
    <script
        src="https://unpkg.com/@phosphor-icons/web"
    ></script>


    {{-- Uses the same Inter font as the login interface. --}}
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
        rel="stylesheet"
    >


    {{-- Page-specific authentication recovery styles. --}}
    <link
        rel="stylesheet"
        href="{{ asset('cssfiles/public_user/forgot-password.css') }}"
    >

</head>


<body>


<main class="recovery-container">


    {{-- =====================================================
         BRAND PANEL
         =====================================================

         This intentionally follows the same visual language
         as the blue panel on the existing login/register page.
    ====================================================== --}}

    <aside
        class="recovery-panel"
        aria-label="KNOWURLOCAL account recovery information"
    >

        <div class="panel-content">


            {{-- Brand mark used by the existing authentication UI. --}}
            <span class="brand-mark">
                KYL
            </span>


            {{-- Small product label. --}}
            <span class="panel-eyebrow">
                KNOWURLOCAL
            </span>


            <h1>
                Account access,
                <span>simplified.</span>
            </h1>


            <p>
                Recover your account securely and get back
                to accessing local services and information.
            </p>


            {{-- Recovery feature indicators. --}}
            <ul class="features">

                <li>

                    <span class="feature-icon">

                        <i
                            class="ph-light ph-envelope"
                            aria-hidden="true"
                        ></i>

                    </span>

                    <span>
                        Email verification
                    </span>

                </li>


                <li>

                    <span class="feature-icon">

                        <i
                            class="ph-light ph-lock-key-open"
                            aria-hidden="true"
                        ></i>

                    </span>

                    <span>
                        Secure recovery
                    </span>

                </li>


                <li>

                    <span class="feature-icon">

                        <i
                            class="ph-light ph-shield-check"
                            aria-hidden="true"
                        ></i>

                    </span>

                    <span>
                        Protected account
                    </span>

                </li>

            </ul>

        </div>

    </aside>



    {{-- =====================================================
         RECOVERY FORM
         ===================================================== --}}

    <section
        class="recovery-form-side"
        aria-labelledby="forgot-password-title"
    >

        <div class="form">


            {{-- =================================================
                 FORM HEADING
                 ================================================= --}}

            <div class="form-heading">

                <span class="form-eyebrow">
                    ACCOUNT RECOVERY
                </span>

                <h2 id="forgot-password-title">
                    Forgot your password?
                </h2>

                <p class="subtitle">
                    Enter the email address associated with
                    your account and we'll send you a
                    verification code.
                </p>

            </div>


            {{-- =================================================
                 EMAIL FORM
                 ================================================= --}}

            <form
                method="POST"
                action="{{ route('password.email') }}"
            >

                {{-- Protects the form against CSRF attacks. --}}
                @csrf


                <div class="input-group">

                    <label
                        for="forgot-email"
                        class="sr-only"
                    >
                        Email address
                    </label>


                    {{-- Email icon. --}}
                    <i
                        class="ph-light ph-envelope"
                        aria-hidden="true"
                    ></i>


                    <input
                        id="forgot-email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="email"
                        inputmode="email"
                        maxlength="255"
                        placeholder="Email Address"
                        autofocus
                    >

                </div>


                {{-- =================================================
                     SERVER VALIDATION ERROR
                     ================================================= --}}

                @error('email')

                    <p
                        class="form-error"
                        role="alert"
                    >
                        {{ $message }}
                    </p>

                @enderror


                {{-- =================================================
                     SUBMIT
                     ================================================= --}}

                <button
                    type="submit"
                    class="btn"
                >

                    Send verification code

                    <i
                        class="ph-light ph-arrow-right"
                        aria-hidden="true"
                    ></i>

                </button>

            </form>


            {{-- =================================================
                 BACK TO LOGIN
                 ================================================= --}}

            <a
                href="{{ route('public.login') }}"
                class="back-link"
            >

                <i
                    class="ph-light ph-arrow-left"
                    aria-hidden="true"
                ></i>

                Back to sign in

            </a>

        </div>

    </section>

</main>

</body>

</html>