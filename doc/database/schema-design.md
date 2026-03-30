# Database Schema Design

## [AI-REF] Complete Schema Specification

This document provides Laravel migration specifications for AI-assisted code generation.

---

## Migration Conventions

```php
// Naming: YYYY_MM_DD_HHMMSS_create_tablename_table.php
// Example: 2026_01_29_100000_create_users_table.php

// Standard columns for all tables
$table->id();                           // bigint unsigned auto_increment
$table->timestamps();                   // created_at, updated_at
$table->softDeletes();                  // deleted_at (where applicable)

// UUID for public exposure
$table->uuid('uuid')->unique();

// Foreign keys naming
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
```

---

## Core Migrations

### 1. Users & Authentication

```php
// 2026_01_29_100001_create_users_table.php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->string('first_name', 50);
    $table->string('last_name', 50);
    $table->string('email', 255)->unique();
    $table->string('phone', 20)->unique()->nullable();
    $table->string('password');
    $table->enum('role', ['customer', 'seller', 'staff', 'admin'])->default('customer');
    $table->boolean('is_verified')->default(false);
    $table->boolean('is_active')->default(true);
    $table->string('avatar_url', 500)->nullable();
    $table->timestamp('email_verified_at')->nullable();
    $table->timestamp('phone_verified_at')->nullable();
    $table->timestamp('last_login_at')->nullable();
    $table->ipAddress('last_login_ip')->nullable();
    $table->json('preferences')->nullable();
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['email', 'is_active']);
    $table->index(['role', 'is_active']);
});

// 2026_01_29_100002_create_user_addresses_table.php
Schema::create('user_addresses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('label', 50)->nullable(); // Home, Office, etc.
    $table->string('first_name', 50);
    $table->string('last_name', 50);
    $table->string('phone', 20);
    $table->string('address_line_1', 255);
    $table->string('address_line_2', 255)->nullable();
    $table->string('city', 100);
    $table->string('state', 100);
    $table->string('postal_code', 20);
    $table->string('country', 2)->default('IN');
    $table->decimal('latitude', 10, 7)->nullable();
    $table->decimal('longitude', 10, 7)->nullable();
    $table->boolean('is_default')->default(false);
    $table->enum('type', ['shipping', 'billing', 'both'])->default('both');
    $table->timestamps();

    $table->index(['user_id', 'is_default']);
    $table->index('postal_code');
});

// 2026_01_29_100003_create_user_sessions_table.php
Schema::create('user_sessions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('token', 64)->unique();
    $table->ipAddress('ip_address');
    $table->text('user_agent');
    $table->json('device_info')->nullable();
    $table->timestamp('last_activity_at');
    $table->timestamp('expires_at');
    $table->timestamp('created_at');

    $table->index(['user_id', 'last_activity_at']);
    $table->index('expires_at');
});
```

### 2. Categories & Brands

```php
// 2026_01_29_100010_create_categories_table.php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
    $table->string('name', 100);
    $table->string('slug', 120)->unique();
    $table->text('description')->nullable();
    $table->string('image_url', 500)->nullable();
    $table->string('icon', 50)->nullable();
    $table->unsignedSmallInteger('position')->default(0);
    $table->unsignedTinyInteger('level')->default(0);
    $table->string('path', 255)->nullable(); // Materialized path: 1/5/12
    $table->boolean('is_active')->default(true);
    $table->boolean('is_featured')->default(false);
    $table->json('seo_data')->nullable();
    $table->json('attributes_schema')->nullable(); // Defines what attributes products should have
    $table->timestamps();

    $table->index(['parent_id', 'position']);
    $table->index(['is_active', 'is_featured']);
    $table->index('path');
});

// 2026_01_29_100011_create_brands_table.php
Schema::create('brands', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->string('slug', 120)->unique();
    $table->text('description')->nullable();
    $table->string('logo_url', 500)->nullable();
    $table->string('website_url', 255)->nullable();
    $table->boolean('is_active')->default(true);
    $table->boolean('is_featured')->default(false);
    $table->unsignedSmallInteger('position')->default(0);
    $table->json('seo_data')->nullable();
    $table->timestamps();

    $table->index(['is_active', 'is_featured']);
});
```

