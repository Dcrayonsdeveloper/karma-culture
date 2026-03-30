# Technical Architecture Document

## System Overview

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                                   CLIENTS                                        │
├─────────────────┬─────────────────┬─────────────────┬───────────────────────────┤
│   Web Browser   │  Mobile App     │   POS App       │   Social Messaging        │
│   (Blade/SSR)   │  (React Native) │   (Electron)    │   (WhatsApp/FB/IG)        │
└────────┬────────┴────────┬────────┴────────┬────────┴──────────────┬────────────┘
         │                 │                 │                       │
         ▼                 ▼                 ▼                       ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              LOAD BALANCER (Nginx)                               │
│                         SSL Termination / Rate Limiting                          │
└─────────────────────────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              CDN (CloudFlare/AWS)                                │
│                    Static Assets / Edge Caching / DDoS Protection                │
└─────────────────────────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           APPLICATION SERVERS                                    │
│  ┌──────────────────────────────────────────────────────────────────────────┐   │
│  │                         Laravel Application                               │   │
│  │  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐ ┌─────────────────────┐ │   │
│  │  │   Blade     │ │   API       │ │  Livewire   │ │   Background Jobs   │ │   │
│  │  │   Views     │ │  Endpoints  │ │  Components │ │   (Horizon)         │ │   │
│  │  └─────────────┘ └─────────────┘ └─────────────┘ └─────────────────────┘ │   │
│  └──────────────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────────────────┘
         │                    │                    │
         ▼                    ▼                    ▼
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│   MySQL/Postgres │  │     Redis       │  │   Meilisearch   │
│   (Primary DB)   │  │  Cache/Queue    │  │  Search Engine  │
│   + Read Replica │  │  Session Store  │  │                 │
└─────────────────┘  └─────────────────┘  └─────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────────────────────────┐
│                           EXTERNAL SERVICES                                      │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ │
│  │ Payment  │ │ Shipping │ │   SMS    │ │  Email   │ │   Push   │ │  Storage │ │
│  │ Gateway  │ │   API    │ │  Service │ │  Service │ │  Notif   │ │   (S3)   │ │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘ │
└─────────────────────────────────────────────────────────────────────────────────┘
```

---

## Technology Stack

### Backend

| Component | Technology | Version | Purpose |
|-----------|------------|---------|---------|
| Framework | Laravel | 11.x | Core application framework |
| Language | PHP | 8.3+ | Server-side programming |
| Web Server | Nginx | 1.24+ | Reverse proxy, static files |
| PHP Runtime | PHP-FPM | 8.3+ | FastCGI process manager |
| Task Queue | Laravel Horizon | 5.x | Queue monitoring & management |

### Frontend

| Component | Technology | Version | Purpose |
|-----------|------------|---------|---------|
| Templating | Blade | (Laravel) | Server-side rendering |
| Interactivity | Alpine.js | 3.x | Lightweight JS framework |
| Real-time | Livewire | 3.x | Dynamic components |
| Styling | Tailwind CSS | 3.4+ | Utility-first CSS |
| Build Tool | Vite | 5.x | Asset compilation |
| Icons | Heroicons / Custom SVG | - | Icon system |

### Database & Storage

| Component | Technology | Version | Purpose |
|-----------|------------|---------|---------|
| Primary DB | MySQL | 8.0+ | Main data store |
| Alternative | PostgreSQL | 15+ | (Optional) Advanced features |
| Cache | Redis | 7.0+ | Caching, sessions, queues |
| Search | Meilisearch | 1.x | Full-text search engine |
| File Storage | S3/MinIO | - | Media, documents |
| CDN | CloudFlare/AWS | - | Static asset delivery |

### DevOps & Infrastructure

| Component | Technology | Purpose |
|-----------|------------|---------|
| Containerization | Docker | Development & deployment |
| Orchestration | Docker Compose / K8s | Container management |
| CI/CD | GitHub Actions | Automated pipeline |
| Monitoring | Laravel Telescope | Debug & monitoring |
| APM | New Relic / Sentry | Performance monitoring |
| Logging | Laravel Log + ELK | Centralized logging |

### External Integrations

| Category | Service | Purpose |
|----------|---------|---------|
| Payments | Razorpay, PayU | Payment processing |
| Shipping | Shiprocket | Logistics aggregation |
| Email | SendGrid / AWS SES | Transactional email |
| SMS | MSG91 / Twilio | OTP & notifications |
| WhatsApp | WhatsApp Business API | Customer messaging |
| Social | Meta Business API | FB/Instagram messaging |
| Analytics | GA4, Facebook Pixel | User analytics |
| Push | Firebase FCM | Mobile push notifications |

---

## Application Architecture

### Directory Structure

```
app/
├── Actions/                    # Single-purpose action classes
│   ├── Auth/
│   ├── Cart/
│   ├── Checkout/
│   ├── Order/
│   ├── Product/
│   └── Search/
│
├── Console/
│   └── Commands/               # Artisan commands
│
├── DTOs/                       # Data Transfer Objects
│   ├── Cart/
│   ├── Checkout/
│   ├── Order/
│   └── Product/
│
├── Enums/                      # PHP 8.1+ Enums
│   ├── OrderStatus.php
│   ├── PaymentStatus.php
│   ├── UserRole.php
│   └── ...
│
├── Events/                     # Domain events
│   ├── Order/
│   ├── Product/
│   └── User/
│
├── Exceptions/                 # Custom exceptions
│   ├── Cart/
│   ├── Checkout/
│   └── Payment/
│
├── Http/
│   ├── Controllers/
│   │   ├── Api/               # API controllers (versioned)
│   │   │   └── V1/
│   │   ├── Admin/             # Admin panel controllers
│   │   ├── Seller/            # Seller dashboard controllers
│   │   ├── Staff/             # Staff dashboard controllers
│   │   └── Web/               # Public website controllers
│   │
│   ├── Middleware/
│   │   ├── CheckRole.php
│   │   ├── EnsureSellerApproved.php
│   │   ├── TrackUserBehavior.php
│   │   └── ...
│   │
│   ├── Requests/              # Form request validation
│   │   ├── Api/
│   │   ├── Admin/
│   │   └── Web/
│   │
│   └── Resources/             # API resources
│       └── Api/
│           └── V1/
│
├── Jobs/                       # Queue jobs
│   ├── Email/
│   ├── Order/
│   ├── Search/
│   └── Sync/
│
├── Listeners/                  # Event listeners
│
├── Livewire/                   # Livewire components
│   ├── Admin/
│   ├── Cart/
│   ├── Checkout/
│   ├── Product/
│   ├── Search/
│   └── Seller/
│
├── Mail/                       # Mailable classes
│
├── Models/                     # Eloquent models
│   ├── Concerns/              # Model traits
│   ├── Scopes/                # Query scopes
│   └── ...
│
├── Notifications/              # Notification classes
│
├── Observers/                  # Model observers
│
├── Policies/                   # Authorization policies
│
├── Providers/                  # Service providers
│
├── Repositories/               # Repository pattern (optional)
│   ├── Contracts/
│   └── Eloquent/
│
├── Rules/                      # Custom validation rules
│
├── Services/                   # Business logic services
│   ├── Cart/
│   ├── Checkout/
│   ├── Discount/
│   ├── Fraud/
│   ├── Messaging/
│   ├── Payment/
│   ├── Personalization/
│   ├── Search/
│   └── Shipping/
│
└── View/
    └── Components/             # Blade components
        ├── Admin/
        ├── Forms/
        ├── Layout/
        ├── Product/
        └── UI/
