<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Admin Login - {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css'])

    <style>
        body { font-family: 'Manrope', sans-serif; margin: 0; }
    </style>
</head>
<body class="layout-admin" style="min-height: 100vh; display: flex; -webkit-font-smoothing: antialiased; background: white;">
    {{-- Left side - Branding --}}
    <div style="display: none; width: 50%; background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460); position: relative; overflow: hidden; align-items: center;">
        <style>@media(min-width:1024px){body>div:first-child{display:flex!important;}}</style>
        <div style="position: absolute; inset: 0; opacity: 0.1;">
            <svg style="width: 100%; height: 100%;" viewBox="0 0 400 400" fill="none">
                <circle cx="200" cy="200" r="150" stroke="white" stroke-width="0.5"/>
                <circle cx="200" cy="200" r="200" stroke="white" stroke-width="0.5"/>
                <circle cx="200" cy="200" r="250" stroke="white" stroke-width="0.5"/>
                <circle cx="200" cy="200" r="100" stroke="white" stroke-width="0.5"/>
            </svg>
        </div>
        <div style="position: relative; z-index: 10; display: flex; flex-direction: column; justify-content: center; padding: 3rem 4rem;">
            <img src="{{ asset('images/white_logo.webp') }}" alt="{{ config('app.name') }}" style="height: 3rem; object-fit: contain; object-position: left; margin-bottom: 2rem;" onerror="this.style.display='none'">
            <h1 style="font-size: 2rem; font-weight: 700; color: white; line-height: 1.2; margin: 0 0 1rem 0;">
                Welcome to<br>Admin Panel
            </h1>
            <p style="color: rgba(255,255,255,0.6); font-size: 1rem; max-width: 24rem; line-height: 1.6; margin: 0;">
                Manage your store, products, orders, and customers all in one place.
            </p>
            <div style="margin-top: 2.5rem; display: flex; align-items: center; gap: 1.5rem; color: rgba(255,255,255,0.5); font-size: 0.875rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    Secure Access
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    Real-time Dashboard
                </div>
            </div>
        </div>
    </div>

    {{-- Right side - Login form --}}
    <div style="flex: 1; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 1.5rem 3rem;">
        <div style="width: 100%; max-width: 24rem;">
            {{-- Logo + Header --}}
            <div style="margin-bottom: 2rem;">
                <img src="{{ asset('images/colorlogo.png') }}" alt="{{ config('app.name') }}" style="height: 2.5rem; object-fit: contain; margin-bottom: 1rem;">
                <h2 style="font-size: 1.5rem; font-weight: 700; color: #303030; margin: 0 0 0.25rem 0;">Sign in</h2>
                <p style="color: #616161; font-size: 0.875rem; margin: 0;">Enter your credentials to access the admin panel</p>
            </div>

            @if(session('error'))
                <div style="margin-bottom: 1.25rem; padding: 0.75rem 1rem; background: #fff4f4; border: 1px solid #f0c2bc; border-radius: 8px; color: #d72c0d; font-size: 13px;">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('admin.login') }}" style="display: flex; flex-direction: column; gap: 1.25rem;">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.375rem;">Email address</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                           class="form-input" style="width: 100%; padding: 0.75rem 1rem; font-size: 13px;"
                           placeholder="admin@example.com">
                    @error('email')
                        <p style="margin-top: 0.375rem; font-size: 12px; color: #d72c0d;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" style="display: block; font-size: 13px; font-weight: 500; color: #303030; margin-bottom: 0.375rem;">Password</label>
                    <div style="position: relative;">
                        <input type="password" name="password" id="password" required
                               class="form-input" style="width: 100%; padding: 0.75rem 1rem; padding-right: 2.75rem; font-size: 13px;"
                               placeholder="Enter your password">
                        <button type="button" onclick="togglePassword()" style="position: absolute; inset: 0 0 0 auto; padding-right: 0.875rem; display: flex; align-items: center; color: #616161; background: none; border: none; cursor: pointer;">
                            <svg id="eye-off" style="width: 1.125rem; height: 1.125rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg id="eye-on" style="width: 1.125rem; height: 1.125rem; display: none;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                        </button>
                    </div>
                    @error('password')
                        <p style="margin-top: 0.375rem; font-size: 12px; color: #d72c0d;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Remember me --}}
                <div style="display: flex; align-items: center;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                        <input type="checkbox" name="remember" style="width: 1rem; height: 1rem; accent-color: #303030;">
                        <span style="font-size: 13px; color: #616161;">Remember me</span>
                    </label>
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; font-size: 13px; font-weight: 600;">
                    Sign in
                </button>
            </form>

            {{-- Back link --}}
            <p style="margin-top: 2rem; text-align: center;">
                <a href="{{ url('/') }}" style="font-size: 13px; color: #616161; text-decoration: none; display: inline-flex; align-items: center; gap: 0.375rem;">
                    <svg style="width: 1rem; height: 1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    Back to store
                </a>
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const eyeOff = document.getElementById('eye-off');
            const eyeOn = document.getElementById('eye-on');
            if (input.type === 'password') {
                input.type = 'text';
                eyeOff.style.display = 'none';
                eyeOn.style.display = 'block';
            } else {
                input.type = 'password';
                eyeOff.style.display = 'block';
                eyeOn.style.display = 'none';
            }
        }
    </script>
</body>
</html>
