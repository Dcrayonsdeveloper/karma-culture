# Design System

## [AI-REF] UI/UX Guidelines & Component Library

This document defines the visual language and component specifications.

---

## Design Principles

1. **Mobile-First**: Design for mobile, enhance for desktop
2. **Sleek & Minimal**: No bulky elements, ultra-light borders
3. **Fast & Light**: Optimize for performance
4. **Accessible**: WCAG 2.1 AA compliance
5. **Consistent**: Unified patterns across all surfaces

---

## Color System

### Tailwind Configuration

```javascript
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      colors: {
        // Primary Brand
        primary: {
          50: '#fef7ee',
          100: '#fdecd3',
          200: '#fad5a5',
          300: '#f6b76d',
          400: '#f19132',
          500: '#ee7a14',  // Main brand color
          600: '#df5f0a',
          700: '#b9470b',
          800: '#933910',
          900: '#773110',
          950: '#401606',
        },

        // Neutral (Gray scale)
        neutral: {
          50: '#fafafa',
          100: '#f5f5f5',
          200: '#e5e5e5',
          300: '#d4d4d4',
          400: '#a3a3a3',
          500: '#737373',
          600: '#525252',
          700: '#404040',
          800: '#262626',
          900: '#171717',
          950: '#0a0a0a',
        },

        // Semantic Colors
        success: {
          50: '#f0fdf4',
          500: '#22c55e',
          600: '#16a34a',
        },
        warning: {
          50: '#fffbeb',
          500: '#f59e0b',
          600: '#d97706',
        },
        error: {
          50: '#fef2f2',
          500: '#ef4444',
          600: '#dc2626',
        },
        info: {
          50: '#eff6ff',
          500: '#3b82f6',
          600: '#2563eb',
        },

        // Special
        rating: '#fbbf24',  // Star rating gold
        deal: '#dc2626',    // Deal/sale red
        prime: '#1e3a8a',   // Premium blue
      },

      // Spacing (8px base)
      spacing: {
        '0.5': '0.125rem',  // 2px
        '1': '0.25rem',     // 4px
        '1.5': '0.375rem',  // 6px
        '2': '0.5rem',      // 8px
        '2.5': '0.625rem',  // 10px
        '3': '0.75rem',     // 12px
        '4': '1rem',        // 16px
        '5': '1.25rem',     // 20px
        '6': '1.5rem',      // 24px
        '8': '2rem',        // 32px
        '10': '2.5rem',     // 40px
        '12': '3rem',       // 48px
        '16': '4rem',       // 64px
      },

      // Border radius
      borderRadius: {
        'none': '0',
        'sm': '0.25rem',    // 4px
        'DEFAULT': '0.375rem', // 6px
        'md': '0.5rem',     // 8px
        'lg': '0.75rem',    // 12px
        'xl': '1rem',       // 16px
        '2xl': '1.5rem',    // 24px
        'full': '9999px',
      },

      // Font sizes
      fontSize: {
        'xs': ['0.75rem', { lineHeight: '1rem' }],
        'sm': ['0.875rem', { lineHeight: '1.25rem' }],
        'base': ['1rem', { lineHeight: '1.5rem' }],
        'lg': ['1.125rem', { lineHeight: '1.75rem' }],
        'xl': ['1.25rem', { lineHeight: '1.75rem' }],
        '2xl': ['1.5rem', { lineHeight: '2rem' }],
        '3xl': ['1.875rem', { lineHeight: '2.25rem' }],
        '4xl': ['2.25rem', { lineHeight: '2.5rem' }],
      },

      // Shadows (subtle)
      boxShadow: {
        'sm': '0 1px 2px 0 rgb(0 0 0 / 0.05)',
        'DEFAULT': '0 1px 3px 0 rgb(0 0 0 / 0.1)',
        'md': '0 4px 6px -1px rgb(0 0 0 / 0.1)',
        'lg': '0 10px 15px -3px rgb(0 0 0 / 0.1)',
        'card': '0 1px 3px 0 rgb(0 0 0 / 0.05)',
        'dropdown': '0 4px 12px 0 rgb(0 0 0 / 0.15)',
      },

      // Borders (ultra-light)
      borderWidth: {
        DEFAULT: '1px',
      },
      borderColor: {
        DEFAULT: '#e5e5e5',  // neutral-200
      },
    },
  },
}
```

### Color Usage Guidelines

