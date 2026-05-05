<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KNOWURLOCAL | Admin Login</title>

    <!-- Phosphor Icons (light weight) -->
    <script src="https://unpkg.com/phosphor-icons"></script>

    <!-- Your CSS -->
    <link rel="stylesheet" href="{{ asset('cssfiles/public_user/login.css') }}">

</head>
<body>
<!DOCTYPE html>

<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>KNOWURLOCAL | Admin Login</title>

<!-- Phosphor (light icons only) -->

<script src="https://unpkg.com/phosphor-icons"></script>

<!-- Inter Font -->

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

<!-- YOUR MODERN CSS -->

<link rel="stylesheet" href="{{ asset('cssfiles/admin/login-modern.css') }}">

</head>

<body>

<div class="container">


<!-- LEFT PANEL -->
<div class="overlay-panel">
    <div class="panel-content active">
        <h1>KNOWURLOCAL</h1>
        <p>Admin control panel</p>

        <ul class="features">
            <li><i class="ph-light ph-buildings"></i> Manage Agencies</li>
            <li><i class="ph-light ph-chat-centered"></i> Chatbot Insights</li>
            <li><i class="ph-light ph-chart-bar"></i> Analytics</li>
        </ul>
    </div>
</div>

<!-- LOGIN SIDE -->
<div class="form-side login-side">

    <form method="POST" action="{{ route('login.submit') }}">
    @csrf

    <div class="form">

        <h2>Welcome back</h2>
        <p class="subtitle">Sign in to admin panel</p>

        <!-- ERROR -->
        @if($errors->any())
            <div class="error-alert">
                {{ $errors->first() }}
            </div>
        @endif

        <!-- EMAIL -->
        <div class="input-group">
            <i class="ph-light ph-envelope-simple"></i>
            <input type="email" name="email" required placeholder="Email Address">
        </div>

        <!-- PASSWORD -->
        <div class="input-group">
            <i class="ph-light ph-lock"></i>
            <input type="password" name="password" id="password" required placeholder="Password">

            <span class="toggle-password">
                <i class="ph-light ph-eye"></i>
            </span>
        </div>

        {{-- <!-- FORGOT -->
        <div class="extra-links">
            <a href="#">Forgot Password?</a>
        </div> --}}

        <!-- BUTTON -->
        <button class="btn">
            Login
        </button>

        {{-- <!-- REQUEST ACCESS -->
        <div class="switch">
            <a href="{{ route('admin.register.page') }}">
                Request Admin Access
            </a>
        </div> --}}

    </div>

    </form>

</div>
```

</div>

<script>

/* ================= PASSWORD TOGGLE ================= */
document.querySelector(".toggle-password").addEventListener("click", function(){

    const input = document.getElementById("password");
    const icon = this.querySelector("i");

    if(input.type === "password"){
        input.type = "text";
        icon.classList.replace("ph-eye", "ph-eye-slash");
    }else{
        input.type = "password";
        icon.classList.replace("ph-eye-slash", "ph-eye");
    }

});

</script>

</body>
</html>