### 3. Products

```php
// 2026_01_29_100020_create_products_table.php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
    $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('category_id')->constrained()->restrictOnDelete();
    $table->string('name', 255);
    $table->string('slug', 280)->unique();
    $table->string('short_description', 500)->nullable();
    $table->text('description')->nullable();
    $table->string('sku', 50)->unique();
    $table->string('barcode', 50)->unique()->nullable();
    $table->decimal('mrp', 12, 2);
    $table->decimal('price', 12, 2);
    $table->decimal('cost_price', 12, 2)->nullable();
    $table->unsignedInteger('stock_quantity')->default(0);
    $table->unsignedInteger('low_stock_threshold')->default(10);
    $table->enum('stock_status', ['in_stock', 'out_of_stock', 'backorder'])->default('in_stock');
    $table->decimal('weight', 8, 3)->nullable();
    $table->decimal('length', 8, 2)->nullable();
    $table->decimal('width', 8, 2)->nullable();
    $table->decimal('height', 8, 2)->nullable();
    $table->enum('weight_unit', ['g', 'kg', 'lb', 'oz'])->default('g');
    $table->enum('dimension_unit', ['cm', 'm', 'in', 'ft'])->default('cm');
    $table->boolean('is_active')->default(true);
    $table->boolean('is_featured')->default(false);
    $table->boolean('is_taxable')->default(true);
    $table->decimal('tax_rate', 5, 2)->nullable();
    $table->string('hsn_code', 20)->nullable();
    $table->decimal('rating', 3, 2)->default(0);
    $table->unsignedInteger('review_count')->default(0);
    $table->unsignedInteger('view_count')->default(0);
    $table->unsignedInteger('sales_count')->default(0);
    $table->unsignedInteger('wishlist_count')->default(0);
    $table->json('seo_data')->nullable();
    $table->json('attributes')->nullable();
    $table->json('specifications')->nullable();
    $table->enum('status', ['draft', 'pending', 'approved', 'rejected'])->default('pending');
    $table->string('rejection_reason', 255)->nullable();
    $table->timestamp('published_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    // Search & filter indexes
    $table->index(['is_active', 'status', 'category_id']);
    $table->index(['is_active', 'status', 'brand_id']);
    $table->index(['is_active', 'status', 'seller_id']);
    $table->index(['is_active', 'price']);
    $table->index(['is_active', 'rating']);
    $table->index(['is_active', 'sales_count']);
    $table->index(['is_active', 'created_at']);
    $table->index('barcode');

    // Full-text search
    $table->fullText(['name', 'short_description']);
});

// 2026_01_29_100021_create_product_variants_table.php
Schema::create('product_variants', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->string('name', 100); // e.g., "Red - Large"
    $table->string('sku', 50)->unique();
    $table->string('barcode', 50)->unique()->nullable();
    $table->decimal('mrp', 12, 2);
    $table->decimal('price', 12, 2);
    $table->unsignedInteger('stock_quantity')->default(0);
    $table->json('attributes'); // {"color": "Red", "size": "L"}
    $table->boolean('is_active')->default(true);
    $table->timestamps();

    $table->index(['product_id', 'is_active']);
});

// 2026_01_29_100022_create_product_images_table.php
Schema::create('product_images', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
    $table->string('url', 500);
    $table->string('alt_text', 255)->nullable();
    $table->unsignedTinyInteger('position')->default(0);
    $table->boolean('is_primary')->default(false);
    $table->timestamp('created_at');

    $table->index(['product_id', 'position']);
    $table->index(['variant_id', 'position']);
});

// 2026_01_29_100023_create_product_attributes_table.php
Schema::create('product_attributes', function (Blueprint $table) {
    $table->id();
    $table->string('name', 50);
    $table->string('slug', 60)->unique();
    $table->enum('type', ['text', 'number', 'boolean', 'select', 'multi_select', 'color']);
    $table->json('options')->nullable(); // For select types
    $table->boolean('is_filterable')->default(false);
    $table->boolean('is_required')->default(false);
    $table->unsignedSmallInteger('position')->default(0);
    $table->timestamps();
});

// 2026_01_29_100024_create_product_attribute_values_table.php
Schema::create('product_attribute_values', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->foreignId('attribute_id')->constrained('product_attributes')->cascadeOnDelete();
    $table->string('value', 255);
    $table->timestamp('created_at');

    $table->unique(['product_id', 'attribute_id']);
    $table->index(['attribute_id', 'value']);
});
```

