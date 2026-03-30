# API Overview

## [AI-REF] API Design Specification

This document defines the API architecture for web, mobile, and POS applications.

---

## API Philosophy

1. **RESTful Design**: Resource-oriented URLs with standard HTTP methods
2. **Consistent Response Format**: Unified JSON structure across all endpoints
3. **Versioning**: URL-based versioning (`/api/v1/`)
4. **Authentication**: Laravel Sanctum for both SPA and token-based auth
5. **Rate Limiting**: Tiered limits based on authentication status
6. **Documentation**: OpenAPI 3.0 specification

---

## Base URL Structure

```
Production:  https://api.shopverse.com/v1
Staging:     https://api.staging.shopverse.com/v1
Development: http://localhost:8000/api/v1
```

---

## Authentication

### Web (SPA) Authentication
Uses cookie-based sessions via Laravel Sanctum.

```http
POST /api/v1/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password123"
}
```

### Mobile/POS Token Authentication
Uses Bearer tokens via Laravel Sanctum.

```http
POST /api/v1/auth/token
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password123",
    "device_name": "iPhone 15 Pro"
}

Response:
{
    "success": true,
    "data": {
        "token": "1|abcdef123456...",
        "expires_at": null
    }
}
```

### Using Token

```http
GET /api/v1/user
Authorization: Bearer 1|abcdef123456...
```

---

## Response Format

### Success Response

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Product Name"
    },
    "meta": {
        "request_id": "req_abc123"
    }
}
```

### Paginated Response

```json
{
    "success": true,
    "data": [
        {"id": 1, "name": "Product 1"},
        {"id": 2, "name": "Product 2"}
    ],
    "meta": {
        "current_page": 1,
        "per_page": 20,
        "total": 150,
        "total_pages": 8,
        "from": 1,
        "to": 20
    },
    "links": {
        "first": "/api/v1/products?page=1",
        "last": "/api/v1/products?page=8",
        "prev": null,
        "next": "/api/v1/products?page=2"
    }
}
```

### Error Response

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "The given data was invalid.",
        "details": {
            "email": ["The email field is required."],
            "password": ["The password must be at least 8 characters."]
        }
    },
    "meta": {
        "request_id": "req_abc123",
        "timestamp": "2026-01-29T10:00:00Z"
    }
}
```

### Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `VALIDATION_ERROR` | 422 | Request validation failed |
| `AUTHENTICATION_REQUIRED` | 401 | No valid authentication |
| `FORBIDDEN` | 403 | Insufficient permissions |
| `NOT_FOUND` | 404 | Resource not found |
| `RATE_LIMITED` | 429 | Too many requests |
| `SERVER_ERROR` | 500 | Internal server error |
| `MAINTENANCE_MODE` | 503 | System under maintenance |
| `PAYMENT_FAILED` | 402 | Payment processing failed |
| `INVENTORY_ERROR` | 409 | Insufficient stock |
| `DUPLICATE_ERROR` | 409 | Resource already exists |

---

## Rate Limiting

### Limits by Authentication

| Type | Requests/Minute | Burst |
|------|-----------------|-------|
| Unauthenticated | 60 | 10 |
| Authenticated Customer | 120 | 20 |
| Authenticated Seller | 300 | 50 |
| POS Device | 600 | 100 |
| Admin | 1000 | 200 |

### Rate Limit Headers

```http
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 115
X-RateLimit-Reset: 1706529600
Retry-After: 45
```

---

## API Endpoints Overview

### Authentication & Users

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/auth/register` | Register new user | No |
| POST | `/auth/login` | Login (cookie) | No |
| POST | `/auth/token` | Get API token | No |
| POST | `/auth/logout` | Logout | Yes |
| POST | `/auth/forgot-password` | Request password reset | No |
| POST | `/auth/reset-password` | Reset password | No |
| POST | `/auth/verify-email` | Verify email | No |
| POST | `/auth/verify-phone` | Verify phone OTP | Yes |
| GET | `/user` | Get current user | Yes |
| PUT | `/user` | Update profile | Yes |
| PUT | `/user/password` | Change password | Yes |
| GET | `/user/addresses` | List addresses | Yes |
| POST | `/user/addresses` | Add address | Yes |
| PUT | `/user/addresses/{id}` | Update address | Yes |
| DELETE | `/user/addresses/{id}` | Delete address | Yes |

### Products

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/products` | List products | No |
| GET | `/products/{slug}` | Get product details | No |
| GET | `/products/{slug}/reviews` | Get product reviews | No |
| GET | `/products/{slug}/questions` | Get product Q&A | No |
| GET | `/products/search` | Search products | No |
| GET | `/products/featured` | Featured products | No |
| GET | `/products/deals` | Deals & discounts | No |
| GET | `/products/new-arrivals` | New arrivals | No |

