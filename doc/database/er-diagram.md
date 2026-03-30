# Entity Relationship Diagram

## [AI-REF] Database Schema Overview

This document provides the complete ER diagram specification for AI-assisted code generation.

---

## IMPORTANT: Multi-Project Base Template

> **This is a BASE TEMPLATE for multiple e-commerce projects across different verticals.**

### Supported Industry Verticals

| Vertical | Category Examples | Special Attributes |
|----------|-------------------|-------------------|
| **Beauty & Cosmetics** | Skincare, Makeup, Haircare, Fragrances | Skin type, Ingredients, Shade finder |
| **Kids Wear** | Boys, Girls, Infants, Teens, School Uniforms | Age group, Size chart, Gender |
| **Fashion** | Men, Women, Accessories, Footwear | Size, Color, Material, Fit |
| **Electronics** | Mobiles, Laptops, Accessories, Home Appliances | Warranty, Specs, Compatibility |
| **Home & Living** | Furniture, Decor, Kitchen, Bedding | Material, Dimensions, Room type |
| **Grocery** | Fresh, Packaged, Beverages, Organic | Expiry, Weight, Diet type |
| **Health & Wellness** | Supplements, Fitness, Medical devices | Dosage, Certifications |
| **Jewelry** | Gold, Silver, Diamond, Artificial | Purity, Certification, Stone type |

### Per-Project Customization

When using this base for a new project:

1. **Select Categories**: Choose relevant categories from the master list or create new ones
2. **Configure Attributes**: Enable/disable product attributes based on vertical
3. **Set Design Theme**: Apply project-specific color palette and typography
4. **Customize Components**: Modify UI components for vertical-specific needs

```php
// config/vertical.php - Configure per project
return [
    'name' => 'BeautyStore',
    'vertical' => 'beauty',

    'categories' => [
        'enabled' => ['skincare', 'makeup', 'haircare', 'fragrances', 'tools'],
    ],

    'attributes' => [
        'skin_type' => true,
        'ingredients' => true,
        'shade_finder' => true,
        'size_chart' => false,  // Not needed for beauty
    ],

    'features' => [
        'virtual_try_on' => true,
        'shade_matcher' => true,
        'skin_quiz' => true,
    ],
];
```

---

## Design System Customization

### Color Palette Configuration

Each project should define its own color palette in `tailwind.config.js`:

```javascript
// tailwind.config.js - Customize per project
module.exports = {
  theme: {
    extend: {
      colors: {
        // PRIMARY - Change per brand
        primary: {
          50: '#fef7ee',
          100: '#fdecd3',
          200: '#fad5a5',
          300: '#f6b76d',
          400: '#f19132',
          500: '#ee7a14',  // Main brand color - CHANGE THIS
          600: '#df5f0a',
          700: '#b9470b',
          800: '#933910',
          900: '#773110',
        },

        // ACCENT - Secondary brand color
        accent: {
          500: '#8b5cf6',  // CHANGE THIS per brand
        },

        // Keep neutral consistent
        neutral: { /* ... standard grays ... */ },
      },
    },
  },
}
```

### Example Brand Palettes

| Vertical | Primary Color | Accent Color | Mood |
|----------|---------------|--------------|------|
| Beauty | `#e91e63` (Pink) | `#9c27b0` (Purple) | Feminine, Luxurious |
| Kids Wear | `#ff9800` (Orange) | `#4caf50` (Green) | Playful, Bright |
| Electronics | `#2196f3` (Blue) | `#ff5722` (Orange) | Tech, Professional |
| Jewelry | `#ffd700` (Gold) | `#1a1a1a` (Black) | Premium, Elegant |
| Grocery | `#4caf50` (Green) | `#ff9800` (Orange) | Fresh, Natural |
| Fashion | `#000000` (Black) | `#ffffff` (White) | Minimal, Modern |

---

## Amazon-Inspired UI Components Library

### Complete Component List

Below is a comprehensive list of UI components inspired by Amazon's e-commerce patterns. When starting a new project from this base, these components will be customized with project-specific colors and styling.

---

### Navigation Components

| Component | Description | Amazon Reference |
|-----------|-------------|------------------|
| `NavHeader` | Main navigation header with logo, search, cart | Top header bar |
| `NavCategories` | Horizontal category navigation | "All" dropdown menu |
| `NavMegaMenu` | Full-width category mega menu | Department flyouts |
| `NavBreadcrumb` | Hierarchical page location | Breadcrumb trail |
| `NavMobile` | Mobile hamburger menu | Mobile nav drawer |
| `NavAccount` | Account dropdown menu | "Account & Lists" |
| `NavLocationSelector` | Delivery location picker | "Deliver to" selector |

### Search Components

| Component | Description | Amazon Reference |
|-----------|-------------|------------------|
| `SearchBar` | Main search input with category filter | Search bar |
| `SearchAutocomplete` | Dropdown with suggestions | Search suggestions |
| `SearchFilters` | Left sidebar filter panel | Refinements sidebar |
| `SearchFilterChips` | Active filter pills | Applied filters |
| `SearchSort` | Sort dropdown (relevance, price, rating) | Sort by dropdown |
| `SearchResultsGrid` | Product grid with view toggle | Results grid |
| `SearchPagination` | Page navigation | Pagination |
| `SearchNoResults` | Empty state with suggestions | No results page |
| `SearchRecentHistory` | Recent searches list | Search history |

### Product Components

| Component | Description | Amazon Reference |
|-----------|-------------|------------------|
| `ProductCard` | Product tile for listings | Product card |
| `ProductCardCompact` | Smaller card for widgets | Compact card |
| `ProductCardHorizontal` | Horizontal layout card | Cart/list items |
| `ProductGallery` | Image gallery with zoom | Product images |
| `ProductThumbnails` | Image thumbnail strip | Thumbnail nav |
| `ProductZoom` | Magnifier on hover | Zoom feature |
| `ProductVideo` | Embedded product video | Video player |
| `Product360View` | 360° product viewer | 360° spin |
| `ProductTitle` | Product name with link | Product title |
| `ProductPrice` | Price display with MRP/discount | Price block |
| `ProductPriceHistory` | Price drop alert | Price history |
| `ProductRating` | Star rating with count | Rating stars |
| `ProductBadge` | Labels (Bestseller, New, Sale) | Product badges |
| `ProductStock` | Stock availability status | Availability |
| `ProductDelivery` | Delivery estimate | Delivery info |
| `ProductVariants` | Size/color selector | Variant picker |
| `ProductColorSwatch` | Color option circles | Color swatches |
| `ProductSizeSelector` | Size button group | Size selector |
| `ProductQuantity` | Quantity +/- input | Qty selector |
| `ProductActions` | Add to cart/wishlist buttons | Action buttons |
| `ProductBuyBox` | Complete purchase section | Buy box |
| `ProductDescription` | Expandable description | Description |
| `ProductSpecifications` | Specs table | Tech specs |
| `ProductFeatures` | Bullet feature list | About this item |
| `ProductCompare` | Add to compare | Compare checkbox |

