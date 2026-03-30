# Security Architecture

## [AI-REF] Comprehensive Security Implementation

This document defines security measures across the platform.

---

## Security Principles

1. **Defense in Depth**: Multiple layers of security
2. **Least Privilege**: Minimal access rights
3. **Secure by Default**: Security-first configuration
4. **Zero Trust**: Verify everything, trust nothing
5. **Fail Securely**: Secure failure modes

---

## Authentication Security

### Password Policy

```php
// config/auth.php
'password' => [
    'min_length' => 8,
    'require_uppercase' => true,
    'require_lowercase' => true,
    'require_numbers' => true,
    'require_symbols' => false,
    'prevent_common' => true,
    'prevent_personal' => true,  // No name, email in password
    'history_count' => 5,        // Prevent reuse of last 5
],

// app/Rules/StrongPassword.php
class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $config = config('auth.password');

        if (strlen($value) < $config['min_length']) {
            $fail("Password must be at least {$config['min_length']} characters.");
        }

        if ($config['require_uppercase'] && !preg_match('/[A-Z]/', $value)) {
            $fail('Password must contain at least one uppercase letter.');
        }

        if ($config['require_lowercase'] && !preg_match('/[a-z]/', $value)) {
            $fail('Password must contain at least one lowercase letter.');
        }

        if ($config['require_numbers'] && !preg_match('/[0-9]/', $value)) {
            $fail('Password must contain at least one number.');
        }

        if ($config['prevent_common'] && $this->isCommonPassword($value)) {
            $fail('This password is too common. Please choose a stronger password.');
        }
    }

    private function isCommonPassword(string $password): bool
    {
        $commonPasswords = Cache::remember('common_passwords', 86400, fn() =>
            file(storage_path('app/security/common-passwords.txt'), FILE_IGNORE_NEW_LINES)
        );

        return in_array(strtolower($password), $commonPasswords);
    }
}
```

### Multi-Factor Authentication

```php
// app/Services/Auth/TwoFactorService.php
class TwoFactorService
{
    public function generateSecret(User $user): string
    {
        $secret = Google2FA::generateSecretKey();

        $user->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_enabled' => false,
        ]);

        return $secret;
    }

    public function verify(User $user, string $code): bool
    {
        $secret = decrypt($user->two_factor_secret);

        $valid = Google2FA::verifyKey($secret, $code, 2); // 2 windows tolerance

        if ($valid && !$user->two_factor_enabled) {
            $user->update(['two_factor_enabled' => true]);
            $this->generateRecoveryCodes($user);
        }

        return $valid;
    }

    public function generateRecoveryCodes(User $user): array
    {
        $codes = Collection::times(8, fn() => Str::random(10))->toArray();

        $user->update([
            'two_factor_recovery_codes' => encrypt(json_encode(
                array_map(fn($code) => bcrypt($code), $codes)
            )),
        ]);

        return $codes;
    }
}
```

### Session Security

```php
// config/session.php
return [
    'driver' => 'redis',
    'lifetime' => 120,              // 2 hours
    'expire_on_close' => false,
    'encrypt' => true,
    'secure' => true,               // HTTPS only
    'http_only' => true,            // No JS access
    'same_site' => 'lax',           // CSRF protection
    'partitioned' => true,          // CHIPS support
];

// app/Http/Middleware/SessionSecurity.php
class SessionSecurity
{
    public function handle(Request $request, Closure $next): Response
    {
        // Regenerate session on privilege change
        if ($request->user() && !session()->has('auth_confirmed')) {
            session()->regenerate();
            session()->put('auth_confirmed', true);
        }

        // Track session fingerprint
        $fingerprint = $this->generateFingerprint($request);
        if (session()->has('fingerprint') && session('fingerprint') !== $fingerprint) {
            Auth::logout();
            session()->invalidate();
            abort(401, 'Session validation failed');
        }
        session()->put('fingerprint', $fingerprint);

        return $next($request);
    }

    private function generateFingerprint(Request $request): string
    {
        return hash('sha256', implode('|', [
            $request->ip(),
            $request->userAgent(),
            $request->user()?->id,
        ]));
    }
}
```

### Brute Force Protection