```

### Frontend Structure

```
resources/
├── css/
│   └── app.css                 # Tailwind imports
│
├── js/
│   ├── app.js                  # Main JS entry
│   ├── alpine/                 # Alpine.js components
│   │   ├── cart.js
│   │   ├── search.js
│   │   └── ...
│   └── utils/                  # Utility functions
│
└── views/
    ├── components/             # Blade components
    │   ├── admin/
    │   ├── forms/
    │   ├── layout/
    │   ├── product/
    │   └── ui/
    │
    ├── layouts/
    │   ├── app.blade.php       # Main layout
    │   ├── admin.blade.php     # Admin layout
    │   ├── seller.blade.php    # Seller layout
    │   └── auth.blade.php      # Auth layout
    │
    ├── livewire/               # Livewire views
    │
    ├── pages/                  # Page views
    │   ├── admin/
    │   ├── auth/
    │   ├── cart/
    │   ├── checkout/
    │   ├── home/
    │   ├── product/
    │   ├── search/
    │   ├── seller/
    │   └── user/
    │
    └── partials/               # Reusable partials
        ├── header.blade.php
        ├── footer.blade.php
        └── ...
```

---

## Design Patterns

### 1. Action Pattern
Single-purpose classes for business operations.

```php
// app/Actions/Checkout/ProcessCheckout.php
class ProcessCheckout
{
    public function __construct(
        private CartService $cartService,
        private PaymentService $paymentService,
        private OrderService $orderService,
    ) {}