### Cart Components

| Component | Description | Amazon Reference |
|-----------|-------------|------------------|
| `CartMini` | Header cart dropdown | Cart popover |
| `CartIcon` | Cart icon with badge | Cart badge |
| `CartPage` | Full cart page | Cart page |
| `CartItem` | Single cart line item | Cart item |
| `CartSummary` | Subtotal box | Order summary |
| `CartCoupon` | Coupon input | Promo code |
| `CartEmpty` | Empty cart state | Empty cart |
| `CartSaveForLater` | Save for later section | Saved items |
| `CartRecommendations` | Frequently bought together | Recommendations |
| `CartShippingEstimate` | Shipping calculator | Shipping estimate |

### Checkout Components (FASTER CHECKOUT)

| Component | Description | Amazon Reference |
|-----------|-------------|------------------|
| `CheckoutProgress` | Step indicator | Checkout steps |
| `CheckoutExpressBar` | One-click checkout bar | Buy Now |
| `CheckoutAddressCard` | Saved address tile | Address card |
| `CheckoutAddressForm` | New address form | Add address |
| `CheckoutAddressList` | Address selection list | Address book |
| `CheckoutShippingOptions` | Delivery speed selector | Shipping options |
| `CheckoutPaymentMethods` | Payment method tabs | Payment section |
| `CheckoutSavedCards` | Saved card list | Saved cards |
| `CheckoutUPI` | UPI payment option | UPI input |
| `CheckoutNetBanking` | Bank selection list | Net banking |
| `CheckoutWallet` | Wallet balance/options | Wallet pay |
| `CheckoutCOD` | Cash on delivery option | COD |
| `CheckoutEMI` | EMI calculator | EMI options |
| `CheckoutBNPL` | Buy now pay later | BNPL option |
| `CheckoutOrderSummary` | Sticky order summary | Order summary |
| `CheckoutOrderItems` | Collapsible item list | Item review |
| `CheckoutGiftOptions` | Gift wrap/message | Gift options |
| `CheckoutPlaceOrder` | Final submit button | Place order |
| `CheckoutProcessing` | Payment loading state | Processing |
| `CheckoutSuccess` | Order confirmation | Confirmation |
| `CheckoutFailure` | Payment failed state | Error page |

### Review Components

| Component | Description | Amazon Reference |
|-----------|-------------|------------------|
| `ReviewSummary` | Rating distribution chart | Rating breakdown |
| `ReviewStars` | Interactive star rating | Star input |
| `ReviewCard` | Single review display | Review card |
| `ReviewVerifiedBadge` | Verified purchase badge | Verified badge |
| `ReviewImages` | Review photo gallery | Customer images |
| `ReviewHelpful` | Helpful voting buttons | Was this helpful? |
| `ReviewFilters` | Filter by rating/verified | Review filters |
| `ReviewSort` | Sort reviews dropdown | Sort reviews |
| `ReviewForm` | Write review form | Write review |
| `ReviewImageUpload` | Photo upload in review | Add photos |
| `QASection` | Questions & answers | Q&A section |
| `QAQuestion` | Single question display | Question card |
| `QAAnswer` | Answer with voting | Answer card |
| `QAForm` | Ask a question form | Ask question |

### User Account Components

| Component | Description | Amazon Reference |
|-----------|-------------|------------------|
| `AccountDashboard` | Account overview page | Your Account |
| `AccountOrders` | Order history list | Your Orders |
| `AccountOrderCard` | Single order summary | Order card |
| `AccountOrderDetail` | Full order details | Order detail |
| `AccountOrderTrack` | Shipment tracking | Track package |
| `AccountAddressBook` | Address management | Addresses |
| `AccountPaymentMethods` | Saved payment methods | Payment options |
| `AccountWishlist` | Wishlist page | Wish List |
| `AccountReviews` | User's reviews | Your Reviews |
| `AccountNotifications` | Notification settings | Notifications |
| `AccountSecurity` | Password/2FA settings | Security |
| `AccountPrivacy` | Privacy controls | Privacy |

### Homepage Components

| Component | Description | Amazon Reference |
|-----------|-------------|------------------|
| `HeroBanner` | Full-width carousel | Hero carousel |
| `HeroSlide` | Single banner slide | Banner slide |
| `CategoryGrid` | Category icon grid | Shop by category |
| `ProductCarousel` | Horizontal product scroll | Deal carousel |
| `DealOfTheDay` | Featured deal with timer | Deal of the day |
| `DealsGrid` | Multi-deal grid | Today's deals |
| `CountdownTimer` | Sale countdown | Timer |
| `RecentlyViewed` | Recently viewed products | Browsing history |
| `Recommendations` | Personalized products | For you |
| `TrendingNow` | Trending products | Trending |
| `BestSellers` | Best sellers section | Best Sellers |
| `NewArrivals` | New products section | New releases |
| `BrandShowcase` | Featured brands | Top brands |
| `PromoBanner` | Promotional banner strip | Promo strip |

### Seller Components

| Component | Description | Amazon Reference |
|-----------|-------------|------------------|
| `SellerDashboard` | Seller overview | Seller Central |
| `SellerStats` | Sales/orders stats | Performance |
| `SellerProductList` | Product management | Inventory |
| `SellerProductForm` | Add/edit product | List product |
| `SellerOrderList` | Order management | Manage orders |
| `SellerOrderDetail` | Single order view | Order detail |
| `SellerInventory` | Stock management | Inventory |
| `SellerPayouts` | Earnings/payouts | Payments |
| `SellerReviews` | Review management | Feedback |
| `SellerAnalytics` | Sales analytics | Reports |