```php
// app/Http/Middleware/ThrottleLogins.php
class ThrottleLogins
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            // Log suspicious activity
            Log::warning('Login throttled', [
                'ip' => $request->ip(),
                'email' => $request->input('email'),
                'attempts' => RateLimiter::attempts($key),
            ]);

            throw new TooManyRequestsHttpException($seconds);
        }

        $response = $next($request);

        if ($response->status() === 401) {
            RateLimiter::hit($key, 300); // 5 minutes
        } else {
            RateLimiter::clear($key);
        }

        return $response;
    }

    private function throttleKey(Request $request): string
    {
        return 'login:' . hash('sha256', $request->ip() . '|' . $request->input('email'));
    }
}
```

---

## Authorization

### Role-Based Access Control

```php
// app/Enums/Role.php
enum Role: string
{
    case CUSTOMER = 'customer';
    case SELLER = 'seller';
    case STAFF = 'staff';
    case ADMIN = 'admin';
    case SUPER_ADMIN = 'super_admin';

    public function permissions(): array
    {
        return match($this) {
            self::CUSTOMER => [
                'orders.view.own',
                'reviews.create',
                'wishlist.manage',
            ],
            self::SELLER => [
                ...self::CUSTOMER->permissions(),
                'products.manage.own',
                'orders.manage.own',
                'inventory.manage.own',
                'analytics.view.own',
            ],
            self::STAFF => [
                'pos.access',
                'orders.view.all',
                'customers.view',
                'inventory.view',
            ],
            self::ADMIN => [
                ...self::STAFF->permissions(),
                'products.manage.all',
                'orders.manage.all',
                'users.manage',
                'sellers.manage',
                'settings.view',
            ],
            self::SUPER_ADMIN => ['*'],
        };
    }
}

// app/Policies/ProductPolicy.php
class ProductPolicy
{
    public function view(?User $user, Product $product): bool
    {
        return $product->is_active || $this->canManage($user, $product);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->canManage($user, $product);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->canManage($user, $product);
    }

    private function canManage(User $user, Product $product): bool
    {
        if ($user->hasRole(Role::ADMIN)) {
            return true;
        }

        if ($user->hasRole(Role::SELLER)) {
            return $product->seller_id === $user->seller?->id;
        }

        return false;
    }
}
```

---

## Input Validation & Sanitization

### Request Validation

```php
// app/Http/Requests/CreateProductRequest.php
class CreateProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                new NoHtmlTags(),
                new NoSqlInjection(),
            ],
            'description' => [
                'required',
                'string',
                'max:10000',
                new SafeHtml(['p', 'br', 'strong', 'em', 'ul', 'ol', 'li']),
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
                'decimal:0,2',
            ],
            'sku' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9\-_]+$/',
                'unique:products,sku',
            ],
            'category_id' => [
                'required',
                'exists:categories,id',
            ],
            'images' => [
                'required',
                'array',
                'min:1',
                'max:10',
            ],
            'images.*' => [
                'image',
                'mimes:jpeg,png,webp',
                'max:5120', // 5MB
                'dimensions:min_width=500,min_height=500,max_width=4000,max_height=4000',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Sanitize inputs
        $this->merge([
            'name' => strip_tags($this->name),
            'sku' => strtoupper(trim($this->sku)),
        ]);
    }
}
```

### SQL Injection Prevention

```php
// Always use parameterized queries
// WRONG:
DB::select("SELECT * FROM users WHERE email = '$email'");

// CORRECT:
DB::select("SELECT * FROM users WHERE email = ?", [$email]);

// CORRECT with Eloquent:
User::where('email', $email)->first();

// For raw expressions:
Product::whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%'])->get();
```

### XSS Prevention

```php
// In Blade templates - auto-escaped
{{ $product->name }}

// For raw HTML (trusted content only)
{!! $product->description !!}

// Custom sanitization
// app/Rules/SafeHtml.php
class SafeHtml implements ValidationRule
{
    public function __construct(private array $allowedTags = []) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Allowed', implode(',', $this->allowedTags));
        $config->set('URI.DisableExternalResources', true);
        $config->set('HTML.TargetBlank', true);

        $purifier = new HTMLPurifier($config);
        $clean = $purifier->purify($value);

        if ($clean !== $value) {
            $fail('The :attribute contains disallowed HTML content.');
        }
    }
}
```

---

## CSRF Protection

