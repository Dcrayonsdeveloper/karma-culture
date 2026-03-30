# GDPR Compliance Implementation

## [AI-REF] Data Protection & Privacy Requirements

---

## GDPR Principles Implementation

### 1. Lawful Basis for Processing

| Data Type | Lawful Basis | Retention |
|-----------|--------------|-----------|
| Account data | Contract | Account lifetime + 6 years |
| Order data | Contract/Legal | 7 years (tax requirement) |
| Marketing emails | Consent | Until withdrawn |
| Analytics data | Legitimate interest | 26 months |
| Support tickets | Contract | 3 years |
| Payment data | Contract/Legal | 7 years |

### 2. Consent Management

```php
// app/Models/UserConsent.php
class UserConsent extends Model
{
    protected $fillable = [
        'user_id',
        'consent_type',  // marketing, analytics, personalization
        'granted',
        'ip_address',
        'user_agent',
        'granted_at',
        'withdrawn_at',
    ];

    protected $casts = [
        'granted' => 'boolean',
        'granted_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];
}

// Cookie Consent Banner
<div x-data="cookieConsent()" x-show="showBanner" class="fixed bottom-0 inset-x-0 bg-white border-t border-neutral-200 p-4 z-50">
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
        <p class="text-sm text-neutral-600">
            We use cookies to improve your experience.
            <a href="/privacy" class="text-primary-500 underline">Learn more</a>
        </p>
        <div class="flex gap-2">
            <button @click="acceptNecessary()" class="px-4 py-2 text-sm text-neutral-700 border border-neutral-200 rounded-md hover:bg-neutral-50">
                Necessary Only
            </button>
            <button @click="acceptAll()" class="px-4 py-2 text-sm text-white bg-primary-500 rounded-md hover:bg-primary-600">
                Accept All
            </button>
        </div>
    </div>
</div>
```

### 3. Data Subject Rights

#### Right to Access (Data Export)

```php
// app/Actions/User/ExportUserData.php
class ExportUserData
{
    public function execute(User $user): array
    {
        return [
            'profile' => $this->exportProfile($user),
            'addresses' => $this->exportAddresses($user),
            'orders' => $this->exportOrders($user),
            'reviews' => $this->exportReviews($user),
            'wishlist' => $this->exportWishlist($user),
            'consents' => $this->exportConsents($user),
            'activity_log' => $this->exportActivityLog($user),
            'export_date' => now()->toIso8601String(),
        ];
    }

    private function exportProfile(User $user): array
    {
        return [
            'name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'created_at' => $user->created_at->toIso8601String(),
            'last_login' => $user->last_login_at?->toIso8601String(),
        ];
    }

    private function exportOrders(User $user): array
    {
        return $user->orders->map(fn($order) => [
            'order_number' => $order->order_number,
            'date' => $order->created_at->toIso8601String(),
            'total' => $order->total,
            'status' => $order->status,
            'items' => $order->items->map(fn($item) => [
                'product' => $item->product_name,
                'quantity' => $item->quantity,
                'price' => $item->price,
            ])->toArray(),
        ])->toArray();
    }
}
```

#### Right to Erasure (Account Deletion)

```php
// app/Actions/User/DeleteUserAccount.php
class DeleteUserAccount
{
    public function execute(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Anonymize orders (keep for legal compliance)
            $user->orders()->update([
                'user_id' => null,
                'shipping_address_snapshot' => $this->anonymizeAddress(
                    $user->orders->first()?->shipping_address_snapshot
                ),
            ]);

            // Delete personal data
            $user->addresses()->delete();
            $user->sessions()->delete();
            $user->consents()->delete();
            $user->wishlist()->delete();

            // Anonymize reviews
            $user->reviews()->update([
                'user_id' => null,
            ]);

            // Delete account
            $user->forceDelete();

            // Log deletion
            AuditLog::create([
                'action' => 'account_deleted',
                'subject_type' => 'user',
                'subject_id' => $user->id,
                'created_at' => now(),
            ]);
        });
    }

    private function anonymizeAddress(?array $address): ?array
    {
        if (!$address) return null;

        return [
            'city' => $address['city'] ?? null,
            'state' => $address['state'] ?? null,
            'country' => $address['country'] ?? null,
            // Personal details removed
        ];
    }
}
```

#### Right to Rectification

```php
// app/Http/Controllers/Api/V1/UserController.php
public function update(UpdateProfileRequest $request): JsonResponse
{
    $user = auth()->user();

    $user->update($request->validated());

    // Log data change
    AuditLog::create([
        'action' => 'profile_updated',
        'subject_type' => 'user',
        'subject_id' => $user->id,
        'properties' => [
            'changed_fields' => array_keys($request->validated()),
        ],
    ]);

    return response()->json([
        'success' => true,
        'data' => new UserResource($user),
    ]);
}
```