| Use Case | Color | Class |
|----------|-------|-------|
| Primary actions | Primary 500 | `bg-primary-500` |
| Primary hover | Primary 600 | `hover:bg-primary-600` |
| Text primary | Neutral 900 | `text-neutral-900` |
| Text secondary | Neutral 500 | `text-neutral-500` |
| Text muted | Neutral 400 | `text-neutral-400` |
| Borders | Neutral 200 | `border-neutral-200` |
| Background | White | `bg-white` |
| Background alt | Neutral 50 | `bg-neutral-50` |
| Success | Success 500 | `text-success-500` |
| Error | Error 500 | `text-error-500` |
| Warning | Warning 500 | `text-warning-500` |

---

## Typography

### Font Stack

```css
/* Primary font */
font-family: 'Inter', system-ui, -apple-system, sans-serif;

/* Monospace (for prices, codes) */
font-family: 'JetBrains Mono', ui-monospace, monospace;
```

### Type Scale

| Element | Size | Weight | Line Height | Class |
|---------|------|--------|-------------|-------|
| H1 | 2.25rem | 700 | 2.5rem | `text-4xl font-bold` |
| H2 | 1.875rem | 600 | 2.25rem | `text-3xl font-semibold` |
| H3 | 1.5rem | 600 | 2rem | `text-2xl font-semibold` |
| H4 | 1.25rem | 600 | 1.75rem | `text-xl font-semibold` |
| H5 | 1.125rem | 500 | 1.75rem | `text-lg font-medium` |
| Body | 1rem | 400 | 1.5rem | `text-base` |
| Small | 0.875rem | 400 | 1.25rem | `text-sm` |
| Caption | 0.75rem | 400 | 1rem | `text-xs` |
| Price | 1.25rem | 700 | 1.75rem | `text-xl font-bold` |

---

## Component Library

### Buttons

```html
<!-- Primary Button -->
<button class="
    inline-flex items-center justify-center
    px-4 py-2
    text-sm font-medium
    text-white bg-primary-500
    rounded-md
    hover:bg-primary-600
    focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2
    disabled:opacity-50 disabled:cursor-not-allowed
    transition-colors duration-150
">
    Add to Cart
</button>

<!-- Secondary Button -->
<button class="
    inline-flex items-center justify-center
    px-4 py-2
    text-sm font-medium
    text-neutral-700 bg-white
    border border-neutral-200
    rounded-md
    hover:bg-neutral-50
    focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2
    transition-colors duration-150
">
    View Details
</button>

<!-- Ghost Button -->
<button class="
    inline-flex items-center justify-center
    px-4 py-2
    text-sm font-medium
    text-primary-500
    rounded-md
    hover:bg-primary-50
    focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2
    transition-colors duration-150
">
    Learn More
</button>

<!-- Icon Button -->
<button class="
    p-2
    text-neutral-500
    rounded-md
    hover:bg-neutral-100
    focus:outline-none focus:ring-2 focus:ring-primary-500
    transition-colors duration-150
">
    <svg class="w-5 h-5" stroke="currentColor" stroke-width="1">...</svg>
</button>
```

### Input Fields

```html
<!-- Text Input -->
<div class="space-y-1">
    <label for="email" class="block text-sm font-medium text-neutral-700">
        Email
    </label>
    <input
        type="email"
        id="email"
        class="
            block w-full
            px-3 py-2
            text-sm text-neutral-900
            placeholder-neutral-400
            bg-white
            border border-neutral-200
            rounded-md
            focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent
            disabled:bg-neutral-50 disabled:text-neutral-500
            transition-shadow duration-150
        "
        placeholder="you@example.com"
    >
</div>

<!-- Input with Error -->
<div class="space-y-1">
    <label class="block text-sm font-medium text-neutral-700">Email</label>
    <input class="
        block w-full px-3 py-2
        text-sm text-neutral-900
        border border-error-500
        rounded-md
        focus:ring-2 focus:ring-error-500
    ">
    <p class="text-sm text-error-500">Please enter a valid email address</p>
</div>

<!-- Search Input -->
<div class="relative">
    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-neutral-400" stroke="currentColor" stroke-width="1">
        <!-- Search icon -->
    </svg>
    <input
        type="search"
        class="
            block w-full
            pl-10 pr-4 py-2
            text-sm
            bg-neutral-50
            border border-neutral-200
            rounded-full
            focus:bg-white focus:ring-2 focus:ring-primary-500
            transition-all duration-150
        "
        placeholder="Search products..."
    >
</div>
```

### Cards

