# POS System Specification

## [AI-REF] Point of Sale System Design

---

## Overview

The POS system is a Windows desktop application built with Electron.js that connects to the Laravel backend API. It enables retail store operations including sales, returns, and inventory management.

---

## System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    Electron POS App (Windows)                    │
│  ┌───────────────┐ ┌───────────────┐ ┌───────────────────────┐  │
│  │  React UI     │ │  Local SQLite │ │   Barcode Scanner     │  │
│  │  Components   │ │  (Offline)    │ │   Integration         │  │
│  └───────────────┘ └───────────────┘ └───────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Laravel API (/api/v1/pos/*)                   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│              MySQL + Redis + Meilisearch                         │
└─────────────────────────────────────────────────────────────────┘
```

---

## POS API Endpoints

### Authentication

```http
POST /api/v1/pos/auth
Content-Type: application/json

{
    "device_id": "POS-STORE001-REG01",
    "device_secret": "encrypted_secret",
    "staff_pin": "1234"
}

Response:
{
    "success": true,
    "data": {
        "token": "pos_token_here",
        "staff": {
            "id": 1,
            "name": "John Doe",
            "role": "cashier"
        },
        "store": {
            "id": 1,
            "name": "Main Store"
        },
        "register": {
            "id": 1,
            "name": "Register 1"
        }
    }
}
```

### Product Lookup

```http
# Search by query
GET /api/v1/pos/products/search?q=iphone
Authorization: Bearer {pos_token}

# Lookup by barcode
GET /api/v1/pos/products/barcode/8901234567890
Authorization: Bearer {pos_token}

Response:
{
    "success": true,
    "data": {
        "id": 123,
        "name": "iPhone 15 Pro",
        "sku": "IPH15PRO-256-BLK",
        "barcode": "8901234567890",
        "price": 134900.00,
        "mrp": 149900.00,
        "stock_quantity": 15,
        "tax_rate": 18.00,
        "image_url": "https://..."
    }
}
```

### Create Sale

```http
POST /api/v1/pos/sales
Authorization: Bearer {pos_token}
Content-Type: application/json

{
    "customer_id": null,
    "customer_phone": "+919876543210",
    "items": [
        {
            "product_id": 123,
            "variant_id": null,
            "quantity": 1,
            "price": 134900.00,
            "discount": 0
        },
        {
            "barcode": "8901234567891",
            "quantity": 2
        }
    ],
    "discount": {
        "type": "percentage",
        "value": 5,
        "reason": "Festival offer"
    },
    "payment": {
        "method": "split",
        "details": [
            {"method": "cash", "amount": 50000},
            {"method": "card", "amount": 78000, "reference": "TXN123"}
        ]
    },
    "credit_note_code": null
}

Response:
{
    "success": true,
    "data": {
        "sale": {
            "id": 456,
            "sale_number": "POS-2026012900001",
            "subtotal": 169800.00,
            "discount": 8490.00,
            "tax": 28966.20,
            "total": 190276.20,
            "paid_amount": 128000,
            "change_amount": 0,
            "status": "completed"
        },
        "receipt": {
            "html": "<html>...</html>",
            "pdf_url": "https://..."
        }
    }
}
```

### Process Return

```http
POST /api/v1/pos/returns
Authorization: Bearer {pos_token}
Content-Type: application/json

{
    "original_sale_id": 456,
    "items": [
        {
            "sale_item_id": 789,
            "quantity": 1,
            "reason": "defective",
            "condition": "opened"
        }
    ],
    "refund_method": "credit_note"
}

Response:
{
    "success": true,
    "data": {
        "return": {
            "id": 101,
            "return_number": "RET-2026012900001",
            "amount": 134900.00,
            "status": "completed"
        },
        "credit_note": {
            "id": 50,
            "code": "CN-ABCD1234",
            "amount": 134900.00,
            "expires_at": "2027-01-29T00:00:00Z"
        }
    }
}
```

### Validate Credit Note

```http
GET /api/v1/pos/credit-notes/CN-ABCD1234/validate
Authorization: Bearer {pos_token}

Response:
{
    "success": true,
    "data": {
        "valid": true,
        "code": "CN-ABCD1234",
        "remaining_amount": 134900.00,
        "expires_at": "2027-01-29T00:00:00Z",
        "customer": {
            "name": "Customer Name",
            "phone": "+919876543210"
        }
    }
}
```

### Shift Management

```http
# Open Shift
POST /api/v1/pos/shifts/open
Authorization: Bearer {pos_token}
Content-Type: application/json

{
    "opening_cash": 5000.00
}

# Close Shift
POST /api/v1/pos/shifts/close
Authorization: Bearer {pos_token}
Content-Type: application/json

{
    "closing_cash": 125000.00,
    "notes": "All clear"
}

Response:
{
    "success": true,
    "data": {
        "shift": {
            "id": 99,
            "started_at": "2026-01-29T09:00:00Z",
            "ended_at": "2026-01-29T18:00:00Z",
            "opening_cash": 5000.00,
            "closing_cash": 125000.00,
            "expected_cash": 123500.00,
            "variance": 1500.00
        },
        "summary": {
            "total_sales": 45,
            "total_revenue": 567890.00,
            "total_returns": 3,
            "total_refunds": 25000.00,
            "by_payment_method": {
                "cash": 118500.00,
                "card": 389390.00,
                "upi": 60000.00
            }
        }
    }
}
```

### Data Sync

```http
GET /api/v1/pos/sync?last_sync=2026-01-29T08:00:00Z
Authorization: Bearer {pos_token}

Response:
{
    "success": true,
    "data": {
        "products": {
            "updated": [...],
            "deleted": [123, 456]
        },
        "inventory": [...],
        "settings": {...},
        "sync_timestamp": "2026-01-29T10:00:00Z"
    }
}
```

---

## Barcode System

### Barcode Generation

```php
// app/Actions/Product/GenerateBarcode.php
class GenerateBarcode
{
    public function execute(Product $product): string
    {
        // Generate EAN-13 barcode
        $prefix = config('pos.barcode_prefix', '890'); // Country code
        $productCode = str_pad($product->id, 9, '0', STR_PAD_LEFT);
        $barcodeWithoutCheck = $prefix . $productCode;
        $checkDigit = $this->calculateCheckDigit($barcodeWithoutCheck);

        $barcode = $barcodeWithoutCheck . $checkDigit;

        // Store barcode
        $product->update(['barcode' => $barcode]);

        // Generate barcode image
        $generator = new BarcodeGenerator();
        $imageUrl = $generator->generate($barcode, 'ean13');

        Barcode::create([
            'product_id' => $product->id,
            'barcode' => $barcode,
            'type' => 'ean13',
            'image_url' => $imageUrl,
        ]);

        return $barcode;
    }

    private function calculateCheckDigit(string $code): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $code[$i];
            $sum += $digit * ($i % 2 === 0 ? 1 : 3);
        }
        return (10 - ($sum % 10)) % 10;
    }
}
```

### Barcode Lookup Service

```php
// app/Services/POS/BarcodeLookupService.php
class BarcodeLookupService
{
    public function lookup(string $barcode): ?Product
    {
        // Check product barcode
        $product = Product::where('barcode', $barcode)->first();
        if ($product) return $product;

        // Check variant barcode
        $variant = ProductVariant::where('barcode', $barcode)->first();
        if ($variant) {
            return $variant->product->setRelation('selectedVariant', $variant);
        }

        // Check external barcode database
        $externalProduct = $this->lookupExternal($barcode);
        if ($externalProduct) {
            return $this->createFromExternal($externalProduct);
        }

        return null;
    }
}
```

---

## Credit Note Security

```php
// app/Models/CreditNote.php
class CreditNote extends Model
{
    protected static function booted(): void
    {
        static::creating(function ($creditNote) {
            // Generate secure, unique code
            $creditNote->credit_note_number = 'CN-' . strtoupper(Str::random(8));
            $creditNote->secure_code = hash('sha256', Str::uuid() . now()->timestamp);
            $creditNote->remaining_amount = $creditNote->amount;
            $creditNote->expires_at = now()->addYear();
        });
    }

    public function isValid(): bool
    {
        return $this->status === 'active'
            && $this->remaining_amount > 0
            && $this->expires_at->isFuture();
    }

    public function redeem(float $amount, Order $order): void
    {
        if ($amount > $this->remaining_amount) {
            throw new InsufficientCreditException();
        }

        $this->remaining_amount -= $amount;
        $this->used_amount += $amount;

        if ($this->remaining_amount <= 0) {
            $this->status = 'fully_used';
        } else {
            $this->status = 'partially_used';
        }

        $this->save();

        CreditNoteUsage::create([
            'credit_note_id' => $this->id,
            'order_id' => $order->id,
            'amount' => $amount,
        ]);
    }
}
```

---

## Receipt Template

```html
<!-- resources/views/pos/receipt.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Courier New', monospace; font-size: 12px; width: 80mm; }
        .header { text-align: center; margin-bottom: 10px; }
        .store-name { font-size: 16px; font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 5px 0; }
        table { width: 100%; }
        .right { text-align: right; }
        .total { font-weight: bold; font-size: 14px; }
        .barcode { text-align: center; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="store-name">{{ $store->name }}</div>
        <div>{{ $store->address }}</div>
        <div>Tel: {{ $store->phone }}</div>
        <div>GSTIN: {{ $store->gst_number }}</div>
    </div>

    <div class="divider"></div>

    <table>
        <tr>
            <td>Receipt #:</td>
            <td class="right">{{ $sale->sale_number }}</td>
        </tr>
        <tr>
            <td>Date:</td>
            <td class="right">{{ $sale->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td>Cashier:</td>
            <td class="right">{{ $sale->staff->name }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <table>
        <thead>
            <tr>
                <th align="left">Item</th>
                <th class="right">Qty</th>
                <th class="right">Price</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td colspan="4">{{ Str::limit($item->product_name, 30) }}</td>
            </tr>
            <tr>
                <td></td>
                <td class="right">{{ $item->quantity }}</td>
                <td class="right">{{ number_format($item->price, 2) }}</td>
                <td class="right">{{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <table>
        <tr>
            <td>Subtotal:</td>
            <td class="right">₹{{ number_format($sale->subtotal, 2) }}</td>
        </tr>
        @if($sale->discount > 0)
        <tr>
            <td>Discount:</td>
            <td class="right">-₹{{ number_format($sale->discount, 2) }}</td>
        </tr>
        @endif
        <tr>
            <td>Tax (GST):</td>
            <td class="right">₹{{ number_format($sale->tax, 2) }}</td>
        </tr>
        <tr class="total">
            <td>TOTAL:</td>
            <td class="right">₹{{ number_format($sale->total, 2) }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <table>
        <tr>
            <td>Payment:</td>
            <td class="right">{{ ucfirst($sale->payment_method) }}</td>
        </tr>
        <tr>
            <td>Paid:</td>
            <td class="right">₹{{ number_format($sale->paid_amount, 2) }}</td>
        </tr>
        @if($sale->change_amount > 0)
        <tr>
            <td>Change:</td>
            <td class="right">₹{{ number_format($sale->change_amount, 2) }}</td>
        </tr>
        @endif
    </table>

    <div class="divider"></div>

    <div class="barcode">
        <img src="{{ $barcodeImage }}" alt="{{ $sale->sale_number }}">
        <div>{{ $sale->sale_number }}</div>
    </div>

    <div class="header" style="margin-top: 10px;">
        <div>Thank you for shopping!</div>
        <div>Exchange within 7 days with receipt</div>
    </div>
</body>
</html>
```

---

## Offline Mode

```javascript
// Electron POS - Offline Queue
class OfflineQueue {
    constructor() {
        this.db = new SQLite('pos_offline.db');
        this.queue = [];
    }

    async addSale(saleData) {
        const offlineId = `offline_${Date.now()}`;
        await this.db.insert('offline_sales', {
            id: offlineId,
            data: JSON.stringify(saleData),
            created_at: new Date().toISOString(),
            synced: false
        });
        return offlineId;
    }

    async syncAll() {
        const pendingSales = await this.db.query(
            'SELECT * FROM offline_sales WHERE synced = 0'
        );

        for (const sale of pendingSales) {
            try {
                const result = await api.post('/pos/sales', JSON.parse(sale.data));
                await this.db.update('offline_sales', sale.id, {
                    synced: true,
                    server_id: result.data.sale.id,
                    synced_at: new Date().toISOString()
                });
            } catch (error) {
                console.error('Sync failed for', sale.id, error);
            }
        }
    }
}
```

---

## Acceptance Criteria

### Sales
- [ ] Products can be added by barcode scan
- [ ] Products can be searched by name/SKU
- [ ] Quantities can be adjusted
- [ ] Line-level discounts work
- [ ] Overall discount works
- [ ] Tax is calculated correctly
- [ ] Multiple payment methods supported
- [ ] Receipt is printed successfully
- [ ] Sale is recorded in backend

### Returns
- [ ] Original sale can be looked up
- [ ] Items can be selected for return
- [ ] Refund amount calculated correctly
- [ ] Credit note generated securely
- [ ] Inventory updated correctly

### Credit Notes
- [ ] Unique code generated
- [ ] Can be validated by code
- [ ] Partial redemption works
- [ ] Expiry is enforced
- [ ] Balance tracking is accurate

### Offline Mode
- [ ] Sales work without internet
- [ ] Queue syncs when online
- [ ] No data loss

