<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Server Error - {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #fafafa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header {
            background: #fff;
            border-bottom: 1px solid #f0f0f0;
            padding: 1rem 1.5rem;
            text-align: center;
        }
        .header img { height: 2.5rem; object-fit: contain; }
        .content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.5rem;
        }
        .card {
            text-align: center;
            max-width: 28rem;
        }
        .icon-wrap {
            width: 6rem;
            height: 6rem;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .icon-wrap svg { width: 3rem; height: 3rem; color: #fff; }
        .code {
            font-size: 3.5rem;
            font-weight: 700;
            color: #222;
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        .title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #222;
            margin-bottom: 0.5rem;
        }
        .message {
            font-size: 0.875rem;
            color: #666;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .actions { display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 0.75rem;
            transition: all 0.2s;
        }
        .btn-primary {
            background: linear-gradient(to right, #F8931D, #E07E0A);
            color: #fff;
            box-shadow: 0 4px 12px rgba(248, 147, 29, 0.25);
        }
        .btn-primary:hover { box-shadow: 0 4px 16px rgba(248, 147, 29, 0.4); transform: translateY(-1px); }
        .btn-outline {
            background: #fff;
            color: #555;
            border: 1px solid #ddd;
        }
        .btn-outline:hover { border-color: #6F9CA2; color: #6F9CA2; }
        .btn svg { width: 1rem; height: 1rem; }
    </style>
</head>
<body>
    <div class="header">
        <a href="{{ url('/') }}">
            <img src="{{ asset('images/colorlogo.png') }}" alt="{{ config('app.name') }}">
        </a>
    </div>
    <div class="content">
        <div class="card">
            <div class="icon-wrap">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div class="code">500</div>
            <h1 class="title">Something Went Wrong</h1>
            <p class="message">We're experiencing a technical issue. Our team has been notified and is working on it. Please try again in a moment.</p>
            <div class="actions">
                <a href="{{ url('/') }}" class="btn btn-primary">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    Go Home
                </a>
                <a href="javascript:location.reload()" class="btn btn-outline">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Try Again
                </a>
            </div>
        </div>
    </div>
</body>
</html>