```html
<!-- Product Card -->
<article class="
    group
    bg-white
    border border-neutral-100
    rounded-lg
    overflow-hidden
    hover:shadow-md
    transition-shadow duration-200
">
    <!-- Image -->
    <div class="relative aspect-square overflow-hidden bg-neutral-50">
        <img
            src="..."
            alt="Product name"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            loading="lazy"
        >
        <!-- Wishlist button -->
        <button class="
            absolute top-2 right-2
            p-1.5
            bg-white/80 backdrop-blur-sm
            rounded-full
            hover:bg-white
            transition-colors
        ">
            <svg class="w-5 h-5 text-neutral-400 hover:text-error-500" stroke="currentColor" stroke-width="1.5">
                <!-- Heart icon -->
            </svg>
        </button>
        <!-- Discount badge -->
        <span class="
            absolute top-2 left-2
            px-2 py-0.5
            text-xs font-medium
            text-white bg-deal
            rounded
        ">
            -20%
        </span>
    </div>

    <!-- Content -->
    <div class="p-3 space-y-2">
        <!-- Brand -->
        <p class="text-xs text-neutral-500 uppercase tracking-wide">Brand Name</p>

        <!-- Title -->
        <h3 class="text-sm font-medium text-neutral-900 line-clamp-2">
            <a href="#" class="hover:text-primary-500">
                Product Title Goes Here With Two Lines Maximum
            </a>
        </h3>

        <!-- Rating -->
        <div class="flex items-center gap-1">
            <div class="flex items-center px-1.5 py-0.5 bg-success-50 rounded text-xs">
                <span class="font-medium text-success-600">4.5</span>
                <svg class="w-3 h-3 ml-0.5 text-success-500" fill="currentColor">
                    <!-- Star icon -->
                </svg>
            </div>
            <span class="text-xs text-neutral-400">(1,234)</span>
        </div>

        <!-- Price -->
        <div class="flex items-baseline gap-2">
            <span class="text-lg font-bold text-neutral-900">₹1,299</span>
            <span class="text-sm text-neutral-400 line-through">₹1,599</span>
        </div>
    </div>
</article>
```

### Navigation

```html
<!-- Header -->
<header class="sticky top-0 z-50 bg-white border-b border-neutral-100">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between h-14">
            <!-- Logo -->
            <a href="/" class="flex-shrink-0">
                <svg class="h-8 w-auto text-primary-500">...</svg>
            </a>

            <!-- Search (desktop) -->
            <div class="hidden md:flex flex-1 max-w-2xl mx-8">
                <div class="relative w-full">
                    <input type="search" class="w-full pl-10 pr-4 py-2 text-sm bg-neutral-50 border-0 rounded-full">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-neutral-400">...</svg>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-1">
                <button class="p-2 text-neutral-600 hover:bg-neutral-100 rounded-full md:hidden">
                    <svg class="w-5 h-5"><!-- Search --></svg>
                </button>
                <a href="/wishlist" class="p-2 text-neutral-600 hover:bg-neutral-100 rounded-full">
                    <svg class="w-5 h-5"><!-- Heart --></svg>
                </a>
                <a href="/cart" class="relative p-2 text-neutral-600 hover:bg-neutral-100 rounded-full">
                    <svg class="w-5 h-5"><!-- Cart --></svg>
                    <span class="absolute -top-0.5 -right-0.5 w-4 h-4 text-xs font-medium text-white bg-primary-500 rounded-full flex items-center justify-center">
                        3
                    </span>
                </a>
                <a href="/account" class="p-2 text-neutral-600 hover:bg-neutral-100 rounded-full">
                    <svg class="w-5 h-5"><!-- User --></svg>
                </a>
            </div>
        </div>
    </div>
</header>
```

---

## SVG Icons

### Icon Guidelines

- **Stroke width**: 1px (default), 1.5px (emphasis)
- **Size**: 20x20 (default), 16x16 (small), 24x24 (large)
- **Style**: Outline only, no fills
- **Colors**: Inherit from text color (`currentColor`)

```html
<!-- Icon template -->
<svg
    class="w-5 h-5"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1"
    stroke-linecap="round"
    stroke-linejoin="round"
>
    <!-- Path data -->
</svg>
```

### Core Icons

```html
<!-- Search -->
<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
    <circle cx="11" cy="11" r="8"/>
    <path d="M21 21l-4.35-4.35"/>
</svg>

<!-- Cart -->
<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
    <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
    <path d="M3 6h18"/>
    <path d="M16 10a4 4 0 01-8 0"/>
</svg>

<!-- Heart -->
<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
    <path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>
</svg>

<!-- Star (filled for ratings) -->
<svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
</svg>

<!-- Chevron -->
<svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
    <path d="M9 18l6-6-6-6"/>
</svg>

<!-- Check -->
<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M20 6L9 17l-5-5"/>
</svg>

<!-- X (Close) -->
<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
    <path d="M18 6L6 18M6 6l12 12"/>
</svg>
```