### 4. Sellers

```php
// 2026_01_29_100030_create_sellers_table.php
Schema::create('sellers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('business_name', 150);
    $table->string('slug', 170)->unique();
    $table->string('legal_name', 150)->nullable();
    $table->string('gst_number', 20)->unique()->nullable();
    $table->string('pan_number', 15)->nullable();
    $table->text('description')->nullable();
    $table->string('logo_url', 500)->nullable();
    $table->string('banner_url', 500)->nullable();
    $table->enum('status', ['pending', 'approved', 'suspended', 'rejected'])->default('pending');
    $table->decimal('commission_rate', 5, 2)->default(10.00);
    $table->decimal('rating', 3, 2)->default(0);
    $table->unsignedInteger('total_reviews')->default(0);
    $table->unsignedInteger('total_products')->default(0);
    $table->unsignedInteger('total_orders')->default(0);
    $table->json('bank_details')->nullable();
    $table->json('documents')->nullable();
    $table->json('settings')->nullable();
    $table->timestamp('approved_at')->nullable();
    $table->timestamps();

    $table->index(['status', 'rating']);
});
```

### 5. Orders

```php
// 2026_01_29_100040_create_orders_table.php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->string('order_number', 20)->unique();
    $table->foreignId('user_id')->constrained()->restrictOnDelete();
    $table->foreignId('seller_id')->nullable()->constrained()->restrictOnDelete();
    $table->foreignId('shipping_address_id')->nullable()->constrained('user_addresses')->nullOnDelete();
    $table->foreignId('billing_address_id')->nullable()->constrained('user_addresses')->nullOnDelete();
    $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
    $table->enum('status', [
        'pending', 'confirmed', 'processing', 'shipped',
        'delivered', 'cancelled', 'returned'
    ])->default('pending');
    $table->enum('payment_status', [
        'pending', 'paid', 'failed', 'refunded', 'partial_refund'
    ])->default('pending');
    $table->decimal('subtotal', 12, 2);
    $table->decimal('discount', 12, 2)->default(0);
    $table->decimal('tax', 12, 2)->default(0);
    $table->decimal('shipping_cost', 12, 2)->default(0);
    $table->decimal('total', 12, 2);
    $table->decimal('paid_amount', 12, 2)->default(0);
    $table->string('currency', 3)->default('INR');
    $table->json('shipping_address_snapshot');
    $table->json('billing_address_snapshot');
    $table->text('notes')->nullable();
    $table->text('admin_notes')->nullable();
    $table->ipAddress('ip_address')->nullable();
    $table->text('user_agent')->nullable();
    $table->enum('source', ['web', 'mobile', 'pos', 'api'])->default('web');
    $table->json('metadata')->nullable();
    $table->timestamp('confirmed_at')->nullable();
    $table->timestamp('shipped_at')->nullable();
    $table->timestamp('delivered_at')->nullable();
    $table->timestamp('cancelled_at')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'status', 'created_at']);
    $table->index(['seller_id', 'status', 'created_at']);
    $table->index(['status', 'created_at']);
    $table->index(['payment_status', 'created_at']);
});

// 2026_01_29_100041_create_order_items_table.php
Schema::create('order_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->restrictOnDelete();
    $table->foreignId('variant_id')->nullable()->constrained('product_variants')->restrictOnDelete();
    $table->foreignId('seller_id')->constrained()->restrictOnDelete();
    $table->string('product_name', 255);
    $table->string('variant_name', 100)->nullable();
    $table->string('sku', 50);
    $table->decimal('mrp', 12, 2);
    $table->decimal('price', 12, 2);
    $table->unsignedSmallInteger('quantity');
    $table->decimal('tax', 12, 2)->default(0);
    $table->decimal('discount', 12, 2)->default(0);
    $table->decimal('total', 12, 2);
    $table->json('product_snapshot');
    $table->enum('status', [
        'pending', 'confirmed', 'shipped', 'delivered', 'cancelled', 'returned'
    ])->default('pending');
    $table->timestamps();

    $table->index(['order_id', 'status']);
    $table->index(['seller_id', 'status']);
    $table->index('product_id');
});
```

