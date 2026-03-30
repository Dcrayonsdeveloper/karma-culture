# Test Strategy & Acceptance Criteria

## [AI-REF] Comprehensive Testing Plan

This document defines the testing approach for quality assurance.

---

## Testing Philosophy

1. **Test Pyramid**: Many unit tests, fewer integration, minimal E2E
2. **Shift Left**: Test early, test often
3. **Automation First**: Automate everything possible
4. **Coverage Target**: 80% code coverage minimum
5. **CI/CD Integration**: Tests run on every commit

---

## Test Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                       E2E Tests (Cypress)                        │
│                   Critical user journeys only                    │
│                        ~5% of tests                              │
└─────────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────────┐
│                  Integration Tests (PHPUnit)                     │
│              API endpoints, feature tests                        │
│                       ~20% of tests                              │
└─────────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────────┐
│                    Unit Tests (PHPUnit)                          │
│           Services, actions, models, utilities                   │
│                       ~75% of tests                              │
└─────────────────────────────────────────────────────────────────┘
```

---

## Testing Tools

| Category | Tool | Purpose |
|----------|------|---------|
| Unit/Integration | PHPUnit | PHP testing |
| Browser Testing | Laravel Dusk | Browser automation |
| E2E Testing | Cypress | End-to-end flows |
| API Testing | Pest + HTTP | API validation |
| Performance | k6 / Artillery | Load testing |
| Security | OWASP ZAP | Security scanning |
| Visual | Percy | Visual regression |
| Coverage | Xdebug | Code coverage |

---

## Directory Structure

```
tests/
├── Unit/
│   ├── Actions/
│   │   ├── Checkout/
│   │   │   ├── ProcessCheckoutTest.php
│   │   │   └── CalculateShippingTest.php
│   │   ├── Cart/
│   │   └── Product/
│   ├── Services/
│   │   ├── Search/
│   │   │   ├── ProductSearchServiceTest.php
│   │   │   └── PersonalizationServiceTest.php
│   │   ├── Payment/
│   │   └── Fraud/
│   ├── Models/
│   │   ├── ProductTest.php
│   │   ├── OrderTest.php
│   │   └── UserTest.php
│   └── DTOs/
│
├── Feature/
│   ├── Api/
│   │   ├── Auth/
│   │   │   ├── LoginTest.php
│   │   │   ├── RegisterTest.php
│   │   │   └── PasswordResetTest.php
│   │   ├── Products/
│   │   │   ├── ProductListTest.php
│   │   │   ├── ProductSearchTest.php
│   │   │   └── ProductDetailTest.php
│   │   ├── Cart/
│   │   ├── Checkout/
│   │   ├── Orders/
│   │   └── Seller/
│   ├── Web/
│   │   ├── HomePageTest.php
│   │   ├── ProductPageTest.php
│   │   └── CheckoutFlowTest.php
│   └── Admin/
│
├── Browser/
│   ├── CheckoutTest.php
│   ├── SearchTest.php
│   └── SellerOnboardingTest.php
│
├── Performance/
│   ├── search-load.js
│   ├── checkout-load.js
│   └── product-page-load.js
│
├── Security/
│   ├── AuthSecurityTest.php
│   ├── InjectionTest.php
│   └── XssTest.php
│
└── Fixtures/
    ├── products.json
    ├── users.json
    └── orders.json
```

---

## Unit Tests

### Service Tests

```php
// tests/Unit/Services/Search/ProductSearchServiceTest.php
namespace Tests\Unit\Services\Search;

use App\Services\Search\ProductSearchService;
use App\Services\Search\SearchQuery;
use Tests\TestCase;
use Mockery;