### Categories & Brands

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/categories` | List categories | No |
| GET | `/categories/{slug}` | Category details | No |
| GET | `/categories/{slug}/products` | Category products | No |
| GET | `/brands` | List brands | No |
| GET | `/brands/{slug}` | Brand details | No |
| GET | `/brands/{slug}/products` | Brand products | No |

### Cart & Wishlist

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/cart` | Get cart | Guest/Yes |
| POST | `/cart/items` | Add to cart | Guest/Yes |
| PUT | `/cart/items/{id}` | Update quantity | Guest/Yes |
| DELETE | `/cart/items/{id}` | Remove item | Guest/Yes |
| DELETE | `/cart` | Clear cart | Guest/Yes |
| POST | `/cart/coupon` | Apply coupon | Guest/Yes |
| DELETE | `/cart/coupon` | Remove coupon | Guest/Yes |
| GET | `/wishlist` | Get wishlist | Yes |
| POST | `/wishlist` | Add to wishlist | Yes |
| DELETE | `/wishlist/{productId}` | Remove from wishlist | Yes |

### Checkout & Orders

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/checkout/validate` | Validate checkout | Yes |
| POST | `/checkout/shipping-rates` | Get shipping options | Yes |
| POST | `/checkout/create-order` | Create order | Yes |
| POST | `/checkout/payment` | Process payment | Yes |
| GET | `/orders` | List orders | Yes |
| GET | `/orders/{orderNumber}` | Order details | Yes |
| POST | `/orders/{orderNumber}/cancel` | Cancel order | Yes |
| GET | `/orders/{orderNumber}/track` | Track shipment | Yes |
| POST | `/orders/{orderNumber}/return` | Request return | Yes |

### Reviews

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/products/{slug}/reviews` | Submit review | Yes |
| PUT | `/reviews/{id}` | Update review | Yes |
| DELETE | `/reviews/{id}` | Delete review | Yes |
| POST | `/reviews/{id}/vote` | Vote helpful/unhelpful | Yes |
| POST | `/products/{slug}/questions` | Ask question | Yes |
| POST | `/questions/{id}/answers` | Answer question | Yes |

### Seller API

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/seller/dashboard` | Dashboard stats | Seller |
| GET | `/seller/products` | List products | Seller |
| POST | `/seller/products` | Create product | Seller |
| PUT | `/seller/products/{id}` | Update product | Seller |
| DELETE | `/seller/products/{id}` | Delete product | Seller |
| GET | `/seller/orders` | List orders | Seller |
| PUT | `/seller/orders/{id}/status` | Update order status | Seller |
| POST | `/seller/orders/{id}/ship` | Mark as shipped | Seller |
| GET | `/seller/inventory` | Inventory overview | Seller |
| PUT | `/seller/inventory/{productId}` | Update stock | Seller |
| GET | `/seller/analytics` | Sales analytics | Seller |
| GET | `/seller/payouts` | Payout history | Seller |

### Admin API

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/admin/dashboard` | Admin dashboard | Admin |
| GET | `/admin/users` | List users | Admin |
| GET | `/admin/users/{id}` | User details | Admin |
| PUT | `/admin/users/{id}` | Update user | Admin |
| GET | `/admin/sellers` | List sellers | Admin |
| PUT | `/admin/sellers/{id}/approve` | Approve seller | Admin |
| GET | `/admin/products` | All products | Admin |
| PUT | `/admin/products/{id}/approve` | Approve product | Admin |
| GET | `/admin/orders` | All orders | Admin |
| GET | `/admin/reviews` | Review moderation | Admin |
| PUT | `/admin/reviews/{id}` | Moderate review | Admin |
| GET | `/admin/reports/*` | Various reports | Admin |
| POST | `/admin/settings` | Update settings | Admin |