### Common UI Components

| Component | Description | Amazon Reference |
|-----------|-------------|------------------|
| `Button` | Primary/secondary buttons | Buttons |
| `ButtonIcon` | Icon-only button | Icon button |
| `Input` | Text input field | Input |
| `InputSearch` | Search input | Search input |
| `InputPassword` | Password with toggle | Password |
| `InputPhone` | Phone with country code | Phone input |
| `InputOTP` | OTP input boxes | OTP input |
| `Select` | Dropdown select | Select |
| `Checkbox` | Checkbox input | Checkbox |
| `Radio` | Radio button group | Radio |
| `Toggle` | On/off switch | Toggle |
| `Textarea` | Multi-line input | Textarea |
| `Modal` | Dialog/popup | Modal |
| `Drawer` | Side panel | Drawer |
| `Toast` | Notification toast | Toast |
| `Alert` | Inline alert message | Alert |
| `Badge` | Count/status badge | Badge |
| `Tag` | Removable tag | Tag |
| `Avatar` | User avatar | Avatar |
| `Tooltip` | Hover tooltip | Tooltip |
| `Popover` | Click popover | Popover |
| `Dropdown` | Dropdown menu | Dropdown |
| `Tabs` | Tab navigation | Tabs |
| `Accordion` | Expandable sections | Accordion |
| `Skeleton` | Loading placeholder | Skeleton |
| `Spinner` | Loading spinner | Spinner |
| `ProgressBar` | Progress indicator | Progress |
| `Pagination` | Page navigation | Pagination |
| `EmptyState` | No data state | Empty state |
| `ErrorState` | Error display | Error state |
| `ConfirmDialog` | Confirmation modal | Confirm |

---

## FASTER CHECKOUT Features

### One-Click Buy Now

```php
// Faster checkout for returning users with saved payment
class ExpressCheckout
{
    // Skip cart, direct to payment
    public function buyNow(Product $product, User $user): Order
    {
        // 1. Use default shipping address
        $address = $user->defaultAddress();

        // 2. Use default payment method
        $paymentMethod = $user->defaultPaymentMethod();

        // 3. Create order immediately
        $order = Order::create([
            'user_id' => $user->id,
            'shipping_address_id' => $address->id,
            // ... pre-filled from defaults
        ]);

        // 4. Process saved payment
        $this->payment->charge($paymentMethod, $order->total);

        return $order;
    }
}
```

### Saved Checkout Preferences

```php
// user_checkout_preferences table
Schema::create('user_checkout_preferences', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('default_shipping_address_id')->nullable();
    $table->foreignId('default_billing_address_id')->nullable();
    $table->string('default_payment_method')->nullable();  // card_xxx, upi_xxx
    $table->string('default_shipping_speed')->default('standard');
    $table->boolean('same_as_shipping')->default(true);
    $table->boolean('save_card_for_future')->default(true);
    $table->boolean('enable_one_click')->default(false);
    $table->timestamps();
});
```

### Checkout Speed Optimizations

| Feature | Implementation | Benefit |
|---------|----------------|---------|
| **Address Autocomplete** | Google Places API | Faster address entry |
| **Saved Cards** | Tokenized via Razorpay | No re-entry needed |
| **Default Selections** | Pre-select last used options | Fewer clicks |
| **Inline Validation** | Real-time form validation | No page reloads |
| **Order Preview** | Show summary without page change | Quick review |
| **Express Pay Buttons** | Apple Pay, Google Pay, UPI | 1-tap checkout |
| **Guest Checkout** | Optional account creation | Lower friction |
| **Cart to Checkout Merge** | Single page checkout | Fewer steps |
| **Sticky Order Summary** | Always visible totals | No scrolling |
| **Progress Persistence** | Save checkout state | Resume later |

### Express Payment Options

```html
<!-- Express checkout buttons - show for returning users -->
<div class="express-checkout-bar bg-neutral-50 p-4 rounded-lg mb-6">
    <p class="text-sm text-neutral-600 mb-3">Express Checkout</p>
    <div class="flex gap-3">
        <!-- One-Click Buy (saved card) -->
        <button class="flex-1 py-3 bg-primary-500 text-white rounded-md font-medium">
            Buy Now with Saved Card
        </button>

        <!-- UPI Express -->
        <button class="flex-1 py-3 bg-white border border-neutral-200 rounded-md">
            <img src="/images/upi.svg" class="h-6 mx-auto">
        </button>

        <!-- Google Pay -->
        <button class="flex-1 py-3 bg-white border border-neutral-200 rounded-md">
            <img src="/images/gpay.svg" class="h-6 mx-auto">
        </button>
    </div>
</div>
```

### Checkout Time Targets

| User Type | Target Time | Steps |
|-----------|-------------|-------|
| Returning (saved card) | < 10 seconds | Select → Confirm → Done |
| Returning (new payment) | < 30 seconds | Select → Pay → Confirm |
| New User (with account) | < 60 seconds | Address → Pay → Confirm |
| Guest Checkout | < 90 seconds | Info → Address → Pay → Confirm |

---

## ER Diagram (Mermaid)