class ProductSearchServiceTest extends TestCase
{
    private ProductSearchService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ProductSearchService(
            Mockery::mock(MeilisearchClient::class),
            Mockery::mock(PersonalizationService::class),
            Mockery::mock(SearchAnalyticsService::class),
            Mockery::mock(SynonymService::class),
        );
    }

    public function test_search_returns_results(): void
    {
        $query = new SearchQuery(term: 'smartphone', page: 1, perPage: 20);

        $results = $this->service->search($query);

        $this->assertInstanceOf(SearchResults::class, $results);
        $this->assertGreaterThan(0, $results->total);
    }

    public function test_search_applies_category_filter(): void
    {
        $query = new SearchQuery(
            term: 'phone',
            categoryId: 5,
        );

        $results = $this->service->search($query);

        foreach ($results->products as $product) {
            $this->assertEquals(5, $product->category_id);
        }
    }

    public function test_search_applies_price_range(): void
    {
        $query = new SearchQuery(
            term: 'laptop',
            minPrice: 50000,
            maxPrice: 100000,
        );

        $results = $this->service->search($query);

        foreach ($results->products as $product) {
            $this->assertGreaterThanOrEqual(50000, $product->price);
            $this->assertLessThanOrEqual(100000, $product->price);
        }
    }

    public function test_search_handles_empty_query(): void
    {
        $query = new SearchQuery(term: '');

        $results = $this->service->search($query);

        $this->assertEquals(0, $results->total);
    }

    public function test_search_handles_special_characters(): void
    {
        $query = new SearchQuery(term: "iPhone 15 Pro Max 256GB");

        $results = $this->service->search($query);

        $this->assertInstanceOf(SearchResults::class, $results);
    }
}
```

### Action Tests

```php
// tests/Unit/Actions/Checkout/ProcessCheckoutTest.php
namespace Tests\Unit\Actions\Checkout;

use App\Actions\Checkout\ProcessCheckout;
use App\DTOs\Checkout\CheckoutDTO;
use App\Models\Cart;
use App\Models\Order;
use App\Models\User;
use App\Exceptions\InsufficientStockException;
use Tests\TestCase;

class ProcessCheckoutTest extends TestCase
{
    private ProcessCheckout $action;

    public function test_checkout_creates_order(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->hasItems(3)->create(['user_id' => $user->id]);

        $dto = new CheckoutDTO(
            userId: $user->id,
            shippingAddressId: 1,
            billingAddressId: 1,
            paymentMethod: 'razorpay',
        );

        $order = $this->action->execute($dto);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals($cart->total, $order->total);
    }

    public function test_checkout_fails_with_empty_cart(): void
    {
        $user = User::factory()->create();
        Cart::factory()->create(['user_id' => $user->id]);

        $dto = new CheckoutDTO(userId: $user->id, ...);

        $this->expectException(EmptyCartException::class);

        $this->action->execute($dto);
    }

    public function test_checkout_fails_with_insufficient_stock(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 1]);
        $cart = Cart::factory()
            ->hasItems(1, ['product_id' => $product->id, 'quantity' => 5])
            ->create(['user_id' => $user->id]);

        $this->expectException(InsufficientStockException::class);

        $this->action->execute(new CheckoutDTO(userId: $user->id, ...));
    }

    public function test_checkout_applies_coupon_discount(): void
    {
        $user = User::factory()->create();
        $coupon = Coupon::factory()->create([
            'type' => 'percentage',
            'value' => 10,
        ]);
        $cart = Cart::factory()
            ->hasItems(1, ['price' => 1000, 'quantity' => 1])
            ->create([
                'user_id' => $user->id,
                'coupon_id' => $coupon->id,
            ]);

        $order = $this->action->execute(new CheckoutDTO(userId: $user->id, ...));

        $this->assertEquals(100, $order->discount); // 10% of 1000
    }

    public function test_checkout_reduces_product_stock(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);
        $user = User::factory()->create();
        Cart::factory()
            ->hasItems(1, ['product_id' => $product->id, 'quantity' => 3])
            ->create(['user_id' => $user->id]);

        $this->action->execute(new CheckoutDTO(userId: $user->id, ...));

        $product->refresh();
        $this->assertEquals(7, $product->stock_quantity);
    }

    public function test_checkout_clears_cart_after_success(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->hasItems(3)->create(['user_id' => $user->id]);

        $this->action->execute(new CheckoutDTO(userId: $user->id, ...));

        $this->assertNull(Cart::find($cart->id));
    }
}
```

### Model Tests

```php
// tests/Unit/Models/ProductTest.php
namespace Tests\Unit\Models;

