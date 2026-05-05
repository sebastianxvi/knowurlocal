<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href={{ asset('cssfiles/test.css')}}>
  <title>Document</title>
</head>
<body>
  <div class="navbar">
    <div class="logo">
      <img src="{{ asset('images/logo.png')}}">
      <p>knowurlocal</p>
    </div>
    <nav>

    <div class="bell-wrapper">
      <i class="fa-regular fa-bell"></i>
      <span class="notification-dot"></span>
    </div>
    
    <button class="logoutbtn">logout</button>
    </nav>
  </div>

  <div class="container">
    <aside class="sidebar">
      <a href="{{ route('dashboard')}}" class="side-item">
        <i class="fa fa-line-chart" aria-hidden="true"></i>
        <span>dashboard</span>
      </a>

      <a href="{{ route('nga-management')}}" class="side-item">
        <i class="fa fa-list-alt" aria-hidden="true"></i>
        <span>nga management</span>
      </a>

      <a href="{{ route('faqmanagement')}}" class="side-item">
        <i class="fa-solid fa-brain"></i>
        <span>faq management</span>
      </a>

      <a href="{{ route('logs')}}" class="side-item">
        <i class="fa fa-book" aria-hidden="true"></i>
        <span>logs</span>
      </a>
    </aside>

    <main>sdshdvhasj</main>
  </div>

  
</body>
</html>