### 4. Privacy Policy

```php
// routes/web.php
Route::get('/privacy', fn() => view('pages.privacy'));
Route::get('/cookies', fn() => view('pages.cookies'));
Route::get('/terms', fn() => view('pages.terms'));

// Privacy center for users
Route::middleware('auth')->group(function () {
    Route::get('/account/privacy', [PrivacyController::class, 'index']);
    Route::post('/account/privacy/export', [PrivacyController::class, 'export']);
    Route::post('/account/privacy/delete', [PrivacyController::class, 'delete']);
    Route::put('/account/privacy/consents', [PrivacyController::class, 'updateConsents']);
});
```

### 5. Data Breach Notification

```php
// app/Services/Security/DataBreachService.php
class DataBreachService
{
    public function reportBreach(DataBreach $breach): void
    {
        // Notify DPO within 72 hours
        if ($breach->severity >= 'high') {
            Notification::route('email', config('gdpr.dpo_email'))
                ->notify(new DataBreachAlert($breach));
        }

        // Notify affected users if high risk
        if ($breach->affectsUsers && $breach->severity === 'critical') {
            foreach ($breach->affectedUsers as $user) {
                $user->notify(new DataBreachUserNotification($breach));
            }
        }

        // Log breach
        DataBreachLog::create([
            'type' => $breach->type,
            'severity' => $breach->severity,
            'affected_count' => $breach->affectedCount,
            'description' => $breach->description,
            'detected_at' => now(),
        ]);
    }
}
```

---

## Cookie Categories

| Category | Examples | Required Consent |
|----------|----------|------------------|
| Necessary | Session, CSRF, cart | No (essential) |
| Functional | Language, currency | No (legitimate interest) |
| Analytics | GA4, Clarity | Yes |
| Marketing | Facebook Pixel, ads | Yes |
| Personalization | Recommendations | Yes |

---

## Data Minimization

```php
// Only collect necessary data
class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'min:8'],
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            // Only optional fields that are needed
            'phone' => ['nullable', 'string', 'max:20'],
            // NO: date of birth (unless needed)
            // NO: gender (unless needed)
            // NO: social security (never needed)
        ];
    }
}
```

---

## User Privacy Dashboard UI

```html
<!-- resources/views/account/privacy.blade.php -->
<div class="max-w-3xl mx-auto py-8 px-4">
    <h1 class="text-2xl font-semibold text-neutral-900 mb-6">Privacy Settings</h1>

    <!-- Consent Preferences -->
    <section class="bg-white border border-neutral-200 rounded-lg p-6 mb-6">
        <h2 class="text-lg font-medium text-neutral-900 mb-4">Cookie Preferences</h2>

        <div class="space-y-4">
            <label class="flex items-center justify-between">
                <div>
                    <span class="text-sm font-medium text-neutral-900">Analytics Cookies</span>
                    <p class="text-sm text-neutral-500">Help us improve our website</p>
                </div>
                <input type="checkbox" name="analytics" class="toggle">
            </label>

            <label class="flex items-center justify-between">
                <div>
                    <span class="text-sm font-medium text-neutral-900">Marketing Cookies</span>
                    <p class="text-sm text-neutral-500">Personalized advertisements</p>
                </div>
                <input type="checkbox" name="marketing" class="toggle">
            </label>
        </div>
    </section>

    <!-- Data Export -->
    <section class="bg-white border border-neutral-200 rounded-lg p-6 mb-6">
        <h2 class="text-lg font-medium text-neutral-900 mb-2">Download Your Data</h2>
        <p class="text-sm text-neutral-500 mb-4">
            Get a copy of all data we have about you.
        </p>
        <form action="/account/privacy/export" method="POST">
            @csrf
            <button type="submit" class="px-4 py-2 text-sm font-medium text-primary-500 border border-primary-500 rounded-md hover:bg-primary-50">
                Request Data Export
            </button>
        </form>
    </section>

    <!-- Account Deletion -->
    <section class="bg-white border border-neutral-200 rounded-lg p-6">
        <h2 class="text-lg font-medium text-neutral-900 mb-2">Delete Account</h2>
        <p class="text-sm text-neutral-500 mb-4">
            Permanently delete your account and all associated data.
            This action cannot be undone.
        </p>
        <button
            onclick="document.getElementById('delete-modal').showModal()"
            class="px-4 py-2 text-sm font-medium text-error-500 border border-error-500 rounded-md hover:bg-error-50"
        >
            Delete My Account
        </button>
    </section>
</div>
```

