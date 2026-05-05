<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KNOWURLOCAL | OTP Verification</title>

<style>
/* ================= DESIGN SYSTEM ================= */
:root {
--blue-main: #1F3A5F;
--red-main: #f06277;
--red-hover: #e2556a;
--bg-color: #e9edf3;


--text-main: #1f2937;
--text-muted: #6b7280;

--border-light: #e5e7eb;


}

/* ================= RESET ================= */
*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Inter', sans-serif;
}

/* ================= BODY (MOBILE FIRST) ================= */
body{
min-height:100vh;
background:var(--bg-color);


display:flex;
align-items:center;
justify-content:center;

padding:16px;


}

/* ================= CONTAINER ================= */
.otp-container{
width:100%;
}

/* ================= CARD ================= */
.otp-card{
width:100%;


background:#fff;
padding:28px 20px;
border-radius:18px;

border:1px solid var(--border-light);

box-shadow:
    0 10px 25px rgba(0,0,0,0.05);

text-align:center;

animation:fadeUp 0.4s ease;


}

/* ================= HEADER ================= */
.otp-card h2{
font-size:18px;
color:var(--blue-main);
margin-bottom:6px;
}

.otp-card p{
font-size:12px;
color:var(--text-muted);
}

/* ================= OTP INPUTS ================= */
.otp-inputs{
    display:flex;
    justify-content:center;
    gap:8px;
    margin:22px 0;
}

/* 🔥 FIXED + RESPONSIVE SIZE */
.otp-inputs input{
    width:40px;
    height:50px;

    font-size:16px;
    font-weight:600;
    text-align:center;

    border-radius:12px;
    border:1px solid var(--border-light);

    background:#fff;
    color:var(--text-main);

    outline:none;
    transition:all 0.2s ease;
}
/* FOCUS */
.otp-inputs input:focus{
border-color:var(--blue-main);
box-shadow:0 0 0 2px rgba(31,58,95,0.15);


transform:translateY(-2px);


}

/* ================= BUTTON ================= */
.btn{
width:100%;
padding:12px;


border:none;
border-radius:12px;

background:var(--blue-main);
color:#fff;

font-size:13px;
font-weight:600;

cursor:pointer;

transition:0.2s;


}

.btn:active{
transform:scale(0.97);
}

/* ================= RESEND ================= */
.resend{
margin-top:14px;
font-size:12px;
color:var(--text-muted);
}

.resend span{
color:var(--red-main);
cursor:pointer;
font-weight:600;
}

/* ================= ERROR ================= */
.error-alert{
background:#ffe5e5;
color:#b91c1c;


padding:10px;
border-radius:10px;

font-size:12px;
margin-bottom:14px;


}

/* ================= ANIMATION ================= */
@keyframes fadeUp{
from{
opacity:0;
transform:translateY(20px);
}
to{
opacity:1;
transform:translateY(0);
}
}

/* =====================================================
TABLET (≥ 640px)
===================================================== */
@media (min-width:640px){


.otp-container{
    max-width:380px;
}

.otp-card{
    padding:32px 26px;
}

.otp-card h2{
    font-size:20px;
}

.otp-card p{
    font-size:13px;
}

.otp-inputs input{
    height:56px;
    font-size:18px;
}

.btn{
    font-size:14px;
    padding:13px;
}


}

/* =====================================================
DESKTOP (≥ 1024px)
===================================================== */
@media (min-width:1024px){


body{
    padding:0;
}

.otp-container{
    max-width:420px;
}

.otp-card{
    padding:40px 32px;
    border-radius:20px;
}

.otp-inputs{
    gap:10px;
}

.otp-inputs input{
    height:60px;
}

.btn:hover{
    background:#162c48;
    transform:translateY(-1px);
    box-shadow:0 8px 18px rgba(31,58,95,0.2);
}


}

</style>
</head>

<body>

    

<div class="otp-container">
    <div class="otp-card">
        <h2>Email Verification</h2>
        <p>Enter the 6-digit code sent to your email</p>

        <!-- ✅ FORM START -->
        <form method="POST" action="/verify-otp">
            @csrf

            @if ($errors->any())
    <div style="color:red; margin-bottom:10px;">
        {{ $errors->first() }}
    </div>
@endif

  <!-- ADD THIS -->
    <input type="hidden" name="email" value="{{ request('email') }}">

    <input type="hidden" name="otp" id="otp">


            <div class="otp-inputs">
                <input type="text" maxlength="1">
                <input type="text" maxlength="1">
                <input type="text" maxlength="1">
                <input type="text" maxlength="1">
                <input type="text" maxlength="1">
                <input type="text" maxlength="1">
            </div>

            <button type="submit" class="btn">Verify</button>

            <p class="resend">
                Didn't receive code? <span>Resend</span>
            </p>
        </form>
        <!-- ✅ FORM END -->

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const inputs = document.querySelectorAll(".otp-inputs input");
    const form = document.querySelector("form");
    const hiddenInput = document.getElementById("otp");

    /* AUTO MOVE */
    inputs.forEach((input, index) => {
        input.addEventListener("input", () => {
            if (input.value.length === 1 && index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        });

        input.addEventListener("keydown", (e) => {
            if (e.key === "Backspace" && index > 0 && !input.value) {
                inputs[index - 1].focus();
            }
        });
    });

    /* SUBMIT */
    form.addEventListener("submit", (e) => {
        let otp = "";

        inputs.forEach(input => {
            otp += input.value.trim();
        });

        hiddenInput.value = otp;

        if (otp.length !== 6) {
            e.preventDefault();
            alert("Please enter complete OTP");
        }
    });

});
</script>

</body>
</html>