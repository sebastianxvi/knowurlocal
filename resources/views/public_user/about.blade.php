<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>KNOWURLOCAL | About</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<script src="https://unpkg.com/phosphor-icons"></script>

<style>

/* ================= MOBILE-FIRST DESIGN SYSTEM ================= */
:root{
    --bg:#0b1020;
    --card:rgba(255,255,255,0.05);
    --glass:rgba(255,255,255,0.06);
    --border:rgba(255,255,255,0.12);

    --text:#e5e7eb;
    --sub:#94a3b8;

    --accent:#3b82f6;
    --accent2:#8b5cf6;
    --accent3:#06b6d4;

    --gradient:linear-gradient(135deg,#3b82f6,#8b5cf6,#06b6d4);
}

/* ================= GLOBAL ================= */
body{
    margin:0;
    font-family:"Inter", sans-serif;
    background:radial-gradient(circle at top,#111827,#020617);
    color:var(--text);
    overflow-x:hidden;
}

/* ================= CONTAINER ================= */
.container{
    padding:20px;
}

/* ================= HERO ================= */
.hero{
    position:relative;
    padding:80px 20px;
    text-align:center;
}

.hero h1{
    font-size:36px;
    background:var(--gradient);
    -webkit-background-clip:text;
    color:transparent;
}

.hero p{
    color:var(--sub);
}

/* ================= FLOATING BLOBS ================= */
.blob{
    position:absolute;
    width:200px;
    height:200px;
    background:var(--gradient);
    filter:blur(80px);
    opacity:0.3;
    z-index:-1;
    animation:float 8s infinite alternate;
}

.blob1{ top:-50px; left:-50px; }
.blob2{ bottom:-50px; right:-50px; }

@keyframes float{
    from{ transform:translateY(0px); }
    to{ transform:translateY(40px); }
}

/* ================= SECTION ================= */
.section{
    margin-top:60px;
}

/* ================= CARDS ================= */
.card{
    background:var(--glass);
    border:1px solid var(--border);
    border-radius:18px;
    padding:20px;
    backdrop-filter:blur(10px);
    margin-bottom:20px;
    transition:0.3s;
}

.card:hover{
    transform:translateY(-5px);
}

/* ================= GRID ================= */
.grid{
    display:grid;
    gap:20px;
}

/* ================= ICON ================= */
.card i{
    font-size:22px;
    margin-bottom:10px;
    opacity:0.9;
}

/* ================= STATS ================= */
.stat{
    text-align:center;
}

.stat h2{
    font-size:28px;
    margin:5px 0;
}

/* ================= STORY ================= */
.story{
    display:flex;
    flex-direction:column;
    gap:20px;
}

.story img{
    width:100%;
    border-radius:18px;
}

/* ================= CHATBOT ================= */
.chatbot{
    background:linear-gradient(135deg,#1e293b,#0f172a);
    border-radius:20px;
    padding:30px;
    text-align:center;
}

/* ================= CTA ================= */
.cta{
    text-align:center;
    padding:40px 20px;
}

.cta input{
    width:100%;
    padding:12px;
    border-radius:10px;
    border:none;
    margin-top:10px;
}

.cta button{
    margin-top:10px;
    width:100%;
    padding:12px;
    background:var(--accent);
    border:none;
    color:white;
    border-radius:10px;
}

/* ================= DESKTOP ================= */
@media(min-width:768px){

    .container{
        max-width:1200px;
        margin:auto;
    }

    .hero{
        text-align:left;
    }

    .hero h1{
        font-size:52px;
    }

    .grid-2{
        grid-template-columns:1fr 1fr;
    }

    .grid-3{
        grid-template-columns:repeat(3,1fr);
    }

    .grid-4{
        grid-template-columns:repeat(4,1fr);
    }

    .story{
        flex-direction:row;
        align-items:center;
    }

}

/* ================= ANIMATION ================= */
.fade{
    opacity:0;
    transform:translateY(30px);
    transition:0.8s;
}

.fade.show{
    opacity:1;
    transform:translateY(0);
}

</style>
</head>

<body>

<!-- HERO -->
<div class="hero">
    <div class="blob blob1"></div>
    <div class="blob blob2"></div>

    <h1>KNOWURLOCAL</h1>
    <p>Modern access to government services in San Jose</p>
</div>

<div class="container">

    <!-- STATS -->
    <div class="section grid grid-2 fade">
        <div class="card stat">
            <small>Agencies</small>
            <h2>50+</h2>
        </div>

        <div class="card stat">
            <small>Accuracy</small>
            <h2>95%</h2>
        </div>

        <div class="card stat">
            <small>Users</small>
            <h2>1K+</h2>
        </div>

        <div class="card stat">
            <small>Speed</small>
            <h2>Instant</h2>
        </div>
    </div>

    <!-- STEPS -->
    <div class="section fade">
        <h2>How It Works</h2>

        <div class="grid grid-4">

            <div class="card">
                <i class="ph-light ph-magnifying-glass"></i>
                <h4>Search</h4>
                <p>Find agencies instantly</p>
            </div>

            <div class="card">
                <i class="ph-light ph-map-pin"></i>
                <h4>Locate</h4>
                <p>Use map navigation</p>
            </div>

            <div class="card">
                <i class="ph-light ph-chat-circle"></i>
                <h4>Ask</h4>
                <p>Chatbot answers</p>
            </div>

            <div class="card">
                <i class="ph-light ph-clock"></i>
                <h4>Save Time</h4>
                <p>No long lines</p>
            </div>

        </div>
    </div>

    <!-- STORY -->
    <div class="section story fade">

        <div>
            <h2>Why We Built This</h2>
            <p>
                Citizens often waste time traveling just to ask simple questions.
                KNOWURLOCAL eliminates that problem by centralizing data and
                automating answers.
            </p>
        </div>

        <img src="https://images.unsplash.com/photo-1524758631624-e2822e304c36">

    </div>

    <!-- CHATBOT -->
    <div class="section chatbot fade">
        <h2>Smart Chatbot</h2>
        <p>Ask anything and get answers instantly.</p>
    </div>

    <!-- CTA -->
    <div class="cta fade">
        <h2>Stay Updated</h2>
        <input placeholder="Enter your email">
        <button>Subscribe</button>
    </div>

</div>

<script>

/* ================= INTERSECTION OBSERVER ================= */
const observer = new IntersectionObserver(entries=>{
    entries.forEach(entry=>{
        if(entry.isIntersecting){
            entry.target.classList.add('show');
        }
    });
},{threshold:0.15});

document.querySelectorAll('.fade').forEach(el=>{
    observer.observe(el);
});

</script>

</body>
</html>