```php
// All forms include CSRF token
<form method="POST">
    @csrf
    <!-- form fields -->
</form>

// AJAX requests include token in header
axios.defaults.headers.common['X-CSRF-TOKEN'] = document
    .querySelector('meta[name="csrf-token"]')
    .getAttribute('content');

// API routes use Sanctum tokens instead
// routes/api.php - no CSRF needed with Bearer token
```

---

## Security Headers

```php
// app/Http/Middleware/SecurityHeaders.php
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        return $response
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('X-Frame-Options', 'SAMEORIGIN')
            ->header('X-XSS-Protection', '1; mode=block')
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->header('Permissions-Policy', $this->permissionsPolicy())
            ->header('Content-Security-Policy', $this->csp());
    }

    private function csp(): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://js.stripe.com https://checkout.razorpay.com https://www.googletagmanager.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data: https: blob:",
            "connect-src 'self' https://api.razorpay.com https://www.google-analytics.com",
            "frame-src https://js.stripe.com https://checkout.razorpay.com",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
            "upgrade-insecure-requests",
        ]);
    }

    private function permissionsPolicy(): string
    {
        return implode(', ', [
            'accelerometer=()',
            'camera=()',
            'geolocation=(self)',
            'gyroscope=()',
            'magnetometer=()',
            'microphone=()',
            'payment=(self)',
            'usb=()',
        ]);
    }
}
```

---

## Fraud Detection

### Fraud Scoring Algorithm

```php
// app/Services/Fraud/FraudDetectionService.php
class FraudDetectionService
{
    public function analyzeOrder(Order $order, Request $request): FraudScore
    {
        $indicators = [];
        $score = 0;

        // Check 1: Multiple accounts from same device
        $deviceFingerprint = $this->getDeviceFingerprint($request);
        $accountCount = $this->getAccountsFromDevice($deviceFingerprint);
        if ($accountCount > 1) {
            $score += 20;
            $indicators[] = 'multiple_accounts_device';
        }

        // Check 2: Velocity check - too many orders
        $recentOrders = $this->getRecentOrders($order->user_id, hours: 24);
        if ($recentOrders > 5) {
            $score += 15;
            $indicators[] = 'high_order_velocity';
        }

        // Check 3: Address mismatch
        if (!$this->addressMatchesBilling($order)) {
            $score += 10;
            $indicators[] = 'address_mismatch';
        }

        // Check 4: High-value first order
        if ($this->isFirstOrder($order->user) && $order->total > 10000) {
            $score += 25;
            $indicators[] = 'high_value_first_order';
        }

        // Check 5: Proxy/VPN detection
        if ($this->isProxyOrVpn($request->ip())) {
            $score += 20;
            $indicators[] = 'proxy_vpn_detected';
        }

        // Check 6: Email domain check
        if ($this->isSuspiciousEmailDomain($order->user->email)) {
            $score += 15;
            $indicators[] = 'suspicious_email';
        }

        // Check 7: Card BIN country mismatch
        if ($this->cardCountryMismatch($order)) {
            $score += 25;
            $indicators[] = 'card_country_mismatch';
        }

        // Check 8: Known fraud patterns
        if ($this->matchesFraudPattern($order)) {
            $score += 40;
            $indicators[] = 'matches_fraud_pattern';
        }

        return new FraudScore(
            score: min($score, 100),
            risk: $this->getRiskLevel($score),
            indicators: $indicators,
            action: $this->getRecommendedAction($score),
        );
    }

    private function getRiskLevel(int $score): string
    {
        return match(true) {
            $score >= 70 => 'high',
            $score >= 40 => 'medium',
            $score >= 20 => 'low',
            default => 'minimal',
        };
    }

    private function getRecommendedAction(int $score): string
    {
        return match(true) {
            $score >= 70 => 'block',
            $score >= 40 => 'manual_review',
            $score >= 20 => 'flag',
            default => 'allow',
        };
    }
}
```

---

## Data Encryption

### At Rest Encryption

```php
// Encrypt sensitive model attributes
// app/Models/User.php
class User extends Authenticatable
{
    protected $casts = [
        'pan_number' => 'encrypted',
        'bank_account' => 'encrypted',
        'two_factor_secret' => 'encrypted',
    ];
}

// app/Models/Seller.php
class Seller extends Model
{
    protected $casts = [
        'bank_details' => 'encrypted:array',
        'documents' => 'encrypted:array',
    ];
}
```

