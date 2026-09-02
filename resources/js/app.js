import './bootstrap';

// Alpine.js Core
import Alpine from 'alpinejs';

// Alpine.js Plugins
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';
import intersect from '@alpinejs/intersect';

// Register plugins
Alpine.plugin(focus);
Alpine.plugin(collapse);
Alpine.plugin(intersect);

// Make Alpine available globally
window.Alpine = Alpine;

// ========================================
// Global Utilities
// ========================================

/**
 * Format currency (INR by default)
 */
window.formatCurrency = function(amount, currency = 'INR') {
    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: currency,
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(amount);
};

/**
 * Debounce function
 */
window.debounce = function(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};

/**
 * Throttle function
 */
window.throttle = function(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
};

// ========================================
// Alpine.js Global Data/Stores
// ========================================

/**
 * Toast notification store
 */
Alpine.store('toast', {
    items: [],

    show(message, type = 'info', duration = 3000) {
        const id = Date.now();
        this.items.push({ id, message, type });

        if (duration > 0) {
            setTimeout(() => this.remove(id), duration);
        }

        return id;
    },

    success(message, duration = 3000) {
        return this.show(message, 'success', duration);
    },

    error(message, duration = 5000) {
        return this.show(message, 'error', duration);
    },

    warning(message, duration = 4000) {
        return this.show(message, 'warning', duration);
    },

    info(message, duration = 3000) {
        return this.show(message, 'info', duration);
    },

    remove(id) {
        this.items = this.items.filter(item => item.id !== id);
    },

    clear() {
        this.items = [];
    }
});

/**
 * Cart store
 */
Alpine.store('cart', {
    items: [],
    itemCount: 0,
    isOpen: false,
    isLoading: false,
    recommendations: [],

    get count() {
        return this.itemCount;
    },

    get subtotal() {
        return this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    },

    _updateCount() {
        this.itemCount = this.items.reduce((sum, item) => sum + item.quantity, 0);
    },

    async fetch() {
        this.isLoading = true;
        try {
            const response = await axios.get('/cart/data');
            this.items = response.data.items || [];
            this.itemCount = response.data.cart_count || this.items.reduce((sum, item) => sum + item.quantity, 0);
        } catch (error) {
            console.error('Failed to fetch cart:', error);
        } finally {
            this.isLoading = false;
        }
    },

    // `reveal: false` puts the item in the cart without the toast, the drawer or
    // the recommendations request that normally confirm it. Buy Now needs that:
    // it is about to navigate to checkout, so opening the drawer only flashes it
    // on screen for a frame. Returns whether the item actually made it in.
    async add(productId, quantity = 1, variantId = null, size = null, colour = null, { reveal = true } = {}) {
        this.isLoading = true;
        try {
            const response = await axios.post('/cart/add', {
                product_id: productId,
                variant_id: variantId,
                quantity: quantity,
                size: size,
                colour: colour
            });
            // Update count immediately from response
            if (response.data.cart_count !== undefined) {
                this.itemCount = response.data.cart_count;
            }
            if (reveal) {
                Alpine.store('toast').success(response.data.message || 'Added to cart');
                await this.fetch();
                this.open();
                this.fetchRecommendations();
            }
            return true;
        } catch (error) {
            const msg = error.response?.data?.error || 'Failed to add to cart';
            Alpine.store('toast').error(msg);
            console.error('Failed to add to cart:', error);
            return false;
        } finally {
            this.isLoading = false;
        }
    },

    async update(itemId, quantity) {
        this.isLoading = true;
        try {
            const response = await axios.put(`/cart/${itemId}`, {
                quantity: quantity
            });
            if (response.data.cart_count !== undefined) {
                this.itemCount = response.data.cart_count;
            }
            await this.fetch();
        } catch (error) {
            Alpine.store('toast').error('Failed to update cart');
            console.error('Failed to update cart:', error);
        } finally {
            this.isLoading = false;
        }
    },

    async remove(itemId) {
        this.isLoading = true;
        try {
            await axios.delete(`/cart/${itemId}`);
            Alpine.store('toast').info('Item removed from cart');
            await this.fetch();
        } catch (error) {
            Alpine.store('toast').error('Failed to remove item');
            console.error('Failed to remove from cart:', error);
        } finally {
            this.isLoading = false;
        }
    },

    toggle() {
        this.isOpen = !this.isOpen;
    },

    open() {
        this.isOpen = true;
    },

    close() {
        this.isOpen = false;
    },

    async fetchRecommendations() {
        try {
            const response = await axios.get('/cart/recommendations');
            this.recommendations = response.data.products || [];
        } catch (error) {
            console.error('Failed to fetch recommendations:', error);
        }
    }
});

/**
 * Wishlist store
 */
Alpine.store('wishlist', {
    ids: [],            // product IDs — persisted in a browser COOKIE (works for guests)
    items: [],          // product data for the drawer / wishlist page
    isOpen: false,      // wishlist drawer (popup) open state
    isLoading: false,

    init() {
        this.ids = this.readCookie();
        if (this.ids.length) this.fetch();
    },

    get count() {
        return this.ids.length;
    },

    // ---- cookie persistence ----
    readCookie() {
        const m = document.cookie.match(/(?:^|;\s*)kk_wishlist=([^;]*)/);
        if (!m) return [];
        try {
            return JSON.parse(decodeURIComponent(m[1])).map(n => parseInt(n, 10)).filter(Boolean);
        } catch (e) {
            return [];
        }
    },
    saveCookie() {
        const value = encodeURIComponent(JSON.stringify(this.ids));
        document.cookie = 'kk_wishlist=' + value + '; path=/; max-age=' + (60 * 60 * 24 * 365) + '; SameSite=Lax';
    },

    has(productId) {
        return this.ids.includes(parseInt(productId, 10));
    },

    async fetch() {
        if (!this.ids.length) { this.items = []; return; }
        this.isLoading = true;
        try {
            const res = await axios.get('/wishlist-items', { params: { ids: this.ids.join(',') } });
            const byId = {};
            (res.data.items || []).forEach(p => byId[p.id] = p);
            this.items = this.ids.map(id => byId[id]).filter(Boolean);
        } catch (e) {
            this.items = [];
        } finally {
            this.isLoading = false;
        }
    },

    async toggle(productId) {
        productId = parseInt(productId, 10);
        if (this.has(productId)) {
            this.remove(productId);
        } else {
            await this.add(productId);
        }
    },

    async add(productId) {
        productId = parseInt(productId, 10);
        if (this.has(productId)) return;

        this.ids.push(productId);
        this.saveCookie();
        // Fetch first: the toast is cosmetic, and if it ever throws it must not
        // stop the item's data loading — that left the id saved but the wishlist
        // page rendering as empty.
        await this.fetch();
        try { Alpine.store('toast').success('Added to wishlist'); } catch (e) {}
    },

    remove(productId) {
        productId = parseInt(productId, 10);
        this.ids = this.ids.filter(id => id !== productId);
        this.items = this.items.filter(p => p.id !== productId);
        this.saveCookie();
        Alpine.store('toast').info('Removed from wishlist');
    },

    open() {
        try { Alpine.store('cart').close(); } catch (e) {}
        this.isOpen = true;
    },
    close() { this.isOpen = false; },
    // legacy alias
    fetchRemote() { this.fetch(); }
});

