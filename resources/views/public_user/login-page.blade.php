<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KNOWURLOCAL | Access Portal</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="{{ secure_asset('cssfiles/public_user/login.css') }}">

</head>

<body>

<div class="container" id="mainContainer">

    <!-- REGISTER -->
    <div class="form-side register-side">
        <form method="POST" action="/register">
        @csrf
        <div class="form">
            <h2>Create account</h2>
            <p class="subtitle">Join our community</p>

            <div class="row">
                <div class="input-group half">
                    <i class="fas fa-user"></i>
                    <input type="text" name="first_name" required placeholder="First Name">
                </div>

                <div class="input-group half">
                    <i class="fas fa-user"></i>
                    <input type="text" name="last_name" required placeholder="Last Name">
                </div>
            </div>

            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" required placeholder="Email Address">
            </div>

            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" required minlength="8" placeholder="Password">
                <span class="toggle-password"><i class="fas fa-eye"></i></span>
            </div>

            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password_confirmation" required minlength="8" placeholder="Confirm Password">
                <span class="toggle-password"><i class="fas fa-eye"></i></span>
            </div>

            <label class="checkbox-group">
                <input type="checkbox" required>
                <span>
                    I agree to the 
                    <a href="/terms" target="_blank">Terms</a> 
                    and 
                    <a href="/privacy" target="_blank">Privacy Policy</a>
                </span>
            </label>

            <button class="btn">Register</button>

            <div class="switch">
                Already a member? <span onclick="toggleAuth()">Login</span>
            </div>
        </div>
        </form>
    </div>

    <!-- LOGIN -->
    <div class="form-side login-side">
        <form method="POST" action="/login">
        @csrf

        <div class="form">
            <h2>Welcome back</h2>
            <p class="subtitle">Enter your credentials</p>

            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" required placeholder="Email Address">
            </div>

            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" required minlength="8" placeholder="Password">
                <span class="toggle-password"><i class="fas fa-eye"></i></span>
            </div>

            <button class="btn">Login</button>

            <div class="divider"><span>OR</span></div>

            <button class="google-btn">
                <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="18">
                Continue with Google
            </button>

            <div class="switch">
                New here? <span onclick="toggleAuth()">Create Account</span>
            </div>
        </div>
        </form>
    </div>

    <!-- CURVED PANEL -->
    <div class="overlay-panel">
        <div class="panel-content login-content active">
            <h1>KNOWURLOCAL</h1>
            <p>Local services, simplified.</p>

            <ul class="features">
                <li><i class="fas fa-building"></i> Agencies</li>
                <li><i class="fas fa-map-marker-alt"></i> Maps</li>
                <li><i class="fas fa-robot"></i> Chatbot</li>
            </ul>
        </div>

        <div class="panel-content register-content">
            <h1>KNOWURLOCAL</h1>
            <p>Get started in seconds.</p>

            <ul class="features">
                <li><i class="fas fa-search"></i> Find</li>
                <li><i class="fas fa-comments"></i> Ask</li>
                <li><i class="fas fa-location-arrow"></i> Navigate</li>
            </ul>
        </div>
    </div>

</div>

<script>
function toggleAuth() {
    document.getElementById("mainContainer").classList.toggle("is-register");

    document.querySelector(".login-content").classList.toggle("active");
    document.querySelector(".register-content").classList.toggle("active");
}

/* PASSWORD TOGGLE */
document.querySelectorAll(".toggle-password").forEach(toggle => {
    toggle.addEventListener("click", () => {
        const input = toggle.previousElementSibling;
        const icon = toggle.querySelector("i");

        if (input.type === "password") {
            input.type = "text";
            icon.classList.replace("fa-eye", "fa-eye-slash");
        } else {
            input.type = "password";
            icon.classList.replace("fa-eye-slash", "fa-eye");
        }
    });
});

const form = document.querySelector(".register-side form");

form.addEventListener("submit", (e) => {

    let hasError = false;

    const email = form.querySelector('input[name="email"]');
    const password = form.querySelector('input[name="password"]');
    const confirm = form.querySelector('input[name="password_confirmation"]');

    const emailValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value);

    if (!emailValid) {
        setState(email.closest(".input-group"), "error", "Invalid email", "fa-times");
        hasError = true;
    }

    if (password.value.length < 8) {
        setState(password.closest(".input-group"), "error", "Minimum 8 characters", "fa-times");
        hasError = true;
    }

    if (password.value !== confirm.value) {
        setState(confirm.closest(".input-group"), "error", "Passwords do not match", "fa-times");
        hasError = true;
    }

    if (hasError) {
        e.preventDefault(); // 🚫 stop submit
    }

});
</script>

</body>
</html>