use App\Models\Product;
use Tests\TestCase;

class ProductTest extends TestCase
{
    public function test_discount_percentage_calculation(): void
    {
        $product = new Product(['mrp' => 1000, 'price' => 800]);

        $this->assertEquals(20, $product->discount_percentage);
    }

    public function test_discount_percentage_with_same_price(): void
    {
        $product = new Product(['mrp' => 1000, 'price' => 1000]);

        $this->assertEquals(0, $product->discount_percentage);
    }

    public function test_is_on_sale_returns_true_when_discounted(): void
    {
        $product = new Product(['mrp' => 1000, 'price' => 800]);

        $this->assertTrue($product->is_on_sale);
    }

    public function test_is_on_sale_returns_false_when_not_discounted(): void
    {
        $product = new Product(['mrp' => 1000, 'price' => 1000]);

        $this->assertFalse($product->is_on_sale);
    }

    public function test_active_scope_filters_correctly(): void
    {
        Product::factory()->create(['is_active' => true, 'status' => 'approved']);
        Product::factory()->create(['is_active' => false, 'status' => 'approved']);
        Product::factory()->create(['is_active' => true, 'status' => 'pending']);

        $active = Product::active()->get();

        $this->assertCount(1, $active);
    }

    public function test_in_stock_scope_filters_correctly(): void
    {
        Product::factory()->create(['stock_quantity' => 10]);
        Product::factory()->create(['stock_quantity' => 0]);

        $inStock = Product::inStock()->get();

        $this->assertCount(1, $inStock);
    }

    public function test_price_range_scope(): void
    {
        Product::factory()->create(['price' => 500]);
        Product::factory()->create(['price' => 1000]);
        Product::factory()->create(['price' => 1500]);

        $products = Product::priceRange(600, 1200)->get();

        $this->assertCount(1, $products);
        $this->assertEquals(1000, $products->first()->price);
    }
}
```

---

## Feature/Integration Tests

### API Tests

```php
// tests/Feature/Api/Products/ProductSearchTest.php
namespace Tests\Feature\Api\Products;

