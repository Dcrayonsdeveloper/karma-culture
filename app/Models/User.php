<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'role',
        'is_verified',
        'is_active',
        'avatar_url',
        'email_verified_at',
        'phone_verified_at',
        'last_login_at',
        'last_login_ip',
        'preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'preferences' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($user) {
            if (empty($user->uuid)) {
                $user->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Send the shop's own password reset email rather than the framework's.
     *
     * The broker calls this method to deliver the reset link; without the
     * override it sends Illuminate's stock notification, which carries no
     * shop name in the body and looks nothing like the other mail this
     * customer has ever had from us.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Relationships
    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(UserAddress::class)->where('is_default', true);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(UserSocialAccount::class);
    }

    public function checkoutPreferences(): HasOne
    {
        return $this->hasOne(UserCheckoutPreference::class);
    }

    public function consents(): HasMany
    {
        return $this->hasMany(UserConsent::class);
    }

    public function admin(): HasOne
    {
        return $this->hasOne(Admin::class);
    }

    public function staff(): HasOne
    {
        return $this->hasOne(Staff::class);
    }

    public function seller(): HasOne
    {
        return $this->hasOne(Seller::class);
    }

    public function wholesaler(): HasOne
    {
        return $this->hasOne(Wholesaler::class);
    }

    public function deliveryPartner(): HasOne
    {
        return $this->hasOne(DeliveryPartner::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Whether this account has taken delivery of a product - what earns a review
     * its "Verified Buyer" badge.
     *
     * Written out three times across the two review controllers before this, so
     * the guest form and the account form could drift on what "verified" means.
     * Note it reads the ORDER's status, not the line item's: a partly delivered
     * order verifies every product on it. That is the behaviour the account form
     * has always had, and this only moves it, it does not change it.
     */
    public function hasPurchased(Product $product): bool
    {
        return $this->orders()
            ->where('status', 'delivered')
            ->whereHas('items', fn ($q) => $q->where('product_id', $product->id))
            ->exists();
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function activeCart(): HasOne
    {
        return $this->hasOne(Cart::class)->latest();
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(UserActivity::class);
    }

    // Helper methods

    /*
     * These gate the admin panel through EnsureUserIsAdmin, so a row that has
     * been switched off must stop counting. Both `admins` and `staff` carry an
     * is_active flag that the admin screens write - Admin > Staff has an
     * activate/deactivate control - and the bare ->exists() checks ignored it,
     * so deactivating a staff member left their admin-panel access exactly as
     * it was. Their password still worked and every screen still opened.
     */

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->admin()->where('is_active', true)->exists();
    }

    public function isSeller(): bool
    {
        return $this->role === 'seller' || $this->seller()->exists();
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff' || $this->staff()->where('is_active', true)->exists();
    }

    public function isWholesaler(): bool
    {
        return $this->wholesaler()->exists();
    }

    public function isDeliveryPartner(): bool
    {
        return $this->role === 'delivery_partner' || $this->deliveryPartner()->exists();
    }

    public function hasWishlisted(int $productId): bool
    {
        return $this->wishlists()->where('product_id', $productId)->exists();
    }

    /**
     * Check if the user can access an admin panel section.
     */
    public function canAccessSection(string $section): bool
    {
        // Admins can access everything
        if ($this->isAdmin()) {
            return true;
        }

        // Staff access based on role + custom permissions
        if ($this->isStaff()) {
            $staff = $this->staff;
            if (!$staff || !$staff->is_active) {
                return false;
            }

            // Check custom permissions first (override defaults)
            if (!empty($staff->permissions)) {
                return in_array($section, $staff->permissions);
            }

            // Default permissions by staff role
            $defaults = self::getDefaultStaffPermissions($staff->role);
            return in_array($section, $defaults);
        }

        return false;
    }

    /**
     * Get default section permissions for a staff role.
     */
    public static function getDefaultStaffPermissions(string $role): array
    {
        return match ($role) {
            'manager' => ['dashboard', 'orders', 'catalog', 'customers', 'marketing', 'content', 'reports'],
            'cashier' => ['dashboard', 'orders', 'customers'],
            'support' => ['dashboard', 'orders', 'customers', 'content'],
            'warehouse' => ['dashboard', 'catalog', 'orders'],
            default => ['dashboard'],
        };
    }

    /**
     * Get all accessible section keys for this user.
     */
    public function getAccessibleSections(): array
    {
        if ($this->isAdmin()) {
            return ['dashboard', 'orders', 'catalog', 'customers', 'staff', 'marketing', 'storefront', 'content', 'reports', 'settings'];
        }

        if ($this->isStaff()) {
            $staff = $this->staff;
            if (!$staff || !$staff->is_active) {
                return ['dashboard'];
            }

            if (!empty($staff->permissions)) {
                return $staff->permissions;
            }

            return self::getDefaultStaffPermissions($staff->role);
        }

        return [];
    }
}