### In Transit Encryption

```php
// Force HTTPS
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    if (app()->environment('production')) {
        URL::forceScheme('https');
    }
}

// Nginx configuration
server {
    listen 443 ssl http2;

    ssl_certificate /etc/ssl/certs/shopverse.crt;
    ssl_certificate_key /etc/ssl/private/shopverse.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 1d;
    ssl_stapling on;
    ssl_stapling_verify on;

    add_header Strict-Transport-Security "max-age=63072000" always;
}
```

---

## API Security

### Rate Limiting

```php
// routes/api.php
Route::middleware(['throttle:api'])->group(function () {
    // API routes
});

// app/Providers/RouteServiceProvider.php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)->by($request->ip());
});

RateLimiter::for('otp', function (Request $request) {
    return Limit::perMinute(3)->by($request->ip());
});
```

### API Key Security

```php
// app/Http/Middleware/ValidateApiKey.php
class ValidateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey) {
            return response()->json(['error' => 'API key required'], 401);
        }

        $hashedKey = hash('sha256', $apiKey);
        $client = ApiClient::where('key_hash', $hashedKey)->first();

        if (!$client || !$client->is_active) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        // Check IP whitelist
        if ($client->ip_whitelist && !in_array($request->ip(), $client->ip_whitelist)) {
            return response()->json(['error' => 'IP not authorized'], 403);
        }

        $request->setApiClient($client);

        return $next($request);
    }
}
```

---

## PCI-DSS Compliance

### Card Data Handling

```php
// NEVER store raw card data
// Use tokenization via payment gateway

// app/Services/Payment/PaymentService.php
class PaymentService
{
    public function processPayment(Order $order, array $paymentData): PaymentResult
    {
        // Card data goes directly to payment gateway
        // We only store tokenized reference
        $response = $this->gateway->createPayment([
            'amount' => $order->total * 100, // Paise
            'currency' => 'INR',
            'receipt' => $order->order_number,
            'notes' => [
                'order_id' => $order->id,
            ],
        ]);

        // Store only non-sensitive data
        Payment::create([
            'order_id' => $order->id,
            'transaction_id' => Str::uuid(),
            'gateway' => 'razorpay',
            'gateway_transaction_id' => $response->id,
            'method' => $paymentData['method'],
            'amount' => $order->total,
            'status' => 'pending',
            // NO card number, CVV, or expiry stored
        ]);

        return new PaymentResult($response);
    }
}
```

---

## Audit Logging

```php
// app/Services/AuditService.php
class AuditService
{
    public function log(
        string $action,
        ?Model $subject = null,
        array $properties = [],
        ?User $causer = null
    ): void {
        AuditLog::create([
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->id,
            'causer_type' => $causer?->getMorphClass() ?? auth()->user()?->getMorphClass(),
            'causer_id' => $causer?->id ?? auth()->id(),
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}

// Usage
$audit->log('order.created', $order, [
    'total' => $order->total,
    'items_count' => $order->items->count(),
]);

$audit->log('user.role_changed', $user, [
    'old_role' => 'customer',
    'new_role' => 'seller',
]);
```

---

## Security Testing Checklist

### Authentication
- [ ] Password brute force protection
- [ ] Account lockout after failed attempts
- [ ] Session timeout implemented
- [ ] Session fixation prevented
- [ ] Remember me tokens secure
- [ ] Password reset tokens expire
- [ ] 2FA implementation tested

### Authorization
- [ ] Role-based access works correctly
- [ ] Horizontal privilege escalation prevented
- [ ] Vertical privilege escalation prevented
- [ ] API endpoints properly protected

### Input Validation
- [ ] SQL injection prevented
- [ ] XSS prevented
- [ ] CSRF tokens validated
- [ ] File upload validation
- [ ] Path traversal prevented

### Data Protection
- [ ] Sensitive data encrypted
- [ ] HTTPS enforced
- [ ] Security headers set
- [ ] PCI-DSS compliance verified

### Logging & Monitoring
- [ ] Security events logged
- [ ] Failed logins tracked
- [ ] Anomaly detection active
- [ ] Audit trail maintained