    public function execute(CheckoutDTO $data): Order
    {
        // Validate cart
        // Process payment
        // Create order
        // Clear cart
        // Send notifications
    }
}
```

### 2. DTO Pattern
Type-safe data transfer.

```php
// app/DTOs/Checkout/CheckoutDTO.php
readonly class CheckoutDTO
{
    public function __construct(
        public int $userId,
        public AddressDTO $shippingAddress,
        public AddressDTO $billingAddress,
        public string $paymentMethod,
        public ?string $couponCode = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            userId: auth()->id(),
            shippingAddress: AddressDTO::from($request->shipping),
            billingAddress: AddressDTO::from($request->billing),
            paymentMethod: $request->payment_method,
            couponCode: $request->coupon_code,
        );
    }
}
```

### 3. Service Layer
Business logic encapsulation.

```php
// app/Services/Search/ProductSearchService.php
class ProductSearchService
{
    public function __construct(
        private MeilisearchClient $search,
        private PersonalizationService $personalization,
    ) {}

    public function search(SearchQuery $query, ?User $user = null): SearchResults
    {
        $results = $this->search->index('products')->search(
            $query->term,
            $this->buildSearchOptions($query)
        );

        if ($user) {
            $results = $this->personalization->rankResults($results, $user);
        }

        return SearchResults::fromMeilisearch($results);
    }
}
```

### 4. Repository Pattern (Optional)
Data access abstraction.

```php
// app/Repositories/Contracts/ProductRepositoryInterface.php
interface ProductRepositoryInterface
{
    public function findById(int $id): ?Product;
    public function findBySlug(string $slug): ?Product;
    public function getActiveProducts(int $page, int $perPage): LengthAwarePaginator;
    public function searchProducts(string $query, array $filters): Collection;
}
```

### 5. Observer Pattern
Model lifecycle hooks.

```php
// app/Observers/ProductObserver.php
class ProductObserver
{
    public function created(Product $product): void
    {
        SearchIndexJob::dispatch($product);
        ProductCreatedEvent::dispatch($product);
    }

    public function updated(Product $product): void
    {
        if ($product->isDirty(['name', 'description', 'price'])) {
            SearchReindexJob::dispatch($product);
        }
    }
}
```

---

## API Architecture

### API Versioning Strategy

```
/api/v1/products          # Current stable
/api/v2/products          # Next version (when needed)
```

### API Response Format

```json
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Product Name",
        "price": 999.00
    },
    "meta": {
        "current_page": 1,
        "total_pages": 10
    },
    "links": {
        "self": "/api/v1/products/1",
        "next": "/api/v1/products?page=2"
    }
}
```

### Error Response Format

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "The given data was invalid.",
        "details": {
            "email": ["The email field is required."]
        }
    },
    "meta": {
        "request_id": "uuid-here",
        "timestamp": "2026-01-29T10:00:00Z"
    }
}
```