```mermaid
erDiagram
    %% ==================== USER MANAGEMENT ====================

    users {
        bigint id PK
        string uuid UK
        string first_name
        string last_name
        string email UK
        string phone UK
        string password
        enum role "customer,seller,staff,admin"
        boolean is_verified
        boolean is_active
        string avatar_url
        timestamp email_verified_at
        timestamp phone_verified_at
        timestamp last_login_at
        string last_login_ip
        json preferences
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    user_addresses {
        bigint id PK
        bigint user_id FK
        string label
        string first_name
        string last_name
        string phone
        string address_line_1
        string address_line_2
        string city
        string state
        string postal_code
        string country
        decimal latitude
        decimal longitude
        boolean is_default
        enum type "shipping,billing,both"
        timestamp created_at
        timestamp updated_at
    }

    user_sessions {
        bigint id PK
        bigint user_id FK
        string token UK
        string ip_address
        string user_agent
        json device_info
        timestamp last_activity_at
        timestamp expires_at
        timestamp created_at
    }

    password_resets {
        bigint id PK
        string email
        string token
        timestamp expires_at
        timestamp created_at
    }

    user_social_accounts {
        bigint id PK
        bigint user_id FK
        string provider
        string provider_id
        string access_token
        string refresh_token
        timestamp expires_at
        timestamp created_at
        timestamp updated_at
    }

    %% ==================== ADMIN/STAFF MANAGEMENT ====================

    admins {
        bigint id PK
        bigint user_id FK
        enum role "super_admin,admin,moderator"
        json permissions
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    staff {
        bigint id PK
        bigint user_id FK
        string employee_id UK
        enum role "manager,cashier,support,warehouse"
        bigint store_id FK
        json permissions
        boolean is_active
        decimal commission_rate
        timestamp joined_at
        timestamp created_at
        timestamp updated_at
    }

    staff_shifts {
        bigint id PK
        bigint staff_id FK
        bigint store_id FK
        timestamp shift_start
        timestamp shift_end
        decimal opening_cash
        decimal closing_cash
        json register_summary
        enum status "open,closed"
        timestamp created_at
        timestamp updated_at
    }

    %% ==================== SELLER MANAGEMENT ====================

    sellers {
        bigint id PK
        bigint user_id FK
        string business_name
        string slug UK
        string legal_name
        string gst_number UK
        string pan_number
        text description
        string logo_url
        string banner_url
        enum status "pending,approved,suspended,rejected"
        decimal commission_rate
        decimal rating
        int total_reviews
        int total_products
        int total_orders
        json bank_details
        json documents
        json settings
        timestamp approved_at
        timestamp created_at
        timestamp updated_at
    }

    seller_documents {
        bigint id PK
        bigint seller_id FK
        enum type "gst_certificate,pan_card,bank_statement,address_proof,other"
        string file_url
        enum status "pending,approved,rejected"
        string rejection_reason
        timestamp verified_at
        timestamp created_at
    }

    seller_payouts {
        bigint id PK
        bigint seller_id FK
        decimal amount
        enum status "pending,processing,completed,failed"
        string transaction_id
        json bank_details
        string failure_reason
        timestamp processed_at
        timestamp created_at
    }

    %% ==================== WHOLESALER ====================

    wholesalers {
        bigint id PK
        bigint user_id FK
        string company_name
        string gst_number UK
        enum gst_status "pending,verified,rejected"
        string pan_number
        enum tier "bronze,silver,gold,platinum"
        decimal discount_percentage
        decimal credit_limit
        decimal available_credit
        bigint account_manager_id FK
        json documents
        enum status "pending,approved,suspended"
        timestamp approved_at
        timestamp created_at
        timestamp updated_at
    }

    wholesaler_prices {
        bigint id PK
        bigint product_id FK
        bigint wholesaler_tier_id FK
        decimal price
        int min_quantity
        timestamp created_at
        timestamp updated_at
    }

    %% ==================== CATEGORY & BRAND ====================

    categories {
        bigint id PK
        bigint parent_id FK
        string name
        string slug UK
        text description
        string image_url
        string icon
        int position
        int level
        string path
        boolean is_active
        boolean is_featured
        json seo_data
        json attributes_schema
        timestamp created_at
        timestamp updated_at
    }

    brands {
        bigint id PK
        string name
        string slug UK
        text description
        string logo_url
        string website_url
        boolean is_active
        boolean is_featured
        int position
        json seo_data
        timestamp created_at
        timestamp updated_at
    }

    %% ==================== PRODUCT MANAGEMENT ====================

    products {
        bigint id PK
        string uuid UK
        bigint seller_id FK
        bigint brand_id FK
        bigint category_id FK
        string name
        string slug UK
        text short_description
        text description
        string sku UK
        string barcode UK
        decimal mrp
        decimal price
        decimal cost_price
        int stock_quantity
        int low_stock_threshold
        enum stock_status "in_stock,out_of_stock,backorder"
        decimal weight
        decimal length
        decimal width
        decimal height
        enum weight_unit "g,kg,lb,oz"
        enum dimension_unit "cm,m,in,ft"
        boolean is_active
        boolean is_featured
        boolean is_taxable
        decimal tax_rate
        string hsn_code
        decimal rating
        int review_count
        int view_count
        int sales_count
        int wishlist_count
        json seo_data
        json attributes
        json specifications
        enum status "draft,pending,approved,rejected"
        string rejection_reason
        timestamp published_at
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at
    }

    product_images {
        bigint id PK
        bigint product_id FK
        bigint variant_id FK
        string url
        string alt_text
        int position
        boolean is_primary
        timestamp created_at
    }

    product_variants {
        bigint id PK
        bigint product_id FK
        string name
        string sku UK
        string barcode UK
        decimal mrp
        decimal price
        int stock_quantity
        json attributes
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    product_attributes {
        bigint id PK
        string name
        string slug UK
        enum type "text,number,boolean,select,multi_select,color"
        json options
        boolean is_filterable
        boolean is_required
        int position
        timestamp created_at
        timestamp updated_at
    }

    product_attribute_values {
        bigint id PK
        bigint product_id FK
        bigint attribute_id FK
        string value
        timestamp created_at
    }

    product_tags {
        bigint id PK
        string name
        string slug UK
        timestamp created_at
    }

    product_tag_pivot {
        bigint product_id FK
        bigint tag_id FK
    }

    related_products {
        bigint id PK
        bigint product_id FK
        bigint related_product_id FK
        enum type "similar,frequently_bought,upsell,cross_sell"
        int position
    }

    %% ==================== INVENTORY ====================

    inventory_locations {
        bigint id PK
        string name
        string code UK
        enum type "warehouse,store,dropship"
        string address
        string city
        string state
        string postal_code
        boolean is_active
        boolean is_default
        timestamp created_at
        timestamp updated_at
    }

    inventory_stocks {
        bigint id PK
        bigint product_id FK
        bigint variant_id FK
        bigint location_id FK
        int quantity
        int reserved_quantity
        int available_quantity
        timestamp last_updated_at
        timestamp created_at
        timestamp updated_at
    }

    inventory_movements {
        bigint id PK
        bigint product_id FK
        bigint variant_id FK
        bigint location_id FK
        bigint reference_id
        enum reference_type "order,return,adjustment,transfer,pos_sale"
        enum type "in,out,adjustment"
        int quantity
        int quantity_before
        int quantity_after
        string reason
        bigint created_by FK
        timestamp created_at
    }

    %% ==================== CART & WISHLIST ====================

    carts {
        bigint id PK
        bigint user_id FK
        string session_id
        bigint coupon_id FK
        decimal subtotal
        decimal discount
        decimal tax
        decimal shipping
        decimal total
        json metadata
        timestamp expires_at
        timestamp created_at
        timestamp updated_at
    }

    cart_items {
        bigint id PK
        bigint cart_id FK
        bigint product_id FK
        bigint variant_id FK
        int quantity
        decimal price
        decimal total
        json attributes
        timestamp created_at
        timestamp updated_at
    }

    wishlists {
        bigint id PK
        bigint user_id FK
        bigint product_id FK
        bigint variant_id FK
        timestamp created_at
    }

    %% ==================== ORDER MANAGEMENT ====================

    orders {
        bigint id PK
        string order_number UK
        bigint user_id FK
        bigint seller_id FK
        bigint shipping_address_id FK
        bigint billing_address_id FK
        bigint coupon_id FK
        enum status "pending,confirmed,processing,shipped,delivered,cancelled,returned"
        enum payment_status "pending,paid,failed,refunded,partial_refund"
        decimal subtotal
        decimal discount
        decimal tax
        decimal shipping_cost
        decimal total
        decimal paid_amount
        string currency
        json shipping_address_snapshot
        json billing_address_snapshot
        text notes
        text admin_notes
        string ip_address
        string user_agent
        enum source "web,mobile,pos,api"
        json metadata
        timestamp confirmed_at
        timestamp shipped_at
        timestamp delivered_at
        timestamp cancelled_at
        timestamp created_at
        timestamp updated_at
    }

    order_items {
        bigint id PK
        bigint order_id FK
        bigint product_id FK
        bigint variant_id FK
        bigint seller_id FK
        string product_name
        string variant_name
        string sku
        decimal mrp
        decimal price
        int quantity
        decimal tax
        decimal discount
        decimal total
        json product_snapshot
        enum status "pending,confirmed,shipped,delivered,cancelled,returned"
        timestamp created_at
        timestamp updated_at
    }

    order_status_history {
        bigint id PK
        bigint order_id FK
        enum status "pending,confirmed,processing,shipped,out_for_delivery,delivered,cancelled,returned"
        string comment
        bigint created_by FK
        timestamp created_at
    }

    order_shipments {
        bigint id PK
        bigint order_id FK
        string tracking_number
        string carrier
        string carrier_code
        string label_url
        decimal weight
        json dimensions
        enum status "created,picked_up,in_transit,out_for_delivery,delivered,failed"
        json tracking_history
        timestamp shipped_at
        timestamp delivered_at
        timestamp created_at
        timestamp updated_at
    }

    %% ==================== PAYMENTS ====================

    payments {
        bigint id PK
        bigint order_id FK
        string transaction_id UK
        string gateway
        string gateway_transaction_id
        enum method "card,upi,netbanking,wallet,cod,emi,bnpl"
        decimal amount
        string currency
        enum status "pending,authorized,captured,failed,refunded"
        json gateway_response
        string failure_reason
        string ip_address
        timestamp authorized_at
        timestamp captured_at
        timestamp created_at
        timestamp updated_at
    }

    refunds {
        bigint id PK
        bigint order_id FK
        bigint payment_id FK
        string refund_id UK
        decimal amount
        enum type "full,partial"
        enum status "pending,processing,completed,failed"
        string reason
        string gateway_refund_id
        json gateway_response
        bigint processed_by FK
        timestamp processed_at
        timestamp created_at
        timestamp updated_at
    }

    %% ==================== RETURNS & EXCHANGES ====================

    returns {
        bigint id PK
        string return_number UK
        bigint order_id FK
        bigint user_id FK
        enum type "return,exchange"
        enum status "requested,approved,rejected,pickup_scheduled,picked_up,received,processed,completed"
        string reason
        text description
        json images
        decimal refund_amount
        enum refund_method "original,wallet,bank"
        bigint exchange_order_id FK
        bigint processed_by FK
        timestamp approved_at
        timestamp completed_at
        timestamp created_at
        timestamp updated_at
    }

    return_items {
        bigint id PK
        bigint return_id FK
        bigint order_item_id FK
        int quantity
        string reason
        enum condition "unopened,opened,damaged"
        enum status "pending,approved,rejected,received"
        timestamp created_at
    }

    credit_notes {
        bigint id PK
        string credit_note_number UK
        bigint user_id FK
        bigint return_id FK
        bigint order_id FK
        decimal amount
        decimal used_amount
        decimal remaining_amount
        enum status "active,partially_used,fully_used,expired,cancelled"
        timestamp expires_at
        string secure_code
        timestamp created_at
        timestamp updated_at
    }

    credit_note_usage {
        bigint id PK
        bigint credit_note_id FK
        bigint order_id FK
        decimal amount
        timestamp created_at
    }

    %% ==================== COUPONS & DISCOUNTS ====================

    coupons {
        bigint id PK
        string code UK
        string name
        text description
        enum type "percentage,fixed,free_shipping,buy_x_get_y"
        decimal value
        decimal max_discount
        decimal min_order_amount
        int usage_limit
        int usage_per_user
        int times_used
        boolean is_active
        timestamp starts_at
        timestamp expires_at
        json conditions
        json applicable_products
        json applicable_categories
        json applicable_users
        timestamp created_at
        timestamp updated_at
    }

    coupon_usage {
        bigint id PK
        bigint coupon_id FK
        bigint user_id FK
        bigint order_id FK
        decimal discount_amount
        timestamp created_at
    }

    price_rules {
        bigint id PK
        string name
        enum type "sale,clearance,bulk,tier,time_based"
        decimal discount_percentage
        decimal discount_amount
        int min_quantity
        int max_quantity
        json applicable_products
        json applicable_categories
        boolean is_active
        int priority
        timestamp starts_at
        timestamp expires_at
        timestamp created_at
        timestamp updated_at
    }

    flash_sales {
        bigint id PK
        string name
        string slug UK
        text description
        string banner_url
        timestamp starts_at
        timestamp ends_at
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    flash_sale_products {
        bigint id PK
        bigint flash_sale_id FK
        bigint product_id FK
        decimal sale_price
        int stock_limit
        int sold_count
        timestamp created_at
    }

    %% ==================== REVIEWS & RATINGS ====================

    reviews {
        bigint id PK
        bigint product_id FK
        bigint user_id FK
        bigint order_item_id FK
        int rating
        string title
        text content
        json pros
        json cons
        boolean is_verified_purchase
        boolean is_approved
        boolean is_featured
        int helpful_count
        int unhelpful_count
        enum status "pending,approved,rejected,flagged"
        bigint moderated_by FK
        timestamp moderated_at
        timestamp created_at
        timestamp updated_at
    }

    review_images {
        bigint id PK
        bigint review_id FK
        string url
        string alt_text
        int position
        timestamp created_at
    }

    review_votes {
        bigint id PK
        bigint review_id FK
        bigint user_id FK
        enum vote "helpful,unhelpful"
        timestamp created_at
    }

    review_responses {
        bigint id PK
        bigint review_id FK
        bigint seller_id FK
        text content
        timestamp created_at
        timestamp updated_at
    }

    product_questions {
        bigint id PK
        bigint product_id FK
        bigint user_id FK
        text question
        boolean is_answered
        boolean is_approved
        int vote_count
        timestamp created_at
        timestamp updated_at
    }

    product_answers {
        bigint id PK
        bigint question_id FK
        bigint user_id FK
        bigint seller_id FK
        text answer
        boolean is_approved
        boolean is_seller_response
        int vote_count
        timestamp created_at
        timestamp updated_at
    }

    %% ==================== POS SYSTEM ====================

    stores {
        bigint id PK
        string name
        string code UK
        string address
        string city
        string state
        string postal_code
        string phone
        string email
        boolean is_active
        json settings
        timestamp created_at
        timestamp updated_at
    }

    pos_registers {
        bigint id PK
        bigint store_id FK
        string name
        string device_id UK
        enum status "active,inactive,maintenance"
        json settings
        timestamp last_sync_at
        timestamp created_at
        timestamp updated_at
    }

    pos_sales {
        bigint id PK
        string sale_number UK
        bigint store_id FK
        bigint register_id FK
        bigint staff_id FK
        bigint customer_id FK
        decimal subtotal
        decimal discount
        decimal tax
        decimal total
        decimal paid_amount
        decimal change_amount
        enum payment_method "cash,card,upi,split"
        json payment_details
        enum status "completed,voided,refunded"
        json receipt_data
        timestamp created_at
    }

    pos_sale_items {
        bigint id PK
        bigint pos_sale_id FK
        bigint product_id FK
        bigint variant_id FK
        string barcode
        string product_name
        int quantity
        decimal price
        decimal discount
        decimal tax
        decimal total
        timestamp created_at
    }

    pos_returns {
        bigint id PK
        string return_number UK
        bigint pos_sale_id FK
        bigint store_id FK
        bigint staff_id FK
        bigint customer_id FK
        decimal amount
        enum refund_method "cash,original_payment,credit_note"
        bigint credit_note_id FK
        string reason
        enum status "completed,pending"
        timestamp created_at
    }

    barcodes {
        bigint id PK
        bigint product_id FK
        bigint variant_id FK
        string barcode UK
        enum type "ean13,ean8,upc,code128,qr"
        string image_url
        timestamp created_at
    }

    %% ==================== MESSAGING ====================

    conversations {
        bigint id PK
        bigint user_id FK
        enum channel "whatsapp,facebook,instagram,email,chat"
        string channel_user_id
        string channel_conversation_id
        enum status "open,closed,pending"
        bigint assigned_to FK
        json metadata
        timestamp last_message_at
        timestamp created_at
        timestamp updated_at
    }

    messages {
        bigint id PK
        bigint conversation_id FK
        bigint user_id FK
        enum sender_type "customer,staff,bot"
        bigint sender_id FK
        text content
        enum type "text,image,product_link,order_update"
        json attachments
        json metadata
        boolean is_read
        timestamp read_at
        timestamp created_at
    }

    message_templates {
        bigint id PK
        string name
        string slug UK
        enum channel "whatsapp,facebook,instagram,email,sms"
        string subject
        text content
        json variables
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    %% ==================== NOTIFICATIONS ====================

    notifications {
        bigint id PK
        string uuid UK
        bigint user_id FK
        string type
        string title
        text content
        json data
        enum channel "database,email,sms,push,whatsapp"
        boolean is_read
        timestamp read_at
        timestamp sent_at
        timestamp created_at
    }

    push_subscriptions {
        bigint id PK
        bigint user_id FK
        string endpoint
        string p256dh_key
        string auth_token
        string device_type
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    %% ==================== SEO & CONTENT ====================

    pages {
        bigint id PK
        string title
        string slug UK
        text content
        json seo_data
        boolean is_published
        timestamp published_at
        timestamp created_at
        timestamp updated_at
    }

    banners {
        bigint id PK
        string name
        string position
        string image_url
        string mobile_image_url
        string link
        int priority
        boolean is_active
        timestamp starts_at
        timestamp ends_at
        timestamp created_at
        timestamp updated_at
    }

    seo_metadata {
        bigint id PK
        string model_type
        bigint model_id
        string title
        string description
        string keywords
        string canonical_url
        string og_title
        string og_description
        string og_image
        json schema_markup
        timestamp created_at
        timestamp updated_at
    }

    redirects {
        bigint id PK
        string from_url UK
        string to_url
        int status_code
        int hit_count
        timestamp last_hit_at
        timestamp created_at
    }

    %% ==================== ANALYTICS & TRACKING ====================

    user_activities {
        bigint id PK
        bigint user_id FK
        string session_id
        enum type "view,search,add_to_cart,purchase,wishlist"
        string model_type
        bigint model_id
        json data
        string ip_address
        string user_agent
        string referrer
        timestamp created_at
    }

    search_logs {
        bigint id PK
        bigint user_id FK
        string session_id
        string query
        int results_count
        json filters
        bigint clicked_product_id FK
        int clicked_position
        timestamp created_at
    }

    product_views {
        bigint id PK
        bigint product_id FK
        bigint user_id FK
        string session_id
        string referrer
        timestamp created_at
    }

    fraud_logs {
        bigint id PK
        bigint user_id FK
        bigint order_id FK
        enum type "multiple_accounts,suspicious_payment,unusual_activity,chargeback"
        decimal risk_score
        json indicators
        enum action "flagged,blocked,allowed"
        bigint reviewed_by FK
        text notes
        timestamp created_at
    }

    %% ==================== SETTINGS & CONFIG ====================

    settings {
        bigint id PK
        string group
        string key UK
        text value
        enum type "string,integer,boolean,json,array"
        boolean is_public
        timestamp created_at
        timestamp updated_at
    }

    tax_rates {
        bigint id PK
        string name
        string state
        decimal cgst_rate
        decimal sgst_rate
        decimal igst_rate
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    currencies {
        bigint id PK
        string code UK
        string name
        string symbol
        decimal exchange_rate
        boolean is_default
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    shipping_zones {
        bigint id PK
        string name
        json regions
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    shipping_rates {
        bigint id PK
        bigint zone_id FK
        string name
        enum type "flat,weight,price,free"
        decimal rate
        decimal min_order
        decimal min_weight
        decimal max_weight
        int estimated_days_min
        int estimated_days_max
        boolean is_active
        timestamp created_at
        timestamp updated_at
    }

    %% ==================== RELATIONSHIPS ====================

    users ||--o{ user_addresses : has
    users ||--o{ user_sessions : has
    users ||--o{ user_social_accounts : has
    users ||--o| admins : can_be
    users ||--o| staff : can_be
    users ||--o| sellers : can_be
    users ||--o| wholesalers : can_be
    users ||--o{ orders : places
    users ||--o{ reviews : writes
    users ||--o{ wishlists : has
    users ||--o{ carts : has
    users ||--o{ conversations : has
    users ||--o{ notifications : receives

    sellers ||--o{ products : sells
    sellers ||--o{ orders : receives
    sellers ||--o{ seller_documents : has
    sellers ||--o{ seller_payouts : receives

    staff ||--o{ staff_shifts : has
    staff }o--|| stores : works_at

    categories ||--o{ categories : has_children
    categories ||--o{ products : contains

    brands ||--o{ products : has

    products ||--o{ product_images : has
    products ||--o{ product_variants : has
    products ||--o{ product_attribute_values : has
    products ||--o{ inventory_stocks : has
    products ||--o{ reviews : has
    products ||--o{ order_items : has
    products ||--o{ cart_items : has
    products ||--o{ wishlists : in

    product_variants ||--o{ product_images : has
    product_variants ||--o{ inventory_stocks : has

    carts ||--o{ cart_items : contains
    carts }o--|| coupons : uses

    orders ||--o{ order_items : contains
    orders ||--o{ payments : has
    orders ||--o{ order_shipments : has
    orders ||--o{ order_status_history : has
    orders ||--o{ returns : has
    orders }o--|| coupons : uses

    returns ||--o{ return_items : contains
    returns ||--o| credit_notes : generates

    credit_notes ||--o{ credit_note_usage : has

    stores ||--o{ pos_registers : has
    stores ||--o{ staff : employs
    stores ||--o{ pos_sales : has

    pos_sales ||--o{ pos_sale_items : contains
    pos_sales ||--o{ pos_returns : has

    conversations ||--o{ messages : contains

    reviews ||--o{ review_images : has
    reviews ||--o{ review_votes : has
    reviews ||--o{ review_responses : has

    product_questions ||--o{ product_answers : has

    flash_sales ||--o{ flash_sale_products : contains

    coupons ||--o{ coupon_usage : tracks

    shipping_zones ||--o{ shipping_rates : has
```