### POS API

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/pos/auth` | POS device auth | POS |
| GET | `/pos/products/search` | Search products | POS |
| GET | `/pos/products/barcode/{code}` | Get by barcode | POS |
| POST | `/pos/sales` | Create sale | POS |
| GET | `/pos/sales/{id}` | Sale details | POS |
| POST | `/pos/sales/{id}/void` | Void sale | POS |
| POST | `/pos/returns` | Process return | POS |
| POST | `/pos/credit-notes` | Create credit note | POS |
| GET | `/pos/credit-notes/{code}/validate` | Validate credit note | POS |
| POST | `/pos/shift/open` | Open shift | POS |
| POST | `/pos/shift/close` | Close shift | POS |
| GET | `/pos/sync` | Sync data | POS |

### Mobile-Specific

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/mobile/home` | Home screen data | No |
| GET | `/mobile/banners` | App banners | No |
| POST | `/mobile/push/register` | Register push token | Yes |
| DELETE | `/mobile/push/unregister` | Unregister push | Yes |
| GET | `/mobile/notifications` | In-app notifications | Yes |
| POST | `/mobile/track` | Track analytics events | Guest/Yes |

### Messaging API (Webhooks)

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/webhooks/whatsapp` | WhatsApp webhook | Webhook |
| POST | `/webhooks/facebook` | Facebook webhook | Webhook |
| POST | `/webhooks/instagram` | Instagram webhook | Webhook |
| POST | `/webhooks/payment` | Payment gateway webhook | Webhook |
| POST | `/webhooks/shipping` | Shipping updates webhook | Webhook |

---

## Detailed Endpoint Specifications

### Search Products

```http
GET /api/v1/products/search?q=smartphone&category=electronics&brand=samsung&price_min=10000&price_max=50000&rating=4&sort=price_asc&page=1&per_page=20
```

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `q` | string | Search query |
| `category` | string | Category slug |
| `brand` | string/array | Brand slug(s) |
| `price_min` | number | Minimum price |
| `price_max` | number | Maximum price |
| `rating` | number | Minimum rating (1-5) |
| `in_stock` | boolean | Only in-stock items |
| `attributes[color]` | string | Filter by attribute |
| `sort` | string | Sort order |
| `page` | integer | Page number |
| `per_page` | integer | Items per page (max 100) |

**Sort Options:**
- `relevance` (default)
- `price_asc`
- `price_desc`
- `rating`
- `newest`
- `bestselling`
- `discount`

**Response:**

```json
{
    "success": true,
    "data": {
        "products": [...],
        "facets": {
            "categories": [
                {"slug": "smartphones", "name": "Smartphones", "count": 45}
            ],
            "brands": [
                {"slug": "samsung", "name": "Samsung", "count": 23}
            ],
            "price_ranges": [
                {"min": 0, "max": 10000, "count": 15},
                {"min": 10000, "max": 25000, "count": 30}
            ],
            "attributes": {
                "color": [
                    {"value": "Black", "count": 20},
                    {"value": "White", "count": 15}
                ],
                "storage": [
                    {"value": "128GB", "count": 25}
                ]
            }
        }
    },
    "meta": {
        "total": 150,
        "query_time_ms": 45
    }
}
```

### Create Order

```http
POST /api/v1/checkout/create-order
Authorization: Bearer {token}
Content-Type: application/json

{
    "shipping_address_id": 1,
    "billing_address_id": 1,
    "shipping_method": "standard",
    "payment_method": "razorpay",
    "notes": "Please handle with care",
    "use_wallet_balance": true,
    "credit_note_code": "CN-123456"
}
```

**Response:**

```json
{
    "success": true,
    "data": {
        "order": {
            "id": 1,
            "order_number": "ORD-2026012900001",
            "status": "pending",
            "payment_status": "pending",
            "subtotal": 4999.00,
            "discount": 500.00,
            "tax": 809.82,
            "shipping_cost": 0.00,
            "total": 5308.82,
            "items": [...]
        },
        "payment": {
            "gateway": "razorpay",
            "order_id": "order_abc123",
            "amount": 530882,
            "currency": "INR",
            "key": "rzp_live_xxx"
        }
    }
}
```

### POS Create Sale

```http
POST /api/v1/pos/sales
Authorization: Bearer {pos_token}
Content-Type: application/json