use App\Models\Product;
use App\Models\Category;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_matching_products(): void
    {
        Product::factory()->create(['name' => 'iPhone 15 Pro']);
        Product::factory()->create(['name' => 'Samsung Galaxy']);

        $response = $this->getJson('/api/v1/products/search?q=iphone');

        $response->assertOk()
            ->assertJsonCount(1, 'data.products')
            ->assertJsonPath('data.products.0.name', 'iPhone 15 Pro');
    }

    public function test_search_returns_facets(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(5)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/v1/products/search?q=*');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'products',
                    'facets' => [
                        'categories',
                        'brands',
                        'price_ranges',
                    ],
                ],
                'meta' => [
                    'total',
                    'current_page',
                ],
            ]);
    }

    public function test_search_filters_by_price_range(): void
    {
        Product::factory()->create(['price' => 500]);
        Product::factory()->create(['price' => 1500]);
        Product::factory()->create(['price' => 2500]);

        $response = $this->getJson('/api/v1/products/search?q=*&price_min=1000&price_max=2000');

        $response->assertOk()
            ->assertJsonCount(1, 'data.products');
    }

    public function test_search_sorts_by_price_asc(): void
    {
        Product::factory()->create(['name' => 'Product A', 'price' => 1000]);
        Product::factory()->create(['name' => 'Product B', 'price' => 500]);
        Product::factory()->create(['name' => 'Product C', 'price' => 1500]);

        $response = $this->getJson('/api/v1/products/search?q=*&sort=price_asc');

        $response->assertOk()
            ->assertJsonPath('data.products.0.price', 500)
            ->assertJsonPath('data.products.1.price', 1000)
            ->assertJsonPath('data.products.2.price', 1500);
    }

    public function test_search_response_time_under_200ms(): void
    {
        Product::factory()->count(100)->create();

        $start = microtime(true);
        $response = $this->getJson('/api/v1/products/search?q=product');
        $elapsed = (microtime(true) - $start) * 1000;

        $response->assertOk();
        $this->assertLessThan(200, $elapsed, 'Search took too long');
    }
}
```

### Authentication Tests

```php
// tests/Feature/Api/Auth/LoginTest.php
namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['user', 'token'],
            ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertUnauthorized()
            ->assertJsonPath('error.code', 'AUTHENTICATION_FAILED');
    }

    public function test_login_validates_email_format(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_is_rate_limited(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'wrongpassword',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertTooManyRequests();
    }

    public function test_login_logs_successful_attempt(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $user->refresh();
        $this->assertNotNull($user->last_login_at);
    }
}
```

---

## E2E Tests

### Checkout Flow

```javascript
// cypress/e2e/checkout.cy.js
describe('Checkout Flow', () => {
    beforeEach(() => {
        cy.login('customer@example.com', 'password');
        cy.addProductToCart('product-slug');
    });

    it('completes checkout successfully', () => {
        // Go to cart
        cy.visit('/cart');
        cy.get('[data-testid="cart-item"]').should('have.length.at.least', 1);

        // Proceed to checkout
        cy.get('[data-testid="checkout-button"]').click();
        cy.url().should('include', '/checkout');

        // Select shipping address
        cy.get('[data-testid="address-card"]').first().click();

        // Select payment method
        cy.get('[data-testid="payment-razorpay"]').click();

        // Place order
        cy.get('[data-testid="place-order"]').click();

        // Handle Razorpay modal (mocked in test)
        cy.completeRazorpayPayment();

        // Verify success
        cy.url().should('include', '/orders/');
        cy.get('[data-testid="order-success"]').should('be.visible');
        cy.get('[data-testid="order-number"]').should('match', /ORD-\d+/);
    });

    it('shows error for insufficient stock', () => {
        cy.intercept('POST', '/api/v1/checkout/validate', {
            statusCode: 409,
            body: {
                success: false,
                error: {
                    code: 'INVENTORY_ERROR',
                    message: 'Some items are out of stock',
                },
            },
        });

        cy.visit('/checkout');
        cy.get('[data-testid="place-order"]').click();

        cy.get('[data-testid="error-message"]')
            .should('be.visible')
            .and('contain', 'out of stock');
    });

    it('applies coupon successfully', () => {
        cy.visit('/checkout');

        cy.get('[data-testid="coupon-input"]').type('SAVE10');
        cy.get('[data-testid="apply-coupon"]').click();

        cy.get('[data-testid="discount-amount"]').should('not.contain', '₹0');
        cy.get('[data-testid="coupon-success"]').should('be.visible');
    });
});
```

### Search Flow

```javascript
// cypress/e2e/search.cy.js
describe('Product Search', () => {
    it('searches and filters products', () => {
        cy.visit('/');

        // Type search query
        cy.get('[data-testid="search-input"]')
            .type('smartphone{enter}');

        // Wait for results
        cy.url().should('include', '/search');
        cy.get('[data-testid="product-card"]').should('have.length.at.least', 1);

        // Apply brand filter
        cy.get('[data-testid="filter-brand-samsung"]').click();
        cy.get('[data-testid="product-card"]').each(($card) => {
            cy.wrap($card).should('contain', 'Samsung');
        });

        // Apply price filter
        cy.get('[data-testid="filter-price-min"]').type('10000');
        cy.get('[data-testid="filter-price-max"]').type('30000');
        cy.get('[data-testid="apply-price-filter"]').click();

        // Sort by price
        cy.get('[data-testid="sort-select"]').select('price_asc');

        // Verify sorting
        cy.get('[data-testid="product-price"]').then(($prices) => {
            const prices = [...$prices].map(el => parseInt(el.innerText.replace(/\D/g, '')));
            const sorted = [...prices].sort((a, b) => a - b);
            expect(prices).to.deep.equal(sorted);
        });
    });

    it('shows autocomplete suggestions', () => {
        cy.visit('/');

        cy.get('[data-testid="search-input"]').type('iph');

        cy.get('[data-testid="autocomplete-suggestions"]')
            .should('be.visible')
            .within(() => {
                cy.contains('iPhone').should('exist');
            });
    });
});
```

---

## Performance Tests

```javascript
// tests/performance/search-load.js (k6)
import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
    stages: [
        { duration: '1m', target: 100 },  // Ramp up
        { duration: '3m', target: 100 },  // Sustain
        { duration: '1m', target: 0 },    // Ramp down
    ],
    thresholds: {
        http_req_duration: ['p(95)<200'],  // 95% under 200ms
        http_req_failed: ['rate<0.01'],    // <1% errors
    },
};

