<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>KNOWURLOCAL | My Inquiries</title>

<!-- Phosphor Icons -->
<script src="https://unpkg.com/phosphor-icons"></script>

<link rel="stylesheet" href="{{ asset('cssfiles/theme.css') }}">

<style>

/* ================= BASE ================= */
body{
    font-family:"Inter", sans-serif;
    background:var(--bg-color);
    color:var(--text-main);

    margin:0;
    padding:16px;
}

/* ================= TOP BAR ================= */
.top-bar{
    position:sticky;
    top:0;
    z-index:50;

    display:flex;
    align-items:center;
    gap:10px;

    padding:10px 0;
    margin-bottom:10px;

    background:var(--bg-color);
}

.top-bar h2{
    font-size:18px;
    font-weight:600;
}

/* ================= BACK BUTTON ================= */
.back-btn{
    width:36px;
    height:36px;
    border-radius:999px;
    border:none;
    background:#fff;

    display:flex;
    align-items:center;
    justify-content:center;

    cursor:pointer;
    box-shadow:0 2px 6px rgba(0,0,0,0.05);
}

.back-btn i{
    font-size:16px;
}

/* ================= CONTENT ================= */
.content{
    padding-bottom:40px;
}

/* ================= HEADER TEXT ================= */
.page-header p{
    font-size:12px;
    color:var(--text-muted);
}

/* ================= FILTER ================= */
.filter-bar{
    display:flex;
    gap:8px;
    margin-top:10px;
}

.filter-btn{
    padding:6px 12px;
    border-radius:999px;
    border:1px solid var(--border-light);
    background:#fff;
    cursor:pointer;
    font-size:12px;
    transition:all 0.2s ease;
}

.filter-btn.active{
    background:var(--blue-main);
    color:#fff;
    border-color:var(--blue-main);
}

/* ================= CARD ================= */
.card{
    background:#fff;
    border:1px solid var(--border-light);
    border-radius:14px;
    padding:14px;
    margin-top:12px;

    box-shadow:0 2px 6px rgba(0,0,0,0.04);
    transition:all 0.2s ease;
}

.card:active{
    transform:scale(0.98);
}

/* ================= TOP ================= */
.card-top{
    display:flex;
    justify-content:space-between;
    margin-bottom:8px;
}

/* ================= STATUS ================= */
.status{
    font-size:11px;
    padding:4px 10px;
    border-radius:999px;
}

.status.pending{
    background:#fef3c7;
    color:#92400e;
}

.status.answered{
    background:#d1fae5;
    color:#065f46;
}

/* ================= QUESTION ================= */
.question{
    font-size:14px;
    margin-bottom:6px;
}

/* ================= ANSWER ================= */
.answer{
    background:#f9fafb;
    border-left:3px solid #10b981;
    padding:10px;
    font-size:13px;
    border-radius:6px;
    color:#374151;
}

/* ================= PENDING ================= */
.pending-text{
    font-size:12px;
    color:var(--text-muted);
    font-style:italic;
}

/* ================= EMPTY ================= */
.empty{
    text-align:center;
    margin-top:60px;
    color:var(--text-muted);
}

.empty i{
    font-size:28px;
    opacity:0.7;
}

.empty a{
    display:inline-block;
    margin-top:10px;
    padding:8px 16px;
    border-radius:999px;
    background:var(--blue-main);
    color:#fff;
    font-size:12px;
    text-decoration:none;
}

</style>
</head>

<body>

<!-- ================= TOP BAR ================= -->
<div class="top-bar">
    <button onclick="history.back()" class="back-btn">
        <i class="ph-light ph-arrow-left"></i>
    </button>

    <h2>My Inquiries</h2>
</div>

<!-- ================= CONTENT ================= -->
<div class="content">

    <div class="page-header">
        <p>Track your submitted questions and responses</p>

        <div class="filter-bar">
            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="pending">Pending</button>
            <button class="filter-btn" data-filter="answered">Answered</button>
        </div>
    </div>

    {{-- ================= LIST ================= --}}
    @forelse($requests as $req)

    <div class="card" data-status="{{ $req->status }}">

        <div class="card-top">
            <span class="status {{ $req->status }}">
                {{ ucfirst($req->status) }}
            </span>

            <span>
                {{ $req->created_at->format('M d, Y') }}
            </span>
        </div>

        <div class="question">
            {{ $req->question }}
        </div>

        @if($req->status === 'answered')
            <div class="answer">
                {{ $req->answer }}
            </div>
        @else
            <div class="pending-text">
                Waiting for response...
            </div>
        @endif

    </div>

    @empty

    <div class="empty">
        <i class="ph-light ph-chat-centered-dots"></i>
        <p>No inquiries yet</p>
        <a href="{{ route('map') }}">Ask a question</a>
    </div>

    @endforelse

</div>

<!-- ================= JS ================= -->
<script>
document.addEventListener("DOMContentLoaded", () => {

    const buttons = document.querySelectorAll(".filter-btn");
    const cards = document.querySelectorAll(".card");

    buttons.forEach(btn => {

        btn.addEventListener("click", () => {

            buttons.forEach(b => b.classList.remove("active"));
            btn.classList.add("active");

            const filter = btn.dataset.filter;

            cards.forEach(card => {
                const status = card.dataset.status;

                if(filter === "all" || filter === status){
                    card.style.display = "block";
                } else {
                    card.style.display = "none";
                }
            });

        });

    });

});
</script>

</body>
</html>