### 6. Payments

```php
// 2026_01_29_100050_create_payments_table.php
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained()->cascadeOnDelete();
    $table->string('transaction_id', 50)->unique();
    $table->string('gateway', 30); // razorpay, payu, stripe
    $table->string('gateway_transaction_id', 100)->nullable();
    $table->enum('method', ['card', 'upi', 'netbanking', 'wallet', 'cod', 'emi', 'bnpl']);
    $table->decimal('amount', 12, 2);
    $table->string('currency', 3)->default('INR');
    $table->enum('status', ['pending', 'authorized', 'captured', 'failed', 'refunded']);
    $table->json('gateway_response')->nullable();
    $table->string('failure_reason', 255)->nullable();
    $table->ipAddress('ip_address')->nullable();
    $table->timestamp('authorized_at')->nullable();
    $table->timestamp('captured_at')->nullable();
    $table->timestamps();

    $table->index(['order_id', 'status']);
    $table->index('gateway_transaction_id');
});

// 2026_01_29_100051_create_refunds_table.php
Schema::create('refunds', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained()->cascadeOnDelete();
    $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
    $table->string('refund_id', 50)->unique();
    $table->decimal('amount', 12, 2);
    $table->enum('type', ['full', 'partial']);
    $table->enum('status', ['pending', 'processing', 'completed', 'failed']);
    $table->string('reason', 255)->nullable();
    $table->string('gateway_refund_id', 100)->nullable();
    $table->json('gateway_response')->nullable();
    $table->foreignId('processed_by')->nullable()->constrained('users');
    $table->timestamp('processed_at')->nullable();
    $table->timestamps();

    $table->index(['order_id', 'status']);
});
```

### 7. Cart & Wishlist

```php
// 2026_01_29_100060_create_carts_table.php
Schema::create('carts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
    $table->string('session_id', 64)->nullable();
    $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
    $table->decimal('subtotal', 12, 2)->default(0);
    $table->decimal('discount', 12, 2)->default(0);
    $table->decimal('tax', 12, 2)->default(0);
    $table->decimal('shipping', 12, 2)->default(0);
    $table->decimal('total', 12, 2)->default(0);
    $table->json('metadata')->nullable();
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();

    $table->index('user_id');
    $table->index('session_id');
});

// 2026_01_29_100061_create_cart_items_table.php
Schema::create('cart_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
    $table->unsignedSmallInteger('quantity');
    $table->decimal('price', 12, 2);
    $table->decimal('total', 12, 2);
    $table->json('attributes')->nullable();
    $table->timestamps();

    $table->unique(['cart_id', 'product_id', 'variant_id']);
});

// 2026_01_29_100062_create_wishlists_table.php
Schema::create('wishlists', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
    $table->timestamp('created_at');

    $table->unique(['user_id', 'product_id', 'variant_id']);
});
```

