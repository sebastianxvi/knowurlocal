<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KNOWURLOCAL | Agencies</title>

<!-- Phosphor Icons -->
<script src="https://unpkg.com/phosphor-icons"></script>

<link rel="stylesheet" href="{{ asset('cssfiles/theme.css') }}">
<link rel="stylesheet" href="{{ asset('cssfiles/public_user/navbar.css')}}">

<style>

/* ================= BASE ================= */
body{
    font-family:"Inter", sans-serif;
    background:var(--bg-color); /* 🔥 from theme */
    color:var(--text-main);     /* 🔥 consistent text color */

    margin:0;
    padding:16px;
    padding-top:80px;
}

/* ================= TITLE ================= */
.page-title{
    font-size:20px;
    font-weight:600;
    margin-bottom:16px;
}

/* ================= LIST ================= */
.agency-list{
    display:flex;
    flex-direction:column;
    gap:12px;
}

/* ================= CARD ================= */
.agency-card{
    display:flex;
    align-items:center;
    gap:12px;

    padding:12px;
    background:#fff;

    border-radius:14px;

    border:1px solid var(--border-light); /* 🔥 added structure */
    box-shadow:0 2px 6px rgba(0,0,0,0.04); /* 🔥 softer shadow */

    text-decoration:none;
    color:inherit;

    transition:all 0.2s ease;
}

/* subtle hover (desktop only effect) */
.agency-card:hover{
    background:var(--table-hover);
}

/* mobile tap */
.agency-card:active{
    transform:scale(0.97);
}

/* ================= IMAGE ================= */
.card-thumb{
    width:60px;
    height:60px;
    border-radius:12px;
    object-fit:cover;
    flex-shrink:0;
}

/* ================= TEXT ================= */
.card-text{
    flex:1;
    min-width:0;
}

/* NAME */
.card-text h3{
    font-size:14px;
    font-weight:600;
    margin:0;

    color:var(--blue-main); /* 🔥 themed */

    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

/* LOCATION */
.location{
    font-size:12px;
    color:var(--text-muted); /* 🔥 themed */
    margin:2px 0 4px;

    display:-webkit-box;
    -webkit-line-clamp:1;
    -webkit-box-orient:vertical;
    overflow:hidden;
}

/* ================= CONTACT ROW ================= */
.contact-row{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

/* CONTACT ITEM */
.contact-item{
    display:flex;
    align-items:center;
    gap:4px;

    font-size:11px;
    color:var(--text-muted); /* 🔥 themed */

    max-width:100%;
}

/* ICON STYLE */
.contact-item i{
    font-size:12px;
    opacity:0.85;
}

/* ================= RIGHT ICON ================= */
.card-action{
    display:flex;
    align-items:center;
    justify-content:center;
}

.card-action i{
    font-size:18px;
    opacity:0.7;
    color:var(--text-muted); /* 🔥 themed */
    transition:all 0.2s ease;
}

.agency-card:active .card-action i{
    transform:translateX(3px);
    opacity:1;
}

</style>
</head>

<body>

<x-public.navbar />

<h1 class="page-title">Agencies</h1>

<div class="agency-list">

@foreach($agencies as $agency)

<a href="{{ route('navigate', $agency->id) }}" class="agency-card">

    <!-- IMAGE -->
    <img 
        class="card-thumb"
        src="{{ $agency->agency_image 
            ? asset('storage/'.$agency->agency_image) 
            : asset('images/default-agency.png') }}"
        alt="{{ e($agency->agency_name) }}"
    >

    <!-- TEXT -->
    <div class="card-text">

        <!-- TOP: NAME -->
        <h3>{{ $agency->agency_name }}</h3>

        <!-- LOCATION -->
        <p class="location">{{ $agency->agency_location }}</p>

        <!-- CONTACT ROW -->
        <div class="contact-row">

            @if($agency->agency_email)
            <span class="contact-item">
                <i class="ph-light ph-envelope"></i>
                {{ $agency->agency_email }}
            </span>
            @endif

            @if($agency->agency_hotline)
            <span class="contact-item">
                <i class="ph-light ph-phone"></i>
                {{ $agency->agency_hotline }}
            </span>
            @endif

        </div>

    </div>

    <!-- RIGHT ICON -->
    <div class="card-action">
        <i class="ph-light ph-caret-right"></i>
    </div>

</a>

@endforeach

</div>

<script src="{{ asset('jsfiles/public_user/navbar.js') }}" defer></script>

</body>
</html>