---

## Micro-Animations

### Animation Classes

```css
/* resources/css/animations.css */

/* Fade in */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
.animate-fade-in {
    animation: fadeIn 0.2s ease-out;
}

/* Slide up */
@keyframes slideUp {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-slide-up {
    animation: slideUp 0.2s ease-out;
}

/* Scale in */
@keyframes scaleIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.animate-scale-in {
    animation: scaleIn 0.15s ease-out;
}

/* Shake (for errors) */
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-4px); }
    75% { transform: translateX(4px); }
}
.animate-shake {
    animation: shake 0.3s ease-in-out;
}

/* Pulse (for notifications) */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Spin (for loading) */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.animate-spin {
    animation: spin 1s linear infinite;
}
```

### Transition Utilities

```javascript
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      transitionDuration: {
        '150': '150ms',
        '200': '200ms',
        '300': '300ms',
      },
      transitionTimingFunction: {
        'ease-out-expo': 'cubic-bezier(0.19, 1, 0.22, 1)',
      },
    },
  },
}
```

### Usage Examples

```html
<!-- Button hover -->
<button class="transition-colors duration-150 hover:bg-primary-600">
    Click me
</button>

<!-- Card hover -->
<div class="transition-shadow duration-200 hover:shadow-md">
    Card content
</div>

<!-- Image zoom -->
<img class="transition-transform duration-300 group-hover:scale-105">

<!-- Dropdown -->
<div x-show="open"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0 translate-y-1"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-1">
    Dropdown content
</div>
```

---

## Responsive Breakpoints

```javascript
// tailwind.config.js
module.exports = {
  theme: {
    screens: {
      'xs': '375px',   // Small phones
      'sm': '640px',   // Large phones
      'md': '768px',   // Tablets
      'lg': '1024px',  // Small laptops
      'xl': '1280px',  // Desktops
      '2xl': '1536px', // Large screens
    },
  },
}
```

### Mobile-First Patterns

```html
<!-- Grid: 2 cols mobile, 3 tablet, 4 desktop -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">

<!-- Stack on mobile, row on desktop -->
<div class="flex flex-col md:flex-row gap-4">

<!-- Hide on mobile, show on desktop -->
<div class="hidden md:block">

<!-- Full width mobile, contained desktop -->
<div class="w-full max-w-7xl mx-auto px-4">
```

---

## Accessibility

### Focus States

```html
<!-- Always visible focus ring -->
<button class="
    focus:outline-none
    focus-visible:ring-2
    focus-visible:ring-primary-500
    focus-visible:ring-offset-2
">
```

### ARIA Patterns

```html
<!-- Loading button -->
<button aria-busy="true" aria-label="Adding to cart">
    <svg class="animate-spin" aria-hidden="true">...</svg>
    Adding...
</button>

<!-- Expandable section -->
<button aria-expanded="false" aria-controls="panel-1">
    Section Title
</button>
<div id="panel-1" hidden>Content</div>

<!-- Live region for updates -->
<div aria-live="polite" aria-atomic="true" class="sr-only">
    Item added to cart
</div>
```

### Screen Reader Only

```css
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border-width: 0;
}
```

---

## Form Validation

### Frontend Validation States

```html
<!-- Valid field -->
<input class="border-success-500 focus:ring-success-500">
<div class="flex items-center gap-1 text-sm text-success-600">
    <svg class="w-4 h-4"><!-- Check icon --></svg>
    Email is available
</div>

<!-- Invalid field -->
<input class="border-error-500 focus:ring-error-500" aria-invalid="true" aria-describedby="email-error">
<p id="email-error" class="text-sm text-error-500">Please enter a valid email</p>

<!-- Loading state -->
<div class="relative">
    <input class="pr-10">
    <svg class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 animate-spin text-neutral-400">...</svg>
</div>
```

---

## Do's and Don'ts

### Do ✓
- Use semantic HTML elements
- Provide adequate color contrast (4.5:1 minimum)
- Include loading states for async actions
- Use `currentColor` for icon colors
- Keep borders ultra-light (neutral-200)
- Use consistent spacing (4px grid)
- Optimize images for web (WebP format)
- Test on real mobile devices

### Don't ✗
- Use inline styles (`style="..."`)
- Use dark or heavy borders
- Use bulky/heavy shadows
- Hardcode colors (use Tailwind classes)
- Skip focus states
- Use small touch targets (< 44px)
- Forget loading/empty/error states
- Use excessive animations

