# ForeverKids API Documentation (v1)

Base URL: `https://foreverkids.com/api/v1`

All requests and responses use JSON. Include `Accept: application/json` in every request header.

---

## Table of Contents

1. [Authentication](#1-authentication)
2. [Profile](#2-profile)
3. [Addresses](#3-addresses)
4. [Products](#4-products)
5. [Categories](#5-categories)
6. [Brands](#6-brands)
7. [Search](#7-search)
8. [Cart](#8-cart)
9. [Checkout](#9-checkout)
10. [Orders](#10-orders)
11. [Reviews](#11-reviews)
12. [Wishlist](#12-wishlist)
13. [Recommendations](#13-recommendations)
14. [Notifications](#14-notifications)
15. [Preferences](#15-preferences)
16. [Home](#16-home)
17. [Sellers](#17-sellers)
18. [Pages](#18-pages)
19. [Settings](#19-settings)

---

## Authentication Format

Protected endpoints require a Bearer token in the `Authorization` header:

```
Authorization: Bearer 1|abc123def456...
```

Tokens are issued by Laravel Sanctum on register or login. Store the token securely on the client side.

---

## 1. Authentication

### 1.1 Register

Create a new customer account.

| | |
|---|---|
| **Method** | `POST` |
| **URL** | `/api/v1/auth/register` |
| **Auth Required** | No |

**Request Body**

```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "+91 9876543210",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!"
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `first_name` | string | Yes | max:50 |
| `last_name` | string | Yes | max:50 |
| `email` | string | Yes | valid email, unique |
| `phone` | string | No | max:20, unique |
| `password` | string | Yes | confirmed, min:8 |
| `password_confirmation` | string | Yes | must match password |

**Response** `201 Created`

```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user": {
      "id": 1,
      "uuid": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "+91 9876543210",
      "role": "customer"
    },
    "token": "1|abc123def456ghi789jkl012mno345pqr678stu901",
    "token_type": "Bearer"
  }
}
```

---

### 1.2 Login

Authenticate an existing user and receive a token.

| | |
|---|---|
| **Method** | `POST` |
| **URL** | `/api/v1/auth/login` |
| **Auth Required** | No |

**Request Body**

```json
{
  "email": "john@example.com",
  "password": "SecurePass123!",
  "device_name": "iPhone 15"
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `email` | string | Yes | valid email |
| `password` | string | Yes | |
| `device_name` | string | No | max:255, defaults to User-Agent |

**Response** `200 OK`

```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "uuid": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "+91 9876543210",
      "role": "customer",
      "avatar_url": "https://cdn.foreverkids.com/avatars/1.jpg",
      "is_verified": true
    },
    "token": "2|xyz789abc012def345ghi678jkl901mno234pqr567",
    "token_type": "Bearer"
  }
}
```

**Error Response** `422 Unprocessable Entity`

```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
```

---

### 1.3 Logout

Revoke the current access token.

| | |
|---|---|
| **Method** | `POST` |
| **URL** | `/api/v1/auth/logout` |
| **Auth Required** | Yes |

**Response** `200 OK`

```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

### 1.4 Logout All Devices

Revoke all tokens for the authenticated user.

| | |
|---|---|
| **Method** | `POST` |
| **URL** | `/api/v1/auth/logout-all` |
| **Auth Required** | Yes |

**Response** `200 OK`

```json
{
  "success": true,
  "message": "Logged out from all devices"
}
```

---

## 2. Profile

### 2.1 Get Profile

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/profile` |
| **Auth Required** | Yes |

**Response** `200 OK`

```json
{
  "success": true,
  "data": {
    "id": 1,
    "uuid": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "first_name": "John",
    "last_name": "Doe",
    "name": "John Doe",
    "email": "john@example.com",
    "phone": "+91 9876543210",
    "role": "customer",
    "avatar_url": "https://cdn.foreverkids.com/avatars/1.jpg",
    "is_verified": true,
    "email_verified_at": "2026-01-15T10:30:00.000000Z",
    "phone_verified_at": null,
    "preferences": {
      "order_updates": true,
      "promotions": false,
      "newsletter": true
    },
    "created_at": "2026-01-15T10:30:00.000000Z"
  }
}
```

---

### 2.2 Update Profile

| | |
|---|---|
| **Method** | `PUT` |
| **URL** | `/api/v1/profile` |
| **Auth Required** | Yes |

**Request Body**

```json
{
  "first_name": "Jonathan",
  "last_name": "Doe",
  "phone": "+91 9876543211",
  "avatar_url": "https://cdn.foreverkids.com/avatars/1-new.jpg",
  "preferences": {
    "order_updates": true,
    "promotions": true
  }
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `first_name` | string | No | max:50 |
| `last_name` | string | No | max:50 |
| `phone` | string | No | max:20, unique (excluding self) |
| `avatar_url` | string | No | valid URL, max:255 |
| `preferences` | object | No | |

**Response** `200 OK`

```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": {
    "id": 1,
    "first_name": "Jonathan",
    "last_name": "Doe",
    "name": "Jonathan Doe",
    "email": "john@example.com",
    "phone": "+91 9876543211",
    "avatar_url": "https://cdn.foreverkids.com/avatars/1-new.jpg",
    "preferences": {
      "order_updates": true,
      "promotions": true
    }
  }
}
```

---

### 2.3 Update Password

| | |
|---|---|
| **Method** | `PUT` |
| **URL** | `/api/v1/profile/password` |
| **Auth Required** | Yes |

**Request Body**

```json
{
  "current_password": "OldPass123!",
  "password": "NewSecurePass456!",
  "password_confirmation": "NewSecurePass456!"
}
```

**Response** `200 OK`

```json
{
  "success": true,
  "message": "Password updated successfully"
}
```

---

### 2.4 Delete Account

| | |
|---|---|
| **Method** | `DELETE` |
| **URL** | `/api/v1/profile` |
| **Auth Required** | Yes |

**Request Body**

```json
{
  "password": "SecurePass123!"
}
```

**Response** `200 OK`

```json
{
  "success": true,
  "message": "Account deleted successfully"
}
```

---

## 3. Addresses

### 3.1 List Addresses

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/addresses` |
| **Auth Required** | Yes |

**Response** `200 OK`

```json
{
  "data": [
    {
      "id": 1,
      "label": "Home",
      "first_name": "John",
      "last_name": "Doe",
      "phone": "+91 9876543210",
      "address_line_1": "123 Main Street",
      "address_line_2": "Apt 4B",
      "city": "Mumbai",
      "state": "Maharashtra",
      "postal_code": "400001",
      "country": "IN",
      "is_default": true,
      "created_at": "2026-01-15T10:30:00.000000Z",
      "updated_at": "2026-01-15T10:30:00.000000Z"
    }
  ]
}
```

---

### 3.2 Create Address

| | |
|---|---|
| **Method** | `POST` |
| **URL** | `/api/v1/addresses` |
| **Auth Required** | Yes |

**Request Body**

```json
{
  "label": "Office",
  "first_name": "John",
  "last_name": "Doe",
  "phone": "+91 9876543210",
  "address_line_1": "456 Business Park",
  "address_line_2": "Floor 3",
  "city": "Mumbai",
  "state": "Maharashtra",
  "postal_code": "400051",
  "country": "IN",
  "is_default": false
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `label` | string | No | max:50 |
| `first_name` | string | Yes | max:50 |
| `last_name` | string | Yes | max:50 |
| `phone` | string | No | max:20 |
| `address_line_1` | string | Yes | max:255 |
| `address_line_2` | string | No | max:255 |
| `city` | string | Yes | max:100 |
| `state` | string | Yes | max:100 |
| `postal_code` | string | Yes | max:20 |
| `country` | string | Yes | ISO 3166-1 alpha-2, max:2 |
| `is_default` | boolean | No | defaults to false |

**Response** `201 Created`

```json
{
  "message": "Address created successfully",
  "data": {
    "id": 2,
    "label": "Office",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+91 9876543210",
    "address_line_1": "456 Business Park",
    "address_line_2": "Floor 3",
    "city": "Mumbai",
    "state": "Maharashtra",
    "postal_code": "400051",
    "country": "IN",
    "is_default": false,
    "created_at": "2026-02-20T14:00:00.000000Z",
    "updated_at": "2026-02-20T14:00:00.000000Z"
  }
}
```

---

### 3.3 Show Address

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/addresses/{id}` |
| **Auth Required** | Yes |

**Response** `200 OK`

```json
{
  "data": {
    "id": 1,
    "label": "Home",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+91 9876543210",
    "address_line_1": "123 Main Street",
    "address_line_2": "Apt 4B",
    "city": "Mumbai",
    "state": "Maharashtra",
    "postal_code": "400001",
    "country": "IN",
    "is_default": true
  }
}
```

---

### 3.4 Update Address

| | |
|---|---|
| **Method** | `PUT` |
| **URL** | `/api/v1/addresses/{id}` |
| **Auth Required** | Yes |

Request body same as Create Address.

**Response** `200 OK`

```json
{
  "message": "Address updated successfully",
  "data": { ... }
}
```

---

### 3.5 Delete Address

| | |
|---|---|
| **Method** | `DELETE` |
| **URL** | `/api/v1/addresses/{id}` |
| **Auth Required** | Yes |

**Response** `200 OK`

```json
{
  "message": "Address deleted successfully"
}
```

---

## 4. Products

### 4.1 List Products

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/products` |
| **Auth Required** | No |

**Query Parameters**

| Parameter | Type | Description |
|-----------|------|-------------|
| `category` | string | Filter by category slug |
| `brand` | string | Filter by brand slug |
| `min_price` | number | Minimum price filter |
| `max_price` | number | Maximum price filter |
| `sort` | string | Sort order: `price_low`, `price_high`, `newest`, `rating`, `popular` |
| `per_page` | integer | Items per page (default: 20) |
| `page` | integer | Page number |

**Response** `200 OK`

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "name": "Wooden Building Blocks Set",
      "slug": "wooden-building-blocks-set",
      "price": 599.00,
      "mrp": 799.00,
      "rating": 4.5,
      "review_count": 23,
      "sales_count": 156,
      "images": ["https://cdn.foreverkids.com/products/blocks-1.jpg"],
      "is_featured": true,
      "stock_quantity": 45,
      "category": {
        "id": 3,
        "name": "Building Toys",
        "slug": "building-toys"
      },
      "brand": {
        "id": 2,
        "name": "WoodCraft",
        "slug": "woodcraft"
      }
    }
  ],
  "first_page_url": "/api/v1/products?page=1",
  "from": 1,
  "last_page": 5,
  "last_page_url": "/api/v1/products?page=5",
  "next_page_url": "/api/v1/products?page=2",
  "per_page": 20,
  "prev_page_url": null,
  "to": 20,
  "total": 98
}
```

---

### 4.2 Show Product

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/products/{slug}` |
| **Auth Required** | No |

**Response** `200 OK`

```json
{
  "data": {
    "id": 1,
    "name": "Wooden Building Blocks Set",
    "slug": "wooden-building-blocks-set",
    "sku": "WBS-001",
    "short_description": "50-piece wooden building blocks for ages 3+",
    "description": "<p>Full HTML description...</p>",
    "price": 599.00,
    "mrp": 799.00,
    "rating": 4.5,
    "review_count": 23,
    "sales_count": 156,
    "stock_quantity": 45,
    "is_active": true,
    "is_featured": true,
    "view_count": 1024,
    "category": {
      "id": 3,
      "name": "Building Toys",
      "slug": "building-toys",
      "parent_id": 1
    },
    "brand": {
      "id": 2,
      "name": "WoodCraft",
      "slug": "woodcraft"
    },
    "seller": {
      "id": 5,
      "business_name": "Toy World",
      "slug": "toy-world",
      "rating": 4.7
    },
    "variants": [
      {
        "id": 1,
        "sku": "WBS-001-50",
        "price": 599.00,
        "stock_quantity": 30,
        "attribute_values": [
          { "attribute": "Size", "value": "50 pieces" }
        ]
      },
      {
        "id": 2,
        "sku": "WBS-001-100",
        "price": 999.00,
        "stock_quantity": 15,
        "attribute_values": [
          { "attribute": "Size", "value": "100 pieces" }
        ]
      }
    ],
    "images": [
      {
        "id": 1,
        "url": "https://cdn.foreverkids.com/products/blocks-1.jpg",
        "alt": "Wooden Building Blocks Set - Front",
        "position": 0
      },
      {
        "id": 2,
        "url": "https://cdn.foreverkids.com/products/blocks-2.jpg",
        "alt": "Wooden Building Blocks Set - Side",
        "position": 1
      }
    ]
  }
}
```

---

### 4.3 Featured Products

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/products/featured` |
| **Auth Required** | No |

**Response** `200 OK`

```json
{
  "data": [
    {
      "id": 1,
      "name": "Wooden Building Blocks Set",
      "slug": "wooden-building-blocks-set",
      "price": 599.00,
      "mrp": 799.00,
      "rating": 4.5,
      "review_count": 23,
      "images": ["https://cdn.foreverkids.com/products/blocks-1.jpg"],
      "category": { "id": 3, "name": "Building Toys", "slug": "building-toys" },
      "brand": { "id": 2, "name": "WoodCraft", "slug": "woodcraft" }
    }
  ]
}
```

Returns up to 12 products where `is_featured = true`.

---

### 4.4 Bestsellers

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/products/bestsellers` |
| **Auth Required** | No |

**Response** `200 OK`

```json
{
  "data": [
    {
      "id": 5,
      "name": "Magnetic Tiles 60pc",
      "slug": "magnetic-tiles-60pc",
      "price": 1299.00,
      "mrp": 1599.00,
      "rating": 4.8,
      "sales_count": 312,
      "category": { "id": 3, "name": "Building Toys", "slug": "building-toys" },
      "brand": { "id": 4, "name": "MagPlay", "slug": "magplay" }
    }
  ]
}
```

Returns up to 12 products ordered by `sales_count` descending.

---

### 4.5 New Arrivals

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/products/new-arrivals` |
| **Auth Required** | No |

**Response** `200 OK`

```json
{
  "data": [
    {
      "id": 42,
      "name": "STEM Robot Kit",
      "slug": "stem-robot-kit",
      "price": 2499.00,
      "mrp": 2999.00,
      "rating": 0,
      "created_at": "2026-02-20T08:00:00.000000Z",
      "category": { "id": 7, "name": "STEM Toys", "slug": "stem-toys" },
      "brand": { "id": 8, "name": "RoboKids", "slug": "robokids" }
    }
  ]
}
```

Returns up to 12 products ordered by `created_at` descending.

---

### 4.6 Product Reviews

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/products/{id}/reviews` |
| **Auth Required** | No |

**Query Parameters**

| Parameter | Type | Description |
|-----------|------|-------------|
| `page` | integer | Page number |

**Response** `200 OK`

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "rating": 5,
      "title": "Great quality!",
      "comment": "My kids love these blocks. Very sturdy and safe.",
      "is_approved": true,
      "created_at": "2026-02-10T14:30:00.000000Z",
      "user": {
        "id": 12,
        "first_name": "Priya",
        "last_name": "Sharma"
      }
    }
  ],
  "per_page": 10,
  "total": 23
}
```

---

### 4.7 Product Questions

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/products/{id}/questions` |
| **Auth Required** | No |

**Response** `200 OK`

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "question": "Is this suitable for a 2-year-old?",
      "is_published": true,
      "created_at": "2026-02-08T09:00:00.000000Z",
      "user": {
        "id": 15,
        "first_name": "Amit",
        "last_name": "Patel"
      },
      "answers": [
        {
          "id": 1,
          "answer": "This set is recommended for ages 3+. The pieces may be too small for a 2-year-old.",
          "created_at": "2026-02-08T12:00:00.000000Z"
        }
      ]
    }
  ],
  "per_page": 10,
  "total": 5
}
```

---

## 5. Categories

### 5.1 List Categories

Returns top-level categories with their direct children.

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/categories` |
| **Auth Required** | No |

**Response** `200 OK`

```json
{
  "data": [
    {
      "id": 1,
      "name": "Toys & Games",
      "slug": "toys-games",
      "image": "https://cdn.foreverkids.com/categories/toys.jpg",
      "description": "Fun toys and games for all ages",
      "is_active": true,
      "position": 1,
      "parent_id": null,
      "children": [
        {
          "id": 3,
          "name": "Building Toys",
          "slug": "building-toys",
          "image": "https://cdn.foreverkids.com/categories/building.jpg",
          "is_active": true,
          "position": 1,
          "parent_id": 1
        },
        {
          "id": 4,
          "name": "Dolls & Figures",
          "slug": "dolls-figures",
          "image": "https://cdn.foreverkids.com/categories/dolls.jpg",
          "is_active": true,
          "position": 2,
          "parent_id": 1
        }
      ]
    }
  ]
}
```

---

### 5.2 Category Tree

Returns full nested category tree (three levels deep).

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/categories/tree` |
| **Auth Required** | No |

**Response** `200 OK`

```json
{
  "data": [
    {
      "id": 1,
      "name": "Toys & Games",
      "slug": "toys-games",
      "position": 1,
      "children": [
        {
          "id": 3,
          "name": "Building Toys",
          "slug": "building-toys",
          "position": 1,
          "children": [
            {
              "id": 10,
              "name": "Wooden Blocks",
              "slug": "wooden-blocks",
              "position": 1,
              "children": []
            }
          ]
        }
      ]
    }
  ]
}
```

---

### 5.3 Show Category

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/categories/{slug}` |
| **Auth Required** | No |

**Response** `200 OK`

```json
{
  "data": {
    "id": 3,
    "name": "Building Toys",
    "slug": "building-toys",
    "image": "https://cdn.foreverkids.com/categories/building.jpg",
    "description": "Blocks, bricks, and construction sets",
    "is_active": true,
    "position": 1,
    "parent_id": 1,
    "children": [
      {
        "id": 10,
        "name": "Wooden Blocks",
        "slug": "wooden-blocks",
        "is_active": true
      }
    ]
  }
}
```

---

### 5.4 Products by Category

Returns paginated products for a category (including products in child categories).

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/categories/{slug}/products` |
| **Auth Required** | No |

**Query Parameters**

| Parameter | Type | Description |
|-----------|------|-------------|
| `per_page` | integer | Items per page (default: 20) |
| `page` | integer | Page number |

**Response** `200 OK`

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "name": "Wooden Building Blocks Set",
      "slug": "wooden-building-blocks-set",
      "price": 599.00,
      "mrp": 799.00,
      "is_active": true,
      "brand": { "id": 2, "name": "WoodCraft", "slug": "woodcraft" }
    }
  ],
  "per_page": 20,
  "total": 15
}
```

---

## 6. Brands

### 6.1 List Brands

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/brands` |
| **Auth Required** | No |

**Response** `200 OK`

```json
{
  "data": [
    {
      "id": 1,
      "name": "FunLearn",
      "slug": "funlearn",
      "logo": "https://cdn.foreverkids.com/brands/funlearn.png",
      "description": "Educational toys for curious minds",
      "is_active": true
    },
    {
      "id": 2,
      "name": "WoodCraft",
      "slug": "woodcraft",
      "logo": "https://cdn.foreverkids.com/brands/woodcraft.png",
      "description": "Handcrafted wooden toys",
      "is_active": true
    }
  ]
}
```

---

### 6.2 Show Brand

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/brands/{id}` |
| **Auth Required** | No |

**Response** `200 OK`

```json
{
  "data": {
    "id": 2,
    "name": "WoodCraft",
    "slug": "woodcraft",
    "logo": "https://cdn.foreverkids.com/brands/woodcraft.png",
    "description": "Handcrafted wooden toys made from sustainable materials",
    "is_active": true
  }
}
```

---

## 7. Search

### 7.1 Search Products

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/search` |
| **Auth Required** | No |

**Query Parameters**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `q` | string | Yes | Search query (min:2, max:100) |
| `per_page` | integer | No | Items per page (default: 20) |
| `page` | integer | No | Page number |

**Example**: `GET /api/v1/search?q=wooden+blocks&per_page=10`

**Response** `200 OK`

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "name": "Wooden Building Blocks Set",
      "slug": "wooden-building-blocks-set",
      "short_description": "50-piece wooden building blocks for ages 3+",
      "price": 599.00,
      "mrp": 799.00,
      "rating": 4.5,
      "images": ["https://cdn.foreverkids.com/products/blocks-1.jpg"],
      "category": { "id": 3, "name": "Building Toys", "slug": "building-toys" },
      "brand": { "id": 2, "name": "WoodCraft", "slug": "woodcraft" }
    }
  ],
  "per_page": 10,
  "total": 3
}
```

---

### 7.2 Search Suggestions

Returns lightweight autocomplete suggestions.

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/search/suggestions` |
| **Auth Required** | No |

**Query Parameters**

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `q` | string | Yes | Search query (min:2, max:100) |

**Example**: `GET /api/v1/search/suggestions?q=wood`

**Response** `200 OK`

```json
{
  "data": [
    { "id": 1, "name": "Wooden Building Blocks Set", "slug": "wooden-building-blocks-set", "price": 599.00 },
    { "id": 7, "name": "Wooden Puzzle Animals", "slug": "wooden-puzzle-animals", "price": 349.00 },
    { "id": 15, "name": "Wooden Train Set", "slug": "wooden-train-set", "price": 899.00 }
  ]
}
```

---

## 8. Cart

All cart endpoints require authentication.

### 8.1 Get Cart

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/cart` |
| **Auth Required** | Yes |

**Response** `200 OK`

```json
{
  "data": {
    "id": 1,
    "user_id": 1,
    "coupon_id": null,
    "items": [
      {
        "id": 1,
        "product_id": 1,
        "variant_id": null,
        "quantity": 2,
        "price": 599.00,
        "product": {
          "id": 1,
          "name": "Wooden Building Blocks Set",
          "slug": "wooden-building-blocks-set",
          "price": 599.00,
          "mrp": 799.00,
          "images": ["https://cdn.foreverkids.com/products/blocks-1.jpg"],
          "stock_quantity": 45
        }
      }
    ]
  },
  "summary": {
    "subtotal": 1198.00,
    "item_count": 2
  }
}
```

---

### 8.2 Add Item to Cart

| | |
|---|---|
| **Method** | `POST` |
| **URL** | `/api/v1/cart/items` |
| **Auth Required** | Yes |

**Request Body**

```json
{
  "product_id": 1,
  "quantity": 2,
  "variant_id": null
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `product_id` | integer | Yes | must exist in products table |
| `quantity` | integer | Yes | min:1, max:100 |
| `variant_id` | integer | No | must exist in product_variants table |

**Response** `201 Created`

```json
{
  "message": "Item added to cart"
}
```

**Error Response** `422 Unprocessable Entity`

```json
{
  "message": "Insufficient stock available"
}
```

---

### 8.3 Update Cart Item

| | |
|---|---|
| **Method** | `PUT` |
| **URL** | `/api/v1/cart/items/{cartItemId}` |
| **Auth Required** | Yes |

**Request Body**

```json
{
  "quantity": 3
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `quantity` | integer | Yes | min:1, max:100 |

**Response** `200 OK`

```json
{
  "message": "Cart item updated"
}
```

---

### 8.4 Remove Cart Item

| | |
|---|---|
| **Method** | `DELETE` |
| **URL** | `/api/v1/cart/items/{cartItemId}` |
| **Auth Required** | Yes |

**Response** `200 OK`

```json
{
  "message": "Item removed from cart"
}
```

---

### 8.5 Clear Cart

| | |
|---|---|
| **Method** | `DELETE` |
| **URL** | `/api/v1/cart` |
| **Auth Required** | Yes |

**Response** `200 OK`

```json
{
  "message": "Cart cleared"
}
```

---

## 9. Checkout

### 9.1 Validate Checkout

Validates cart contents and stock availability before placing an order.

| | |
|---|---|
| **Method** | `POST` |
| **URL** | `/api/v1/checkout/validate` |
| **Auth Required** | Yes |

**Request Body**: None required.

**Response** `200 OK`

```json
{
  "success": true,
  "data": {
    "cart": {
      "items_count": 2,
      "subtotal": 1198.00,
      "discount": 100.00,
      "total": 1098.00,
      "coupon": "WELCOME100"
    },
    "addresses": [
      {
        "id": 1,
        "label": "Home",
        "first_name": "John",
        "last_name": "Doe",
        "address_line_1": "123 Main Street",
        "city": "Mumbai",
        "state": "Maharashtra",
        "postal_code": "400001",
        "country": "IN",
        "is_default": true
      }
    ]
  }
}
```

**Error Response** `422 Unprocessable Entity` (out of stock)

```json
{
  "success": false,
  "message": "Some items are out of stock.",
  "errors": [
    {
      "product": "Wooden Building Blocks Set",
      "requested": 5,
      "available": 2
    }
  ]
}
```

**Error Response** `422 Unprocessable Entity` (empty cart)

```json
{
  "success": false,
  "message": "Cart is empty."
}
```

---

### 9.2 Process Checkout

Places an order from the current cart.

| | |
|---|---|
| **Method** | `POST` |
| **URL** | `/api/v1/checkout` |
| **Auth Required** | Yes |

**Request Body**

```json
{
  "shipping_address_id": 1,
  "billing_address_id": null,
  "payment_method": "razorpay",
  "notes": "Please gift wrap this order"
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `shipping_address_id` | integer | Yes | must exist in user_addresses |
| `billing_address_id` | integer | No | must exist in user_addresses; defaults to shipping address |
| `payment_method` | string | Yes | e.g., `razorpay`, `cod`, `upi` |
| `notes` | string | No | max:500 |

**Response** `201 Created`

```json
{
  "success": true,
  "message": "Order placed successfully.",
  "data": {
    "order_id": 42,
    "order_number": "FK-20260225-0042",
    "total": 1098.00,
    "status": "confirmed"
  }
}
```

**Error Response** `422 Unprocessable Entity`

```json
{
  "success": false,
  "message": "\"Wooden Building Blocks Set\" only has 2 item(s) in stock."
}
```

---

## 10. Orders

### 10.1 List Orders

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/orders` |
| **Auth Required** | Yes |

**Query Parameters**

| Parameter | Type | Description |
|-----------|------|-------------|
| `page` | integer | Page number |

**Response** `200 OK`

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 42,
      "order_number": "FK-20260225-0042",
      "status": "confirmed",
      "payment_status": "pending",
      "subtotal": 1198.00,
      "discount": 100.00,
      "shipping_cost": 0.00,
      "tax": 0.00,
      "total": 1098.00,
      "created_at": "2026-02-25T10:00:00.000000Z",
      "items": [
        {
          "id": 1,
          "product_id": 1,
          "product_name": "Wooden Building Blocks Set",
          "quantity": 2,
          "price": 599.00,
          "total": 1198.00,
          "product": {
            "id": 1,
            "name": "Wooden Building Blocks Set",
            "slug": "wooden-building-blocks-set",
            "images": ["https://cdn.foreverkids.com/products/blocks-1.jpg"]
          }
        }
      ]
    }
  ],
  "per_page": 15,
  "total": 3
}
```

---

### 10.2 Show Order

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/orders/{id}` |
| **Auth Required** | Yes |

**Response** `200 OK`

```json
{
  "data": {
    "id": 42,
    "order_number": "FK-20260225-0042",
    "status": "confirmed",
    "payment_status": "pending",
    "subtotal": 1198.00,
    "discount": 100.00,
    "shipping_cost": 0.00,
    "tax": 0.00,
    "total": 1098.00,
    "notes": "Please gift wrap this order",
    "created_at": "2026-02-25T10:00:00.000000Z",
    "cancelled_at": null,
    "items": [
      {
        "id": 1,
        "product_id": 1,
        "variant_id": null,
        "seller_id": 5,
        "product_name": "Wooden Building Blocks Set",
        "sku": "WBS-001",
        "variant_name": null,
        "quantity": 2,
        "mrp": 799.00,
        "price": 599.00,
        "tax": 0.00,
        "discount": 0.00,
        "total": 1198.00,
        "product": {
          "id": 1,
          "name": "Wooden Building Blocks Set",
          "slug": "wooden-building-blocks-set",
          "images": ["https://cdn.foreverkids.com/products/blocks-1.jpg"]
        }
      }
    ],
    "shipping_address": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "address_line_1": "123 Main Street",
      "city": "Mumbai",
      "state": "Maharashtra",
      "postal_code": "400001",
      "country": "IN"
    },
    "billing_address": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "address_line_1": "123 Main Street",
      "city": "Mumbai",
      "state": "Maharashtra",
      "postal_code": "400001",
      "country": "IN"
    },
    "payments": [],
    "shipments": []
  }
}
```

---

### 10.3 Cancel Order

Cancels an order that is in `pending` or `processing` status.

| | |
|---|---|
| **Method** | `POST` |
| **URL** | `/api/v1/orders/{id}/cancel` |
| **Auth Required** | Yes |

**Response** `200 OK`

```json
{
  "message": "Order cancelled successfully"
}
```

**Error Response** `422 Unprocessable Entity`

```json
{
  "message": "Order cannot be cancelled at this stage"
}
```

---

## 11. Reviews

### 11.1 List My Reviews

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/reviews` |
| **Auth Required** | Yes |

**Response** `200 OK`

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "product_id": 1,
      "rating": 5,
      "title": "Great quality!",
      "comment": "My kids love these blocks.",
      "is_approved": true,
      "created_at": "2026-02-10T14:30:00.000000Z",
      "product": {
        "id": 1,
        "name": "Wooden Building Blocks Set",
        "slug": "wooden-building-blocks-set",
        "images": ["https://cdn.foreverkids.com/products/blocks-1.jpg"]
      }
    }
  ],
  "per_page": 15,
  "total": 2
}
```

---

### 11.2 Create Review

| | |
|---|---|
| **Method** | `POST` |
| **URL** | `/api/v1/reviews` |
| **Auth Required** | Yes |

**Request Body**

```json
{
  "product_id": 1,
  "rating": 5,
  "title": "Great quality!",
  "comment": "My kids love these blocks. Very sturdy and safe."
}
```

| Field | Type | Required | Rules |
|-------|------|----------|-------|
| `product_id` | integer | Yes | must exist in products table |
| `rating` | integer | Yes | min:1, max:5 |
| `title` | string | No | max:255 |
| `comment` | string | No | max:2000 |

**Response** `201 Created`

```json
{
  "message": "Review submitted successfully",
  "data": {
    "id": 5,
    "product_id": 1,
    "rating": 5,
    "title": "Great quality!",
    "comment": "My kids love these blocks. Very sturdy and safe.",
    "is_approved": false,
    "created_at": "2026-02-25T12:00:00.000000Z"
  }
}
```

**Error Response** `409 Conflict`

```json
{
  "message": "You have already reviewed this product"
}
```

---

### 11.3 Show Review

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/reviews/{id}` |
| **Auth Required** | Yes |

**Response** `200 OK`

```json
{
  "data": {
    "id": 1,
    "product_id": 1,
    "rating": 5,
    "title": "Great quality!",
    "comment": "My kids love these blocks.",
    "is_approved": true,
    "product": {
      "id": 1,
      "name": "Wooden Building Blocks Set",
      "slug": "wooden-building-blocks-set"
    }
  }
}
```

---

### 11.4 Update Review

| | |
|---|---|
| **Method** | `PUT` |
| **URL** | `/api/v1/reviews/{id}` |
| **Auth Required** | Yes |

**Request Body**

```json
{
  "rating": 4,
  "title": "Updated: Good quality",
  "comment": "Still great but found a small chip."
}
```

**Response** `200 OK`

```json
{
  "message": "Review updated successfully",
  "data": {
    "id": 1,
    "rating": 4,
    "title": "Updated: Good quality",
    "comment": "Still great but found a small chip."
  }
}
```

---

### 11.5 Delete Review

| | |
|---|---|
| **Method** | `DELETE` |
| **URL** | `/api/v1/reviews/{id}` |
| **Auth Required** | Yes |

**Response** `200 OK`

```json
{
  "message": "Review deleted successfully"
}
```

---

## 12. Wishlist

### 12.1 List Wishlist

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/wishlist` |
| **Auth Required** | Yes |

**Query Parameters**

| Parameter | Type | Description |
|-----------|------|-------------|
| `page` | integer | Page number |

**Response** `200 OK`

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "product_id": 1,
      "created_at": "2026-02-15T10:00:00.000000Z",
      "product": {
        "id": 1,
        "name": "Wooden Building Blocks Set",
        "slug": "wooden-building-blocks-set",
        "price": 599.00,
        "mrp": 799.00,
        "images": ["https://cdn.foreverkids.com/products/blocks-1.jpg"]
      }
    }
  ],
  "per_page": 20,
  "total": 3
}
```

---

### 12.2 Add to Wishlist

| | |
|---|---|
| **Method** | `POST` |
| **URL** | `/api/v1/wishlist/{productId}` |
| **Auth Required** | Yes |

**Response** `201 Created`

```json
{
  "message": "Product added to wishlist"
}
```

**Error Response** `409 Conflict`

```json
{
  "message": "Product already in wishlist"
}
```

---

### 12.3 Remove from Wishlist

| | |
|---|---|
| **Method** | `DELETE` |
| **URL** | `/api/v1/wishlist/{productId}` |
| **Auth Required** | Yes |

**Response** `200 OK`

```json
{
  "message": "Product removed from wishlist"
}
```

---

## 13. Recommendations

### 13.1 Popular Products

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/recommendations/popular` |
| **Auth Required** | No |

**Response** `200 OK`

```json
{
  "success": true,
  "data": [
    {
      "id": 5,
      "name": "Magnetic Tiles 60pc",
      "slug": "magnetic-tiles-60pc",
      "price": 1299.00,
      "mrp": 1599.00,
      "rating": 4.8,
      "images": ["https://cdn.foreverkids.com/products/magnetic-1.jpg"]
    }
  ]
}
```

---

### 13.2 Similar Products

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/recommendations/similar/{productId}` |
| **Auth Required** | No |

**Response** `200 OK`

```json
{
  "success": true,
  "data": [
    {
      "id": 7,
      "name": "Wooden Puzzle Animals",
      "slug": "wooden-puzzle-animals",
      "price": 349.00,
      "mrp": 449.00,
      "rating": 4.3,
      "images": ["https://cdn.foreverkids.com/products/puzzle-1.jpg"]
    }
  ]
}
```

---

### 13.3 Recently Viewed

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/recommendations/recently-viewed` |
| **Auth Required** | Yes |

**Query Parameters**

| Parameter | Type | Description |
|-----------|------|-------------|
| `limit` | integer | Number of results (default: 10) |

**Response** `200 OK`

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Wooden Building Blocks Set",
      "slug": "wooden-building-blocks-set",
      "price": 599.00,
      "mrp": 799.00,
      "images": ["https://cdn.foreverkids.com/products/blocks-1.jpg"]
    }
  ]
}
```

---

### 13.4 Personalized Recommendations

Returns personalized product recommendations for the authenticated user. Falls back to popular products if the user has no purchase/view history.

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/recommendations/personalized` |
| **Auth Required** | Yes |

**Response** `200 OK`

```json
{
  "success": true,
  "data": [
    {
      "id": 12,
      "name": "Science Experiment Kit",
      "slug": "science-experiment-kit",
      "price": 899.00,
      "mrp": 1099.00,
      "rating": 4.6,
      "images": ["https://cdn.foreverkids.com/products/science-1.jpg"]
    }
  ]
}
```

---

## 14. Notifications

### 14.1 List Notifications

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/notifications` |
| **Auth Required** | Yes |

**Query Parameters**

| Parameter | Type | Description |
|-----------|------|-------------|
| `page` | integer | Page number |

**Response** `200 OK`

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "type": "order_confirmed",
      "title": "Order Confirmed",
      "message": "Your order FK-20260225-0042 has been confirmed.",
      "data": {
        "order_id": 42,
        "order_number": "FK-20260225-0042"
      },
      "read_at": null,
      "created_at": "2026-02-25T10:01:00.000000Z"
    },
    {
      "id": 2,
      "user_id": 1,
      "type": "promotion",
      "title": "Weekend Sale!",
      "message": "Get 20% off on all building toys this weekend.",
      "data": {
        "category_slug": "building-toys",
        "discount": 20
      },
      "read_at": "2026-02-24T08:00:00.000000Z",
      "created_at": "2026-02-24T06:00:00.000000Z"
    }
  ],
  "per_page": 20,
  "total": 5
}
```

---

### 14.2 Mark Notification as Read

| | |
|---|---|
| **Method** | `PUT` |
| **URL** | `/api/v1/notifications/{id}/read` |
| **Auth Required** | Yes |

**Response** `200 OK`

```json
{
  "message": "Notification marked as read"
}
```

---

### 14.3 Mark All Notifications as Read

| | |
|---|---|
| **Method** | `PUT` |
| **URL** | `/api/v1/notifications/read-all` |
| **Auth Required** | Yes |

**Response** `200 OK`

```json
{
  "message": "All notifications marked as read"
}
```

---

## 15. Preferences

### 15.1 Get Preferences

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/preferences` |
| **Auth Required** | Yes |

**Response** `200 OK`

```json
{
  "success": true,
  "data": {
    "order_updates": true,
    "shipping_updates": true,
    "promotions": false,
    "newsletter": true,
    "review_reminders": true,
    "price_drop_alerts": false,
    "back_in_stock_alerts": true
  }
}
```

---

### 15.2 Update Preferences

| | |
|---|---|
| **Method** | `PUT` |
| **URL** | `/api/v1/preferences` |
| **Auth Required** | Yes |

**Request Body**

Send only the preferences you want to change:

```json
{
  "promotions": true,
  "price_drop_alerts": true,
  "newsletter": false
}
```

All fields are boolean and optional. Only known preference keys are accepted; unknown keys are ignored.

**Response** `200 OK`

```json
{
  "success": true,
  "message": "Preferences updated."
}
```

---

## 16. Home

### 16.1 Home Page Data

Aggregated endpoint that returns all data needed to render the home screen. Response is cached for 15 minutes.

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/home` |
| **Auth Required** | No |

**Response** `200 OK`

```json
{
  "success": true,
  "data": {
    "banners": [
      {
        "id": 1,
        "title": "Summer Collection 2026",
        "image": "https://cdn.foreverkids.com/banners/summer-2026.jpg",
        "link": "/categories/summer-collection",
        "position": 1
      }
    ],
    "categories": [
      {
        "id": 1,
        "name": "Toys & Games",
        "slug": "toys-games",
        "image": "https://cdn.foreverkids.com/categories/toys.jpg"
      },
      {
        "id": 2,
        "name": "Baby & Toddler",
        "slug": "baby-toddler",
        "image": "https://cdn.foreverkids.com/categories/baby.jpg"
      }
    ],
    "featured": [
      {
        "id": 1,
        "name": "Wooden Building Blocks Set",
        "slug": "wooden-building-blocks-set",
        "price": 599.00,
        "mrp": 799.00,
        "rating": 4.5,
        "review_count": 23,
        "images": [
          { "url": "https://cdn.foreverkids.com/products/blocks-1.jpg", "position": 0 }
        ]
      }
    ],
    "new_arrivals": [
      {
        "id": 42,
        "name": "STEM Robot Kit",
        "slug": "stem-robot-kit",
        "price": 2499.00,
        "mrp": 2999.00,
        "rating": 0,
        "created_at": "2026-02-20T08:00:00.000000Z",
        "images": [
          { "url": "https://cdn.foreverkids.com/products/robot-1.jpg", "position": 0 }
        ]
      }
    ],
    "bestsellers": [
      {
        "id": 5,
        "name": "Magnetic Tiles 60pc",
        "slug": "magnetic-tiles-60pc",
        "price": 1299.00,
        "mrp": 1599.00,
        "rating": 4.8,
        "sales_count": 312,
        "images": [
          { "url": "https://cdn.foreverkids.com/products/magnetic-1.jpg", "position": 0 }
        ]
      }
    ],
    "flash_sales": [
      {
        "id": 1,
        "name": "Weekend Flash Sale",
        "is_active": true,
        "start_date": "2026-02-24T00:00:00.000000Z",
        "end_date": "2026-02-26T23:59:59.000000Z",
        "products": [
          {
            "id": 10,
            "name": "Plush Teddy Bear",
            "slug": "plush-teddy-bear",
            "price": 399.00,
            "mrp": 699.00
          }
        ]
      }
    ]
  }
}
```

---

## 17. Sellers

### 17.1 Show Seller

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/sellers/{slug}` |
| **Auth Required** | No |

**Response** `200 OK`

```json
{
  "data": {
    "id": 5,
    "business_name": "Toy World",
    "slug": "toy-world",
    "description": "Premium toys for children of all ages",
    "logo_url": "https://cdn.foreverkids.com/sellers/toy-world-logo.png",
    "banner_url": "https://cdn.foreverkids.com/sellers/toy-world-banner.jpg",
    "rating": 4.7,
    "total_reviews": 89,
    "total_products": 45
  }
}
```

---

### 17.2 Seller Products

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/sellers/{slug}/products` |
| **Auth Required** | No |

**Query Parameters**

| Parameter | Type | Description |
|-----------|------|-------------|
| `per_page` | integer | Items per page (default: 20) |
| `page` | integer | Page number |

**Response** `200 OK`

```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "name": "Wooden Building Blocks Set",
      "slug": "wooden-building-blocks-set",
      "price": 599.00,
      "mrp": 799.00,
      "category": { "id": 3, "name": "Building Toys", "slug": "building-toys" },
      "brand": { "id": 2, "name": "WoodCraft", "slug": "woodcraft" }
    }
  ],
  "per_page": 20,
  "total": 45
}
```

---

## 18. Pages

### 18.1 Show Page (CMS)

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/pages/{slug}` |
| **Auth Required** | No |