### 8. Reviews

```php
// 2026_01_29_100070_create_reviews_table.php
Schema::create('reviews', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
    $table->unsignedTinyInteger('rating'); // 1-5
    $table->string('title', 100)->nullable();
    $table->text('content');
    $table->json('pros')->nullable();
    $table->json('cons')->nullable();
    $table->boolean('is_verified_purchase')->default(false);
    $table->boolean('is_approved')->default(false);
    $table->boolean('is_featured')->default(false);
    $table->unsignedInteger('helpful_count')->default(0);
    $table->unsignedInteger('unhelpful_count')->default(0);
    $table->enum('status', ['pending', 'approved', 'rejected', 'flagged'])->default('pending');
    $table->foreignId('moderated_by')->nullable()->constrained('users');
    $table->timestamp('moderated_at')->nullable();
    $table->timestamps();

    $table->index(['product_id', 'is_approved', 'rating']);
    $table->index(['user_id', 'created_at']);
    $table->unique(['product_id', 'user_id']); // One review per user per product
});

// 2026_01_29_100071_create_review_images_table.php
Schema::create('review_images', function (Blueprint $table) {
    $table->id();
    $table->foreignId('review_id')->constrained()->cascadeOnDelete();
    $table->string('url', 500);
    $table->string('alt_text', 255)->nullable();
    $table->unsignedTinyInteger('position')->default(0);
    $table->timestamp('created_at');
});
```

### 9. POS System

```php
// 2026_01_29_100080_create_stores_table.php
Schema::create('stores', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->string('code', 20)->unique();
    $table->string('address', 255);
    $table->string('city', 100);
    $table->string('state', 100);
    $table->string('postal_code', 20);
    $table->string('phone', 20)->nullable();
    $table->string('email', 255)->nullable();
    $table->boolean('is_active')->default(true);
    $table->json('settings')->nullable();
    $table->timestamps();
});

// 2026_01_29_100081_create_pos_registers_table.php
Schema::create('pos_registers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('store_id')->constrained()->cascadeOnDelete();
    $table->string('name', 50);
    $table->string('device_id', 100)->unique();
    $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
    $table->json('settings')->nullable();
    $table->timestamp('last_sync_at')->nullable();
    $table->timestamps();
});

// 2026_01_29_100082_create_pos_sales_table.php
Schema::create('pos_sales', function (Blueprint $table) {
    $table->id();
    $table->string('sale_number', 20)->unique();
    $table->foreignId('store_id')->constrained()->restrictOnDelete();
    $table->foreignId('register_id')->constrained('pos_registers')->restrictOnDelete();
    $table->foreignId('staff_id')->constrained('staff')->restrictOnDelete();
    $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
    $table->decimal('subtotal', 12, 2);
    $table->decimal('discount', 12, 2)->default(0);
    $table->decimal('tax', 12, 2)->default(0);
    $table->decimal('total', 12, 2);
    $table->decimal('paid_amount', 12, 2);
    $table->decimal('change_amount', 12, 2)->default(0);
    $table->enum('payment_method', ['cash', 'card', 'upi', 'split']);
    $table->json('payment_details')->nullable();
    $table->enum('status', ['completed', 'voided', 'refunded'])->default('completed');
    $table->json('receipt_data')->nullable();
    $table->timestamp('created_at');

    $table->index(['store_id', 'created_at']);
    $table->index(['staff_id', 'created_at']);
});

// 2026_01_29_100083_create_pos_sale_items_table.php
Schema::create('pos_sale_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('pos_sale_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->restrictOnDelete();
    $table->foreignId('variant_id')->nullable()->constrained('product_variants');
    $table->string('barcode', 50)->nullable();
    $table->string('product_name', 255);
    $table->unsignedSmallInteger('quantity');
    $table->decimal('price', 12, 2);
    $table->decimal('discount', 12, 2)->default(0);
    $table->decimal('tax', 12, 2)->default(0);
    $table->decimal('total', 12, 2);
    $table->timestamp('created_at');
});

// 2026_01_29_100084_create_credit_notes_table.php
Schema::create('credit_notes', function (Blueprint $table) {
    $table->id();
    $table->string('credit_note_number', 20)->unique();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('return_id')->nullable()->constrained('returns')->nullOnDelete();
    $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
    $table->decimal('amount', 12, 2);
    $table->decimal('used_amount', 12, 2)->default(0);
    $table->decimal('remaining_amount', 12, 2);
    $table->enum('status', ['active', 'partially_used', 'fully_used', 'expired', 'cancelled']);
    $table->timestamp('expires_at')->nullable();
    $table->string('secure_code', 32)->unique(); // For verification
    $table->timestamps();

    $table->index(['user_id', 'status']);
});
```

