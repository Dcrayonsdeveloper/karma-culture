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

    async add(productId, quantity = 1, variantId = null, size = null, colour = null) {
        this.isLoading = true;
        try {
            const response = await axios.post('/cart/add', {
                product_id: productId,
                variant_id: variantId,
                quantity: quantity,
                size: size,
                colour: colour
            });
            Alpine.store('toast').success(response.data.message || 'Added to cart');
            // Update count immediately from response
            if (response.data.cart_count !== undefined) {
                this.itemCount = response.data.cart_count;
            }
            await this.fetch();
            this.open();
            this.fetchRecommendations();
        } catch (error) {
            const msg = error.response?.data?.error || 'Failed to add to cart';
            Alpine.store('toast').error(msg);
            console.error('Failed to add to cart:', error);
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

        // After the field itself, or after its wrapper when the input sits
        // inside a relative box (icon buttons, password eyes) so the message
        // does not land between the input and its own decoration.
        const wrapper = field.closest('.relative, .kk-loginmodal__inputwrap');
        const anchor = wrapper && wrapper.contains(field) && wrapper !== field.parentNode ? wrapper : field;
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

    // Clear a field's message as soon as it becomes valid, so the form stops
    // scolding the customer while they are still fixing it.
    ['input', 'change'].forEach(function (type) {
        document.addEventListener(type, function (event) {
            const field = event.target;
            if (!field || !field.willValidate) return;
            if (field.classList.contains(INVALID_CLASS) && field.checkValidity()) clearError(field);
        }, true);
    });

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