---

## Table Specifications

### Core Tables Count

| Domain | Tables | Description |
|--------|--------|-------------|
| User Management | 6 | Users, addresses, sessions, social |
| Admin/Staff | 3 | Admins, staff, shifts |
| Seller | 4 | Sellers, documents, payouts, wholesalers |
| Products | 10 | Products, variants, attributes, images |
| Inventory | 3 | Locations, stocks, movements |
| Cart/Wishlist | 3 | Carts, cart items, wishlists |
| Orders | 5 | Orders, items, shipments, history |
| Payments | 2 | Payments, refunds |
| Returns | 3 | Returns, items, credit notes |
| Coupons | 4 | Coupons, rules, flash sales |
| Reviews | 6 | Reviews, images, votes, Q&A |
| POS | 6 | Stores, registers, sales, returns |
| Messaging | 3 | Conversations, messages, templates |
| Notifications | 2 | Notifications, push subscriptions |
| SEO/Content | 4 | Pages, banners, metadata, redirects |
| Analytics | 4 | Activities, search logs, views, fraud |
| Settings | 4 | Settings, tax, currencies, shipping |
| **Total** | **72** | |

---

## Indexing Strategy

### Primary Indexes
Every table has a primary key on `id` column.

### Unique Indexes
```sql
-- Users
CREATE UNIQUE INDEX idx_users_uuid ON users(uuid);
CREATE UNIQUE INDEX idx_users_email ON users(email);
CREATE UNIQUE INDEX idx_users_phone ON users(phone);

-- Products
CREATE UNIQUE INDEX idx_products_uuid ON products(uuid);
CREATE UNIQUE INDEX idx_products_slug ON products(slug);
CREATE UNIQUE INDEX idx_products_sku ON products(sku);
CREATE UNIQUE INDEX idx_products_barcode ON products(barcode);

-- Orders
CREATE UNIQUE INDEX idx_orders_order_number ON orders(order_number);

-- Categories
CREATE UNIQUE INDEX idx_categories_slug ON categories(slug);
```