**Response** `200 OK`

```json
{
  "data": {
    "id": 1,
    "title": "About Us",
    "slug": "about-us",
    "content": "<h1>About ForeverKids</h1><p>We are dedicated to...</p>",
    "meta_title": "About Us - ForeverKids",
    "meta_description": "Learn about ForeverKids, India's leading children's e-commerce store.",
    "created_at": "2026-01-01T00:00:00.000000Z",
    "updated_at": "2026-02-15T12:00:00.000000Z"
  }
}
```

---

## 19. Settings

### 19.1 Public Settings

| | |
|---|---|
| **Method** | `GET` |
| **URL** | `/api/v1/settings/public` |
| **Auth Required** | No |

**Response** `200 OK`

```json
{
  "data": {
    "site_name": "ForeverKids",
    "site_tagline": "Where Play Meets Learning",
    "currency": "INR",
    "currency_symbol": "₹",
    "contact_email": "support@foreverkids.com",
    "contact_phone": "+91 1800-123-4567",
    "social_links": {
      "facebook": "https://facebook.com/foreverkids",
      "instagram": "https://instagram.com/foreverkids",
      "twitter": "https://twitter.com/foreverkids"
    },
    "free_shipping_threshold": 999.00,
    "min_order_amount": 199.00
  }
}
```