---

## Caching Strategy

### Cache Layers

```
┌─────────────────────────────────────────────────────────────────┐
│                        Browser Cache                             │
│                   (Static assets, ETags)                         │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                          CDN Cache                               │
│                (Static assets, product images)                   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Application Cache                           │
│                         (Redis)                                  │
│  ┌─────────────┐ ┌─────────────┐ ┌─────────────┐               │
│  │   Route     │ │    View     │ │   Query     │               │
│  │   Cache     │ │    Cache    │ │   Cache     │               │
│  └─────────────┘ └─────────────┘ └─────────────┘               │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                       Database Cache                             │
│                    (Query cache, InnoDB)                         │
└─────────────────────────────────────────────────────────────────┘
```

### Cache Keys Convention

```
products:list:{page}:{filters_hash}
products:detail:{id}
products:search:{query_hash}
users:cart:{user_id}
users:session:{session_id}
categories:tree
settings:global
```

### Cache TTL Strategy

| Data Type | TTL | Strategy |
|-----------|-----|----------|
| Static pages | 1 hour | Invalidate on content change |
| Product list | 5 minutes | Tag-based invalidation |
| Product detail | 15 minutes | Invalidate on update |
| Search results | 5 minutes | Query hash based |
| User cart | No expiry | Session-based |
| Categories | 1 hour | Event-based invalidation |
| Settings | 24 hours | Manual invalidation |

---

## Queue Architecture

### Queue Configuration

```php
// config/horizon.php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['high', 'default', 'low'],
            'balance' => 'auto',
            'processes' => 10,
            'tries' => 3,
        ],
        'supervisor-emails' => [
            'connection' => 'redis',
            'queue' => ['emails'],
            'processes' => 3,
        ],
        'supervisor-search' => [
            'connection' => 'redis',
            'queue' => ['search-indexing'],
            'processes' => 5,
        ],
    ],
],
```

### Queue Priority

| Queue | Priority | Use Case |
|-------|----------|----------|
| high | 1 | Payment processing, order confirmation |
| default | 2 | General tasks, notifications |
| emails | 3 | Email sending |
| search-indexing | 4 | Search index updates |
| low | 5 | Analytics, reports |
| bulk | 6 | Bulk operations, imports |

---

## Search Architecture

### Meilisearch Configuration

```php
// config/scout.php
'meilisearch' => [
    'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
    'key' => env('MEILISEARCH_KEY'),
    'index-settings' => [
        Product::class => [
            'filterableAttributes' => [
                'category_id',
                'brand_id',
                'seller_id',
                'price',
                'rating',
                'in_stock',
                'attributes.*',
            ],
            'sortableAttributes' => [
                'price',
                'rating',
                'created_at',
                'sales_count',
                'relevance_score',
            ],
            'searchableAttributes' => [
                'name',
                'description',
                'brand_name',
                'category_name',
                'sku',
                'tags',
            ],
            'rankingRules' => [
                'words',
                'typo',
                'proximity',
                'attribute',
                'sort',
                'exactness',
                'sales_count:desc',
            ],
        ],
    ],
],
```

### Search Index Schema

```json
{
    "id": 1,
    "name": "Product Name",
    "description": "Product description...",
    "slug": "product-name",
    "sku": "SKU-001",
    "price": 999.00,
    "sale_price": 899.00,
    "category_id": 5,
    "category_name": "Electronics",
    "category_path": "Electronics > Mobiles > Smartphones",
    "brand_id": 3,
    "brand_name": "Brand Name",
    "seller_id": 10,
    "rating": 4.5,
    "review_count": 150,
    "sales_count": 5000,
    "in_stock": true,
    "stock_quantity": 50,
    "image_url": "https://cdn.example.com/products/1.jpg",
    "tags": ["smartphone", "5g", "android"],
    "attributes": {
        "color": "Black",
        "storage": "128GB",
        "ram": "8GB"
    },
    "created_at": 1706000000,
    "relevance_score": 0.95
}
```