### Composite Indexes
```sql
-- Product Search
CREATE INDEX idx_products_search ON products(is_active, status, category_id, brand_id);
CREATE INDEX idx_products_price ON products(is_active, price);
CREATE INDEX idx_products_rating ON products(is_active, rating DESC);
CREATE INDEX idx_products_sales ON products(is_active, sales_count DESC);

-- Order Lookup
CREATE INDEX idx_orders_user_status ON orders(user_id, status, created_at DESC);
CREATE INDEX idx_orders_seller ON orders(seller_id, status, created_at DESC);

-- Inventory
CREATE INDEX idx_inventory_product_location ON inventory_stocks(product_id, location_id);
CREATE INDEX idx_inventory_available ON inventory_stocks(location_id, available_quantity);

-- Cart
CREATE INDEX idx_carts_user ON carts(user_id, updated_at DESC);
CREATE INDEX idx_carts_session ON carts(session_id);

-- Reviews
CREATE INDEX idx_reviews_product ON reviews(product_id, is_approved, rating DESC);

-- Analytics
CREATE INDEX idx_activities_user ON user_activities(user_id, created_at DESC);
CREATE INDEX idx_search_logs_query ON search_logs(query, created_at DESC);
```

### Full-Text Indexes
```sql
-- Product Search (MySQL)
CREATE FULLTEXT INDEX idx_products_fulltext ON products(name, description);

-- Review Search
CREATE FULLTEXT INDEX idx_reviews_fulltext ON reviews(title, content);
```