---

## Eloquent Model Conventions

### Base Model

```php
// app/Models/Concerns/HasUuid.php
trait HasUuid
{
    protected static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}

// app/Models/Concerns/HasSlug.php
trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            $model->slug = Str::slug($model->{$model->slugSource});
        });
    }

    protected function slugSource(): string
    {
        return 'name';
    }
}
```

### Example Model

```php
// app/Models/Product.php
class Product extends Model
{
    use HasFactory, SoftDeletes, HasUuid, HasSlug, Searchable;

    protected $fillable = [
        'seller_id', 'brand_id', 'category_id', 'name', 'short_description',
        'description', 'sku', 'barcode', 'mrp', 'price', 'cost_price',
        'stock_quantity', 'is_active', 'attributes', 'specifications',
    ];

    protected $casts = [
        'mrp' => 'decimal:2',
        'price' => 'decimal:2',
        'rating' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'attributes' => 'array',
        'specifications' => 'array',
        'seo_data' => 'array',
        'published_at' => 'datetime',
    ];

    // Relationships
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('is_approved', true);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->where('status', 'approved');
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeInCategory(Builder $query, int|array $categoryIds): Builder
    {
        return $query->whereIn('category_id', (array) $categoryIds);
    }

    public function scopePriceRange(Builder $query, ?float $min, ?float $max): Builder
    {
        return $query
            ->when($min, fn ($q) => $q->where('price', '>=', $min))
            ->when($max, fn ($q) => $q->where('price', '<=', $max));
    }

    // Accessors
    public function getDiscountPercentageAttribute(): float
    {
        if ($this->mrp <= 0) return 0;
        return round((($this->mrp - $this->price) / $this->mrp) * 100, 1);
    }

    public function getIsOnSaleAttribute(): bool
    {
        return $this->price < $this->mrp;
    }

    // Search
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'price' => $this->price,
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name,
            'brand_id' => $this->brand_id,
            'brand_name' => $this->brand?->name,
            'rating' => $this->rating,
            'review_count' => $this->review_count,
            'sales_count' => $this->sales_count,
            'in_stock' => $this->stock_quantity > 0,
            'attributes' => $this->attributes,
        ];
    }
}
```

---

## Database Seeders Structure

```php
// database/seeders/DatabaseSeeder.php
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Core data
            RolePermissionSeeder::class,
            SettingsSeeder::class,
            TaxRateSeeder::class,
            CurrencySeeder::class,
            ShippingZoneSeeder::class,

            // Categories & Brands
            CategorySeeder::class,
            BrandSeeder::class,
            ProductAttributeSeeder::class,

            // Users
            AdminSeeder::class,

            // Development only
            ...($this->isLocal() ? [
                UserSeeder::class,
                SellerSeeder::class,
                ProductSeeder::class,
                ReviewSeeder::class,
                OrderSeeder::class,
            ] : []),
        ]);
    }
}
```

