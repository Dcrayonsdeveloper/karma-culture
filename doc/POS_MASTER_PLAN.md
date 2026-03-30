# ForeverKids Enterprise POS — Master Implementation Plan

> **Version**: 2.0 — Enhanced
> **Approach**: Web-based PWA (not Electron) — same Laravel codebase, runs in Chrome/Edge on any tablet/laptop
> **Route**: `yourdomain.com/pos/` — integrated into existing Laravel app
> **Stack**: Laravel 12 + Alpine.js 3 + Tailwind CSS v4 + Axios + IndexedDB (offline)

---

## TABLE OF CONTENTS

1. [What Already Exists](#1-what-already-exists)
2. [What Needs to Be Built](#2-what-needs-to-be-built)
3. [Architecture & Integration](#3-architecture--integration)
4. [Security Model](#4-security-model)
5. [UI/UX Design System](#5-uiux-design-system)
6. [Screen Designs](#6-screen-designs)
7. [Epics & User Stories](#7-epics--user-stories)
8. [Functional Flows](#8-functional-flows)
9. [Testing Strategy](#9-testing-strategy)
10. [Implementation Phases](#10-implementation-phases)

---

## 1. WHAT ALREADY EXISTS

### Database Tables (all migrated, ready to use)
| Table | Status | Notes |
|---|---|---|
| `pos_registers` | ✅ Exists | store_id, name, device_id (unique), status, settings(json), last_sync_at |
| `pos_sales` | ✅ Exists | sale_number (POS-YYYYMMDDNNNNN), store/register/staff/customer, totals, payment_method, payment_details(json), receipt_data(json), status |
| `pos_sale_items` | ✅ Exists | barcode field, product_name, qty, price, discount, tax, total |
| `pos_returns` | ✅ Exists | return_number (PRET-...), refund_method (cash/original_payment/credit_note), reason, status |
| `barcodes` | ✅ Exists | product_id, variant_id, barcode (unique), type (ean13/ean8/upc/code128/qr) |
| `credit_notes` | ✅ Exists | Secure code, amount, remaining_amount, expires_at, status |
| `stores` | ✅ Exists | name, code, address, gst_number, settings(json) |
| `staff` | ✅ Exists | user_id, employee_id, role (manager/cashier/support/warehouse), store_id, permissions(json) |
| `staff_shifts` | ✅ Exists | opening_cash, closing_cash, register_summary(json), status (open/closed) |
| `inventory_stocks` | ✅ Exists | Per-location: quantity, reserved_quantity, available_quantity |
| `inventory_movements` | ✅ Exists | Reference type supports `pos_sale` |
| `orders` | ✅ Exists | source column supports `pos` |
| `tax_rates` | ✅ Exists | CGST/SGST/IGST rates |

### Models (all exist with relationships)
| Model | Key Methods |
|---|---|
| `PosRegister` | BelongsTo Store, HasMany PosSale |
| `PosSale` | Auto-generates sale_number, BelongsTo Store/Register/Staff/Customer, HasMany items/returns |
| `PosSaleItem` | BelongsTo PosSale/Product/Variant |
| `PosReturn` | Auto-generates return_number, BelongsTo PosSale/Store/Staff/CreditNote |
| `Store` | HasMany Staff, PosRegister, PosSale, StaffShift |
| `Staff` | BelongsTo User/Store, hasPermission() method |
| `StaffShift` | close(closingCash), variance accessor |
| `InventoryStock` | reserve(), release(), deduct(), add() |
| `InventoryMovement` | Full audit trail |
| `CreditNote` | isValid(), redeem() |
| `Product` | Has barcode field, hsn_code, tax_rate, isInStock(), scopeActive() |
| `ProductVariant` | Has barcode field, stock_quantity |

### API Spec (documented but not implemented)
- `doc/features/10-pos-system.md` — full API endpoint specs for auth, product lookup, barcode scan, create sale, process return, validate credit note, shift management, data sync

---

## 2. WHAT NEEDS TO BE BUILT

### Controllers
| File | Purpose |
|---|---|
| `app/Http/Controllers/Pos/AuthController.php` | Terminal registration, staff PIN login, session management |
| `app/Http/Controllers/Pos/DashboardController.php` | POS main screen, shift status |
| `app/Http/Controllers/Pos/SaleController.php` | Create sale, void sale, sale history |
| `app/Http/Controllers/Pos/ProductController.php` | Search, barcode lookup, stock check |
| `app/Http/Controllers/Pos/CartController.php` | POS cart management (server-side for integrity) |
| `app/Http/Controllers/Pos/PaymentController.php` | Process payments (cash/card/UPI/split/credit note) |
| `app/Http/Controllers/Pos/ReturnController.php` | Process returns, exchanges |
| `app/Http/Controllers/Pos/CreditNoteController.php` | Validate, issue, redeem credit notes |
| `app/Http/Controllers/Pos/ShiftController.php` | Open/close shift, Z-report |
| `app/Http/Controllers/Pos/ReportController.php` | Sales, GST, cashier, stock reports |
| `app/Http/Controllers/Pos/CustomerController.php` | Lookup/create customer at POS |
| `app/Http/Controllers/Pos/ReceiptController.php` | Generate/print/send receipts |

### Middleware
| File | Purpose |
|---|---|
| `app/Http/Middleware/PosAuthenticate.php` | Verify staff session + device token |
| `app/Http/Middleware/PosShiftRequired.php` | Ensure shift is open before billing |
| `app/Http/Middleware/PosRoleCheck.php` | Role-based actions (cashier/supervisor/manager) |

### Routes
| File | Purpose |
|---|---|
| `routes/pos.php` | All POS routes under `/pos` prefix |

### Views (Blade + Alpine.js)
| File | Purpose |
|---|---|
| `resources/views/pos/layout.blade.php` | POS-specific full-screen layout (no storefront header/footer) |
| `resources/views/pos/login.blade.php` | Terminal + PIN login screen |
| `resources/views/pos/shift-open.blade.php` | Shift opening screen |
| `resources/views/pos/billing.blade.php` | Main POS billing screen (the core UI) |
| `resources/views/pos/payment.blade.php` | Payment modal/screen |
| `resources/views/pos/returns.blade.php` | Return/exchange flow |
| `resources/views/pos/reports.blade.php` | Reports dashboard |
| `resources/views/pos/shift-close.blade.php` | Z-report + shift close |
| `resources/views/pos/receipt.blade.php` | Receipt template (already exists in doc spec) |

### JavaScript
| File | Purpose |
|---|---|
| `resources/js/pos/pos-app.js` | Alpine.js POS stores and components |
| `resources/js/pos/barcode-scanner.js` | Camera + USB barcode scanner integration |
| `resources/js/pos/receipt-printer.js` | ESC/POS thermal printer bridge |
| `resources/js/pos/offline-queue.js` | IndexedDB offline sale queue |
| `resources/js/pos/keyboard-shortcuts.js` | Keyboard shortcut handler |

### Migrations (additional — extend existing)
| Migration | Purpose |
|---|---|
| `add_pos_enhancements.php` | Add: pos_held_bills table, pos_cash_movements table, pos_audit_log table, exchange_bill_id to pos_returns, manager_approved_by to pos_returns, pos_sale_items discount_reason column |

---

## 3. ARCHITECTURE & INTEGRATION

```
Browser (Chrome/Edge on Tablet/Laptop)
    │
    ▼
┌──────────────────────────────────────────────────────────┐
│  yourdomain.com/pos/*                                     │
│                                                          │
│  Laravel App (same codebase as storefront)                │
│  ┌──────────────┐  ┌──────────────┐  ┌────────────────┐  │
│  │ POS Routes   │  │ POS          │  │ POS Views      │  │
│  │ routes/pos   │  │ Controllers  │  │ (Blade+Alpine) │  │
│  └──────┬───────┘  └──────┬───────┘  └────────────────┘  │
│         │                  │                              │
│  ┌──────┴──────────────────┴───────────────────────────┐  │
│  │  Shared Models: Product, ProductVariant, Order,      │  │
│  │  InventoryStock, InventoryMovement, CreditNote,      │  │
│  │  PosSale, PosReturn, Store, Staff, StaffShift        │  │
│  └──────────────────────┬──────────────────────────────┘  │
│                         │                                │
│  ┌──────────────────────┴──────────────────────────────┐  │
│  │  MySQL (shared DB) │  Redis (real-time sync)         │  │
│  └─────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────┘

Integration Points:
  Products table    ← shared (POS reads same products as storefront)
  Stock             ← shared (POS deducts, online deducts, same pool)
  Customers/Users   ← shared (POS can look up online customers)
  Coupons           ← shared (same coupon codes work at POS)
  Credit Notes      ← shared (issued at POS, redeemable online and vice versa)
  Orders            ← POS creates orders with source='pos' (visible in admin)
```

### Stock Deduction Strategy
POS sale must update BOTH layers:
1. `product.stock_quantity -= qty` (or `variant.stock_quantity -= qty`)
2. `inventory_stocks` table: `deduct(qty)` for the store's inventory location
3. `inventory_movements`: INSERT with reference_type = 'pos_sale'

This keeps both layers consistent and creates full audit trail.

### Route Registration
```php
// bootstrap/app.php or routes/pos.php
Route::prefix('pos')
    ->name('pos.')
    ->middleware(['web'])
    ->group(base_path('routes/pos.php'));
```

POS uses the `web` middleware group (session-based auth, CSRF) — NOT Sanctum API tokens. This simplifies auth and lets us use standard Laravel sessions.

---

## 4. SECURITY MODEL

### Layer 1 — Device Registration
```
Admin Panel → Settings → Stores → POS Terminals
  ↓
  Admin creates terminal: name="Counter 1", generates unique device_id
  ↓
  First time POS opened on a device → enter device_id
  ↓
  Server validates → stores device_id in localStorage
  ↓
  All future requests include X-POS-Device header
  ↓
  Middleware rejects requests from unregistered/inactive devices
```

### Layer 2 — Staff PIN Authentication
```
Staff enters 4-6 digit PIN on numpad
  ↓
  POST /pos/login {device_id, pin}
  ↓
  Server: find staff by PIN hash + store match + is_active
  ↓
  Creates Laravel session (staff_id, store_id, register_id in session)
  ↓
  Session expires: 8 hours OR shift close (whichever first)
  ↓
  Auto-logout after 15 min idle (Alpine.js timer)
```

### Layer 3 — Role-Based Permissions
| Action | cashier | supervisor | manager |
|---|---|---|---|
| Create sale | ✅ | ✅ | ✅ |
| Apply item discount ≤ 10% | ✅ | ✅ | ✅ |
| Apply item discount > 10% | ❌ | ✅ | ✅ |
| Apply bill discount | ❌ | ✅ | ✅ |
| Void completed sale | ❌ | ❌ | ✅ |
| Process return (cash ≤ ₹1000) | ✅ | ✅ | ✅ |
| Process return (cash > ₹1000) | ❌ | ✅ | ✅ |
| Override price | ❌ | ❌ | ✅ |
| Open cash drawer without sale | ❌ | ✅ | ✅ |
| View reports | ❌ | ✅ (own shift) | ✅ (all) |
| Close shift | ✅ (own) | ✅ (own) | ✅ (any) |
| Manage held bills (delete others') | ❌ | ✅ | ✅ |

### Layer 4 — Manager Override PIN
When a cashier tries a restricted action:
```
Modal appears: "Manager Authorization Required"
  ↓
  Manager enters their PIN (without logging out cashier)
  ↓
  Server validates: staff with that PIN has manager/supervisor role at same store
  ↓
  Action proceeds, audit log records: action, cashier_id, authorizer_id
  ↓
  Cashier session continues (no disruption)
```

### Layer 5 — Audit Log
Every sensitive action creates a `pos_audit_log` entry:
```json
{
  "action": "sale_voided",
  "entity_type": "pos_sale",
  "entity_id": 42,
  "staff_id": 5,
  "authorized_by": 2,
  "terminal_id": "POS-MAIN",
  "ip": "192.168.1.50",
  "old_value": {"status": "completed"},
  "new_value": {"status": "voided"},
  "created_at": "2026-02-24 14:30:00"
}
```

### Layer 6 — Network Security
- All POS traffic over HTTPS (AWS Lightsail free SSL)
- Optional: IP whitelist middleware for POS routes (restrict to store network)
- Rate limiting: 120 req/min per device (higher than storefront since POS is rapid-fire)
- CSRF token on all POST/PUT/DELETE (standard Laravel web middleware)
- Session stored in Redis (fast, shared across requests)

---

## 5. UI/UX DESIGN SYSTEM

### Design Principles
1. **Speed first** — Cashier should complete a sale in under 30 seconds
2. **Touch-friendly** — All tap targets ≥ 44×44px (Apple HIG minimum)
3. **Keyboard-driven** — Every action has a keyboard shortcut for power users
4. **Minimal clicks** — Most common actions within 1-2 taps
5. **High contrast** — Easy to read under store lighting
6. **Real-time feedback** — Every action shows immediate visual response
7. **Error prevention** — Warn before destructive actions, confirm before void/return

### Color System (extends existing brand)
```css
/* POS-specific semantic colors */
--pos-bg:        #F5F5F7;       /* Light gray background (easy on eyes all day) */
--pos-sidebar:   #1E293B;       /* Dark slate sidebar */
--pos-card:      #FFFFFF;       /* White cards */
--pos-primary:   #6F9CA2;       /* Brand teal — buttons, headers */
--pos-success:   #22C55E;       /* Green — payment confirmed, in stock */
--pos-warning:   #F59E0B;       /* Amber — low stock, partial payment */
--pos-danger:    #EF4444;       /* Red — void, out of stock, errors */
--pos-info:      #3B82F6;       /* Blue — informational */
--pos-accent:    #F8931D;       /* Brand orange — sale badges, highlights */
```

### Typography
```css
/* POS uses larger base for readability at arm's length */
--pos-text-base: 15px;          /* Body text (vs 14px on storefront) */
--pos-text-sm:   13px;          /* Secondary text */
--pos-text-lg:   18px;          /* Section headers */
--pos-text-xl:   24px;          /* Totals, prices */
--pos-text-2xl:  32px;          /* Grand total */
--pos-font:      'Poppins';     /* Consistent with storefront */
--pos-font-mono: 'JetBrains Mono'; /* Amounts, receipt preview */
```

### Spacing & Layout
- **Gutter**: 16px between panels, 12px between cards
- **Border radius**: 12px for panels, 8px for buttons, 6px for inputs
- **Shadows**: Subtle (`0 1px 3px rgba(0,0,0,0.08)`) — not heavy
- **Scrolling**: Smooth native scroll with momentum. Products grid: virtual scroll for 1000+ products. Cart: auto-scroll to newly added item with highlight animation.

### Animations & Transitions
```css
/* Item added to cart — slide in from left, brief green flash */
@keyframes pos-item-added {
  0%   { transform: translateX(-20px); opacity: 0; background: #DCFCE7; }
  30%  { transform: translateX(0); opacity: 1; background: #DCFCE7; }
  100% { background: transparent; }
}

/* Price change flash — brief yellow highlight */
@keyframes pos-price-flash {
  0%, 100% { background: transparent; }
  50%      { background: #FEF3C7; }
}

/* Payment success — checkmark scale bounce */
@keyframes pos-success-bounce {
  0%   { transform: scale(0); }
  60%  { transform: scale(1.2); }
  100% { transform: scale(1); }
}

/* All panel transitions: 200ms ease-out (fast but smooth) */
/* Modal overlays: 150ms fade */
/* Toasts: 300ms slide-in from right */
```

### Keyboard Shortcuts
| Key | Action |
|---|---|
| `F1` | Help/shortcuts overlay |
| `F2` | Search products (focus search bar) |
| `F3` | Barcode scan mode (activate camera) |
| `F5` | New bill |
| `F8` | Hold current bill |
| `F9` | Recall held bill |
| `F10` | Open payment screen |
| `F12` | Void last item |
| `Esc` | Close current modal/panel |
| `Enter` | Confirm current action |
| `Ctrl+R` | Returns screen |
| `Ctrl+E` | Exchange screen |
| `Ctrl+Shift+Z` | Z-Report (shift close) |
| `↑ / ↓` | Navigate cart items |
| `+` / `-` | Increment/decrement selected item qty |
| `Del` | Remove selected cart item |

### Responsive Breakpoints
```
≥ 1280px : Full desktop layout (side-by-side products + cart)
≥ 1024px : Compact desktop (narrower product grid)
≥ 768px  : Tablet landscape (stacked with toggle between products/cart)
≥ 640px  : Tablet portrait (full-width cart, products in slide-out panel)
< 640px  : Not supported (show "use tablet or larger" message)
```

---

## 6. SCREEN DESIGNS

### Screen 1 — Terminal Setup (first-time only)

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│                  [ForeverKids Logo]                          │
│                  Point of Sale Terminal                      │
│                                                             │
│           ┌───────────────────────────────────┐              │
│           │                                   │              │
│           │   Enter Terminal ID                │              │
│           │   ┌─────────────────────────────┐ │              │
│           │   │  POS-MAIN                   │ │              │
│           │   └─────────────────────────────┘ │              │
│           │                                   │              │
│           │   This device will be registered  │              │
│           │   as the POS terminal above.      │              │
│           │                                   │              │
│           │         [Register Device →]        │              │
│           │                                   │              │
│           └───────────────────────────────────┘              │
│                                                             │
│           🔒 Terminal ID provided by store admin             │
└─────────────────────────────────────────────────────────────┘
```

**Behavior**: After registration, device_id stored in localStorage. Never shown again unless cleared.

---

### Screen 2 — Staff PIN Login

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│    [ForeverKids Logo]      POS-MAIN · Main Counter          │
│                                                             │
│           ┌───────────────────────────────────┐              │
│           │                                   │              │
│           │   Enter Your PIN                  │              │
│           │                                   │              │
│           │        ● ● ● ○ ○ ○                │              │
│           │                                   │              │
│           │   ┌─────┐ ┌─────┐ ┌─────┐        │              │
│           │   │  1  │ │  2  │ │  3  │        │              │
│           │   └─────┘ └─────┘ └─────┘        │              │
│           │   ┌─────┐ ┌─────┐ ┌─────┐        │              │
│           │   │  4  │ │  5  │ │  6  │        │              │
│           │   └─────┘ └─────┘ └─────┘        │              │
│           │   ┌─────┐ ┌─────┐ ┌─────┐        │              │
│           │   │  7  │ │  8  │ │  9  │        │              │
│           │   └─────┘ └─────┘ └─────┘        │              │
│           │   ┌─────┐ ┌─────┐ ┌─────┐        │              │
│           │   │  ⌫  │ │  0  │ │  ✓  │        │              │
│           │   └─────┘ └─────┘ └─────┘        │              │
│           │                                   │              │
│           │   ⚠ Wrong PIN (shakes on error)   │              │
│           │                                   │              │
│           └───────────────────────────────────┘              │
│                                                             │
│   Store: ForeverKids Main · 🟢 Online                       │
└─────────────────────────────────────────────────────────────┘
```

**Behavior**:
- PIN digits show as filled circles (●)
- Wrong PIN: input shakes, clears, shows red error for 3s
- 5 failed attempts: 30-second lockout
- Physical keyboard input also works (type digits directly)
- Auto-submit when PIN length reached (4 or 6 digits configurable)

---

### Screen 3 — Shift Open

```
┌───────────────────────────────────────────────────────┐
│                                                       │
│   👋 Welcome, Priya!                                  │
│   POS-MAIN · Main Counter · 24 Feb 2026               │
│                                                       │
│   ┌───────────────────────────────────────────────┐   │
│   │                                               │   │
│   │   Open Your Shift                             │   │
│   │                                               │   │
│   │   Count the cash currently in the drawer      │   │
│   │   and enter the amount below.                 │   │
│   │                                               │   │
│   │   Opening Cash Amount                         │   │
│   │   ┌──────────────────────────────┐            │   │
│   │   │  ₹  2,000.00                │   [Numpad] │   │
│   │   └──────────────────────────────┘            │   │
│   │                                               │   │
│   │   Quick amounts:                              │   │
│   │   [₹1,000] [₹2,000] [₹3,000] [₹5,000]      │   │
│   │                                               │   │
│   │           [Open Shift & Start Billing →]       │   │
│   │                                               │   │
│   └───────────────────────────────────────────────┘   │
│                                                       │
│   Previous shift: Closed at 9:00 PM yesterday         │
│   by Ravi · Closing cash: ₹18,450                     │
│                                                       │
└───────────────────────────────────────────────────────┘
```

---

### Screen 4 — Main Billing (Core POS Screen)

```
┌────────────────────────────────────────────────────────────────────────────┐
│  ┌─ Header Bar ──────────────────────────────────────────────────────────┐ │
│  │ 🏷 ForeverKids  │ POS-MAIN │ Priya (Cashier) │ Shift: 4h 20m │ 🟢   │ │
│  │                 │          │ [Lock 🔒] [⚙]   │ F1=Help       │Online│ │
│  └───────────────────────────────────────────────────────────────────────┘ │
│                                                                            │
│  ┌─ LEFT PANEL: Products ──────────────────┐ ┌─ RIGHT PANEL: Cart ──────┐ │
│  │                                         │ │                          │ │
│  │  🔍 [Search by name, SKU, barcode  F2]  │ │  Bill #POS-2026-0042     │ │
│  │  [📷 Scan F3]                           │ │  ────────────────────── │ │
│  │                                         │ │  👤 Walk-in  [+ Attach]  │ │
│  │  Category Tabs (horizontal scroll):     │ │  ────────────────────── │ │
│  │  [All] [Boys] [Girls] [Infant] [Party]  │ │                          │ │
│  │  [Ethnic] [Winter] [Footwear] [→]       │ │  ┌────────────────────┐ │ │
│  │                                         │ │  │ Red Frock Age-4    │ │ │
│  │  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐  │ │  │ [−] 1 [+]    ₹599 │ │ │
│  │  │ img  │ │ img  │ │ img  │ │ img  │  │ │  │ ✅ In stock (12)   │ │ │
│  │  │      │ │      │ │      │ │      │  │ │  └────────────────────┘ │ │
│  │  │ Red  │ │ Blue │ │Denim │ │Party │  │ │  ┌────────────────────┐ │ │
│  │  │Frock │ │Shirt │ │Jeans │ │Dress │  │ │  │ Blue Shirt Age-6   │ │ │
│  │  │₹599  │ │₹349  │ │₹799  │ │₹999  │  │ │  │ [−] 2 [+]    ₹698 │ │ │
│  │  │12 in │ │ 8 in │ │⚠ 3   │ │ 0 ✗  │  │ │  │ ✅ In stock (8)    │ │ │
│  │  │stock │ │stock │ │left  │ │ OOS  │  │ │  └────────────────────┘ │ │
│  │  └──────┘ └──────┘ └──────┘ └──────┘  │ │                          │ │
│  │                                         │ │  (auto-scrolls here ↑)  │ │
│  │  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐  │ │  ────────────────────── │ │
│  │  │ img  │ │ img  │ │ img  │ │ img  │  │ │  Subtotal       ₹1,297  │ │
│  │  │      │ │      │ │      │ │      │  │ │  CGST 2.5%        ₹32.43│ │
│  │  │Kurta │ │Tee   │ │Pjama │ │Shoes │  │ │  SGST 2.5%        ₹32.43│ │
│  │  │₹649  │ │₹249  │ │₹399  │ │₹599  │  │ │  Discount            —  │ │
│  │  │15 in │ │20 in │ │ 6 in │ │ 4 in │  │ │  ─────────────────────  │ │
│  │  │stock │ │stock │ │stock │ │stock │  │ │                          │ │
│  │  └──────┘ └──────┘ └──────┘ └──────┘  │ │  TOTAL         ₹1,361.86│ │
│  │                                         │ │  (in large bold font)   │ │
│  │  ← Page 1 of 12 →                      │ │                          │ │
│  │  (or infinite scroll with lazy load)    │ │  [🏷 Coupon] [% Disc]   │ │
│  │                                         │ │                          │ │
│  │  Showing 48 of 580 products             │ │  ┌────────────────────┐ │ │
│  │                                         │ │  │  ⏸ HOLD BILL  F8  │ │ │
│  └─────────────────────────────────────────┘ │  └────────────────────┘ │ │
│                                              │  ┌────────────────────┐ │ │
│                                              │  │ 💳 CHARGE ₹1,361  │ │ │
│                                              │  │     F10            │ │ │
│                                              │  └────────────────────┘ │ │
│                                              └──────────────────────────┘ │
│                                                                            │
│  ┌─ Bottom Action Bar ──────────────────────────────────────────────────┐ │
│  │ [+ New Bill F5] [↩ Returns Ctrl+R] [🔄 Exchange Ctrl+E]             │ │
│  │ [⏸ Held Bills F9 (2)] [👥 Customers] [📊 Reports] [☰ More]         │ │
│  └───────────────────────────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────────────────────────────┘
```

**UI Features — Product Panel (Left)**:
- Search: instant-as-you-type (debounced 200ms) with Meilisearch
- Barcode: USB scanner auto-types into search → auto-detects barcode format → instant lookup
- Camera scan: opens modal with camera viewfinder, decodes barcode via quagga2.js
- Category tabs: horizontal scroll with arrow buttons, highlight active
- Product cards: image (lazy load, placeholder on error), name, price, stock badge
- Stock badges: Green "12 in stock", Amber "⚠ 3 left", Red "✗ OOS" (dimmed card, tap shows "Out of Stock" toast)
- Out of stock products: visible but greyed out, cannot be added to cart
- Infinite scroll OR pagination (configurable in store settings)
- Pull-to-refresh on tablet (touch)
- Product card tap: adds 1 to cart with slide-in animation. Long-press: shows variant selector modal

**UI Features — Cart Panel (Right)**:
- Each item: product name, variant info, [−] qty [+] buttons, line total, stock info
- Qty input: tap the number to type exact quantity (up to stock limit)
- Swipe left on item: delete button slides in (mobile gesture)
- Auto-scroll to newly added item with green flash animation
- Subtotal, tax breakdown (CGST + SGST or IGST), discount, grand total
- Grand total: large 32px bold font, updates in real-time
- Coupon: text input modal, validates against shared coupon table
- Discount: modal with % or fixed amount, requires supervisor PIN if > 10%
- Hold Bill: saves cart to pos_held_bills, shows count badge on recall button
- Charge button: prominent, shows total amount, disabled if cart empty

**UI Features — Header**:
- Terminal name, cashier name + role badge
- Shift duration timer (counts up)
- Connection indicator (🟢 Online / 🔴 Offline — auto-detects)
- Lock button: locks screen, shows PIN entry (for bathroom breaks)
- Settings gear: sound on/off, receipt auto-print toggle, theme

**UI Features — Bottom Bar**:
- Held Bills button shows badge count of parked bills
- Returns/Exchange: opens respective flow
- Reports: opens reports panel (supervisor/manager only)
- More menu: settings, about, keyboard shortcuts, logout

---

### Screen 5 — Variant Selector Modal

```
┌───────────────────────────────────────────────┐
│  Select Variant — Red Frock                   │
│  ─────────────────────────────────────────── │
│                                               │
│  Size:                                        │
│  [Age 2-3] [Age 4-5 ✓] [Age 6-7] [Age 8-9]  │
│                                               │
│  Color:                                       │
│  [● Red ✓] [● Blue] [● Pink]                 │
│                                               │
│  Selected: Red Frock, Age 4-5                 │
│  Price: ₹599  │  Stock: 12 available          │
│                                               │
│  Quantity: [−] 1 [+]                          │
│                                               │
│  [Cancel]              [Add to Bill →]         │
└───────────────────────────────────────────────┘
```

---

### Screen 6 — Payment Screen

```
┌────────────────────────────────────────────────────────────────┐
│                                                                │
│  💳 Payment                          Bill #POS-2026-0042       │
│                                                                │
│  ┌─ Bill Summary ─────────────────────────────────────────┐    │
│  │  3 items · Subtotal ₹1,297 · Tax ₹64.86 · Disc -₹0    │    │
│  │  ═══════════════════════════════════════════════════    │    │
│  │  TOTAL PAYABLE                           ₹1,361.86     │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                │
│  Payment Method                                                │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────────┐  │
│  │   💵     │ │   💳     │ │   📱     │ │   📄            │  │
│  │   CASH   │ │   CARD   │ │   UPI    │ │   CREDIT NOTE   │  │
│  │          │ │          │ │          │ │                  │  │
│  └──────────┘ └──────────┘ └──────────┘ └──────────────────┘  │
│                                                                │
│  ╔═══ Cash Selected ════════════════════════════════════════╗  │
│  ║                                                          ║  │
│  ║  Amount Tendered                                         ║  │
│  ║  ₹ [  2,000.00                              ]           ║  │
│  ║                                                          ║  │
│  ║  Quick: [₹1,362] [₹1,400] [₹1,500] [₹2,000] [Exact]   ║  │
│  ║                                                          ║  │
│  ║  ┌───────────────────────────────────────────────┐       ║  │
│  ║  │  CHANGE TO RETURN                             │       ║  │
│  ║  │          ₹ 638.14                             │       ║  │
│  ║  │  (large, unmissable — green if positive)      │       ║  │
│  ║  └───────────────────────────────────────────────┘       ║  │
│  ║                                                          ║  │
│  ╚══════════════════════════════════════════════════════════╝  │
│                                                                │
│  [+ Add Split Payment]  ← allows multiple methods              │
│                                                                │
│  [← Back]                     [✅ CONFIRM PAYMENT   Enter]     │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

**Split Payment View** (when [+ Add Split Payment] clicked):
```
│  ╔═══ Split Payment ═══════════════════════════════════════╗  │
│  ║  Method        Amount         Ref         [Remove]      ║  │
│  ║  ─────────────────────────────────────────────────      ║  │
│  ║  💵 Cash      ₹[ 1,000.00 ]                    [✗]     ║  │
│  ║  📱 UPI       ₹[   361.86 ]  [Ref#___]         [✗]     ║  │
│  ║  ─────────────────────────────────────────────────      ║  │
│  ║  Total:       ₹1,361.86    Remaining: ₹0.00  ✅         ║  │
│  ║                                                          ║  │
│  ║  [+ Add Another Method]                                  ║  │
│  ╚══════════════════════════════════════════════════════════╝  │
```

**Credit Note View** (when Credit Note selected):
```
│  ╔═══ Credit Note ═════════════════════════════════════════╗  │
│  ║                                                          ║  │
│  ║  Credit Note Number                                      ║  │
│  ║  [  CN-ABCD1234  ] [🔍 Verify]                          ║  │
│  ║                                                          ║  │
│  ║  ┌─ Verified ✅ ──────────────────────────────────┐      ║  │
│  ║  │  Customer: Meera Shah                           │      ║  │
│  ║  │  Balance: ₹800.00                               │      ║  │
│  ║  │  Expires: 24 Mar 2026 (28 days left)            │      ║  │
│  ║  └─────────────────────────────────────────────────┘      ║  │
│  ║                                                          ║  │
│  ║  Apply: ₹800.00 (full balance)                           ║  │
│  ║  Remaining bill: ₹561.86 → pay via another method        ║  │
│  ║                                                          ║  │
│  ╚══════════════════════════════════════════════════════════╝  │
```

---

### Screen 7 — Sale Success

```
┌────────────────────────────────────────────────┐
│                                                │
│              ✅                                 │
│         (scale-bounce animation)               │
│                                                │
│         Payment Successful!                    │
│                                                │
│  Bill: POS-2026-0042                           │
│  Total: ₹1,361.86                              │
│  Method: Cash ₹1,000 + UPI ₹361.86            │
│  Change: ₹0.00                                 │
│  Cashier: Priya · POS-MAIN                     │
│                                                │
│  Send Receipt:                                 │
│  [🖨 Print] [📱 WhatsApp] [💬 SMS] [📧 Email] │
│                                                │
│  ─────────────────────────────────────────    │
│  [View Full Bill]     [✨ New Bill → F5]       │
│                                                │
│  Auto-redirects to new bill in 5s...           │
└────────────────────────────────────────────────┘
```

---

### Screen 8 — Return Flow (3 steps)

**Step 1: Find Original Bill**
```
┌──────────────────────────────────────────────────────┐
│  ↩ Return — Step 1 of 3: Find Original Purchase       │
│  ──────────────────────────────────────────────────  │
│                                                      │
│  Search by:                                          │
│  ┌──────────────┐ ┌───────────────┐ ┌─────────────┐ │
│  │ 🧾 Bill No.  │ │ 📱 Customer   │ │ 📷 Scan     │ │
│  │   (active)   │ │    Phone      │ │  Receipt    │ │
│  └──────────────┘ └───────────────┘ └─────────────┘ │
│                                                      │
│  Bill Number:                                        │
│  [ POS-2026-0035              ] [🔍 Search]          │
│                                                      │
│  ┌─ Found ────────────────────────────────────────┐  │
│  │  Bill #POS-2026-0035 · 20 Feb 2026, 3:42 PM    │  │
│  │  Customer: Meera Shah (+91-98765-43210)         │  │
│  │  Items: 3 · Total: ₹2,147 · Paid: Cash         │  │
│  │  Status: Completed · Within return window ✅     │  │
│  │                                                 │  │
│  │  [Select This Bill →]                           │  │
│  └─────────────────────────────────────────────────┘  │
│                                                      │
│  [← Back to Billing]                                  │
└──────────────────────────────────────────────────────┘
```

**Step 2: Select Items to Return**
```
┌──────────────────────────────────────────────────────┐
│  ↩ Return — Step 2 of 3: Select Items                 │
│  Bill #POS-2026-0035 · Meera Shah                     │
│  ──────────────────────────────────────────────────  │
│                                                      │
│  ┌────────────────────────────────────────────────┐  │
│  │  ☑ Red Frock Age-4                             │  │
│  │    Bought: 1 · ₹599 each                      │  │
│  │    Return qty: [−] 1 [+]                       │  │
│  │    Reason: [Wrong size ▼]                      │  │
│  │    Condition: [○ Unused with tags] [○ Used]    │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
│  ┌────────────────────────────────────────────────┐  │
│  │  ☐ Blue Shirt Age-6                            │  │
│  │    Bought: 2 · ₹349 each                      │  │
│  │    (unchecked — not being returned)            │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
│  ┌────────────────────────────────────────────────┐  │
│  │  ☑ Party Dress Age-5                           │  │
│  │    Bought: 1 · ₹999 each                      │  │
│  │    Return qty: [−] 1 [+]                       │  │
│  │    Reason: [Defective ▼]                       │  │
│  │    Condition: [○ Unused] [● Defective]         │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
│  ────────────────────────────────────────────────── │
│  Return Summary:                                     │
│  2 items · Refund amount: ₹1,598                     │
│                                                      │
│  [← Back]              [Next: Refund Method → ]      │
└──────────────────────────────────────────────────────┘
```

**Step 3: Refund Method**
```
┌──────────────────────────────────────────────────────┐
│  ↩ Return — Step 3 of 3: Refund                       │
│  Refund Amount: ₹1,598                                │
│  ──────────────────────────────────────────────────  │
│                                                      │
│  Refund via:                                         │
│  ┌───────────┐ ┌───────────┐ ┌─────────────────┐    │
│  │   💵      │ │   💳      │ │   📄           │    │
│  │   Cash    │ │  Original │ │  Credit Note   │    │
│  │  Refund   │ │  Method   │ │  (Recommended) │    │
│  └───────────┘ └───────────┘ └─────────────────┘    │
│                                                      │
│  ⚠ Cash refund > ₹1,000 requires manager approval   │
│                                                      │
│  ┌─ Manager Authorization ──────────────────────┐    │
│  │  Manager PIN: [● ● ● ●]                     │    │
│  │  Authorized by: Ravi Kumar (Manager)  ✅      │    │
│  └───────────────────────────────────────────────┘    │
│                                                      │
│  ────────────────────────────────────────────────── │
│  Items returned → back to inventory ✅               │
│  Refund ₹1,598 in cash → from cash drawer           │
│  Audit logged ✅                                     │
│                                                      │
│  [← Back]              [✅ Process Return]            │
└──────────────────────────────────────────────────────┘
```

---

### Screen 9 — Exchange Flow

Same as Return Steps 1–2, then diverges:

**Step 3: Select Replacement Items**
```
┌──────────────────────────────────────────────────────┐
│  🔄 Exchange — Step 3: Select New Items               │
│  Return value: ₹599 (Red Frock Age-4)                │
│  ──────────────────────────────────────────────────  │
│                                                      │
│  [Same product search/grid as main billing screen]   │
│                                                      │
│  New Items Selected:                                 │
│  ┌────────────────────────────────────────────────┐  │
│  │  Green Frock Age-5       ×1         ₹649       │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
│  ────────────────────────────────────────────────── │
│  Return Value:          ₹599                         │
│  New Items:             ₹649                         │
│  ─────────────                                       │
│  Customer Pays:         ₹50    ← positive: collect   │
│  (or)                                                │
│  Customer Gets CN:      ₹0    ← would show if neg.  │
│                                                      │
│  [← Back]        [Next: Collect ₹50 →]               │
└──────────────────────────────────────────────────────┘
```

---

### Screen 10 — Z-Report / Shift Close

```
┌──────────────────────────────────────────────────────┐
│  📊 Z-Report — Close Shift                           │
│  POS-MAIN · Priya · 24 Feb 2026                      │
│  Shift: 10:00 AM → 7:15 PM (9h 15m)                  │
│  ──────────────────────────────────────────────────  │
│                                                      │
│  SALES SUMMARY                                       │
│  ┌────────────────────────────────────────────────┐  │
│  │  Total Bills            42                     │  │
│  │  Gross Sales        ₹38,420                    │  │
│  │  Discounts Given     -₹1,200                   │  │
│  │  Returns Processed   -₹1,599  (3 returns)      │  │
│  │  ═════════════════════════════                  │  │
│  │  Net Sales          ₹35,621                    │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
│  PAYMENT BREAKDOWN                                   │
│  ┌────────────────────────────────────────────────┐  │
│  │  💵 Cash           ₹18,000   (50.5%)           │  │
│  │  💳 Card           ₹10,000   (28.1%)           │  │
│  │  📱 UPI             ₹6,021   (16.9%)           │  │
│  │  📄 Credit Notes    ₹1,600   (4.5%)            │  │
│  │  ═════════════════════════════                  │  │
│  │  Total Collected   ₹35,621                     │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
│  CASH RECONCILIATION                                 │
│  ┌────────────────────────────────────────────────┐  │
│  │  Opening Cash           ₹2,000                 │  │
│  │  + Cash Sales          ₹18,000                 │  │
│  │  − Cash Refunds          -₹599                 │  │
│  │  ═════════════════════════════                  │  │
│  │  Expected in Drawer    ₹19,401                 │  │
│  │                                                │  │
│  │  Actual Cash Count: ₹[  19,400  ]  ← enter    │  │
│  │                                                │  │
│  │  Variance: -₹1  (within tolerance ✅)          │  │
│  │  [Large variance flags in red ⚠]               │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
│  GST COLLECTED                                       │
│  ┌────────────────────────────────────────────────┐  │
│  │  CGST (2.5%)       ₹445.50                     │  │
│  │  SGST (2.5%)       ₹445.50                     │  │
│  │  CGST (6%)         ₹223.00                     │  │
│  │  SGST (6%)         ₹223.00                     │  │
│  │  Total GST       ₹1,337.00                     │  │
│  └────────────────────────────────────────────────┘  │
│                                                      │
│  Notes (optional):                                   │
│  [ All smooth today, no issues              ]        │
│                                                      │
│  [🖨 Print Z-Report]         [✅ Close Shift]        │
│                                                      │
└──────────────────────────────────────────────────────┘
```

---

### Screen 11 — Held Bills

```
┌──────────────────────────────────────────────┐
│  ⏸ Held Bills (3 parked)                    │
│  ────────────────────────────────────────── │
│                                              │
│  ┌──────────────────────────────────────┐    │
│  │  Hold #1 · 2:15 PM · 30 min ago      │    │
│  │  3 items · ₹1,297                     │    │
│  │  Customer: Walk-in                    │    │
│  │  Note: "Customer checking other items"│    │
│  │  [Resume →]              [🗑 Delete]  │    │
│  └──────────────────────────────────────┘    │
│                                              │
│  ┌──────────────────────────────────────┐    │
│  │  Hold #2 · 2:40 PM · 5 min ago       │    │
│  │  1 item · ₹599                        │    │
│  │  Customer: Meera Shah                 │    │
│  │  [Resume →]              [🗑 Delete]  │    │
│  └──────────────────────────────────────┘    │
│                                              │
│  Max 5 held bills per cashier                │
│  [← Back to Billing]                         │
└──────────────────────────────────────────────┘
```

---

## 7. EPICS & USER STORIES

### EPIC 1: POS Authentication & Terminal Management

---

**Story 1.1: Terminal Registration**
> As an admin, I can register POS terminals in the admin panel so that only authorized devices can access the POS system.

**Tasks:**
- [ ] Admin UI: Stores → Terminals list (CRUD) in admin panel
- [ ] Generate unique device_id on creation
- [ ] POST `/pos/register-device` endpoint — validates device_id, returns confirmation
- [ ] Store device_id in localStorage on the POS browser
- [ ] Middleware `PosAuthenticate` — checks X-POS-Device header against active pos_registers
- [ ] Reject requests from unknown/inactive devices with clear error

**Acceptance Criteria:**
- [ ] Admin can create terminal with name and store assignment
- [ ] Admin can activate/deactivate terminals
- [ ] Deactivated terminal is immediately blocked from POS
- [ ] Device remembers registration across browser restarts
- [ ] Unregistered device shows "Contact admin to register this terminal"

---

**Story 1.2: Staff PIN Login**
> As a cashier, I can log into the POS with my PIN so I can start my shift quickly.

**Tasks:**
- [ ] PIN login screen UI (numpad, filled dots, shake animation on error)
- [ ] POST `/pos/login` — validates PIN (bcrypt hash) + store assignment + is_active
- [ ] Create Laravel session with staff_id, store_id, register_id
- [ ] Redirect to shift-open or billing screen (based on shift status)
- [ ] Lockout after 5 failed attempts (30-second cooldown)
- [ ] Physical keyboard digit input support (not just click)
- [ ] Auto-submit when PIN length matches configured length

**Acceptance Criteria:**
- [ ] Correct PIN → logged in within 1 second
- [ ] Wrong PIN → shake animation, error message, clears input
- [ ] 5 wrong attempts → 30-second lockout with countdown
- [ ] Inactive staff member → "Account deactivated. Contact manager."
- [ ] Staff from different store → "Not authorized for this terminal"
- [ ] Session expires after 8 hours
- [ ] Keyboard number keys work (type 1-2-3-4 to enter PIN)

---

**Story 1.3: Idle Lock Screen**
> As a cashier, the POS auto-locks after 15 minutes of inactivity to prevent unauthorized access.

**Tasks:**
- [ ] Alpine.js idle timer (resets on any interaction: click, keypress, touch)
- [ ] After 15 min idle → show lock overlay with PIN input
- [ ] Correct PIN → resume exactly where left off (cart preserved)
- [ ] Different staff PIN → switch user (but only if same store)
- [ ] Lock button in header for manual lock

**Acceptance Criteria:**
- [ ] Screen locks after exactly 15 minutes of no interaction
- [ ] Current cart and state preserved through lock/unlock
- [ ] Manual lock via button works instantly
- [ ] Lock screen shows "POS Locked — Enter PIN to resume"

---

**Story 1.4: Staff Logout**
> As a cashier, I can log out when my shift is done.

**Tasks:**
- [ ] Logout button in ☰ menu
- [ ] If shift is open: warn "Your shift is still open. Close shift before logging out?"
- [ ] POST `/pos/logout` — destroys session
- [ ] Redirect to PIN login screen
- [ ] Cart data cleared on logout

**Acceptance Criteria:**
- [ ] Logout with open shift → confirmation prompt
- [ ] Logout with closed shift → immediate logout
- [ ] All session data cleared
- [ ] Cannot access POS routes after logout

---

### EPIC 2: Shift Management

---

**Story 2.1: Open Shift**
> As a cashier, I must open a shift before I can process any sales.

**Tasks:**
- [ ] Shift-open screen UI with opening cash amount input
- [ ] Quick amount buttons (₹1000, ₹2000, ₹3000, ₹5000)
- [ ] POST `/pos/shift/open` — creates staff_shifts record
- [ ] Middleware `PosShiftRequired` — blocks billing routes if no open shift
- [ ] Show last shift info (who closed it, when, closing cash)

**Acceptance Criteria:**
- [ ] Cannot access billing without open shift
- [ ] Opening cash amount stored and visible in Z-report later
- [ ] Previous shift info shown for context
- [ ] Cannot open shift if another shift already open for this terminal
- [ ] Quick amount buttons fill the input field

---

**Story 2.2: Close Shift (Z-Report)**
> As a cashier, I can close my shift and see a complete summary of all transactions.

**Tasks:**
- [ ] Z-Report screen UI showing all summary sections
- [ ] Aggregate: total bills, gross sales, discounts, returns, net sales
- [ ] Payment breakdown: cash, card, UPI, credit notes (amounts + percentages)
- [ ] Cash reconciliation: opening + cash sales − cash refunds = expected. Cashier enters actual count.
- [ ] Variance calculation with tolerance threshold (configurable, default ₹50)
- [ ] GST collected breakdown by rate
- [ ] POST `/pos/shift/close` — updates staff_shifts with closing_cash and register_summary JSON
- [ ] Print Z-report option
- [ ] Redirect to login screen after close

**Acceptance Criteria:**
- [ ] All numbers match actual database records exactly
- [ ] Cash variance > ₹50 flagged in red with explanation required
- [ ] Z-Report can be printed (thermal or PDF)
- [ ] After close, cannot create new sales (redirected to login)
- [ ] register_summary JSON captures complete shift data for later reporting

---

### EPIC 3: Product Search & Barcode

---

**Story 3.1: Search Products by Name/SKU**
> As a cashier, I can quickly search products by typing to find items for billing.

**Tasks:**
- [ ] Search input with debounced 200ms API call
- [ ] GET `/pos/products/search?q=...` — searches name, SKU, barcode via Meilisearch
- [ ] Return: id, name, sku, price, mrp, image, stock_quantity, variants count
- [ ] Results show as product cards in the grid
- [ ] Empty state: "No products found for 'xyz'"
- [ ] F2 shortcut focuses search input

**Acceptance Criteria:**
- [ ] Results appear within 300ms of typing
- [ ] Minimum 2 characters to trigger search
- [ ] Shows stock quantity on each result
- [ ] Out-of-stock items shown but greyed out
- [ ] Pressing Enter on single result adds it to cart
- [ ] Search cleared when Escape pressed

---

**Story 3.2: Barcode Scan (USB Scanner)**
> As a cashier, I can scan a product barcode with a USB scanner to instantly add it to the bill.

**Tasks:**
- [ ] USB barcode scanners type characters rapidly then Enter — detect this pattern
- [ ] If search input receives rapid character input + Enter within 100ms: treat as barcode
- [ ] GET `/pos/products/barcode/{code}` — lookup in products.barcode, product_variants.barcode, barcodes table
- [ ] Auto-add to cart on successful lookup (or increment qty if already in cart)
- [ ] Beep sound on successful scan (configurable)
- [ ] Error sound + toast on unknown barcode

**Acceptance Criteria:**
- [ ] USB scanner barcode → product added within 200ms
- [ ] Unknown barcode → "Product not found for barcode XXXXX" error
- [ ] Scanning same barcode twice → qty incremented
- [ ] Works with EAN-13, EAN-8, UPC, Code128 formats

---

**Story 3.3: Camera Barcode Scan**
> As a cashier using a tablet, I can scan barcodes using the device camera.

**Tasks:**
- [ ] Camera scan button (F3) opens full-screen camera modal
- [ ] Use quagga2.js for barcode detection from camera feed
- [ ] Auto-close camera after successful scan
- [ ] Same lookup flow as USB scanner
- [ ] Handle camera permission denied gracefully

**Acceptance Criteria:**
- [ ] Camera opens within 1 second
- [ ] Detects barcode within 2 seconds of pointing at it
- [ ] Works with rear and front camera (toggle available)
- [ ] If camera permission denied: show instructions to enable
- [ ] Auto-closes modal after scan, adds product to cart

---

**Story 3.4: Browse Products by Category**
> As a cashier, I can browse products by category using tabs for quick access.

**Tasks:**
- [ ] GET `/pos/categories` — returns active categories with product counts
- [ ] Horizontal scrollable tab bar above product grid
- [ ] "All" tab selected by default
- [ ] GET `/pos/products?category={id}&page={n}` — paginated product list
- [ ] Infinite scroll or pagination (configurable via store settings)
- [ ] Each product card: image, name, price, stock badge

**Acceptance Criteria:**
- [ ] Category tabs load within 500ms
- [ ] Switching category refreshes product grid instantly
- [ ] Product images lazy-load (placeholder shown while loading)
- [ ] Out-of-stock products visible but cannot be added to cart
- [ ] Low stock shows amber "⚠ 3 left" badge

---

### EPIC 4: Cart & Billing

---

**Story 4.1: Add Products to Cart**
> As a cashier, I can add products to the bill by tapping product cards.

**Tasks:**
- [ ] POST `/pos/cart/add` — add product (with optional variant_id) to server-side POS cart
- [ ] Validate: product active, in stock, quantity available
- [ ] If product has variants → show variant selector modal first
- [ ] If already in cart → increment quantity
- [ ] Cart item slide-in animation with green flash
- [ ] Auto-scroll cart to newly added item
- [ ] Beep/haptic feedback on add

**Acceptance Criteria:**
- [ ] Tap product card → added to cart within 200ms
- [ ] Product with 0 stock → shows toast "Out of stock", not added
- [ ] Adding qty beyond stock → shows toast "Only X available"
- [ ] Cart auto-scrolls to new item
- [ ] Product with variants → variant selector modal shown first
- [ ] Cart total updates in real-time

---

**Story 4.2: Modify Cart Items**
> As a cashier, I can adjust quantities, remove items, and edit the bill before payment.

**Tasks:**
- [ ] Quantity controls: [−] [qty] [+] buttons per item
- [ ] Tap qty number to type exact quantity
- [ ] Swipe-left to reveal delete button (touch gesture)
- [ ] Delete key removes currently selected item
- [ ] PATCH `/pos/cart/{item}` — update qty
- [ ] DELETE `/pos/cart/{item}` — remove item
- [ ] DELETE `/pos/cart` — clear entire cart (with confirmation)

**Acceptance Criteria:**
- [ ] Quantity change → total recalculates instantly
- [ ] Cannot set qty to 0 (must delete instead)
- [ ] Cannot exceed available stock
- [ ] Cart clear requires confirmation: "Remove all 5 items?"
- [ ] Empty cart shows: "Scan a product or search to start billing"

---

**Story 4.3: Apply Discount**
> As a cashier/supervisor, I can apply discounts at item or bill level.

**Tasks:**
- [ ] Item-level: tap item → discount input (% or ₹ fixed)
- [ ] Bill-level: [% Disc] button → discount modal
- [ ] If discount > 10%: require manager PIN
- [ ] Store discount reason (optional text)
- [ ] PATCH `/pos/cart/discount` — apply with optional authorization
- [ ] Show both original price and discounted price on each item

**Acceptance Criteria:**
- [ ] Cashier can apply ≤ 10% item discount without override
- [ ] Discount > 10% → manager PIN modal appears
- [ ] Invalid manager PIN → discount rejected
- [ ] Discount shows as strikethrough original + new price
- [ ] Bill total reflects all discounts accurately
- [ ] Discount reason logged in audit trail

---

**Story 4.4: Apply Coupon Code**
> As a cashier, I can apply a coupon code to the bill.

**Tasks:**
- [ ] [🏷 Coupon] button → text input modal
- [ ] POST `/pos/cart/coupon` — validates against existing coupon table
- [ ] Reuse existing Coupon model's `isValid()`, `calculateDiscount()` methods
- [ ] Show applied coupon name + discount amount
- [ ] Remove coupon button

**Acceptance Criteria:**
- [ ] Valid coupon → discount applied, coupon name shown
- [ ] Invalid/expired coupon → clear error message
- [ ] Coupon minimum order not met → "Minimum order ₹X required"
- [ ] Only one coupon at a time (applying new one replaces old)

---

**Story 4.5: Attach Customer to Bill**
> As a cashier, I can attach a customer to the bill for tracking and loyalty.

**Tasks:**
- [ ] [+ Attach] button near "Walk-in Customer"
- [ ] Search by phone number or name → GET `/pos/customers/search?q=...`
- [ ] Quick-create new customer if not found (name + phone, minimal form)
- [ ] Customer displayed on bill with name and phone
- [ ] Optional: show customer's recent purchases and loyalty points

**Acceptance Criteria:**
- [ ] Search by phone returns results within 500ms
- [ ] Can create new customer with just name + phone
- [ ] Existing website customers found by same phone number
- [ ] Detach customer button to revert to "Walk-in"
- [ ] Customer ID saved on the pos_sale record

---

**Story 4.6: Hold & Resume Bills**
> As a cashier, I can hold (park) the current bill and resume it later.

**Tasks:**
- [ ] [⏸ Hold F8] button — saves current cart to pos_held_bills table
- [ ] Optional hold note ("Customer checking other items")
- [ ] [Held Bills F9] button — shows list of held bills
- [ ] Resume: loads held bill back into cart
- [ ] Delete held bill (supervisor permission for others' held bills)
- [ ] Max 5 held bills per cashier (configurable)
- [ ] Held bills expire after 24 hours

**Acceptance Criteria:**
- [ ] Hold preserves all items, quantities, customer, discounts
- [ ] Held bill count shown as badge on button
- [ ] Resume restores exact state including customer
- [ ] Cannot hold an empty bill
- [ ] Current cart cleared when resuming a held bill (warn if non-empty)
- [ ] Held bills from other cashiers visible but not resumable without permission

---

### EPIC 5: Payment Processing

---

**Story 5.1: Cash Payment**
> As a cashier, I can accept cash payment with automatic change calculation.

**Tasks:**
- [ ] Payment screen with cash selected
- [ ] Amount tendered input (numpad or keyboard)
- [ ] Quick amount buttons: exact, round up to ₹10, common amounts
- [ ] Change calculation: large, prominent display (green)
- [ ] Cannot confirm if amount < total
- [ ] On confirm: POST `/pos/sale/complete` with payment_method=cash, paid_amount
- [ ] Open cash drawer signal (if supported)

**Acceptance Criteria:**
- [ ] Change calculated correctly to paisa
- [ ] Quick amounts contextually correct (always ≥ total)
- [ ] Confirm button disabled until amount ≥ total
- [ ] Sale created, stock deducted, receipt generated
- [ ] Cash drawer opens on payment confirmation

---

**Story 5.2: Card Payment**
> As a cashier, I can record a card payment with optional reference number.

**Tasks:**
- [ ] Card payment view with optional reference/last-4-digits input
- [ ] Amount auto-filled as total (card must be exact)
- [ ] On confirm: same sale completion flow

**Acceptance Criteria:**
- [ ] Card amount always equals total (no split needed)
- [ ] Reference number optional but encouraged
- [ ] Sale completes identically to cash

---

**Story 5.3: UPI Payment**
> As a cashier, I can record a UPI payment.

**Tasks:**
- [ ] UPI view: amount (auto-filled), optional UPI reference
- [ ] Future: QR code display for customer scan-to-pay
- [ ] Cashier manually confirms "Payment Received"

**Acceptance Criteria:**
- [ ] UPI amount editable (in case of partial in split)
- [ ] Reference number captured for reconciliation
- [ ] Sale completes after cashier confirmation

---

**Story 5.4: Split Payment**
> As a cashier, I can accept payment across multiple methods (e.g., cash + UPI).

**Tasks:**
- [ ] [+ Add Split Payment] button on payment screen
- [ ] Each split line: method selector + amount + reference
- [ ] Running total: "Paid: ₹X / Remaining: ₹Y"
- [ ] Cannot confirm until remaining = ₹0
- [ ] All payment lines saved to pos_payments table

**Acceptance Criteria:**
- [ ] Up to 4 payment methods per bill
- [ ] Remaining amount auto-fills into last method
- [ ] Total of all methods must exactly equal bill total
- [ ] Each method saved separately in payment_details JSON
- [ ] Receipt shows all payment methods used

---

**Story 5.5: Credit Note Payment**
> As a cashier, I can apply a credit note as payment.

**Tasks:**
- [ ] Credit Note tab in payment screen
- [ ] Enter CN number → POST `/pos/credit-note/validate`
- [ ] Show: customer name, balance, expiry
- [ ] Apply full balance or partial amount
- [ ] If CN < total: remaining must be paid via another method (split)
- [ ] If CN > total: remaining balance stays on credit note
- [ ] On confirm: CreditNote::redeem() called

**Acceptance Criteria:**
- [ ] Invalid CN → "Credit note not found"
- [ ] Expired CN → "This credit note expired on DD/MM/YYYY"
- [ ] Fully used CN → "This credit note has no remaining balance"
- [ ] Partial CN → auto-adds split payment for remainder
- [ ] Customer mismatch (if CN tied to customer) → warning
- [ ] After use: CN balance updated correctly

---

### EPIC 6: Returns & Exchanges

---

**Story 6.1: Process Return**
> As a cashier, I can process a return against an original POS sale.

**Tasks:**
- [ ] Return screen: search by bill number, customer phone, or scan receipt barcode
- [ ] Show original bill with all items
- [ ] Select items + qty to return (checkbox + qty selector per item)
- [ ] Return reason dropdown (wrong size, defective, changed mind, wrong item, other)
- [ ] Item condition radio (unused with tags, used, defective)
- [ ] Refund method selection: cash, original method, credit note
- [ ] Cash refund > threshold (₹1000): manager PIN required
- [ ] POST `/pos/return` — creates pos_returns + return items
- [ ] Inventory: stock += returned qty (both layers)
- [ ] InventoryMovement: reference_type = 'return'

**Acceptance Criteria:**
- [ ] Only completed sales within return window can be returned
- [ ] Cannot return more qty than originally purchased
- [ ] Cannot return items already returned
- [ ] Refund amount calculated based on original item price (not current price)
- [ ] Cash refund > ₹1000 → manager authorization required
- [ ] Stock restored to inventory immediately
- [ ] Return receipt generated
- [ ] Audit log: return details + authorizer if applicable

---

**Story 6.2: Process Exchange**
> As a cashier, I can process an exchange: return items and sell new items in one transaction.

**Tasks:**
- [ ] Exchange flow: return item selection (same as 6.1 steps 1-2)
- [ ] After selecting return items → show product search for replacement
- [ ] Calculate difference: new items total − return value
- [ ] If positive: customer pays the difference (payment screen)
- [ ] If negative: issue credit note for the surplus
- [ ] If equal: no payment needed
- [ ] Single transaction: pos_returns (type=exchange) + new pos_sale (linked)

**Acceptance Criteria:**
- [ ] Return value based on original purchase price
- [ ] New items can be any product (not just same product)
- [ ] Positive difference → payment screen for the balance
- [ ] Negative difference → automatic credit note issued
- [ ] Zero difference → complete immediately
- [ ] Both return and new sale linked in database
- [ ] Inventory: old items restored, new items deducted

---

### EPIC 7: Credit Notes

---

**Story 7.1: Issue Credit Note**
> As a cashier, the system issues credit notes automatically on eligible returns.

**Tasks:**
- [ ] Auto-generated when refund_method = 'credit_note' on return
- [ ] Unique number: CN-XXXX-NNNN (store prefix + sequential)
- [ ] HMAC signature for anti-forgery verification
- [ ] QR code generated (encodes CN number + signature)
- [ ] Printed on return receipt
- [ ] Linked to customer (if customer was on original sale)
- [ ] Configurable expiry: default 90 days

**Acceptance Criteria:**
- [ ] CN number is unique across system
- [ ] QR code scannable and verifiable
- [ ] Cannot be manually created by cashier (only via return/exchange)
- [ ] Customer receives CN details on receipt
- [ ] CN visible in customer's online account (if linked)
- [ ] Admin can view all CNs and their usage history

---

**Story 7.2: Redeem Credit Note**
> As a cashier, I can verify and apply a credit note during payment.

Tasks and acceptance criteria covered in Story 5.5.

---

**Story 7.3: Credit Note Online Redemption**
> As a customer, I can use my POS-issued credit note on the online store.

**Tasks:**
- [ ] Checkout page: "Have a credit note?" input field
- [ ] Same validation API used by POS
- [ ] Applied as payment method during online checkout
- [ ] Partial use: remaining balance preserved

**Acceptance Criteria:**
- [ ] Same CN works at POS and online
- [ ] Cannot exceed CN balance
- [ ] Partially used CN has correct remaining balance
- [ ] Fully used CN cannot be used again

---

### EPIC 8: GST & Tax

---

**Story 8.1: GST Calculation on Bills**
> As the system, GST is automatically calculated on every sale based on product HSN codes.

**Tasks:**
- [ ] Each product has hsn_code and tax_rate in DB (already exists)
- [ ] Tax service: calculates CGST+SGST (intra-state) or IGST (inter-state)
- [ ] Default: intra-state (store and customer in same state)
- [ ] Line-item tax calculation: (price × qty − discount) × tax_rate
- [ ] Split into CGST (half) + SGST (half) or full IGST
- [ ] Tax displayed in cart and on receipt

**Acceptance Criteria:**
- [ ] Tax calculated correctly per GST rules
- [ ] CGST = SGST = tax_rate / 2 for intra-state
- [ ] IGST = tax_rate for inter-state
- [ ] Tax shown per item and as total on receipt
- [ ] Receipt shows HSN code, tax rate, CGST, SGST amounts
- [ ] Rounding follows GST rules (round to nearest paisa)

---

**Story 8.2: GSTR-1 Report Export**
> As a manager, I can export GSTR-1 data for the selected period.

**Tasks:**
- [ ] Report screen: date range selector
- [ ] Aggregate: B2B invoices (GSTIN-wise), B2C invoices (state-wise)
- [ ] HSN summary with quantity, taxable value, tax amounts
- [ ] Export as Excel (using existing phpoffice/phpspreadsheet)
- [ ] Include POS + online sales with source column

**Acceptance Criteria:**
- [ ] Data matches government GSTR-1 format
- [ ] Includes both POS and online sales
- [ ] HSN-wise summary accurate
- [ ] Export downloads as .xlsx file

---

**Story 8.3: GSTR-3B Summary**
> As a manager, I can view monthly GSTR-3B tax liability summary.

**Tasks:**
- [ ] Monthly summary: outward supplies, inward supplies (from purchases)
- [ ] Tax liability: CGST, SGST, IGST totals
- [ ] Input tax credit (from vendor purchases, if POS tracks them)
- [ ] Net payable

**Acceptance Criteria:**
- [ ] Monthly totals match individual transaction sums
- [ ] Clearly shows tax collected vs input credit vs net payable

---

### EPIC 9: Reports & Analytics

---

**Story 9.1: Daily Sales Dashboard**
> As a manager, I can see today's sales overview across all terminals.

**Tasks:**
- [ ] Live dashboard: total sales, bills count, average bill value
- [ ] Per-terminal breakdown
- [ ] Per-cashier breakdown
- [ ] Top selling products today
- [ ] Hourly sales chart (bar chart)

**Acceptance Criteria:**
- [ ] Data refreshes every 30 seconds (or on-demand refresh)
- [ ] Accessible to supervisor and manager roles only
- [ ] Shows POS sales only (not online — separate section for combined)

---

**Story 9.2: Product Performance Report**
> As a manager, I can see which products sell best/worst.

**Tasks:**
- [ ] Date range selector
- [ ] Top N sellers by quantity and by revenue
- [ ] Bottom N sellers (slow-moving stock)
- [ ] Category-wise breakdown
- [ ] Export to Excel

---

**Story 9.3: Cashier Performance Report**
> As a manager, I can see each cashier's sales, speed, and accuracy.

**Tasks:**
- [ ] Per-cashier: total sales, bills count, average bill value
- [ ] Returns processed, voids
- [ ] Discounts given (amount + count)
- [ ] Cash variance history from shift closes
- [ ] Average time per transaction (from shift data)

---

**Story 9.4: Stock Report**
> As a manager, I can see current stock levels and movement history.

**Tasks:**
- [ ] Current stock: all products with quantities
- [ ] Low stock items (below threshold)
- [ ] Out of stock items
- [ ] Movement history: who, what, when, qty change, reason
- [ ] Filter by date range, product, movement type

---

### EPIC 10: Receipts & Printing

---

**Story 10.1: Receipt Generation**
> As the system, a GST-compliant receipt is generated for every sale.

**Tasks:**
- [ ] Receipt template (already designed in doc spec)
- [ ] Store name, address, GSTIN, phone
- [ ] Sale number, date, cashier name
- [ ] Line items with HSN, qty, price, tax breakup
- [ ] Payment method(s), paid amount, change
- [ ] Barcode/QR of sale number (for return lookups)
- [ ] Return policy footer
- [ ] HTML template for screen preview
- [ ] PDF generation for download/email

**Acceptance Criteria:**
- [ ] Receipt matches GST invoice requirements
- [ ] Barcode on receipt scannable for returns
- [ ] PDF downloads correctly
- [ ] HTML preview matches print output

---

**Story 10.2: Thermal Printer Support**
> As a cashier, I can print receipts on a thermal printer.

**Tasks:**
- [ ] ESC/POS receipt formatting (80mm width, monospace)
- [ ] Print via browser Print API (window.print with receipt stylesheet)
- [ ] Or: companion Print Bridge service (future — local WebSocket server)
- [ ] Auto-print toggle in settings (print on every sale automatically)

**Acceptance Criteria:**
- [ ] Receipt prints correctly on 80mm thermal printer
- [ ] Text properly wrapped, no cut-off
- [ ] Barcode prints and is scannable
- [ ] Auto-print works when enabled

---

**Story 10.3: Digital Receipt**
> As a cashier, I can send receipts via WhatsApp/SMS/Email.

**Tasks:**
- [ ] Success screen: send options (WhatsApp, SMS, Email)
- [ ] WhatsApp: generates wa.me link with pre-filled receipt PDF URL
- [ ] SMS: sends receipt link via SMS (using existing notification system)
- [ ] Email: sends receipt as email attachment
- [ ] Only available when customer attached to bill (has phone/email)

**Acceptance Criteria:**
- [ ] WhatsApp opens with receipt link
- [ ] SMS delivered within 30 seconds
- [ ] Email delivered with PDF attachment
- [ ] Options disabled for walk-in (no contact info)

---

### EPIC 11: Offline Mode

---

**Story 11.1: Offline Sale Processing**
> As a cashier, I can complete sales even when the internet is down.

**Tasks:**
- [ ] Detect offline: navigator.onLine + failed API healthcheck
- [ ] Switch to offline mode: 🔴 "Offline" indicator in header
- [ ] Cache product catalog in IndexedDB (sync on login/shift open)
- [ ] Cart managed locally in Alpine.js state
- [ ] Sales saved to IndexedDB queue with all details
- [ ] Temporary sale numbers: OFFLINE-{timestamp}-{seq}
- [ ] Warning: "Sales will be synced when connection resumes"

**Acceptance Criteria:**
- [ ] POS continues to work without internet
- [ ] Product search works from local cache
- [ ] Stock quantities may be stale (show warning)
- [ ] Sales queued locally with full data
- [ ] Receipt shows "OFFLINE" watermark
- [ ] No data loss

---

**Story 11.2: Sync Queue**
> As the system, offline sales are automatically synced when connection restores.

**Tasks:**
- [ ] Detect online: navigator.onLine event + API healthcheck
- [ ] Process queue: POST each pending sale to server
- [ ] Handle conflicts: stock insufficient → flag for manager review
- [ ] Update local sale numbers with server-assigned numbers
- [ ] Show sync progress: "Syncing 3 of 5 sales..."
- [ ] Retry failed syncs (3 attempts with exponential backoff)

**Acceptance Criteria:**
- [ ] All offline sales synced within 60 seconds of reconnection
- [ ] Stock conflicts flagged (not silently dropped)
- [ ] Sale numbers corrected to server format
- [ ] Duplicate prevention (same offline sale never synced twice)
- [ ] Sync status visible to cashier

---

### EPIC 12: Security & Audit

---

**Story 12.1: Audit Log**
> As the system, every sensitive POS action is logged for accountability.

**Tasks:**
- [ ] pos_audit_log table (see migration spec)
- [ ] Log on: sale created, sale voided, return processed, discount applied, price overridden, shift opened/closed, cash movement, held bill deleted
- [ ] Each log: action, entity, staff_id, authorized_by (if override), terminal, IP, old_value, new_value, timestamp
- [ ] Admin panel: audit log viewer with filters

**Acceptance Criteria:**
- [ ] Every sensitive action creates audit entry
- [ ] Logs cannot be modified or deleted (append-only)
- [ ] Admin can filter by: date, staff, terminal, action type
- [ ] Manager overrides clearly show both cashier and authorizer

---

**Story 12.2: Manager Override PIN**
> As a cashier, I can request manager authorization for restricted actions.

**Tasks:**
- [ ] Reusable Alpine.js "Manager PIN" modal component
- [ ] POST `/pos/authorize` — validates PIN belongs to supervisor/manager at same store
- [ ] Returns staff info for display: "Authorized by: Ravi Kumar"
- [ ] Used by: large discount, cash refund > threshold, void, price override
- [ ] Timeout: authorization valid for 60 seconds (single use)

**Acceptance Criteria:**
- [ ] Any supervisor/manager PIN works (not just specific person)
- [ ] Wrong PIN → shake + error (same as login)
- [ ] Authorization is one-time (must re-enter for next restricted action)
- [ ] Audit log captures both cashier and authorizer

---

### EPIC 13: Admin POS Management

---

**Story 13.1: POS Dashboard in Admin Panel**
> As an admin, I can see live POS status and performance from the admin panel.

**Tasks:**
- [ ] Admin → POS section with dashboard
- [ ] Live: active terminals, current cashiers, open shifts
- [ ] Today's totals: sales, returns, net across all terminals
- [ ] Alert: terminals offline, high cash variance, void activity

---

**Story 13.2: Terminal Management**
> As an admin, I can manage POS terminals (create, edit, deactivate, remote wipe).

**Tasks:**
- [ ] CRUD for pos_registers in admin panel
- [ ] Activate/deactivate toggle
- [ ] Remote wipe: revoke device token → terminal forced to re-register

---

**Story 13.3: Cashier/Staff Management for POS**
> As an admin, I can manage POS staff accounts and PINs.

**Tasks:**
- [ ] Create/edit staff with POS PIN
- [ ] Assign role (cashier/supervisor/manager)
- [ ] Assign to store
- [ ] Reset PIN
- [ ] Deactivate account

---

---

## 8. FUNCTIONAL FLOWS

### Flow: Complete Sale (DB Operations)

```
1. Cashier hits CHARGE → payment screen
2. Selects payment method(s), enters amounts
3. Hits CONFIRM PAYMENT
4. Server-side (single DB transaction):
   a. Validate all items still in stock
   b. INSERT pos_sales (sale_number, totals, payment_method, payment_details)
   c. INSERT pos_sale_items (one per line item, with tax breakup)
   d. For each item:
      - product.stock_quantity -= qty (or variant.stock_quantity)
      - inventory_stocks.deduct(qty) for store's location
      - INSERT inventory_movements (reference_type='pos_sale', qty out)
   e. UPDATE staff_shifts.register_summary (increment counters)
   f. If customer attached: UPDATE customers.total_orders, total_spent
   g. If coupon used: INCREMENT coupon.times_used
   h. INSERT pos_audit_log (sale_created)
5. Return: sale data + receipt HTML
6. Client: show success screen, trigger print if auto-print enabled
```

### Flow: Return (DB Operations)

```
1. Cashier selects items to return from original bill
2. Selects refund method, gets manager approval if needed
3. Hits PROCESS RETURN
4. Server-side (single DB transaction):
   a. Validate: original sale exists, items not already returned, within window
   b. INSERT pos_returns (return_number, original_sale_id, refund_method, reason)
   c. INSERT pos_return_items (one per returned item)
   d. For each returned item:
      - product.stock_quantity += qty (back to inventory)
      - inventory_stocks.add(qty) for store's location
      - INSERT inventory_movements (reference_type='return', qty in)
   e. If refund_method = 'credit_note':
      - INSERT credit_notes (amount, customer, expiry, HMAC signature)
   f. If refund_method = 'cash':
      - INSERT pos_cash_movements (type='refund', amount negative)
   g. UPDATE original pos_sale status if fully returned
   h. INSERT pos_audit_log (return_processed, authorized_by if applicable)
5. Return: return data + credit note details if issued
```

### Flow: Exchange (DB Operations)

```
1. Steps 1-4 from Return flow execute (items returned, stock restored)
2. New items added via billing flow:
   a. INSERT new pos_sale (linked to return via exchange_bill_id)
   b. INSERT pos_sale_items for new items
   c. Stock deducted for new items
3. Difference calculation:
   - If new > return value: collect payment (saved to pos_payments)
   - If return > new value: issue credit note for difference
   - If equal: no payment
4. Both transactions linked and logged
```

### Flow: Credit Note Lifecycle

```
CREATION:
  Return processed → refund_method='credit_note'
  → credit_notes INSERT: amount, remaining_amount, status='active'
  → QR code generated with HMAC
  → Printed on return receipt

VALIDATION (at POS or online):
  Input: CN number (typed or QR scanned)
  → DB lookup: exists? active? not expired? balance > 0?
  → HMAC verification (if scanned QR)
  → Customer match (if tied to customer)
  → Return: valid/invalid + details

REDEMPTION:
  Apply CN to new sale
  → credit_notes.remaining_amount -= applied
  → credit_note_usages INSERT: cn_id, sale_id, amount_used
  → If remaining = 0: status = 'fully_used'
  → If remaining > 0: status = 'partially_used'

EXPIRY:
  Scheduled job (daily): mark expired CNs
  → WHERE status IN ('active','partially_used') AND expires_at < now()
  → UPDATE status = 'expired'
```

---

## 9. TESTING STRATEGY

### Unit Tests (`tests/Unit/`)

| Test File | What It Tests |
|---|---|
| `GstCalculatorTest` | CGST/SGST split, IGST calculation, rounding, multiple rates, zero-rate |
| `CreditNoteTest` | Creation, validation, partial redemption, full redemption, expiry, HMAC |
| `PosSaleNumberTest` | Format (POS-YYYYMMDDNNNNN), uniqueness, sequential numbering |
| `PosReturnNumberTest` | Format (PRET-...), uniqueness |
| `SplitPaymentTest` | Total validation, multiple methods, remainder calculation |
| `DiscountCalculationTest` | Percentage, fixed, item-level, bill-level, combined |
| `StockDeductionTest` | Both layers (product.stock_quantity + inventory_stocks), movement created |
| `CashReconciliationTest` | Expected cash = opening + cash_sales - cash_refunds, variance |

### Feature Tests (`tests/Feature/Pos/`)

| Test File | What It Tests |
|---|---|
| `PosAuthTest` | Device registration, PIN login, wrong PIN, lockout, session expiry, logout |
| `PosShiftTest` | Open shift, close shift, Z-report accuracy, cannot bill without shift |
| `PosSaleTest` | Complete sale flow (cash/card/UPI/split/credit note), stock deduction, receipt |
| `PosReturnTest` | Return flow, stock restoration, credit note issuance, manager authorization |
| `PosExchangeTest` | Full exchange: return + new sale, difference calculation, linking |
| `PosCartTest` | Add/remove/update items, discount, coupon, customer attachment |
| `PosProductSearchTest` | Name search, barcode lookup, category filter, stock display |
| `PosCreditNoteTest` | Issue, validate, redeem partial, redeem full, expire, online use |
| `PosPermissionTest` | Cashier blocked from manager actions, supervisor access, manager override |
| `PosAuditTest` | All sensitive actions create audit log entries |
| `PosReportTest` | Daily summary, GST report, cashier report accuracy |

### Browser/UI Tests (`tests/Browser/` — Dusk or Playwright)

| Test File | What It Tests |
|---|---|
| `PosLoginFlowTest` | PIN entry, numpad click, keyboard input, error shake, lockout |
| `PosBillingFlowTest` | Search product, add to cart, adjust qty, apply discount, charge, complete |
| `PosReturnFlowTest` | Find bill, select items, choose refund method, manager PIN, complete |
| `PosKeyboardShortcutsTest` | F2 (search), F5 (new bill), F8 (hold), F9 (recall), F10 (charge) |
| `PosOfflineTest` | Go offline, create sale, go online, verify sync |
| `PosResponsiveTest` | Desktop, tablet landscape, tablet portrait layouts |

### Acceptance Test Checklist

```
AUTHENTICATION
  ☐ Unregistered device blocked with clear message
  ☐ Correct PIN logs in within 1 second
  ☐ Wrong PIN × 5 = 30s lockout
  ☐ Session expires after 8 hours
  ☐ Auto-lock after 15 min idle
  ☐ Lock/unlock preserves cart state

SHIFT
  ☐ Must open shift before billing
  ☐ Opening cash recorded
  ☐ Z-report numbers match actual transactions
  ☐ Cash variance calculated correctly
  ☐ Cannot open two shifts simultaneously

BILLING
  ☐ Product added by tap in < 200ms
  ☐ Barcode scan adds product in < 200ms
  ☐ Out-of-stock items cannot be added
  ☐ Qty cannot exceed stock
  ☐ GST calculated per HSN code
  ☐ Total = subtotal + tax - discount
  ☐ Coupon validation uses same rules as online
  ☐ Hold/resume preserves all cart data

PAYMENT
  ☐ Cash: change calculated correctly
  ☐ Card: reference saved
  ☐ UPI: reference saved
  ☐ Split: total of methods = bill total
  ☐ Credit note: validated, balance deducted
  ☐ Cannot confirm if amount < total

STOCK
  ☐ product.stock_quantity decremented on sale
  ☐ inventory_stocks.deduct() called on sale
  ☐ inventory_movements created for every sale
  ☐ Stock restored on return (both layers)
  ☐ Online store sees POS stock changes in real-time

RETURNS
  ☐ Only completed sales within return window
  ☐ Cannot return more than purchased
  ☐ Cannot return already-returned items
  ☐ Refund amount = original price (not current)
  ☐ Cash refund > ₹1000 requires manager PIN
  ☐ Credit note issued correctly on return
  ☐ Stock restored to inventory

EXCHANGE
  ☐ Return value based on original price
  ☐ Difference calculated correctly
  ☐ Positive difference: payment collected
  ☐ Negative difference: credit note issued
  ☐ Both transactions linked

CREDIT NOTES
  ☐ Unique number generated
  ☐ HMAC signature valid
  ☐ Partial redemption: balance updated
  ☐ Full redemption: status = fully_used
  ☐ Expired CN rejected
  ☐ Works at POS and online store

SECURITY
  ☐ Cashier cannot void sales
  ☐ Cashier cannot give > 10% discount without override
  ☐ Manager PIN required for restricted actions
  ☐ All sensitive actions in audit log
  ☐ Rate limiting enforced (120/min)

OFFLINE
  ☐ Sales work without internet
  ☐ Sales sync when connection restores
  ☐ No data loss
  ☐ Stock conflicts flagged

RECEIPTS
  ☐ GST-compliant format
  ☐ Barcode on receipt scannable
  ☐ Prints correctly on 80mm thermal
  ☐ WhatsApp/SMS/Email delivery works
```

---

## 10. IMPLEMENTATION PHASES

### Phase 1 — Foundation (Week 1-2)
**Deliverables**: POS loads, staff logs in, shift opens, products display

| # | Task | Story |
|---|---|---|
| 1.1 | Create `routes/pos.php` + register in bootstrap/app.php | — |
| 1.2 | Create POS layout (`pos/layout.blade.php`) — full-screen, no storefront chrome | — |
| 1.3 | Migration: `pos_held_bills`, `pos_cash_movements`, `pos_audit_log`, extend existing tables | — |
| 1.4 | `PosAuthenticate` middleware (device token check) | 1.1 |
| 1.5 | `PosShiftRequired` middleware | 2.1 |
| 1.6 | `Pos\AuthController` — register device, PIN login, logout | 1.1, 1.2, 1.4 |
| 1.7 | Login screen UI (numpad, shake, lockout) | 1.2 |
| 1.8 | `Pos\ShiftController` — open/close | 2.1, 2.2 |
| 1.9 | Shift open screen UI | 2.1 |
| 1.10 | `Pos\ProductController` — search, barcode lookup, category list, paginated grid | 3.1, 3.2, 3.4 |
| 1.11 | Main billing screen UI (product grid + empty cart) | 3.4 |

### Phase 2 — Core Billing (Week 3-4)
**Deliverables**: Full sale flow working end-to-end

| # | Task | Story |
|---|---|---|
| 2.1 | `Pos\CartController` — add, update, remove, clear (server-side) | 4.1, 4.2 |
| 2.2 | Cart panel UI (items, qty controls, totals, GST breakdown) | 4.1, 4.2 |
| 2.3 | Variant selector modal | 3.1 |
| 2.4 | GST calculation service (CGST/SGST/IGST per HSN) | 8.1 |
| 2.5 | `Pos\PaymentController` — cash, card, UPI | 5.1, 5.2, 5.3 |
| 2.6 | Payment screen UI | 5.1-5.4 |
| 2.7 | `Pos\SaleController` — complete sale (DB transaction, stock deduction both layers) | 5.1 |
| 2.8 | Sale success screen | — |
| 2.9 | Receipt template (HTML + PDF) | 10.1 |
| 2.10 | Receipt print via browser Print API | 10.2 |
| 2.11 | Split payment support | 5.4 |
| 2.12 | Discount + coupon support | 4.3, 4.4 |
| 2.13 | Customer attach/search/quick-create | 4.5 |
| 2.14 | Hold/resume bills | 4.6 |
| 2.15 | Keyboard shortcuts | — |
| 2.16 | Idle lock screen | 1.3 |

### Phase 3 — Returns & Credit Notes (Week 5-6)
**Deliverables**: Full return, exchange, credit note lifecycle

| # | Task | Story |
|---|---|---|
| 3.1 | `Pos\ReturnController` — find bill, process return, stock restore | 6.1 |
| 3.2 | Return UI (3-step flow) | 6.1 |
| 3.3 | Manager override PIN modal + authorize endpoint | 12.2 |
| 3.4 | Exchange flow (return + new sale linked) | 6.2 |
| 3.5 | Exchange UI | 6.2 |
| 3.6 | `Pos\CreditNoteController` — issue, validate, redeem | 7.1, 7.2 |
| 3.7 | Credit note payment in payment screen | 5.5 |
| 3.8 | Credit note QR code generation (HMAC signed) | 7.1 |
| 3.9 | Online store credit note redemption at checkout | 7.3 |
| 3.10 | Audit log service + writes on all sensitive actions | 12.1 |

### Phase 4 — Reports & GST (Week 7-8)
**Deliverables**: All reports, GST export, admin POS section

| # | Task | Story |
|---|---|---|
| 4.1 | `Pos\ReportController` — daily, cashier, product, stock | 9.1-9.4 |
| 4.2 | Reports UI (charts, tables, date filters) | 9.1-9.4 |
| 4.3 | Z-report screen (shift close with full summary) | 2.2 |
| 4.4 | GSTR-1 export (Excel) | 8.2 |
| 4.5 | GSTR-3B summary | 8.3 |
| 4.6 | Admin panel: POS dashboard, terminal management, staff management | 13.1-13.3 |
| 4.7 | Admin panel: audit log viewer | 12.1 |
| 4.8 | Digital receipts (WhatsApp/SMS/Email) | 10.3 |

### Phase 5 — Offline & Polish (Week 9-10)
**Deliverables**: Offline mode, camera scan, polished UI

| # | Task | Story |
|---|---|---|
| 5.1 | Product catalog cache in IndexedDB | 11.1 |
| 5.2 | Offline sale queue (IndexedDB + Dexie.js) | 11.1 |
| 5.3 | Sync queue on reconnection | 11.2 |
| 5.4 | Camera barcode scanner (quagga2.js) | 3.3 |
| 5.5 | PWA manifest + service worker for installability | — |
| 5.6 | All animations and transitions polished | — |
| 5.7 | Responsive tablet layout (768px-1024px) | — |
| 5.8 | Touch gestures (swipe to delete) | — |
| 5.9 | Sound effects (scan beep, success chime, error buzz) | — |

### Phase 6 — Testing (Week 10-11)
**Deliverables**: Full test suite, all acceptance criteria verified

| # | Task |
|---|---|
| 6.1 | Unit tests: GST, credit notes, sale numbers, stock, discounts, cash reconciliation |
| 6.2 | Feature tests: auth, shift, sale, return, exchange, cart, search, permissions, audit, reports |
| 6.3 | Browser tests: login flow, billing flow, return flow, keyboard shortcuts, offline, responsive |
| 6.4 | Manual acceptance testing against full checklist |
| 6.5 | Performance testing: search < 300ms, sale completion < 1s, 50 concurrent terminals |
| 6.6 | Security testing: unauthorized access, role enforcement, rate limiting, CSRF |

---

## FILE STRUCTURE

```
app/
├── Http/
│   ├── Controllers/Pos/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── SaleController.php
│   │   ├── ProductController.php
│   │   ├── CartController.php
│   │   ├── PaymentController.php
│   │   ├── ReturnController.php
│   │   ├── CreditNoteController.php
│   │   ├── ShiftController.php
│   │   ├── ReportController.php
│   │   ├── CustomerController.php
│   │   └── ReceiptController.php
│   └── Middleware/
│       ├── PosAuthenticate.php
│       ├── PosShiftRequired.php
│       └── PosRoleCheck.php
├── Services/
│   ├── Pos/
│   │   ├── GstCalculator.php
│   │   ├── SaleService.php
│   │   ├── ReturnService.php
│   │   ├── CreditNoteService.php
│   │   ├── StockService.php
│   │   ├── ReceiptService.php
│   │   └── AuditService.php
routes/
│   └── pos.php
resources/
├── views/pos/
│   ├── layout.blade.php
│   ├── login.blade.php
│   ├── shift-open.blade.php
│   ├── billing.blade.php
│   ├── payment.blade.php
│   ├── sale-success.blade.php
│   ├── returns.blade.php
│   ├── exchange.blade.php
│   ├── shift-close.blade.php
│   ├── reports/
│   │   ├── dashboard.blade.php
│   │   ├── gst.blade.php
│   │   ├── cashier.blade.php
│   │   └── stock.blade.php
│   ├── partials/
│   │   ├── header.blade.php
│   │   ├── product-card.blade.php
│   │   ├── cart-item.blade.php
│   │   ├── variant-modal.blade.php
│   │   ├── manager-pin-modal.blade.php
│   │   └── receipt.blade.php
│   └── components/
│       ├── numpad.blade.php
│       ├── search-input.blade.php
│       └── payment-method.blade.php
├── js/pos/
│   ├── pos-app.js
│   ├── barcode-scanner.js
│   ├── receipt-printer.js
│   ├── offline-queue.js
│   └── keyboard-shortcuts.js
tests/
├── Unit/
│   ├── GstCalculatorTest.php
│   ├── CreditNoteTest.php
│   ├── PosSaleNumberTest.php
│   ├── SplitPaymentTest.php
│   ├── DiscountCalculationTest.php
│   ├── StockDeductionTest.php
│   └── CashReconciliationTest.php
├── Feature/Pos/
│   ├── PosAuthTest.php
│   ├── PosShiftTest.php
│   ├── PosSaleTest.php
│   ├── PosReturnTest.php
│   ├── PosExchangeTest.php
│   ├── PosCartTest.php
│   ├── PosProductSearchTest.php
│   ├── PosCreditNoteTest.php
│   ├── PosPermissionTest.php
│   ├── PosAuditTest.php
│   └── PosReportTest.php
└── Browser/
    ├── PosLoginFlowTest.php
    ├── PosBillingFlowTest.php
    ├── PosReturnFlowTest.php
    └── PosKeyboardShortcutsTest.php
```

---

*Document generated: 24 Feb 2026*
*Source: ForeverKids POS Master Plan v2.0*