export default function () {
    const queries = ['smartphone', 'laptop', 'headphones', 'camera', 'watch'];
    const query = queries[Math.floor(Math.random() * queries.length)];

    const response = http.get(
        `${__ENV.BASE_URL}/api/v1/products/search?q=${query}`
    );

    check(response, {
        'status is 200': (r) => r.status === 200,
        'has products': (r) => JSON.parse(r.body).data.products.length > 0,
        'response time < 200ms': (r) => r.timings.duration < 200,
    });

    sleep(1);
}
```

---

## Acceptance Criteria Templates

### User Registration

| ID | Criteria | Priority |
|----|----------|----------|
| REG-001 | User can register with email and password | P0 |
| REG-002 | Email must be unique | P0 |
| REG-003 | Password must meet strength requirements | P0 |
| REG-004 | Verification email is sent | P0 |
| REG-005 | User cannot access protected routes until verified | P1 |
| REG-006 | Phone number validation with OTP | P1 |
| REG-007 | Social login (Google, Facebook) works | P2 |

### Product Search

| ID | Criteria | Priority |
|----|----------|----------|
| SRC-001 | Search returns relevant results in < 200ms | P0 |
| SRC-002 | Typo tolerance works (1-2 character errors) | P0 |
| SRC-003 | Category filter shows correct products | P0 |
| SRC-004 | Price range filter works correctly | P0 |
| SRC-005 | Brand filter works correctly | P0 |
| SRC-006 | Sorting options work correctly | P0 |
| SRC-007 | Pagination works correctly | P0 |
| SRC-008 | Facet counts update with filters | P1 |
| SRC-009 | Search suggestions appear within 100ms | P1 |
| SRC-010 | Zero results shows helpful message | P1 |

### Checkout

| ID | Criteria | Priority |
|----|----------|----------|
| CHK-001 | User can complete checkout with valid cart | P0 |
| CHK-002 | Stock validation prevents overselling | P0 |
| CHK-003 | Payment integration works (Razorpay) | P0 |
| CHK-004 | Order is created with correct totals | P0 |
| CHK-005 | Order confirmation email is sent | P0 |
| CHK-006 | Coupon codes apply correct discount | P0 |
| CHK-007 | Shipping cost calculated correctly | P0 |
| CHK-008 | Guest checkout works | P1 |
| CHK-009 | Cart persists across sessions | P1 |
| CHK-010 | Multiple payment retries allowed | P1 |

---

## CI/CD Integration

```yaml
# .github/workflows/test.yml
name: Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  unit-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
          extensions: mbstring, xml, ctype, json, pdo_mysql
          coverage: xdebug

      - name: Install dependencies
        run: composer install --prefer-dist --no-interaction

      - name: Run unit tests
        run: php artisan test --testsuite=Unit --coverage-clover=coverage.xml

      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          files: coverage.xml

  integration-tests:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_DATABASE: testing
          MYSQL_ROOT_PASSWORD: password
        ports:
          - 3306:3306

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2

      - name: Run integration tests
        run: php artisan test --testsuite=Feature

  e2e-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Setup Node
        uses: actions/setup-node@v4

      - name: Install Cypress
        run: npm install

      - name: Run E2E tests
        run: npx cypress run

  security-scan:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Run security audit
        run: composer audit

      - name: Run OWASP scan
        uses: zaproxy/action-baseline@v0.7.0
```

