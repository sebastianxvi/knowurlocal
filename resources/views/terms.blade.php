<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f1f4f9">
    <title>KNOWURLOCAL | Terms of Service</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/knowurlocal-kyl-logo.svg') }}?v=3" sizes="any">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('cssfiles/components/registration-legal-modal.css') }}?v={{ file_exists(public_path('cssfiles/components/registration-legal-modal.css')) ? filemtime(public_path('cssfiles/components/registration-legal-modal.css')) : time() }}">
</head>
<body>
    @php($legalStandaloneDocument = 'terms')
    <main class="legal-standalone-page" data-legal-standalone="terms">
        <article class="legal-standalone-shell">
            <a href="{{ route('public.login') }}" style="display:inline-flex;align-items:center;gap:7px;margin-bottom:25px;color:#4f70d4;font-size:12px;font-weight:650;text-decoration:none;">← Back to KNOWURLOCAL</a>
            @include('components.legal-documents')
        </article>
    </main>
</body>
</html>