/**
 * Auth Modal store
 */
Alpine.store('authModal', {
    isOpen: false,
    isLoading: false,
    mode: 'login',
    errors: {},
    message: '',

    open(mode = 'login') {
        this.mode = mode;
        this.errors = {};
        this.message = '';
        this.isOpen = true;
        document.body.style.overflow = 'hidden';
    },

    close() {
        this.isOpen = false;
        this.errors = {};
        this.message = '';
        document.body.style.overflow = '';
    },

    switchMode(mode) {
        this.mode = mode;
        this.errors = {};
        this.message = '';
    },

    async login(email, password, remember) {
        this.isLoading = true;
        this.errors = {};
        try {
            const response = await axios.post('/login', {
                email: email,
                password: password,
                remember: remember
            });
            this.close();
            window.location.reload();
        } catch (error) {
            if (error.response && error.response.status === 422) {
                this.errors = error.response.data.errors || {};
                if (error.response.data.message) {
                    this.message = error.response.data.message;
                }
            } else {
                this.message = 'Something went wrong. Please try again.';
            }
        } finally {
            this.isLoading = false;
        }
    },

    async register(name, email, password, passwordConfirmation) {
        this.isLoading = true;
        this.errors = {};
        try {
            const response = await axios.post('/register', {
                full_name: name,
                email: email,
                password: password,
                password_confirmation: passwordConfirmation,
                terms: true
            });
            this.close();
            window.location.reload();
        } catch (error) {
            if (error.response && error.response.status === 422) {
                this.errors = error.response.data.errors || {};
                if (error.response.data.message) {
                    this.message = error.response.data.message;
                }
            } else {
                this.message = 'Something went wrong. Please try again.';
            }
        } finally {
            this.isLoading = false;
        }
    }
});

// ========================================
// Alpine.js Reusable Components
// ========================================

/**
 * Dropdown component
 */
Alpine.data('dropdown', () => ({
    open: false,

    toggle() {
        this.open = !this.open;
    },

    close() {
        this.open = false;
    }
}));

/**
 * Modal component
 */
Alpine.data('modal', (initialOpen = false) => ({
    open: initialOpen,

    show() {
        this.open = true;
        document.body.classList.add('overflow-hidden');
    },

    hide() {
        this.open = false;
        document.body.classList.remove('overflow-hidden');
    },

    toggle() {
        if (this.open) {
            this.hide();
        } else {
            this.show();
        }
    }
}));

/**
 * Tabs component
 */
Alpine.data('tabs', (initialTab = null) => ({
    activeTab: initialTab,

    isActive(tab) {
        return this.activeTab === tab;
    },

    select(tab) {
        this.activeTab = tab;
    }
}));

/**
 * Accordion component
 */
Alpine.data('accordion', (allowMultiple = false) => ({
    openItems: [],
    allowMultiple: allowMultiple,

    isOpen(item) {
        return this.openItems.includes(item);
    },

    toggle(item) {
        if (this.isOpen(item)) {
            this.openItems = this.openItems.filter(i => i !== item);
        } else {
            if (this.allowMultiple) {
                this.openItems.push(item);
            } else {
                this.openItems = [item];
            }
        }
    }
}));

/**
 * Quantity selector component
 */
Alpine.data('quantitySelector', (initialValue = 1, min = 1, max = 99) => ({
    quantity: initialValue,
    min: min,
    max: max,

    increment() {
        if (this.quantity < this.max) {
            this.quantity++;
        }
    },

    decrement() {
        if (this.quantity > this.min) {
            this.quantity--;
        }
    },

    set(value) {
        const num = parseInt(value) || this.min;
        this.quantity = Math.max(this.min, Math.min(this.max, num));
    }
}));

/**
 * Image gallery component
 */
Alpine.data('imageGallery', (images = []) => ({
    images: images,
    currentIndex: 0,

    get currentImage() {
        return this.images[this.currentIndex] || null;
    },

    get hasMultiple() {
        return this.images.length > 1;
    },

    select(index) {
        if (index >= 0 && index < this.images.length) {
            this.currentIndex = index;
        }
    },

    next() {
        this.currentIndex = (this.currentIndex + 1) % this.images.length;
    },

    prev() {
        this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
    }
}));

/**
 * Search component with debounce
 */
Alpine.data('search', (endpoint = '/api/search') => ({
    query: '',
    results: [],
    isLoading: false,
    isOpen: false,
    selectedIndex: -1,
    endpoint: endpoint,

    async search() {
        if (this.query.length < 2) {
            this.results = [];
            this.isOpen = false;
            return;
        }

        this.isLoading = true;
        this.isOpen = true;

        try {
            const response = await axios.get(this.endpoint, {
                params: { q: this.query }
            });
            this.results = response.data.results || [];
        } catch (error) {
            console.error('Search failed:', error);
            this.results = [];
        } finally {
            this.isLoading = false;
        }
    },

    clear() {
        this.query = '';
        this.results = [];
        this.isOpen = false;
        this.selectedIndex = -1;
    },

    close() {
        this.isOpen = false;
        this.selectedIndex = -1;
    },

    selectNext() {
        if (this.selectedIndex < this.results.length - 1) {
            this.selectedIndex++;
        }
    },

    selectPrev() {
        if (this.selectedIndex > 0) {
            this.selectedIndex--;
        }
    },

    selectCurrent() {
        if (this.selectedIndex >= 0 && this.results[this.selectedIndex]) {
            window.location.href = this.results[this.selectedIndex].url;
        }
    }
}));

