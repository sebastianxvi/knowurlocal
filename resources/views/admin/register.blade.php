<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <link rel="stylesheet" href="{{ asset('cssfiles/admin/login-page.css')}}">
  <title>ADMIN REGISTRATION</title>
</head>
<body>

<div class="form-container">

    {{-- ERRORS --}}
    @if ($errors->any())
        <div class="error-alert">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('admin.register') }}" method="POST">
        @csrf

        <div class="logo">
            <img src="{{ asset('images/logo.png') }}">
            <p class="web-name">knowurlocal - admin registration</p>
        </div>

        {{-- 🔐 TOKEN (CRITICAL) --}}
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="input-group">
    <input type="text" name="first_name" required>
    <label>First Name</label>
</div>

<div class="input-group">
    <input type="text" name="last_name" required>
    <label>Last Name</label>
</div>

        {{-- 📧 EMAIL (LOCKED) --}}
        <div class="input-group">
            <input type="email" name="email" value="{{ $email }}" readonly>
            <label>Email</label>
        </div>

        {{-- 🔑 PASSWORD --}}
        <div class="input-group">
            <input type="password" name="password" id="password" required>
            <label>Password</label>
            <p id="showbtn">show</p>
        </div>

        {{-- 🔑 CONFIRM PASSWORD --}}
        <div class="input-group">
            <input type="password" name="password_confirmation" required>
            <label>Confirm Password</label>
        </div>

        <button type="submit" id="loginbtn">Create Account</button>

        <a href="{{ route('admin.login') }}" id="register-link">
            Back to Login
        </a>

    </form>
</div>

<script>
const show = document.getElementById('showbtn');
const pass = document.getElementById('password');

if (show && pass) {
    show.addEventListener('click', function(){
        if (pass.type === "password") {
            pass.type = "text";
            show.textContent = "hide";
        } else {
            pass.type = "password";
            show.textContent = "show";
        }
    });
}
</script>

</body>
</html>