---

## Partitioning Strategy

### Orders Table (by month)
```sql
ALTER TABLE orders PARTITION BY RANGE (YEAR(created_at) * 100 + MONTH(created_at)) (
    PARTITION p202601 VALUES LESS THAN (202602),
    PARTITION p202602 VALUES LESS THAN (202603),
    -- Continue for each month
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

### User Activities (by month)
```sql
ALTER TABLE user_activities PARTITION BY RANGE (UNIX_TIMESTAMP(created_at)) (
    PARTITION p_old VALUES LESS THAN (UNIX_TIMESTAMP('2026-01-01')),
    PARTITION p202601 VALUES LESS THAN (UNIX_TIMESTAMP('2026-02-01')),
    -- Continue
);
```

### Search Logs (by week)
Partition and archive old search logs weekly.

---

## Data Retention Policy

| Table | Retention | Archive Strategy |
|-------|-----------|------------------|
| orders | Forever | Move to cold storage after 2 years |
| user_activities | 90 days | Delete after retention period |
| search_logs | 30 days | Aggregate and delete |
| user_sessions | 30 days | Auto-expire |
| inventory_movements | 1 year | Archive annually |
| fraud_logs | 2 years | Compliance requirement |

---

## Sample Category Structures by Vertical

### Beauty & Cosmetics

```
Beauty & Cosmetics
├── Skincare
│   ├── Cleansers
│   ├── Moisturizers
│   ├── Serums
│   ├── Sunscreen
│   ├── Face Masks
│   └── Eye Care
├── Makeup
│   ├── Face (Foundation, Concealer, Powder)
│   ├── Eyes (Eyeshadow, Mascara, Eyeliner)
│   ├── Lips (Lipstick, Lip Gloss, Lip Liner)
│   ├── Nails (Nail Polish, Nail Art)
│   └── Makeup Tools
├── Haircare
│   ├── Shampoo
│   ├── Conditioner
│   ├── Hair Treatments
│   ├── Styling Products
│   └── Hair Tools
├── Fragrances
│   ├── Women's Perfume
│   ├── Men's Cologne
│   ├── Body Mists
│   └── Gift Sets
└── Bath & Body
    ├── Body Wash
    ├── Body Lotion
    ├── Deodorants
    └── Hand Care