{
    "store_id": 1,
    "register_id": 1,
    "customer_id": null,
    "customer_phone": "+919876543210",
    "items": [
        {
            "product_id": 123,
            "variant_id": null,
            "quantity": 2,
            "price": 499.00,
            "discount": 0
        },
        {
            "barcode": "8901234567890",
            "quantity": 1
        }
    ],
    "discount": {
        "type": "percentage",
        "value": 10
    },
    "payment": {
        "method": "split",
        "details": [
            {"method": "cash", "amount": 500},
            {"method": "upi", "amount": 397.10}
        ]
    },
    "credit_note_code": null
}
```

---

## Webhooks

### Webhook Security

All webhooks are verified using:
1. **Signature Verification**: HMAC-SHA256 signature in header
2. **Timestamp Validation**: Request must be within 5 minutes
3. **IP Whitelist**: For payment gateways

```php
// Verify webhook signature
$signature = hash_hmac(
    'sha256',
    $request->getContent(),
    config('services.webhook_secret')
);

if (!hash_equals($signature, $request->header('X-Webhook-Signature'))) {
    abort(401, 'Invalid signature');
}
```

### Payment Webhook

```http
POST /api/v1/webhooks/payment
X-Webhook-Signature: sha256=abc123...
Content-Type: application/json

{
    "event": "payment.captured",
    "payload": {
        "payment_id": "pay_abc123",
        "order_id": "order_xyz789",
        "amount": 50000,
        "currency": "INR",
        "status": "captured",
        "method": "upi"
    }
}
```

---

## API Versioning Strategy

### Breaking Changes (New Version)
- Removing endpoints
- Changing response structure
- Changing authentication method
- Removing required fields

### Non-Breaking Changes (Same Version)
- Adding new endpoints
- Adding optional fields
- Adding new enum values
- Performance improvements

### Deprecation Process
1. Announce deprecation 3 months in advance
2. Add `Deprecation` header to responses
3. Document migration path
4. Remove after deprecation period

```http
Deprecation: true
Sunset: Sat, 01 Jul 2026 00:00:00 GMT
Link: </api/v2/products>; rel="successor-version"
```

---

## SDK Considerations

### Mobile SDK Requirements

```typescript
// React Native SDK interface
interface ShopVerseSDK {
    // Auth
    login(email: string, password: string): Promise<AuthResponse>;
    register(data: RegisterData): Promise<AuthResponse>;
    logout(): Promise<void>;

    // Products
    getProducts(params: ProductParams): Promise<PaginatedResponse<Product>>;
    getProduct(slug: string): Promise<Product>;
    searchProducts(query: string, filters: Filters): Promise<SearchResponse>;

    // Cart
    getCart(): Promise<Cart>;
    addToCart(productId: number, quantity: number): Promise<Cart>;
    updateCartItem(itemId: number, quantity: number): Promise<Cart>;
    removeFromCart(itemId: number): Promise<Cart>;

    // Checkout
    createOrder(data: CheckoutData): Promise<Order>;
    processPayment(orderId: number, paymentData: PaymentData): Promise<PaymentResult>;

    // Orders
    getOrders(page: number): Promise<PaginatedResponse<Order>>;
    getOrder(orderNumber: string): Promise<Order>;
    trackOrder(orderNumber: string): Promise<TrackingInfo>;
}
```

### POS SDK Requirements

```typescript
// Electron POS SDK interface
interface POSClient {
    // Device
    authenticate(deviceId: string, secret: string): Promise<AuthResponse>;
    sync(): Promise<SyncResult>;

    // Products
    searchProducts(query: string): Promise<Product[]>;
    getProductByBarcode(barcode: string): Promise<Product>;

    // Sales
    createSale(sale: SaleData): Promise<Sale>;
    voidSale(saleId: number): Promise<Sale>;
    printReceipt(saleId: number): Promise<void>;

    // Returns
    processReturn(returnData: ReturnData): Promise<Return>;
    createCreditNote(data: CreditNoteData): Promise<CreditNote>;
    validateCreditNote(code: string): Promise<CreditNote>;

    // Shift
    openShift(data: ShiftData): Promise<Shift>;
    closeShift(data: CloseShiftData): Promise<ShiftReport>;

    // Offline
    queueOfflineSale(sale: SaleData): void;
    syncOfflineSales(): Promise<SyncResult>;
}
```