---

## Error Response Format

All error responses follow a consistent format.

### Validation Error (422)

```json
{
  "message": "The email field is required.",
  "errors": {
    "email": [
      "The email field is required."
    ],
    "password": [
      "The password field is required."
    ]
  }
}
```

### Not Found (404)

```json
{
  "message": "Not Found"
}
```

### Unauthorized (401)

```json
{
  "message": "Unauthenticated."
}
```

### Forbidden (403)

```json
{
  "message": "This action is unauthorized."
}
```

### Too Many Requests (429)

```json
{
  "message": "Too Many Attempts.",
  "retry_after": 60
}
```

### Server Error (500)

```json
{
  "message": "Server Error"
}
```

---

## Pagination Format

All paginated endpoints return the standard Laravel pagination envelope:

```json
{
  "current_page": 1,
  "data": [ ... ],
  "first_page_url": "/api/v1/products?page=1",
  "from": 1,
  "last_page": 5,
  "last_page_url": "/api/v1/products?page=5",
  "links": [
    { "url": null, "label": "&laquo; Previous", "active": false },
    { "url": "/api/v1/products?page=1", "label": "1", "active": true },
    { "url": "/api/v1/products?page=2", "label": "2", "active": false },
    { "url": "/api/v1/products?page=2", "label": "Next &raquo;", "active": false }
  ],
  "next_page_url": "/api/v1/products?page=2",
  "path": "/api/v1/products",
  "per_page": 20,
  "prev_page_url": null,
  "to": 20,
  "total": 98
}
```

---

## Rate Limiting

API requests are rate limited. Default limits:

| Scope | Limit |
|-------|-------|
| Unauthenticated | 60 requests per minute |
| Authenticated | 120 requests per minute |

When rate limited, the API returns `429 Too Many Requests` with a `Retry-After` header.

Response headers on every request:

```
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 115
```