```

### Kids Wear

```
Kids Wear
├── Boys
│   ├── T-Shirts & Polos
│   ├── Shirts
│   ├── Jeans & Trousers
│   ├── Shorts
│   ├── Ethnic Wear
│   └── Winter Wear
├── Girls
│   ├── Dresses
│   ├── Tops & T-Shirts
│   ├── Skirts
│   ├── Jeans & Jeggings
│   ├── Ethnic Wear
│   └── Winter Wear
├── Infants (0-2 years)
│   ├── Bodysuits
│   ├── Rompers
│   ├── Sets
│   └── Sleepwear
├── Teens
│   ├── Boys Teen
│   └── Girls Teen
├── School Uniforms
│   ├── Shirts
│   ├── Pants/Skirts
│   └── Accessories
├── Footwear
│   ├── Casual Shoes
│   ├── Sports Shoes
│   ├── Sandals
│   └── School Shoes
└── Accessories
    ├── Bags & Backpacks
    ├── Watches
    ├── Belts
    └── Hair Accessories
```

### Attributes by Vertical

```php
// Beauty vertical attributes
$beautyAttributes = [
    'skin_type' => ['oily', 'dry', 'combination', 'normal', 'sensitive'],
    'skin_concern' => ['acne', 'aging', 'dark_spots', 'dryness', 'dullness'],
    'ingredients' => ['text'], // Free text
    'coverage' => ['light', 'medium', 'full'],
    'finish' => ['matte', 'dewy', 'satin', 'natural'],
    'shade' => ['color_picker'], // Color swatches
    'size_ml' => ['number'],
    'spf' => ['number'],
    'vegan' => ['boolean'],
    'cruelty_free' => ['boolean'],
];

// Kids wear attributes
$kidsAttributes = [
    'age_group' => ['0-6m', '6-12m', '1-2y', '2-4y', '4-6y', '6-8y', '8-10y', '10-12y', '12-14y'],
    'size' => ['XS', 'S', 'M', 'L', 'XL', 'XXL', '2', '4', '6', '8', '10', '12', '14'],
    'color' => ['color_picker'],
    'material' => ['cotton', 'polyester', 'denim', 'wool', 'silk', 'linen', 'blend'],
    'pattern' => ['solid', 'striped', 'printed', 'checked', 'floral', 'cartoon'],
    'sleeve_type' => ['sleeveless', 'half_sleeve', 'full_sleeve', '3/4_sleeve'],
    'neck_type' => ['round', 'v-neck', 'collar', 'hooded'],
    'occasion' => ['casual', 'party', 'formal', 'sports', 'ethnic'],
    'gender' => ['boys', 'girls', 'unisex'],
    'washcare' => ['machine_wash', 'hand_wash', 'dry_clean'],
];
```

---

## Quick Start for New Project

When creating a new project from this base:

```bash
# 1. Clone base repository
git clone base-laravel.git my-beauty-store
cd my-beauty-store

# 2. Copy environment config
cp .env.example .env

# 3. Configure vertical
# Edit config/vertical.php with your vertical settings

# 4. Run category seeder for your vertical
php artisan db:seed --class=BeautyCategoriesSeeder

# 5. Configure brand colors in tailwind.config.js

# 6. Start development
php artisan serve
```