// ========================================
// Initialize on page load
// ========================================

function initStores() {
    // Always fetch cart (works for both guests and authenticated users)
    Alpine.store('cart').fetch();

    // The wishlist lives in a cookie and works for guests too, so re-read it
    // here as well as in the store's init(): whichever runs first wins, and a
    // signed-out shopper is no longer left with an empty list.
    const wishlist = Alpine.store('wishlist');
    wishlist.ids = wishlist.readCookie();
    if (wishlist.ids.length) wishlist.fetch();
}

// Handle timing: if DOM already loaded (module scripts can run late), init immediately
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStores);
} else {
    initStores();
}

// ========================================
// Marketing / conversion components (offer popup, purchase notification,
// exit-intent cart popup). Registered here so init() always runs reliably.
// ========================================
const _csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const _seen = (k) => { try { if (localStorage.getItem(k)) return true; } catch (e) {} return document.cookie.split('; ').some((c) => c.startsWith(k + '=')); };
const _markSeen = (k) => { try { localStorage.setItem(k, '1'); } catch (e) {} document.cookie = `${k}=1; path=/; max-age=${60 * 60 * 24 * 30}; SameSite=Lax`; };
const _validPhone = (v) => /^[0-9]{10}$/.test((v || '').replace(/\D/g, ''));