---

## Security Architecture

### Authentication Flow

```
┌──────────┐     ┌──────────────┐     ┌──────────────┐
│  Client  │────▶│   Laravel    │────▶│   Database   │
│          │     │   Sanctum    │     │              │
└──────────┘     └──────────────┘     └──────────────┘
     │                  │
     │                  ▼
     │           ┌──────────────┐
     │           │    Redis     │
     │           │   Sessions   │
     │           └──────────────┘
     │
     ▼
┌──────────────────────────────────────────────────────┐
│                   API Auth (Mobile/POS)               │
│               Laravel Sanctum API Tokens              │
└──────────────────────────────────────────────────────┘
```

### Authorization Levels

```
Admin
├── Super Admin (full access)
├── Admin (limited admin)
└── Moderator (content only)

Staff
├── Manager (store operations)
├── Cashier (POS only)
└── Support (customer service)

Seller
├── Owner (full seller access)
├── Manager (inventory + orders)
└── Assistant (order view only)

Customer
├── Verified (full features)
├── Unverified (limited)
└── Wholesaler (B2B features)
```

---

## Deployment Architecture

### Production Environment

```
┌─────────────────────────────────────────────────────────────────┐
│                         CloudFlare                               │
│                    (CDN + DDoS Protection)                       │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Load Balancer (AWS ALB)                     │
└─────────────────────────────────────────────────────────────────┘
           │                    │                    │
           ▼                    ▼                    ▼
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│   App Server 1  │  │   App Server 2  │  │   App Server N  │
│   (Laravel)     │  │   (Laravel)     │  │   (Laravel)     │
└─────────────────┘  └─────────────────┘  └─────────────────┘
           │                    │                    │
           └────────────────────┼────────────────────┘
                               │
           ┌───────────────────┼───────────────────┐
           │                   │                   │
           ▼                   ▼                   ▼
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│  MySQL Primary  │  │ Redis Cluster   │  │   Meilisearch   │
│  + Read Replicas│  │                 │  │                 │
└─────────────────┘  └─────────────────┘  └─────────────────┘
           │
           ▼
┌─────────────────────────────────────────────────────────────────┐
│                         S3 / MinIO                               │
│                      (File Storage)                              │
└─────────────────────────────────────────────────────────────────┘
```

### CI/CD Pipeline

```yaml
# .github/workflows/deploy.yml
name: Deploy
on:
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
      - name: Run Tests
        run: php artisan test

  build:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - name: Build Docker Image
      - name: Push to Registry

  deploy:
    needs: build
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to Production
```

---

## Performance Optimization

### Database Optimization
- Read replicas for reporting queries
- Query caching with Redis
- Proper indexing strategy
- Partitioning for large tables (orders, logs)
- Connection pooling

### Application Optimization
- OPcache enabled
- Route caching
- Config caching
- View caching
- Lazy loading prevention (N+1)
- Eager loading optimization

### Frontend Optimization
- Asset minification
- Gzip compression
- Image optimization (WebP)
- Lazy loading images
- Critical CSS inlining
- HTTP/2 push

### Search Optimization
- Index optimization
- Query caching
- Facet caching
- Synonym handling
- Stop words configuration

---

## Monitoring & Observability

### Metrics to Track

| Category | Metrics |
|----------|---------|
| Application | Response time, error rate, throughput |
| Database | Query time, connections, slow queries |
| Cache | Hit rate, memory usage, evictions |
| Queue | Job throughput, failure rate, wait time |
| Search | Query latency, index size, query rate |
| Business | Orders/minute, cart abandonment, conversion |

### Alerting Rules

| Alert | Condition | Severity |
|-------|-----------|----------|
| High Error Rate | > 1% errors | Critical |
| Slow Response | p95 > 2s | Warning |
| Database Connections | > 80% pool | Warning |
| Queue Backup | > 1000 pending | Warning |
| Disk Space | > 85% used | Critical |
| Memory Usage | > 90% used | Critical |

