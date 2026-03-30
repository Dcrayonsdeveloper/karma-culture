<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to PayU...</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: white; border-radius: 12px; padding: 2.5rem; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.08); max-width: 400px; width: 90%; }
        .spinner { width: 40px; height: 40px; border: 3px solid #e5e7eb; border-top-color: #6F9CA2; border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 1.25rem; }
        @keyframes spin { to { transform: rotate(360deg); } }
        h2 { font-size: 16px; font-weight: 600; color: #1a1a1a; margin-bottom: 0.5rem; }
        p { font-size: 13px; color: #6b7280; line-height: 1.5; }
        .secure { display: inline-flex; align-items: center; gap: 0.375rem; font-size: 11px; color: #059669; margin-top: 1rem; }
    </style>
</head>
<body>
    <div class="card">
        <div class="spinner"></div>
        <h2>Redirecting to PayU</h2>
        <p>Please wait while we securely redirect you to the payment page. Do not close this window.</p>
        <div class="secure">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            256-bit SSL Secured
        </div>
    </div>

    <form id="payu_form" action="{{ $payuUrl }}" method="POST" style="display:none;">
        @foreach($params as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
    </form>

    <script>
        document.getElementById('payu_form').submit();
    </script>
</body>
</html>