// Client-side mirror of App\Rules\PersonName (see also ValidationRules::name).
// Kept message-for-message identical to the server so a name the popup accepts
// is never bounced back by the endpoint, and vice versa. Returns '' when valid.
const _NAME_CHARSET = /^[\p{L}\p{M}][\p{L}\p{M}  '’.\-]*$/u;
const _NAME_MASHED = /(\p{L})\1{4,}/u;
const _NAME_URLISH = /(?:https?|ftp|file|javascript|data|vbscript):|(?:^|\s)www\.|\.(?:com|net|org|edu|gov|io|co|in|uk|ru|de|fr|xyz|info|biz|shop|online|site|top|club|live|app|dev)(?:\b|$)/iu;

const _nameError = (v) => {
    const name = (v || '').trim();
    if (!name) return 'Please enter your name.';
    // Count code points, not UTF-16 units: an emoji or an astral-plane letter is
    // one character to the user and to PHP's mb_strlen, but two to `.length`.
    const len = [...name].length;
    if (len < 2) return 'Please enter your full name.';
    if (len > 100) return 'Please keep your name under 100 characters.';
    if (!_NAME_CHARSET.test(name)) return 'The name may only contain letters, spaces, hyphens, apostrophes and periods.';
    if (_NAME_URLISH.test(name)) return 'The name may not contain a web address.';
    if (_NAME_MASHED.test(name)) return 'Please enter a real name.';
    return '';
};

// Client-side mirror of App\Rules\IndianMobile::normalize(). Returns the bare
// ten digits, or null when the number is not one - so, like the PHP, it doubles
// as the validity check. The prefix stripping is the whole point: a naive
// client test of /^[6-9]\d{9}$/ against the raw field rejects "+91 98765 43210",
// which the server accepts, and a client rule stricter than the server turns a
// valid signup into one the shopper cannot complete.
const _normalizeMobile = (value) => {
    let d = (value || '').replace(/\D+/g, '');
    if (d.length === 13 && d.startsWith('091')) d = d.slice(3);
    else if (d.length === 12 && d.startsWith('91')) d = d.slice(2);
    else if (d.length === 11 && d.startsWith('0')) d = d.slice(1);
    if (!/^[6-9]\d{9}$/.test(d)) return null;
    if (/^(\d)\1{9}$/.test(d)) return null; // 9999999999 - legal shape, never a subscriber
    return d;
};

// The signup fields ask for the bare ten digits and hold the box to exactly
// that: an Indian mobile number IS ten digits, and a field that quietly takes
// fifteen is one a shopper can fill in completely and still get wrong.
//
// Deliberately not `maxlength="10"`. The browser applies maxlength to a PASTE
// before any script sees it, so "+91 98765 43210" would arrive as "+91 98765"
// with the real digits already thrown away. Stripping the prefix first and
// cutting afterwards keeps the number that was actually pasted.
//
// The prefix stripping only fires once there are more than ten digits, which is
// also what makes typing the country code by hand work: "919876543210" loses
// its 91 on the eleventh keystroke rather than the second, so the digits on
// screen are never rewritten while they still read as a number in progress.
const _capMobile = (value) => {
    let d = (value || '').replace(/\D+/g, '');
    while (d.length > 10 && (d.startsWith('91') || d.startsWith('0'))) {
        d = d.startsWith('91') ? d.slice(2) : d.slice(1);
    }
    return d.slice(0, 10);
};

// ========================================
// Create Account form - inline field validation (auth/login.blade.php)
// ========================================
//
// The form still posts normally and the server is still the authority. This
// only runs the checks that CAN be answered in the browser, at the moment the
// shopper leaves a field, instead of making them submit the whole form to find
// out that the name had a digit in it.
//
// Two server rules are deliberately NOT mirrored, because answering them needs
// the database: whether the email or the mobile number is already registered.
// An endpoint that reports whether an address has an account is an enumeration
// oracle, so those two keep surfacing after submit, exactly as they do today.
//
// Every message below is copied from RegisterController's $messages array, or
// from the rule object that raises it, so a field says the same sentence
// whichever side rejected it. The _nameError() above is deliberately not
// reused: it words its messages for the popup's "name" field, and this form's
// server messages say "full name".
const _fullNameError = (v) => {
    const name = (v || '').trim();
    // RegisterController maps full_name.required and full_name.min to the same
    // sentence, so both land here.
    if (!name) return 'Please enter your full name.';
    // Code points, not UTF-16 units - PHP's mb_strlen counts the same way.
    const len = [...name].length;
    if (len < 2) return 'Please enter your full name.';
    if (len > 30) return 'Please keep your name to 30 characters or fewer.';
    if (!_NAME_CHARSET.test(name)) return 'The full name may only contain letters, spaces, hyphens, apostrophes and periods.';
    if (_NAME_URLISH.test(name)) return 'The full name may not contain a web address.';
    if (_NAME_MASHED.test(name)) return 'Please enter a real name.';
    // The single field is split on the first space into two varchar(50)
    // columns, so the halves are what the length limit really applies to.
    const first = name.split(' ')[0];
    const last = name.slice(first.length).trim();
    if ([...first].length > 50 || [...last].length > 50) {
        return 'Please enter your first and last name, each 50 characters or fewer.';
    }
    return '';
};

// Client-side mirror of App\Rules\EmailAddress, which registration adds on top
// of Laravel's email:strict - same checks, in the same order, in the same
// sentences, so the browser and the server never disagree about which part of
// the address is wrong.
//
// The RFC, and therefore email:strict, is far wider than what any provider
// issues: "_asha@gmail.com" and "!!!@gmail.com" are both legal mail. Signup is
// where an address is minted rather than matched, so the local part has to open
// on a letter or a digit and carry only . _ % + - ' after it.
const _EMAIL_LOCAL = /^[A-Za-z0-9](?:[A-Za-z0-9._%+\-']*[A-Za-z0-9])?$/;
const _EMAIL_DOMAIN = /^(?:[A-Za-z0-9](?:[A-Za-z0-9\-]*[A-Za-z0-9])?\.)+[A-Za-z]{2,63}$/;
const _EMAIL_GENERIC = 'Enter a valid email address, like you@example.com.';
const _EMAIL_STARTS = 'An email address must start with a letter or a number.';
const _EMAIL_DOTS = 'An email address cannot contain two dots in a row.';

const _emailError = (v) => {
    const email = (v || '').trim();
    if (!email) return 'Please enter your email address.';
    if (email.length > 255) return 'That email address is too long.';

    const parts = email.split('@');
    if (parts.length !== 2) return _EMAIL_GENERIC;
    const [local, domain] = parts;

    // Empty local part lands here too: "" has no opening letter either, and the
    // server says the same sentence about it.
    if (!/^[A-Za-z0-9]/.test(local)) return _EMAIL_STARTS;
    if (email.includes('..')) return _EMAIL_DOTS;
    if (!_EMAIL_LOCAL.test(local)) return "The part before the @ may only contain letters, numbers, and these symbols: . _ % + - '";
    if (!_EMAIL_DOMAIN.test(domain)) return _EMAIL_GENERIC;
    return '';
};

const _mobileError = (v) => {
    const raw = (v || '').trim();
    if (!raw) return 'Please enter your mobile number.';
    if (raw.length > 20) return 'That phone number is too long.';
    if (_normalizeMobile(raw) === null) return 'Please enter a valid 10-digit mobile number starting with 6, 7, 8 or 9.';
    return '';
};

// Mirrors Password::min(8)->mixedCase()->numbers()->symbols() from
// AppServiceProvider. Laravel tests those with Unicode properties rather than
// ASCII classes, so this does too - an accented capital still counts as one.
// Only the first unmet requirement is shown; the hint under the field already
// lists all four.
const _passwordError = (v) => {
    const pw = v || '';
    if (!pw) return 'Please choose a password.';
    if (pw.length < 8) return 'Your password must be at least 8 characters long.';
    if (pw.length > 255) return 'Your password must be 255 characters or fewer.';
    if (!/\p{Lu}/u.test(pw) || !/\p{Ll}/u.test(pw)) return 'Your password must include both an uppercase and a lowercase letter.';
    if (!/\p{N}/u.test(pw)) return 'Your password must include at least one number.';
    if (!/[\p{Z}\p{S}\p{P}]/u.test(pw)) return 'Your password must include at least one special character, such as @ # ! or ?.';
    return '';
};

// Problems that are already true no matter what is typed next: the value is
// past its limit, or carries a character the field never accepts. Those are
// worth saying on the keystroke that causes them, rather than making the
// shopper leave the field to be told they typed one character too many.
//
// Everything else a full check would catch - an empty box, half an address, a
// name that is still one letter long - is unfinished rather than wrong, and
// still waits for blur. That distinction is the whole reason this is a separate
// function and not a flag on messageFor().
const _instantError = (field, value) => {
    const trimmed = (value || '').trim();
    if (!trimmed) return '';

    if (field === 'full_name') {
        if ([...trimmed].length > 30) return 'Please keep your name to 30 characters or fewer.';
        // A digit or a symbol in a name is wrong the moment it appears. The
        // rest of _fullNameError (too short, reads as a URL) is not: both can
        // still be typed out of.
        if (!_NAME_CHARSET.test(trimmed)) return 'The full name may only contain letters, spaces, hyphens, apostrophes and periods.';
        return '';
    }

    if (field === 'email') {
        if (trimmed.length > 255) return 'That email address is too long.';
        if (!/^[A-Za-z0-9]/.test(trimmed)) return _EMAIL_STARTS;
        if (trimmed.includes('..')) return _EMAIL_DOTS;
        return '';
    }

    return '';
};

Alpine.data('kkRegisterForm', (serverErrors = {}) => ({
    // One message slot per field, seeded from whatever the server just said, so
    // a rejected submit and a live check write to the same place and a field can
    // never end up showing two contradictory messages at once.
    errors: { ...serverErrors },

    // A field is judged only once the shopper has left it - typing "p" into an
    // empty email box should not immediately be called wrong. A field the server
    // already flagged starts out touched, so its message clears as it is fixed.
    touched: Object.fromEntries(
        Object.entries(serverErrors).filter(([, m]) => m).map(([f]) => [f, true])
    ),

    fields: ['full_name', 'email', 'phone', 'password', 'password_confirmation', 'terms'],

    messageFor(field) {
        const el = this.$refs[field];
        const value = el ? el.value : '';
        switch (field) {
            case 'full_name': return _fullNameError(value);
            case 'email': return _emailError(value);
            case 'phone': return _mobileError(value);
            case 'password': return _passwordError(value);
            case 'password_confirmation': {
                const pw = this.$refs.password ? this.$refs.password.value : '';
                // With both boxes empty the password field carries the message;
                // adding "they do not match" is just noise. It is also what the
                // server does - `confirmed` passes when both sides are null.
                if (!pw && !value) return '';
                return value === pw ? '' : 'The two passwords do not match.';
            }
            case 'terms':
                return this.$refs.terms && this.$refs.terms.checked
                    ? '' : 'Please accept the Terms and Privacy Policy to continue.';
            default: return '';
        }
    },

    check(field) { this.errors[field] = this.messageFor(field); },

    blur(field) {
        this.touched[field] = true;
        this.check(field);
    },

    // Re-check only a field already left once, so a message appears a single
    // time and then tracks the correction keystroke by keystroke.
    //
    // The exception is a mistake that is already certain - a name past 30
    // characters, an address opening on a dot. Waiting for blur to report those
    // means the shopper carries on typing into a field that is already wrong,
    // so the first one to appear marks the field touched and takes over from
    // there like any other message.
    input(field) {
        if (this.touched[field]) {
            this.check(field);
        } else {
            const el = this.$refs[field];
            const instant = _instantError(field, el ? el.value : '');
            if (instant) {
                this.touched[field] = true;
                this.errors[field] = instant;
            }
        }
        // The confirmation is a judgement about the pair, so editing either half
        // has to re-run it.
        if (field === 'password' && this.touched.password_confirmation) this.check('password_confirmation');
    },

    onSubmit(event) {
        let first = null;
        for (const field of this.fields) {
            this.touched[field] = true;
            this.check(field);
            if (this.errors[field] && !first) first = field;
        }
        if (!first) return; // nothing local left to catch - let the POST go
        event.preventDefault();
        const el = this.$refs[first];
        if (el) {
            el.focus();
            el.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
    },
}));

Alpine.data('offerPopup', () => ({
    open: false, submitting: false, done: false, error: '',
    form: { name: '', email: '', phone: '' },
    key: 'kk_offer_popup_seen',
    init() {
        if (_seen(this.key)) return;
        window.setTimeout(() => { this.open = true; _markSeen(this.key); }, 3500);
    },
    close() { this.open = false; },
    async submit() {
        this.error = '';
        const nameError = _nameError(this.form.name);
        if (nameError) { this.error = nameError; return; }
        if (!this.form.email) { this.error = 'Please enter your email address.'; return; }
        if (!_validPhone(this.form.phone)) { this.error = 'Please enter a valid 10-digit mobile number.'; return; }
        this.submitting = true;
        try {
            const res = await fetch('/newsletter/subscribe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': _csrf() },
                body: JSON.stringify({ ...this.form, source: 'offer_popup' }),
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) { this.done = true; window.setTimeout(() => this.close(), 2800); }
            else { this.error = data.message || 'Something went wrong. Please try again.'; }
        } catch (e) { this.error = 'Network error. Please try again.'; }
        finally { this.submitting = false; }
    },
}));

Alpine.data('purchaseNotif', (items = [], productName = '', thumb = '') => ({
    items, productName, thumb, idx: 0, current: { time: '' }, visible: false, reduced: false, _t: null,
    init() {
        if (!this.items.length) return;
        // Always show the toast (social proof). For reduced-motion users we still
        // display it once but skip the repeated cycling.
        this.reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.setTimeout(() => this.showNext(), 3500);
    },
    showNext() {
        if (this.idx >= this.items.length) return;
        this.current = this.items[this.idx];
        this.idx++;
        this.visible = true;
        this._t = window.setTimeout(() => {
            this.visible = false;
            if (this.reduced) return;
            const gap = 14000 + Math.floor(Math.random() * 12000);
            window.setTimeout(() => this.showNext(), gap);
        }, 6000);
    },
    dismiss() { this.visible = false; this.idx = this.items.length; if (this._t) window.clearTimeout(this._t); },
}));

Alpine.data('exitPopup', (code = 'KARMAA10', minutes = 10) => ({
    open: false, submitting: false, done: false, error: '',
    form: { email: '', phone: '' },
    code, timeLeft: `${minutes}:00`, _tick: null, _dwell: null, _armed: false, _lastY: 0,
    key: 'kk_exit_popup_seen',
    init() {
        if (_seen(this.key)) return;
        this._onMouseOut = (e) => { if (e.clientY <= 0 && !e.relatedTarget) this.trigger(); };
        document.addEventListener('mouseout', this._onMouseOut);
        // Mobile-friendly fallbacks: a fast upward scroll near the top, or long dwell.
        this._lastY = window.scrollY;
        this._onScroll = () => {
            const y = window.scrollY;
            if (this._lastY - y > 60 && y < 120) this.trigger();
            this._lastY = y;
        };
        window.addEventListener('scroll', this._onScroll, { passive: true });
        this._dwell = window.setTimeout(() => this.trigger(), 60000);
    },
    trigger() {
        if (this.open || _seen(this.key)) return;
        this.open = true;
        _markSeen(this.key);
        this.startCountdown(parseInt((this.timeLeft || '10:00').split(':')[0], 10) || 10);
        this.cleanup();
    },
    startCountdown(minutes) {
        const end = Date.now() + minutes * 60 * 1000;
        this._tick = window.setInterval(() => {
            const ms = Math.max(0, end - Date.now());
            const m = Math.floor(ms / 60000), s = Math.floor((ms % 60000) / 1000);
            this.timeLeft = `${m}:${s < 10 ? '0' : ''}${s}`;
            if (ms <= 0) window.clearInterval(this._tick);
        }, 1000);
    },
    cleanup() {
        if (this._onMouseOut) document.removeEventListener('mouseout', this._onMouseOut);
        if (this._onScroll) window.removeEventListener('scroll', this._onScroll);
        if (this._dwell) window.clearTimeout(this._dwell);
    },
    close() { this.open = false; if (this._tick) window.clearInterval(this._tick); },
    async claim() {
        this.error = '';
        if (!this.form.email) { this.error = 'Please enter your email address.'; return; }
        if (this.form.phone && !_validPhone(this.form.phone)) { this.error = 'Enter a valid 10-digit mobile number.'; return; }
        this.submitting = true;
        try {
            const res = await fetch('/newsletter/subscribe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': _csrf() },
                body: JSON.stringify({ ...this.form, source: 'exit_intent' }),
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) { this.done = true; } else { this.error = data.message || 'Something went wrong.'; }
        } catch (e) { this.error = 'Network error. Please try again.'; }
        finally { this.submitting = false; }
    },
}));

Alpine.data('saleCountdown', (seconds = 0) => ({
    hours: '00', minutes: '00', secs: '00',
    _end: 0, _tick: null,
    init() {
        // Anchor to a wall-clock deadline instead of decrementing a counter:
        // browsers throttle setInterval in background tabs, so a self-decrementing
        // timer drifts minutes behind after the shopper switches away and back.
        this._end = Date.now() + Math.max(0, seconds) * 1000;
        this.render();
        this._tick = window.setInterval(() => this.render(), 1000);
    },
    render() {
        const left = Math.max(0, Math.floor((this._end - Date.now()) / 1000));
        this.hours = String(Math.floor(left / 3600)).padStart(2, '0');
        this.minutes = String(Math.floor((left % 3600) / 60)).padStart(2, '0');
        this.secs = String(left % 60).padStart(2, '0');
        if (left <= 0) this.stop();
    },
    stop() { if (this._tick) { window.clearInterval(this._tick); this._tick = null; } },
    destroy() { this.stop(); },
}));

// ========================================
// Start Alpine.js (MUST be after all stores and components are registered)
// ========================================
Alpine.start();

// ========================================
// Site-wide inline form validation
// ========================================
//
// The browser's own validation bubble says "Please fill out this field" over a
// box the customer then has to identify themselves — and on a form with several
// empty inputs it names none of them. This keeps the native constraints as the
// source of truth (required, type, minlength, pattern) but renders the failures
// as text under each field, naming it from its own label.
//
// Opt a form out with data-no-validate. Forms that already handle their own
// submission (Alpine's @submit.prevent sets novalidate) are skipped.
(function () {
    const ERROR_CLASS = 'kk-field-error';
    const INVALID_CLASS = 'kk-input-invalid';

    function labelFor(field) {
        if (field.labels && field.labels.length) {
            return field.labels[0].textContent.replace(/\*/g, '').trim().replace(/:$/, '');
        }
        return field.getAttribute('aria-label')
            || field.getAttribute('placeholder')
            || 'This field';
    }

    function messageFor(field) {
        const v = field.validity;
        const name = labelFor(field);

        // A message set with setCustomValidity() was written for this exact
        // failure, by the code that knows why the value is wrong. Anything
        // derived from the constraint below can only be vaguer — for a date
        // field, "must be 2026-09-02T10:55 or more" vaguer.
        if (v.customError && field.validationMessage) return field.validationMessage;

        if (v.valueMissing) {
            return field.type === 'checkbox'
                ? `Please tick "${name}" to continue.`
                : `${name} is required.`;
        }
        if (v.typeMismatch && field.type === 'email') return 'Enter a valid email address, like you@example.com.';
        if (v.typeMismatch && field.type === 'url') return 'Enter a full web address, starting with https://';
        if (v.tooShort) return `${name} must be at least ${field.minLength} characters.`;
        if (v.tooLong) return `${name} must be ${field.maxLength} characters or fewer.`;
        if (v.rangeUnderflow) return `${name} must be ${field.min} or more.`;
        if (v.rangeOverflow) return `${name} must be ${field.max} or less.`;
        if (v.stepMismatch) return `${name} is not a valid amount.`;
        if (v.patternMismatch) return field.title || `${name} is not in the expected format.`;
        return field.validationMessage;
    }

    let noteSeq = 0;

    function clearError(field) {
        field.classList.remove(INVALID_CLASS);
        field.removeAttribute('aria-invalid');

        // The note is tracked on the field itself rather than looked up among
        // the field's siblings. showError anchors the message to the field's
        // WRAPPER when the input is nested inside one, which puts the note in
        // wrapper.parentNode — somewhere a `field.parentNode` search cannot
        // reach. That left the stale message on screen after the field was
        // fixed, and stacked a fresh copy under it on every further attempt.
        if (field._kkErrorNote) {
            field._kkErrorNote.remove();
            field._kkErrorNote = null;
        }

        // Fallback for a note rendered before this field was tracked.
        const orphan = field.parentNode && field.parentNode.querySelector(':scope > .' + ERROR_CLASS);
        if (orphan) orphan.remove();

        const describedBy = field.getAttribute('aria-describedby');
        if (describedBy && describedBy.startsWith('kk-err-')) field.removeAttribute('aria-describedby');
    }

    function showError(field, message) {
        clearError(field);
        field.classList.add(INVALID_CLASS);
        field.setAttribute('aria-invalid', 'true');

        const note = document.createElement('p');
        note.className = ERROR_CLASS;
        note.textContent = message;

        // aria-invalid alone tells a screen reader the field is wrong but not
        // why; pointing at the note is what gets the message read out.
        note.id = 'kk-err-' + (++noteSeq);
        if (!field.getAttribute('aria-describedby')) field.setAttribute('aria-describedby', note.id);

        // The message goes after the field's positioned wrapper, never inside
        // it. A wrapper like
        //
        //     <div class="relative">
        //       <span class="absolute left-3.5 top-1/2 -translate-y-1/2">+91</span>
        //       <input type="tel">
        //     </div>
        //
        // centres its decoration on the wrapper's height. Putting the note
        // inside makes the wrapper taller, so "+91" - and the same goes for
        // password eyes and search icons - slides down out of the input and
        // lands on top of the error text.
        //
        // The previous rule only stepped out to the wrapper when it was NOT the
        // field's direct parent, which is the rarer arrangement; in the common
        // one it anchored to the input and inserted the note inside the box.
        const wrapper = field.closest('.relative, .kk-loginmodal__inputwrap');
        const anchor = wrapper && wrapper !== field ? wrapper : field;
        anchor.parentNode.insertBefore(note, anchor.nextSibling);
        field._kkErrorNote = note;
    }

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (form.hasAttribute('data-no-validate') || form.hasAttribute('novalidate')) return;

        const fields = Array.from(form.elements).filter(function (el) {
            return el.willValidate && !el.disabled && el.type !== 'hidden';
        });

        fields.forEach(clearError);
        const invalid = fields.filter(function (el) { return !el.checkValidity(); });
        if (invalid.length === 0) return;

        event.preventDefault();
        event.stopPropagation();
        invalid.forEach(function (el) { showError(el, messageFor(el)); });

        const first = invalid[0];
        first.focus({ preventScroll: true });
        first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, true);

    // Marks a field the customer has actually typed in or chosen from, as
    // opposed to one they merely tabbed across on the way somewhere else.
    const TOUCHED = '_kkTouched';

    // Clear a field's message as soon as it becomes valid, so the form stops
    // scolding the customer while they are still fixing it.
    ['input', 'change'].forEach(function (type) {
        document.addEventListener(type, function (event) {
            const field = event.target;
            if (!field || !field.willValidate) return;
            field[TOUCHED] = true;
            if (field.classList.contains(INVALID_CLASS) && field.checkValidity()) clearError(field);
        }, true);
    });

    // Characters a field will accept while it is being typed in.
    //
    // `type="tel"` and `pattern` do not restrict typing at all - the browser
    // happily accepts "sasadasdasdsada" in a phone box and only objects once
    // the form is submitted. Refusing the character outright is both faster to
    // understand and impossible to argue with: the letter simply never appears.
    //
    // Phone keeps +, spaces, hyphens and brackets because real numbers are
    // written with them and the server rule strips them before checking.
    //
    // \u00A0 rides along with the space because the server charset keeps it -
    // a name pasted from Word carries them, and dropping it here would join
    // the words rather than warn.
    // `letters` is opt-in via data-kk-chars rather than inferred: most text
    // inputs are not names, and a street or a business name legitimately
    // carries digits. \p{M} travels with \p{L} because Devanagari matras and
    // Vietnamese tone marks are separate code points - dropping them would eat
    // the vowel signs out of a name as it was typed.
    const CHAR_POLICIES = {
        phone: { allow: /[0-9+\-\s()]/, message: 'Phone numbers can only contain digits.' },
        digits: { allow: /[0-9]/, message: 'This field takes digits only.' },
        decimal: { allow: /[0-9.]/, message: 'This field takes numbers only.' },
        letters: { allow: /[\p{L}\p{M} \u00A0]/u, message: 'Names can only contain letters.' },
    };

    function charPolicy(field) {
        const named = field.getAttribute && field.getAttribute('data-kk-chars');
        if (named) return CHAR_POLICIES[named] || null;

        // Inferred, so every existing tel/numeric input is covered without
        // touching thirteen blade files, and new ones get it for free.
        if (field.type === 'tel') return CHAR_POLICIES.phone;

        const mode = (field.getAttribute && field.getAttribute('inputmode') || '').toLowerCase();
        if (mode === 'numeric') return CHAR_POLICIES.digits;
        if (mode === 'decimal') return CHAR_POLICIES.decimal;
        return null;
    }

    document.addEventListener('input', function (event) {
        const field = event.target;
        if (!field || typeof field.value !== 'string') return;

        const form = field.form;
        if (form && form.hasAttribute('data-no-validate')) return;

        const policy = charPolicy(field);
        if (!policy) return;

        const before = field.value;
        let cleaned = '';
        for (let i = 0; i < before.length; i++) {
            if (policy.allow.test(before[i])) cleaned += before[i];
        }
        if (cleaned === before) return;

        // Put the caret back where it was, minus whatever was dropped ahead of
        // it, so editing the middle of a number does not throw the cursor to
        // the end on every rejected keystroke.
        const caret = typeof field.selectionStart === 'number' ? field.selectionStart : before.length;
        let removedBeforeCaret = 0;
        for (let i = 0; i < caret && i < before.length; i++) {
            if (!policy.allow.test(before[i])) removedBeforeCaret++;
        }

        field.value = cleaned;
        try { field.setSelectionRange(caret - removedBeforeCaret, caret - removedBeforeCaret); } catch (e) { /* unsupported on some types */ }

        showError(field, policy.message);

        // The note explains a keystroke, not the state of the field, so it
        // stands down shortly after and hands back to the real message.
        clearTimeout(field._kkCharTimer);
        field._kkCharTimer = setTimeout(function () {
            if (field.checkValidity()) clearError(field);
            else showError(field, messageFor(field));
        }, 2000);
    }, true);

    // Mobile fields that want the bare ten digits and nothing else, capped as
    // they are typed by _capMobile().
    //
    // Opt-in through data-kk-mobile="10" rather than inferred from type="tel":
    // only the fields the server validates with IndianMobile can be held to ten
    // digits. A wholesale enquiry, for one, is allowed to leave an
    // international number.
    //
    // Registered in the capture phase, so it runs before Alpine's own input
    // listener on the element and an x-model bound to the same field reads the
    // capped value rather than the one the keystroke produced.
    document.addEventListener('input', function (event) {
        const field = event.target;
        if (!field || typeof field.value !== 'string') return;
        if (field.getAttribute && field.getAttribute('data-kk-mobile') !== '10') return;

        const before = field.value;
        const capped = _capMobile(before);
        if (capped === before) return;

        // Same caret arithmetic as the character filter above: however much of
        // the capped value lies before the caret is where the caret belongs, so
        // editing the middle of a number does not throw the cursor to the end.
        const caret = typeof field.selectionStart === 'number' ? field.selectionStart : before.length;
        const kept = Math.min(_capMobile(before.slice(0, caret)).length, capped.length);

        field.value = capped;
        try { field.setSelectionRange(kept, kept); } catch (e) { /* unsupported on some types */ }
    }, true);

    // Report a field the moment the customer leaves it, instead of saving every
    // complaint for the submit button. Finding out at the end that the email
    // three fields back was malformed means walking back up the form; hearing
    // it on the way past means fixing it while it is still the thing you were
    // thinking about.
    //
    // Two restraints stop this from turning into nagging:
    //
    //   - A field that is still empty and was never typed in is left alone.
    //     Tabbing through a form to see what it asks for should not light it up
    //     red before the customer has had a chance to answer anything. Once it
    //     has been touched, or is already showing a message, it is fair game.
    //   - Checkboxes and radios are skipped. Focus moves between the options of
    //     a radio group with the arrow keys, so validating on the way out would
    //     fire on each option in turn while the customer is still choosing.
    //
    // `blur` does not bubble, so this listens in the capture phase; that is also
    // why it can be registered once on the document rather than per field, and
    // why it works for markup added later by Alpine.
    function skipOnBlur(field) {
        if (!field || !field.willValidate || field.disabled) return true;
        if (field.type === 'checkbox' || field.type === 'radio') return true;

        const form = field.form;
        return !!(form && form.hasAttribute('data-no-validate'));
    }

    document.addEventListener('blur', function (event) {
        const field = event.target;
        if (skipOnBlur(field)) return;

        const isEmpty = String(field.value == null ? '' : field.value).trim() === '';
        const alreadyFlagged = field.classList.contains(INVALID_CLASS);
        if (isEmpty && !field[TOUCHED] && !alreadyFlagged) return;

        if (field.checkValidity()) {
            clearError(field);
        } else {
            showError(field, messageFor(field));
        }
    }, true);

    // Suppress the native bubble without losing the constraints themselves —
    // and render our own message in its place.
    //
    // This is where the "dead button" came from. When a required field fails,
    // the browser fires `invalid` on each offending control and then abandons
    // the submission WITHOUT ever firing `submit`. The handler above therefore
    // never ran, while this one cancelled the only feedback the browser would
    // have given, so clicking Create Account (or Sign In, or any other native
    // validated form) did nothing at all: no bubble, no message, no request.
    let pendingInvalid = [];

    document.addEventListener('invalid', function (event) {
        event.preventDefault();

        const field = event.target;
        if (!field || !field.willValidate) return;

        const form = field.form;
        if (form && form.hasAttribute('data-no-validate')) return;

        showError(field, messageFor(field));

        // `invalid` fires once per failing control, in document order. Collect
        // them and act on the first once the browser has finished the pass.
        if (pendingInvalid.length === 0) {
            Promise.resolve().then(function () {
                const first = pendingInvalid[0];
                pendingInvalid = [];
                if (!first) return;
                first.focus({ preventScroll: true });
                first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        }
        pendingInvalid.push(field);
    }, true);
})();


// ========================================
// Schedule pairs - a start date and an end date
// ========================================
//
// The same three rules the server applies (App\Rules\NotPastDateTime, and the
// `after:` on the end field), applied here BEFORE the form is submitted:
//
//   * a newly chosen date may not be in the past
//   * the end must be later than the start
//   * a date the page was RENDERED with stays valid, so a coupon or sale that
//     has already begun can still be edited and saved without its schedule
//     being dragged forward
//
// The pair is declared in the markup, not listed here, so a new form picks this
// up by naming its fields:
//
//   <input type="datetime-local" id="starts_at" data-schedule-start
//          data-schedule-original="2026-09-01T10:00">
//   <input type="datetime-local" data-schedule-end="starts_at"
//          data-schedule-original="2026-09-05T10:00">
//
// `min` is kept in step so the native calendar greys the impossible dates out,
// and the messages go through setCustomValidity so the inline validator above
// prints them under the field instead of the browser's own bubble.
(function () {
    const pad = (n) => String(n).padStart(2, '0');

    // A datetime-local value is a minute-granular ISO string, so every
    // comparison below is plain text ordering - no Date parsing, no timezone.
    function nowValue() {
        const d = new Date();
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate())
            + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    // Some browsers append seconds; the floors never carry them.
    const valueOf = (field) => (field.value || '').slice(0, 16);
    const originalOf = (field) => (field.dataset.scheduleOriginal || '').slice(0, 16);

    function labelOf(field, fallback) {
        const label = field.labels && field.labels[0];
        return label ? label.textContent.replace(/\*/g, '').trim() : fallback;
    }

    function wire(end) {
        const start = document.getElementById(end.dataset.scheduleEnd);
        if (!start) return;

        function refresh() {
            const now = nowValue();
            const startValue = valueOf(start);
            const endValue = valueOf(end);
            const startName = labelOf(start, 'The start date');
            const endName = labelOf(end, 'The end date');
            const startFloor = originalOf(start) && originalOf(start) < now ? originalOf(start) : now;
            const endFloor = originalOf(end) && originalOf(end) < now ? originalOf(end) : now;

            // What the picker itself offers. The end cannot open before the
            // start, so the start's own value is the end's floor once set.
            start.min = startFloor;
            end.min = startValue || endFloor;

            start.setCustomValidity(
                startValue && startValue !== originalOf(start) && startValue < now
                    ? startName + ' cannot be in the past.'
                    : ''
            );

            // Order first: an end before the start is wrong whether or not the
            // date was the one already stored, and saying so is more use than
            // calling it past.
            if (startValue && endValue && endValue <= startValue) {
                end.setCustomValidity(endName + ' must be later than ' + startName + '.');
            } else if (endValue && endValue !== originalOf(end) && endValue < now) {
                end.setCustomValidity(endName + ' cannot be in the past.');
            } else {
                end.setCustomValidity('');
            }
        }

        [start, end].forEach((field) => {
            ['input', 'change', 'blur'].forEach((type) => field.addEventListener(type, refresh));
        });

        // A click on the submit button lands BEFORE the browser checks the
        // form's constraints, which makes it the last chance to re-floor "now"
        // on a page that has been sitting open.
        document.addEventListener('click', function (event) {
            const control = event.target.closest && event.target.closest('button, input[type="submit"]');
            if (control && control.type === 'submit' && control.form === start.form) refresh();
        }, true);

        // And for a submit by Enter, which never produces that click.
        window.setInterval(refresh, 20000);

        refresh();
    }

    function init() {
        document.querySelectorAll('[data-schedule-end]').forEach(wire);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
