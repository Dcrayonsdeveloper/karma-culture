import './bootstrap';

// Alpine.js Core
import Alpine from 'alpinejs';

// Alpine.js Plugins
import focus from '@alpinejs/focus';
import collapse from '@alpinejs/collapse';
import intersect from '@alpinejs/intersect';

// Site-wide dropdown: draws every <select>'s option list itself instead of
// letting the operating system paint it.
import './kk-select';

// Register plugins
Alpine.plugin(focus);
Alpine.plugin(collapse);
Alpine.plugin(intersect);

// Make Alpine available globally
window.Alpine = Alpine;

// Runtime bridge for the inline Blade components. An inline classic <script> is
// executed while the page is still parsing, long before this deferred module, so
// `Alpine` is undefined in its body. Those components reach the popup queue
// lazily from inside init() through this, never at parse time.
window.kkPopupQueue = () => Alpine.store('popupQueue');

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
// The one place an API failure becomes a sentence
// ========================================
//
// Every component that talked to the server used to invent its own wording for
// a failure, and most of them printed whatever the response happened to carry:
//
//     this.error = data.message || 'Something went wrong. Please try again.';
//
// Two problems with that. A request that never reached the server has no `data`
// at all, so a dead connection and a rejected payload came out as the same
// shrug; and on a 500 the `message` field is the exception's own text, which is
// how a class name, a file path or a fragment of SQL ends up on a customer's
// screen.
//
// So the status code decides, and the rule is one line: a 4xx is the server
// answering the request deliberately, and its message is written for the person
// reading it; a 5xx is the server falling over, and nothing it says at that
// moment is fit to show. Anything with no response at all is the network.
const KK_API_MESSAGES = {
    400: 'That request could not be processed. Please check what you entered and try again.',
    401: 'Please sign in to continue.',
    403: 'You do not have permission to do that.',
    404: 'We could not find what you were looking for.',
    409: 'That has already been done. Please refresh the page and try again.',
    // Laravel's own code for an expired CSRF token, which is a page left open
    // too long rather than anything the visitor got wrong.
    419: 'This page has been open a while and your session expired. Please refresh and try again.',
    422: 'Please check the highlighted fields and try again.',
    429: 'That is a few too many attempts. Please wait a moment and try again.',
    500: 'Something went wrong at our end. Please try again in a moment.',
    502: 'The service is briefly unavailable. Please try again in a moment.',
    503: 'The service is briefly unavailable. Please try again in a moment.',
    504: 'The server took too long to answer. Please try again in a moment.',
};

const KK_NETWORK_MESSAGE = 'Unable to connect to the server. Please check your internet connection and try again.';

// The three statuses whose body is never this application's writing.
//
// The 4xx rule above - "the server answered deliberately, so its message is
// written for the person reading it" - holds for the codes a controller
// chooses. These three are raised by the framework's own middleware before any
// controller runs, and their bodies carry Laravel's diagnostics rather than
// anybody's copy: {"message":"Unauthenticated."}, {"message":"CSRF token
// mismatch."} and, from the throttle on /newsletter/subscribe,
// {"message":"Too Many Attempts."} - which is precisely what a shopper who
// signed up twice in a minute used to read in the popup's red line.
//
// Nothing in app/ or routes/ answers with a 401, 419 or 429 body of its own, so
// there is no deliberate message being thrown away here; the canned sentences
// above say the same thing in words a customer can act on.
const KK_FRAMEWORK_STATUSES = new Set([401, 419, 429]);

/**
 * Normalise any failure - an axios rejection, a fetch Response, a thrown
 * TypeError from a dropped connection - into one shape:
 *
 *     { status, message, fields }
 *
 * `status` is 0 when the request never got an answer. `fields` is the
 * {name: 'message'} map from a 422, ready to be written straight into a
 * component's per-field error slots, and is empty for everything else.
 *
 * @param {*} error   an axios error, a fetch Response, or a thrown Error
 * @param {object} [payload]  the already-parsed body, when the caller has it
 */
window.kkApiError = function (error, payload = null) {
    let status = 0;
    let body = payload;

    // An axios rejection is tested FIRST, and the order is load-bearing. Axios
    // copies the status onto the error itself (AxiosError sets
    // `this.status = response.status`), so an AxiosError satisfies the
    // fetch-Response test below just as well as a Response does - and taking
    // that branch left `body` null, which threw away the 422 field map and the
    // 4xx sentence for every axios caller on the site. What reaches the screen
    // is the difference between "That coupon has expired." and a generic shrug,
    // so the branch that can read a body has to be the one that runs.
    if (error && error.response) {
        status = error.response.status || 0;
        if (body === null) body = error.response.data;
    } else if (error && typeof error.status === 'number') {
        // A fetch Response, handed over directly. Its body has already been
        // read by the caller (or by kkFetchError) and passed in as `payload`.
        status = error.status;
    }

    if (!status) {
        return { status: 0, message: KK_NETWORK_MESSAGE, fields: {} };
    }

    const fields = {};
    const raw = (body && body.errors) || null;
    if (raw && typeof raw === 'object') {
        Object.keys(raw).forEach(function (key) {
            // Laravel sends an ARRAY of messages per field. Only the first is
            // shown: a field has one thing wrong with it as far as the person
            // filling it in is concerned, and the bag is already ordered by the
            // rule that failed - required before format before length.
            const value = raw[key];
            const message = Array.isArray(value) ? value[0] : value;
            if (message) fields[key] = String(message);
        });
    }

    const canned = KK_API_MESSAGES[status]
        || (status >= 500 ? KK_API_MESSAGES[500] : KK_API_MESSAGES[400]);

    // 5xx: never the server's own words, and neither for the three statuses the
    // framework raises for itself. Below that, a deliberate message from the
    // endpoint beats the generic one - that is where "This email address is
    // already registered" and "That coupon has expired" come from.
    //
    // TWO envelopes are read, because this codebase answers in two. `message` is
    // what the validator and most controllers use; `error` is what CartController
    // and six others say a business refusal in - {"error":"Only 2 left in
    // stock"}, with no `message` in the body at all. Reading `message` alone is
    // why a stock cap the server had spelled out arrived at the shopper as the
    // generic "Please check the highlighted fields and try again."
    //
    // `error` is read FIRST, which is the same order the cart page's own
    // failureMessage() settled on: where a body ever carried both, `error` is
    // the sentence a controller sat down and wrote, while `message` may be no
    // more than the validator's aggregate of the bag below it.
    const authored = status < 500 && !KK_FRAMEWORK_STATUSES.has(status) && body
        ? [body.error, body.message]
            .filter(function (m) { return typeof m === 'string'; })
            .map(function (m) { return m.trim(); })
            .find(Boolean)
        : '';

    return {
        status,
        message: authored || canned,
        fields,
    };
};

/**
 * The same thing for a `fetch` that resolved with a non-2xx status: reads the
 * body once, tolerating an HTML error page where JSON was expected (which is
 * what a 500 behind a proxy usually returns).
 */
window.kkFetchError = async function (response) {
    let body = null;
    try {
        body = await response.clone().json();
    } catch (e) {
        body = null;
    }
    return window.kkApiError(response, body);
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

    // Whether the last attempt to read the cart failed. It is the difference
    // between "there is nothing in your bag" and "we could not find out what is
    // in your bag", which are the same picture on screen and opposite facts.
    loadFailed: false,

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
            this.loadFailed = false;
        } catch (error) {
            // Whatever was already loaded stays exactly as it was. A dropped
            // connection says nothing about what the server is holding, and
            // clearing the list here would answer "what is in my bag?" with a
            // confident, wrong "nothing" - which on a cart reads as an order
            // that has just been lost.
            this.loadFailed = true;
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
            // ONE reading of the failure, shared with update() and remove()
            // below and with the cart page, so the drawer and the page tell the
            // same story about the same refusal.
            //
            // Reading `error.response.data.error` by hand only ever understood
            // one of the three answers this endpoint gives. A stock cap is
            // {"error":"Only 2 left in stock"}, but a ValidationException from
            // $request->validate() is {message, errors} with no `error` key at
            // all, and an expired CSRF token is a 419 carrying Laravel's own
            // "CSRF token mismatch." - so a cart that had just refused for a
            // reason it could name said "Failed to add to cart" instead, and the
            // shopper was left to guess. kkApiError knows all three shapes, and
            // is the only thing here allowed to decide when the server's own
            // words are fit to print.
            Alpine.store('toast').error(window.kkApiError(error).message);
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
            // The same reading as add() above, and it matters most here: this is
            // the handler CartController answers with "Only 2 left in stock"
            // when the quantity is raised past the shelf. That sentence was
            // being thrown away and replaced with "Failed to update cart", which
            // describes the request rather than the reason - and left the
            // shopper pressing + against a limit nobody had mentioned.
            Alpine.store('toast').error(window.kkApiError(error).message);
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
            // And again the same reading, so a session that expired while the
            // cart sat open says so - "Failed to remove item" invited the
            // shopper to press the bin a second time against a request that was
            // never going to be accepted until the page was refreshed.
            Alpine.store('toast').error(window.kkApiError(error).message);
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
    // Set when the products behind the saved ids could not be loaded, so the
    // page can tell "you have not saved anything" from "we could not load what
    // you saved" - see fetch() for why those two were indistinguishable.
    loadFailed: false,

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
        if (!this.ids.length) { this.items = []; this.loadFailed = false; return; }
        this.isLoading = true;
        try {
            const res = await axios.get('/wishlist-items', { params: { ids: this.ids.join(',') } });
            const byId = {};
            (res.data.items || []).forEach(p => byId[p.id] = p);
            this.items = this.ids.map(id => byId[id]).filter(Boolean);
            this.loadFailed = false;
        } catch (e) {
            // `this.items = []` here was the lie. The ids live in a cookie and
            // the products come from the server, so a request that failed says
            // nothing at all about whether the shopper saved anything - and
            // emptying the list made the page say the opposite of the badge in
            // the header, which counts the cookie: "Your wishlist is empty"
            // underneath a heart reading 3.
            //
            // So whatever was already loaded stays, and the failure is recorded
            // rather than answered.
            this.loadFailed = true;
            // With nothing loaded there is no stale list to contradict, and the
            // empty state is about to be shown as though it were the truth. Say
            // what actually happened instead - through kkApiError, so a 500's
            // own text never reaches the shopper. Wrapped because a toast is
            // cosmetic and must not take the store down with it.
            if (!this.items.length) {
                try { Alpine.store('toast').error(window.kkApiError(e).message); } catch (err) {}
            }
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

    // One message slot per field, holding a STRING - not the array Laravel
    // sends. A field has one thing wrong with it as far as the person filling it
    // in is concerned, and the bag arrives ordered by the rule that failed.
    errors: {},

    // The form-level line, for what no field can speak for: a credentials
    // failure (which is about the pair, not either box), an error for a field
    // this modal does not render, a mapped status.
    message: '',

    open(mode = 'login') {
        this.mode = mode;
        this.reset();
        this.isOpen = true;
        document.body.style.overflow = 'hidden';
    },

    close() {
        this.isOpen = false;
        this.reset();
        document.body.style.overflow = '';
    },

    switchMode(mode) {
        this.mode = mode;
        this.reset();
    },

    /** Step 1 of every submission: nothing the last one was told survives it. */
    reset() {
        this.errors = {};
        this.message = '';
    },

    /**
     * Step 4: one failure becomes at most one message per field plus at most one
     * form-level line, and never the same sentence in both places.
     *
     * LoginController answers a credentials failure with the SAME text in
     * `message` and in `errors.email`, deliberately - it is a JSON API and
     * either shape may be what a caller reads. Printing both is what put the
     * identical sentence in the banner and under the email box at once, so the
     * pair is collapsed here: a credentials failure is about the two fields
     * together, so it belongs in the banner and the field slots stay empty.
     */
    fail(error) {
        const failure = window.kkApiError(error);

        this.errors = {};
        this.message = '';

        const shown = this.mode === 'login'
            ? ['email', 'password']
            : ['full_name', 'email', 'password', 'password_confirmation', 'terms'];

        Object.entries(failure.fields).forEach(([field, text]) => {
            if (shown.includes(field)) this.errors[field] = text;
            // A field this modal does not render would map to nothing and leave
            // the button looking dead, so it goes to the banner instead.
            else if (!this.message) this.message = text;
        });

        if (this.mode === 'login' && this.errors.email) {
            this.message = this.errors.email;
            this.errors = {};
            return;
        }

        // Only if no field is speaking. Otherwise the banner would repeat what
        // is already under the boxes.
        if (!this.message && !Object.keys(this.errors).length) this.message = failure.message;
    },

    async login(email, password, remember) {
        if (this.isLoading) return;
        this.reset();

        // Step 2: nothing is sent while a field is wrong, and the message the
        // visitor reads is this one - not an authentication failure for a
        // request that was never worth making.
        const errors = {};
        const emailError = _emailShapeError(email);
        if (emailError) errors.email = emailError;
        if (!password) errors.password = 'Please enter your password.';

        if (Object.keys(errors).length) {
            this.errors = errors;
            return;
        }

        this.isLoading = true;
        try {
            await axios.post('/login', { email, password, remember });
            this.close();
            window.location.reload();
        } catch (error) {
            this.fail(error);
        } finally {
            this.isLoading = false;
        }
    },

    async register(name, email, password, passwordConfirmation) {
        if (this.isLoading) return;
        this.reset();

        // _emailError, not _emailShapeError: this is where an address is minted,
        // and RegisterController holds it to the tighter EmailAddress shape.
        const errors = {};
        const checks = {
            full_name: _fullNameError(name),
            email: _emailError(email),
            password: _passwordError(password),
            password_confirmation: password !== passwordConfirmation ? 'The two passwords do not match.' : '',
        };
        Object.entries(checks).forEach(([field, text]) => { if (text) errors[field] = text; });

        if (Object.keys(errors).length) {
            this.errors = errors;
            return;
        }

        this.isLoading = true;
        try {
            await axios.post('/register', {
                full_name: name,
                email,
                password,
                password_confirmation: passwordConfirmation,
                terms: true,
            });
            this.close();
            window.location.reload();
        } catch (error) {
            this.fail(error);
        } finally {
            this.isLoading = false;
        }
    },
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

    // "Nothing matched" is a statement about the catalogue; "the search did not
    // run" is a statement about the connection. They produced the same empty
    // dropdown, and the shopper read the second as the first - so a search that
    // never reached the server told them the thing they wanted is not sold here.
    // The two are separated so the dropdown can say which happened.
    failed: false,
    errorMessage: '',

    async search() {
        if (this.query.length < 2) {
            this.results = [];
            this.failed = false;
            this.errorMessage = '';
            this.isOpen = false;
            return;
        }

        this.isLoading = true;
        this.isOpen = true;
        this.failed = false;
        this.errorMessage = '';

        try {
            const response = await axios.get(this.endpoint, {
                params: { q: this.query }
            });
            this.results = response.data.results || [];
        } catch (error) {
            console.error('Search failed:', error);
            // Stale results for a query they no longer describe would be worse
            // than none, so the list still goes - but it goes alongside a reason,
            // mapped through kkApiError so a 500's own text is never printed.
            this.results = [];
            this.failed = true;
            this.errorMessage = window.kkApiError(error).message;
        } finally {
            this.isLoading = false;
        }
    },

    clear() {
        this.query = '';
        this.results = [];
        this.failed = false;
        this.errorMessage = '';
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

// ========================================
// Popup queue - the one arbiter for every timed overlay on the storefront.
//
// The rule: one popup on screen at a time; the next opens 2s after the previous
// one CLOSES, never 2s after it opened; on home the cycle runs again once for a
// shopper who has neither engaged nor left; the first real click on anything
// that is not a popup's own chrome stops all of it for the visit.
//
// Each popup used to own a private timer, so the flash sale opened at 1.5s and
// the offer popup painted over it at 3.5s - the reported bug. Nothing arbitrated
// because nothing could: they are three components in two files.
//
// Two different questions used to share one flag, and they are separated here:
//   - "shown this visit?" -> kk_popup_visit in sessionStorage, owned below.
//                            Per-tab, dies with the visit, survives a reload so
//                            the cycle cannot be farmed by refreshing.
//   - "shopper is done?"  -> the existing _seen/_markSeen 30-day localStorage +
//                            cookie pair, unchanged. Read once per page load as
//                            an admission gate in each component's init(), and
//                            written only on retirement (see _maybeRetire).
// Conflating them is why a restart was impossible: _markSeen fired the instant a
// popup opened, so a second cycle vetoed itself against the key the first wrote.
// It also burned a 30-day gate on a visitor who closed the tab at t=3.6s without
// ever reading the thing.
// ========================================
const PQ_GAP_MS = 2000;           // R2: close -> next open. The number the owner asked for.
const PQ_REST_MS = 45000;         // end of a cycle -> the next one. Re-running modals 2s
                                  // apart reads as a bug rather than a campaign.
const PQ_MAX_CYCLES = 2;          // the first run plus one restart (1 under reduced motion)
const PQ_CONSENT_GRACE_MS = 6000; // longest the queue waits on an unanswered cookie bar
const PQ_VISIT_KEY = 'kk_popup_visit';
const PQ_CHROME = '[data-kk-popup]';
const PQ_IGNORE = '[data-kk-popup-ignore]';
const PQ_ENGAGE = 'a[href], button, [role="button"], input, select, textarea, summary, label';

let _visitCache = null;
const _visit = () => {
    if (_visitCache) return _visitCache;
    let v = { engaged: false, cycle: 0, shown: {} };
    try {
        const raw = sessionStorage.getItem(PQ_VISIT_KEY);
        if (raw) v = Object.assign(v, JSON.parse(raw) || {});
    } catch (e) {}
    if (!v.shown || typeof v.shown !== 'object') v.shown = {};
    _visitCache = v;
    return _visitCache;
};
// try/catch with the in-memory copy as the fallback: sessionStorage throws in
// Safari private mode and in some embedded webviews. That degrades to
// per-pageview behaviour, which is the old behaviour, and never throws.
const _saveVisit = () => { try { sessionStorage.setItem(PQ_VISIT_KEY, JSON.stringify(_visitCache)); } catch (e) {} };

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

// The LENIENT email mirror, for the endpoints that validate with V::email()
// rather than V::email(strictShape: true).
//
// _emailError() above is the SIGNUP mirror, and it is deliberately stricter:
// the local part has to open on a letter or a digit, because that is what
// App\Rules\EmailAddress demands where an address is being minted. Pointing it
// at the newsletter endpoint - which validates with a plain V::email() - would
// make the browser refuse "_asha@example.com" for a form the server would have
// accepted, and a client-side check that is tighter than the server's is worse
// than none: it rejects real people for a rule that does not exist.
//
// So this one only tests the shape `email:strict` itself tests: one @, a local
// part, a domain with a real TLD, and no whitespace anywhere.
const _emailShapeError = (v) => {
    const email = (v || '').trim();
    if (!email) return 'Please enter your email address.';
    if (email.length > 255) return 'That email address is too long.';
    if (/\s/.test(email)) return _EMAIL_GENERIC;

    const parts = email.split('@');
    if (parts.length !== 2) return _EMAIL_GENERIC;

    const [local, domain] = parts;
    if (!local || !_EMAIL_DOMAIN.test(domain)) return _EMAIL_GENERIC;
    return '';
};

// 10 to 15 digits, mirroring the closure on the `phone` rule in
// NewsletterController - NOT _mobileError above, which is the Indian-mobile
// rule. The subscriber list is the one place an overseas number is legitimate,
// which the server rule says in as many words; holding the box to ten digits
// starting 6-9 would have turned that allowance back off from the browser.
const _subscriberPhoneError = (v, required = false) => {
    const raw = (v || '').trim();
    if (!raw) return required ? 'Please enter your mobile number.' : '';
    if (raw.length > 20) return 'That phone number is too long.';

    const INVALID = 'Please enter a valid mobile number (10-15 digits).';
    if (!/^[0-9+\-\s()]+$/.test(raw)) return INVALID;

    const digits = raw.replace(/\D/g, '');
    return digits.length < 10 || digits.length > 15 ? INVALID : '';
};

// Mirrors Password::min(10)->mixedCase()->numbers()->symbols() from
// AppServiceProvider. Laravel tests those with Unicode properties rather than
// ASCII classes, so this does too - an accented capital still counts as one.
// Only the first unmet requirement is shown; the hint under the field already
// lists all four.
//
// Length is counted in code points, the way Laravel's Str::length() counts it,
// so an emoji or an astral-plane letter is worth one on both sides. Counting
// UTF-16 units instead would make the browser call a nine-character password
// long enough and the server hand it straight back.
const _PASSWORD_MIN = 10;
const _passwordError = (v) => {
    const pw = v || '';
    if (!pw) return 'Please choose a password.';
    const len = [...pw].length;
    if (len < _PASSWORD_MIN) return `Your password must be at least ${_PASSWORD_MIN} characters long.`;
    if (len > 255) return 'Your password must be 255 characters or fewer.';
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
        // A password is the exception to waiting for blur. Its rule has four
        // parts and its characters are dots on the screen, so being told after
        // the fact that the password just invented is a character short means
        // inventing another one; said on the keystroke, the message counts down
        // as it is typed and disappears the moment the rule is met.
        //
        // The first CHARACTER promotes the field, not the first keystroke: a
        // shopper who focuses the box and tabs straight out of it has been told
        // nothing, which is the restraint every other field here observes.
        if (field === 'password' || field === 'password_confirmation') {
            const box = this.$refs[field];
            if (box && box.value) this.touched[field] = true;
        }

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

Alpine.store('popupQueue', {
    members: {},   // id -> registration options
    order: [],     // planned TIMED ids, ascending priority
    idx: 0,        // cursor into order for the cycle in progress
    current: null, // id of the popup on screen, or null
    stopped: false,
    cycle: 0,
    homePage: false,
    reduced: false,
    planned: false,
    holds: null,   // Set of hold tags; a non-empty set parks the queue
    _urgent: null, // an event-driven member jumping the cursor - never splices order
    _t: null, _waitFn: null, _waitMs: 0, _waitFrom: 0, _waitTag: null,

    init() {
        this.holds = new Set();
        const v = _visit();
        this.cycle = v.cycle || 0;
        // <body> is already parsed by now: @vite emits a deferred module.
        this.homePage = !!(document.body && document.body.dataset.kkPage === 'home');
        this.reduced = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
        // The kill switch is honoured on home only. Someone clicking around /shop
        // must not arrive at home already silenced.
        if (this.homePage && v.engaged) this.stopped = true;

        let consent = null;
        try { consent = localStorage.getItem('fk_cookie_consent'); } catch (e) {}
        if (!consent) {
            // Seeded from the stored key deliberately: a returning visitor never
            // sees the banner, so neither accept handler runs and waiting on the
            // event alone would stall every return visit for the whole grace.
            this.hold('consent');
            const release = () => this.unhold('consent');
            window.addEventListener('kk-consent-resolved', release, { once: true });
            window.setTimeout(release, PQ_CONSENT_GRACE_MS);
        }

        // Capture phase for both, and that is load-bearing rather than stylistic:
        // the modal panels carry Alpine's @click.stop and the form validator near
        // the bottom of this file stopPropagation()s an invalid submit, so a
        // bubble-phase listener goes blind to exactly the clicks that matter.
        document.addEventListener('click', (e) => this._onInteract(e), true);
        document.addEventListener('submit', (e) => this._onInteract(e), true);
        // Deliberately not focusin: x-trap hands focus back to the pre-open
        // element when a popup closes, so a header button or the search box would
        // latch the kill switch on the queue's own close path. Enter on a focused
        // link or button already dispatches a click.
        document.addEventListener('visibilitychange', () => this._onVisibility());
        window.addEventListener('pageshow', (e) => { if (e.persisted) this._onPageshow(); });
        window.addEventListener('pagehide', () => this._onPagehide());
        // Component init() runs in plain DOM order during Alpine's initTree walk,
        // so planning there would let markup position pick the running order.
        document.addEventListener('alpine:initialized', () => this._plan());
    },

    // ---- the API the popups use -------------------------------------
    register(id, opts) {
        this.members[id] = Object.assign({
            priority: 50, delay: PQ_GAP_MS, mode: 'timed',
            seenKey: '', seenStore: 'local', root: null,
            canShow: () => true, show: () => {}, hide: () => {},
        }, opts);
        if (!this.current && this.idx === 0) this._buildOrder();
    },

    request(id) {
        if (this.stopped || !this.members[id] || this.current === id) return;
        if (!this.isEligible(id)) return;
        this._urgent = id;
        // A real exit gesture must not be made to sit out a 45s rest.
        if (this._waitTag === 'rest') { this._clearWait(); this._waitFn = null; this._waitTag = null; this._advance(0); return; }
        // If a gap is already armed, leave it alone: _peek() hands it the urgent
        // member when it fires, so the 2s-after-close rule still holds.
        if (!this.current && !this._waitFn) this._advance(0);
    },

    release(id) {
        if (this.current !== id) return;   // idempotent - a stray call is a no-op
        this.current = null;
        this._maybeRetire(id);
        if (this.stopped) return;
        this._advance(PQ_GAP_MS);          // R2: the clock starts at CLOSE
    },

    stop(reason) {
        if (this.stopped) return;
        this.stopped = true;
        this._urgent = null;
        this._clearWait();
        this._waitFn = null; this._waitTag = null;
        if (this.homePage) { const v = _visit(); v.engaged = true; _saveVisit(); }
        // Whatever the shopper actually saw is finished with now.
        Object.keys(this.members).forEach((id) => this._maybeRetire(id));
        // Deliberately does not close what is on screen: pulling a modal out from
        // under a half-finished click is its own bug.
    },

    hold(tag) { this.holds.add(tag); this._clearWait(); this._waitFn = null; this._waitTag = null; },
    unhold(tag) {
        if (!this.holds.delete(tag)) return;
        // An exit gesture that arrived while the queue was held keeps its
        // immediacy: without the 0 it would fall through to the member's own
        // delay, and an event-driven member has no meaningful one.
        this._advance(this._urgent ? 0 : undefined);
    },

    isEligible(id) {
        const m = this.members[id];
        if (!m || this.stopped) return false;
        if (!m.canShow()) return false;
        const shows = _visit().shown[id] || 0;
        if (m.mode === 'event' && shows >= 1) return false;   // the exit popup: once a visit
        return shows < this._cap();
    },

    // ---- internals --------------------------------------------------
    _cap() { return this.reduced ? 1 : PQ_MAX_CYCLES; },

    // Is a hold currently blocking the queue? A timed marketing popup always
    // waits - that is what the consent hold is for. An exit gesture does not:
    // it is shopper-initiated and time-critical, and the thing that triggered it
    // is the shopper leaving, so deferring it for the consent grace does not
    // delay it, it cancels it. That would silently take abandoned-cart recovery
    // off every page for first-time visitors, who are the ones being held.
    _heldOut() {
        if (!this.holds.size) return false;
        const u = this._urgent && this.members[this._urgent];
        return !(u && u.mode === 'event');
    },

    _buildOrder() {
        this.order = Object.keys(this.members)
            .filter((id) => this.members[id].mode === 'timed')
            .sort((a, b) => this.members[a].priority - this.members[b].priority);
    },

    _plan() {
        if (this.planned) return;
        this.planned = true;
        this._buildOrder();
        this._advance();   // no delay argument => each member's own opening delay
    },

    // The 30-day key means "this shopper is done with this popup", not "it was
    // painted once". One predicate on purpose: it is the rule most likely to be
    // misread later.
    _maybeRetire(id) {
        const m = this.members[id];
        // The flash sale writes its own per-slug session key in dismiss().
        if (!m || !m.seenKey || m.seenStore !== 'local') return;
        if (!(_visit().shown[id] || 0)) return;
        const finished = this.stopped || m.mode === 'event' || this.cycle + 1 >= this._cap();
        if (finished) _markSeen(m.seenKey);
    },

    _peek() {
        if (this._urgent) {
            if (this.isEligible(this._urgent)) return this._urgent;
            this._urgent = null;
        }
        while (this.idx < this.order.length && !this.isEligible(this.order[this.idx])) this.idx++;
        return this.idx < this.order.length ? this.order[this.idx] : null;
    },

    _advance(delayMs) {
        // Stall guard. A component torn down or navigated over while it was open
        // would otherwise pin `current` and silently kill every popup after it,
        // which is a worse and much harder-to-report failure than stacking.
        if (this.current) {
            const root = this.members[this.current] && this.members[this.current].root;
            if (root && !document.contains(root)) this.current = null;
            else return;
        }
        if (this.stopped || this._heldOut() || this._waitFn) return;
        const id = this._peek();
        if (!id) { this._scheduleRestart(); return; }
        const ms = delayMs === undefined ? this.members[id].delay : delayMs;
        this._wait(ms, 'gap', () => this._fire());
    },

    _fire() {
        if (this.stopped || this.current || this._heldOut()) return;
        const id = this._peek();
        if (!id) { this._scheduleRestart(); return; }
        if (this._urgent === id) this._urgent = null; else this.idx++;
        this._show(id);
    },

    _show(id) {
        this.current = id;
        const v = _visit();
        v.shown[id] = (v.shown[id] || 0) + 1;
        _saveVisit();
        this.members[id].show();
    },

    // R3: the shopper is still sitting on home, has not engaged and has not left,
    // so run the list again - once. "Restart the cycle" is a permission, not a
    // licence to loop for as long as the tab stays open.
    _scheduleRestart() {
        if (!this.homePage || this.stopped || this._waitTag === 'rest') return;
        if (this.cycle + 1 >= this._cap()) return;
        if (!this.order.some((id) => (_visit().shown[id] || 0) > 0)) return;
        this._wait(PQ_REST_MS, 'rest', () => {
            if (this.stopped) return;
            this.cycle++;
            const v = _visit(); v.cycle = this.cycle; _saveVisit();
            this.idx = 0;
            this._advance(PQ_GAP_MS);
        });
    },

    // One pausable timer for the whole queue. Browsers throttle timers in a
    // background tab, so an unpaused gap would burn a popup's turn against a tab
    // nobody is looking at and then ambush the shopper on return.
    _wait(ms, tag, fn) {
        this._clearWait();
        this._waitFn = fn; this._waitTag = tag; this._waitMs = ms; this._waitFrom = Date.now();
        if (document.hidden) return;
        this._t = window.setTimeout(() => {
            this._t = null;
            const f = this._waitFn;
            this._waitFn = null; this._waitTag = null;
            if (f) f();
        }, ms);
    },
    _clearWait() { if (this._t) { window.clearTimeout(this._t); this._t = null; } },

    _onVisibility() {
        if (document.hidden) {
            if (this._t && this._waitFn) {
                this._waitMs = Math.max(0, this._waitMs - (Date.now() - this._waitFrom));
                this._clearWait();
            }
        } else if (this._waitFn && !this._t) {
            this._wait(this._waitMs, this._waitTag, this._waitFn);
        }
    },

    _onPagehide() {
        // Never let a bfcache restore paint a stale modal over a page whose
        // scroll lock was torn down with it.
        const id = this.current;
        if (id && this.members[id]) this.members[id].hide();
        this.current = null;
        // Retire it here rather than leaving it to the $watch. hide() only flips
        // the component's `open` flag, and Alpine defers a watcher to a
        // microtask - by the time it runs, current is null and release() bails
        // before retiring. That matters most for the exit popup, whose likeliest
        // ending is exactly this one: it appears because the shopper is leaving,
        // and then they leave. Without this its 30-day key is never written and
        // it greets them again on the next visit. _maybeRetire is idempotent, so
        // a later real release() costs nothing.
        if (id) this._maybeRetire(id);
        // The pending callback has to go with its timer. _clearWait() only drops
        // the timer handle, and _advance()/request() both refuse to do anything
        // while _waitFn is set - so leaving it behind parks the queue for the
        // rest of the page view, with nothing able to re-arm it.
        this._clearWait();
        this._waitFn = null; this._waitTag = null;
    },

    _onPageshow() {
        _visitCache = null;
        const v = _visit();
        this.cycle = v.cycle || 0;
        if (this.homePage && v.engaged) { this.stop('engaged'); return; }
        // A page restored from the bfcache never re-runs init(), so this is the
        // only thing that puts the queue back to work after _onPagehide cleared it.
        if (!this.stopped) this._advance();
    },

    _onInteract(e) {
        if (this.stopped || !this.homePage) return;
        const el = e.target instanceof Element ? e.target : (e.target && e.target.parentElement);
        if (!el) return;
        if (el.closest(PQ_IGNORE)) return;
        if (el.closest(PQ_CHROME)) {
            // Inside a popup only a real navigation counts. The close button, the
            // backdrop, the inputs and the submit are the shopper saying no, and
            // treating those as engagement would stop the queue on its own chrome.
            const a = el.closest('a[href]');
            if (a && !(a.getAttribute('href') || '').startsWith('#')) this.stop('converted-cta');
            return;
        }
        if (e.type === 'submit' || el.closest(PQ_ENGAGE)) this.stop('engaged');
    },
});

Alpine.data('offerPopup', () => ({
    open: false, submitting: false, done: false, error: '',
    form: { name: '', email: '', phone: '' },
    key: 'kk_offer_popup_seen',
    init() {
        if (_seen(this.key)) return;   // the 30-day gate, read once, here
        const q = this.$store.popupQueue;
        q.register('offer', {
            priority: 20, delay: 3500,
            seenKey: this.key, seenStore: 'local',
            root: this.$root,
            canShow: () => !this.done,
            // Clear the transient submit state on the way in, not on the way
            // out: a shopper who closed this after a failed submit would
            // otherwise meet the same red error, announced again by role=alert,
            // when the cycle brings the popup back. What they typed is kept.
            show: () => { this.error = ''; this.submitting = false; this.open = true; },
            hide: () => { this.open = false; },
        });
        // This tells the queue about every close path there is - the X, the
        // backdrop, Escape and the 2800ms auto-close after a signup - without any
        // of them being edited, and it cannot be forgotten the way a manual
        // release() call in one branch can.
        this.$watch('open', (v) => { if (!v) q.release('offer'); });
    },
    destroy() { this.$store.popupQueue.release('offer'); },
    close() { this.open = false; },
    async submit() {
        // A second click while the first request is still out would post the
        // same signup twice and answer one action with two outcomes. The button
        // is disabled as well; this is the half a second Enter key cannot get
        // round.
        if (this.submitting) return;

        // Whatever the last attempt was told, gone - before anything is judged,
        // so a server message is never left sitting beside a fresh client-side
        // one.
        this.error = '';

        // Every field, in priority order, and the request is NOT sent while one
        // of them is wrong. The email check is the LENIENT mirror because this
        // endpoint validates with a plain V::email(), and the phone check allows
        // 10-15 digits because the server does - a browser rule tighter than the
        // server's would reject subscribers the site is happy to take.
        const invalid = _nameError(this.form.name)
            || _emailShapeError(this.form.email)
            || _subscriberPhoneError(this.form.phone, true);
        if (invalid) { this.error = invalid; return; }

        this.submitting = true;
        try {
            const res = await fetch('/newsletter/subscribe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': _csrf() },
                body: JSON.stringify({ ...this.form, source: 'offer_popup' }),
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                this.done = true;
                // Before the auto-close, so the close below finds the queue
                // already stopped and schedules nothing: nobody who just handed
                // over an email should be shown another popup 47s later.
                this.$store.popupQueue.stop('converted');
                window.setTimeout(() => this.close(), 2800);
                return;
            }
            // Only now, and only one message: a 422's first field complaint if
            // the server caught something the checks above could not, otherwise
            // the sentence mapped from the status. Never the raw body on a 5xx.
            const failure = window.kkApiError(res, data);
            this.error = Object.values(failure.fields)[0] || failure.message;
        } catch (e) {
            this.error = window.kkApiError(e).message;
        }
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

// accountEmail is the signed-in customer's address, server-rendered. It seeds
// the email field so the common case - a logged-in shopper claiming for
// themselves - matches without them retyping anything, and it is what the
// success copy switches on. Still editable: claiming for somebody else stays
// possible, it just cannot apply to your own cart.
Alpine.data('exitPopup', (code = 'KARMAA10', minutes = 10, accountEmail = '') => ({
    open: false, submitting: false, done: false, error: '',
    form: { email: '', phone: '' },
    code, timeLeft: `${minutes}:00`, _minutes: minutes, _tick: null, _dwell: null, _armed: false, _lastY: 0,
    accountEmail, state: '', expired: false, discount: 0, claimedEmail: '', _reloadHost: false,
    key: 'kk_exit_popup_seen',
    // Whether the address claimed with is the account being browsed as. Derived
    // here rather than sent by the server on purpose: every input is already
    // known to the browser, so the response never has to carry a signal for
    // whether an address has an account behind it.
    get matchesAccount() {
        const a = (this.accountEmail || '').trim().toLowerCase();
        return a !== '' && a === (this.claimedEmail || '').trim().toLowerCase();
    },
    // The same test against the LIVE field rather than the submitted address, so
    // the "Applied automatically" chip tracks what is actually typed.
    get willApplyAutomatically() {
        const a = (this.accountEmail || '').trim().toLowerCase();
        return a !== '' && a === (this.form.email || '').trim().toLowerCase();
    },
    get discountLabel() {
        const n = Number(this.discount) || 0;
        return '₹' + n.toLocaleString('en-IN', { maximumFractionDigits: 2 });
    },
    init() {
        if (_seen(this.key)) return;
        this.form.email = this.accountEmail || '';
        const q = this.$store.popupQueue;
        q.register('exit', {
            priority: 30, mode: 'event',
            seenKey: this.key, seenStore: 'local',
            root: this.$root,
            canShow: () => !this.done,
            // The countdown runs off the minutes the admin configured, never off
            // the already-decremented timeLeft the old trigger() parsed back out.
            show: () => { this.open = true; this.startCountdown(this._minutes); },
            hide: () => { this.open = false; },
        });
        this.$watch('open', (v) => { if (!v) q.release('exit'); });

        this._onMouseOut = (e) => { if (e.clientY <= 0 && !e.relatedTarget) this.trigger(); };
        document.addEventListener('mouseout', this._onMouseOut);
        // Mobile-friendly fallbacks: a fast upward scroll near the top, or long dwell.
        this._lastY = window.scrollY;
        // The scroll heuristic is kept everywhere except home, where the
        // back-to-top float's own window.scrollTo({top: 0, behavior: 'smooth'})
        // satisfies it exactly and makes this popup fire itself. Every other page
        // keeps all three triggers, so abandoned-cart recovery is unchanged.
        if (!q.homePage) {
            this._onScroll = () => {
                const y = window.scrollY;
                if (this._lastY - y > 60 && y < 120) this.trigger();
                this._lastY = y;
            };
            window.addEventListener('scroll', this._onScroll, { passive: true });
        }
        this._dwell = window.setTimeout(() => this.trigger(), 60000);
    },
    destroy() { this.$store.popupQueue.release('exit'); },
    trigger() {
        if (this.open) return;
        // The _seen() re-check that used to be here is gone on purpose: the
        // 30-day gate is read once in init(), and re-reading it here would veto
        // this popup against the key its own retirement had just written.
        this.$store.popupQueue.request('exit');
        this.cleanup();
    },
    startCountdown(minutes) {
        if (this._tick) window.clearInterval(this._tick);   // a second show must not double-tick
        const end = Date.now() + minutes * 60 * 1000;
        this._tick = window.setInterval(() => {
            const ms = Math.max(0, end - Date.now());
            const m = Math.floor(ms / 60000), s = Math.floor((ms % 60000) / 1000);
            this.timeLeft = `${m}:${s < 10 ? '0' : ''}${s}`;
            // Closing the form is what the timer has always implied and never
            // did. A claim already made is unaffected - its real horizon lives
            // server-side in offer_claims.expires_at.
            if (ms <= 0) { window.clearInterval(this._tick); this.expired = true; }
        }, 1000);
    },
    cleanup() {
        if (this._onMouseOut) document.removeEventListener('mouseout', this._onMouseOut);
        if (this._onScroll) window.removeEventListener('scroll', this._onScroll);
        if (this._dwell) window.clearTimeout(this._dwell);
    },
    close() {
        this.open = false;
        if (this._tick) window.clearInterval(this._tick);
        if (this._reloadHost) window.location.reload();
    },
    async claim() {
        if (this.submitting) return;

        this.error = '';
        if (this.expired) return;

        // Same two mirrors as the offer popup, for the same reason: this posts
        // to the newsletter endpoint too. The number is optional here, so an
        // empty box is not an error - only a malformed one is.
        const invalid = _emailShapeError(this.form.email)
            || _subscriberPhoneError(this.form.phone, false);
        if (invalid) { this.error = invalid; return; }

        this.submitting = true;
        try {
            const res = await fetch('/newsletter/subscribe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': _csrf() },
                body: JSON.stringify({ ...this.form, source: 'exit_intent' }),
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                // Falls back to 'saved' rather than 'applied': an old cached
                // bundle talking to a new server, or the reverse, must never
                // tell the customer a discount is on when it may not be.
                this.claimedEmail = this.form.email;
                const state = data.offer?.state;
                this.state = ['applied', 'saved', 'none'].includes(state) ? state : 'saved';
                this.discount = Number(data.offer?.discount) || 0;
                if (this.state === 'applied' && this.discount <= 0) this.state = 'saved';
                // The popup renders over /cart and /checkout too, and on those
                // pages the totals behind it have just gone stale: the server
                // attached the coupon, but the summary on screen was painted
                // before that. Saying "already off your bag" over a total that
                // still shows full price reads as a bug, so reconcile the page.
                if (this.state === 'applied') this._reloadHost = /^\/(cart|checkout)\/?$/.test(window.location.pathname);
                this.done = true;
                this.$store.popupQueue.stop('converted');
                return;
            }
            const failure = window.kkApiError(res, data);
            this.error = Object.values(failure.fields)[0] || failure.message;
        } catch (e) {
            this.error = window.kkApiError(e).message;
        }
        finally { this.submitting = false; }
    },
}));

// The homepage signup band. Both popups are once-per-browser (see _seen), so
// without this there is no way at all to join the list after dismissing them -
// which is also why this posts source 'homepage' rather than reusing 'footer'.
// The endpoint answers JSON, so the form cannot be a plain POST: it would
// render the response body as text over the page.
Alpine.data('newsletterSignup', () => ({
    email: '', submitting: false, done: false, error: '', message: '',
    async submit() {
        if (this.submitting) return;

        this.error = '';

        // The box only ever checked that SOMETHING had been typed, so "priya@"
        // made the round trip just to come back rejected. Same shape test the
        // server runs, before the request instead of after it.
        const invalid = _emailShapeError(this.email);
        if (invalid) { this.error = invalid; return; }

        this.submitting = true;
        try {
            const res = await fetch('/newsletter/subscribe', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': _csrf() },
                body: JSON.stringify({ email: this.email, source: 'homepage' }),
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data.success) {
                this.done = true;
                this.message = data.message || 'You are subscribed. Thanks for joining us.';
                return;
            }
            const failure = window.kkApiError(res, data);
            this.error = Object.values(failure.fields)[0] || failure.message;
        } catch (e) {
            this.error = window.kkApiError(e).message;
        }
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
// It also OWNS the messages Blade printed for the same fields - see
// <x-field-error> and the SERVER_NOTE block below - so a field can only ever
// carry one sentence, whichever side of the wire it came from.
//
// Two opt-outs, and they mean different things:
//
//   novalidate        the form validates itself (kkRegisterForm, the header
//                     sign-in modal, both popups). This module prints nothing
//                     for its fields; typing-level help still applies.
//   data-no-validate  hands off entirely.
//
// See optedOut()/handsOff() below for why the distinction earns its keep.
(function () {
    const ERROR_CLASS = 'kk-field-error';
    const INVALID_CLASS = 'kk-input-invalid';

    // ------------------------------------------------------------------
    // Server-rendered messages, owned by the same system as the live ones
    // ------------------------------------------------------------------
    //
    // This module used to know only about the notes it created itself. Blade
    // printed the $errors bag as its own paragraphs, which clearError() had no
    // way to reach, so the two renderers stacked: the message from the LAST
    // response stayed on screen while this one added the message about THIS
    // attempt, and one empty email box produced
    //
    //     Email Address is required.                          (this module, now)
    //     The provided credentials do not match our records.   (Blade, last POST)
    //
    // Both were true of different moments and neither said so. <x-field-error>
    // now prints the server's message as a `.kk-field-error` carrying
    // data-kk-field-error="<key>", and <x-form-errors> marks its banner with
    // data-kk-form-error, which makes both of them this module's to retire.
    //
    // WHEN a server message is retired is the whole of the rule, and there are
    // exactly two moments:
    //
    //   * the field is EDITED - the server judged the old value, and the value
    //     has just changed, so the verdict no longer describes what is in the
    //     box. Not on blur: tabbing through a field is not correcting it, and a
    //     server message like "that email is already registered" has to survive
    //     the visitor looking at it.
    //   * a new submission STARTS - the whole response is about what was sent
    //     last time. Clearing it here is what guarantees that a submit blocked
    //     by client-side validation shows only the client-side message.
    //
    // A third moment falls out of showError(): if this module has something to
    // say about a field, it says it INSTEAD of the server's older sentence, so
    // one field never carries two messages.
    const SERVER_NOTE = '[data-kk-field-error]';
    const SERVER_BANNER = '[data-kk-form-error]';

    // What a form means when it says "not you".
    //
    // There are two ways to say it and they used to mean different things in
    // different handlers, which is how one box ended up with two messages under
    // it. Only the submit handler tested `novalidate`; the blur, character,
    // password and invalid handlers tested `data-no-validate` alone. But
    // `novalidate` suppresses only the browser's INTERACTIVE validation -
    // willValidate and checkValidity() keep working - so on a form that had
    // opted out and printed its own per-field messages (the Create Account
    // panel, the header sign-in modal, both popups), those four handlers went
    // right on injecting a second, differently worded paragraph under the same
    // field: "Full Name is required." from here, "Please enter your full name."
    // from the component, at the same moment.
    //
    // One helper now, used by all five:
    //
    //   novalidate         - the form has a validator of its own. This module
    //                        says NOTHING about its fields; the component owns
    //                        every message. Typing-level help (stripping a
    //                        letter out of a phone box, capping a mobile at ten
    //                        digits) still applies, because that is not a
    //                        message and no component duplicates it.
    //   data-no-validate   - hands off entirely, typing included.
    function optedOut(form) {
        return !!(form && (form.hasAttribute('data-no-validate') || form.hasAttribute('novalidate')));
    }

    function handsOff(form) {
        return !!(form && form.hasAttribute('data-no-validate'));
    }

    // Laravel keys an array field with dots ("variants.0.sku"); the input that
    // produced it is named "variants[0][sku]". <x-field-error> writes the dotted
    // form, so the input's name is brought to the same shape before matching.
    function fieldKey(field) {
        const name = (field.getAttribute && field.getAttribute('name')) || '';

        // A multi-file input is named "images[]", and Laravel reports the rules
        // that judge the SELECTION - max:10, and the like - under the bare key
        // "images". Left as-is, the bracket pair became a trailing dot and
        // matched nothing, so the one message about the upload as a whole was
        // the one message this module could not retire. The empty pair names
        // the collection itself, so it is dropped rather than translated.
        return name.replace(/\[\]$/, '').replace(/\[/g, '.').replace(/\]/g, '');
    }

    // Scoped to the field's own form so two forms on one page - the sign-in and
    // Create Account panels share a page and an error bag - cannot retire each
    // other's messages. Falls back to the document for a stray control.
    function serverNotesFor(field) {
        const key = fieldKey(field);
        if (!key) return [];

        const scope = field.form || document;
        return Array.from(scope.querySelectorAll(SERVER_NOTE))
            .filter((note) => note.getAttribute('data-kk-field-error') === key);
    }

    function dropServerNotes(field) {
        const notes = serverNotesFor(field);
        if (!notes.length) return;

        notes.forEach(function (note) {
            // Hand back the aria link this note was borrowing before it goes,
            // or the input is left pointing at an id that no longer exists.
            if (field.getAttribute('aria-describedby') === note.id) {
                field.removeAttribute('aria-describedby');
            }
            note.remove();
        });

        // The red box goes with the message that explained it. Leaving the
        // outline behind is the same stale-state bug in a quieter form: a field
        // marked wrong with nothing on screen saying why.
        field.classList.remove(INVALID_CLASS);
        if (field.getAttribute('aria-invalid') === 'true') field.removeAttribute('aria-invalid');
    }

    // The form a banner speaks for.
    //
    // <x-form-errors> is written immediately ABOVE its form far more often than
    // inside it, which puts the banner outside form.querySelectorAll() and left
    // it as the one piece of the previous response that survived a blocked
    // submit. Rather than making every one of sixty views declare the link, it
    // is resolved from where the banner sits: inside a form, that form; before
    // one, the next form in document order - which is what "the banner above
    // this form" means. data-kk-form-error-for="<form id>" overrides both, for
    // the rare layout where the banner is somewhere else entirely.
    function ownerFormFor(banner) {
        const explicit = banner.getAttribute('data-kk-form-error-for');
        if (explicit) return document.getElementById(explicit);

        const enclosing = banner.closest('form');
        if (enclosing) return enclosing;

        return Array.from(document.forms).find(function (f) {
            return banner.compareDocumentPosition(f) & Node.DOCUMENT_POSITION_FOLLOWING;
        }) || null;
    }

    function bannersFor(form) {
        return Array.from(document.querySelectorAll(SERVER_BANNER))
            .filter(function (b) { return ownerFormFor(b) === form; });
    }

    // Everything the last response said about this form, gone. Called at the top
    // of every submission, before a single constraint is checked.
    function dropFormServerState(form) {
        if (!form) return;

        Array.from(form.querySelectorAll(SERVER_NOTE)).forEach(function (note) {
            const key = note.getAttribute('data-kk-field-error');
            const owner = key && form.querySelector('[name="' + cssEscape(keyToName(key)) + '"]');
            if (owner) dropServerNotes(owner);
            else note.remove();
        });

        bannersFor(form).forEach(function (b) { b.remove(); });
    }

    // "variants.0.sku" back to "variants[0][sku]", to find the input again.
    function keyToName(key) {
        const parts = String(key).split('.');
        return parts.length === 1 ? parts[0] : parts[0] + parts.slice(1).map((p) => '[' + p + ']').join('');
    }

    function cssEscape(value) {
        if (window.CSS && typeof window.CSS.escape === 'function') return window.CSS.escape(value);
        return String(value).replace(/["\\]/g, '\\$&');
    }

    // On the way in: adopt every message Blade printed, so a server error and a
    // live one are indistinguishable from here on - same outline, same aria
    // wiring, same rules about when they disappear.
    function adoptServerNotes(root) {
        Array.from((root || document).querySelectorAll(SERVER_NOTE)).forEach(function (note) {
            const key = note.getAttribute('data-kk-field-error');
            if (!key) return;

            const scope = note.closest('form') || document;
            const field = scope.querySelector('[name="' + cssEscape(keyToName(key)) + '"]');
            if (!field || !field.willValidate) return;

            field.classList.add(INVALID_CLASS);
            field.setAttribute('aria-invalid', 'true');
            // aria-invalid alone says "wrong" without saying why; the link is
            // what gets the sentence read out.
            if (!field.getAttribute('aria-describedby') && note.id) {
                field.setAttribute('aria-describedby', note.id);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { adoptServerNotes(document); });
    } else {
        adoptServerNotes(document);
    }

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

        // The character filter below arms a 2s timer that repaints this field's
        // message when it expires. Clearing the note without cancelling that
        // timer means the message comes back on its own a moment later, over a
        // field nothing is wrong with - and in a REUSED dialog (the restock
        // modal opens over whichever product was clicked) it comes back inside
        // the next product's form, describing the last one. Whoever clears the
        // message is also saying the field is settled, so the pending repaint
        // goes with it.
        if (field._kkCharTimer) {
            clearTimeout(field._kkCharTimer);
            field._kkCharTimer = null;
        }

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

    // The wrapper a message steps out to is the field's OWN decoration box - the
    // one holding a "+91" prefix, a password eye or a search icon. Nothing more.
    //
    // closest('.relative') does not stop there. `relative` is on cards, modal
    // panels and page sections too, so a bare <input> in an undecorated <div>
    // matched the nearest panel instead of a wrapper of its own. On the offer
    // popup that panel IS the dialog:
    //
    //     <div class="fixed inset-0 flex items-center justify-center">
    //       <div class="absolute inset-0 bg-black/60"></div>   <- backdrop
    //       <div class="relative w-full max-w-2xl">...</div>   <- the dialog
    //     </div>
    //
    // so the note landed as the dialog's SIBLING, inside the centring row. It
    // carries flex-basis: 100% (so it clears the field above it rather than
    // sitting beside it), which as a flex item meant it claimed the row and
    // shoved the dialog off to the left edge - while the message itself, being
    // static next to an absolutely positioned backdrop, was painted underneath
    // it. The customer saw the dialog slide sideways and no reason why.
    //
    // A field's own wrapper is close by and holds that field alone; a panel
    // holds the rest of the form as well, which is what tells the two apart.
    const WRAPPER_SELECTOR = '.relative, .kk-loginmodal__inputwrap';
    const WRAPPER_MAX_DEPTH = 3;

    function wrapperFor(field) {
        let node = field.parentElement;

        for (let depth = 0; node && depth < WRAPPER_MAX_DEPTH; depth++, node = node.parentElement) {
            if (node.matches('form, fieldset, body')) break;
            if (!node.matches(WRAPPER_SELECTOR)) continue;

            // More than one control under it makes it a panel, a card or a row
            // of fields - somewhere this field's message does not belong.
            // Hidden inputs (CSRF, _method) are not decorated and do not count.
            const controls = node.querySelectorAll('input:not([type="hidden"]), select, textarea');
            if (controls.length > 1) break;

            return node;
        }

        return field;
    }

    function showError(field, message) {
        clearError(field);

        // If this module has something to say about the field, it says it
        // INSTEAD of whatever the last response said - never underneath it.
        // This is the line that makes "one error per field" true rather than
        // merely intended.
        dropServerNotes(field);

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
        const anchor = wrapperFor(field);
        anchor.parentNode.insertBefore(note, anchor.nextSibling);
        field._kkErrorNote = note;
    }

    // Hand a control back in a clean state.
    //
    // For the dialogs that are opened over one row and then reopened over
    // another - the restock modal on the low-stock and out-of-stock screens is
    // the same <form>, refilled each time - the fields survive between openings
    // and so does everything hanging off them: the note, the red outline, the
    // aria link, the pending repaint timer. Clearing the DOM by hand from the
    // page's own script reaches the first three and not the fourth, and cannot
    // reach the note tracked on the element as _kkErrorNote at all. This is the
    // one call that undoes all of it, so a dialog does not have to know how any
    // of it is stored.
    window.kkResetField = function (field) {
        if (!field || !field.classList) return;
        dropServerNotes(field);
        clearError(field);
        field[TOUCHED] = false;
        if (field.setCustomValidity) field.setCustomValidity('');
    };

    /** The same, for every control in a form. */
    window.kkResetForm = function (form) {
        if (!form) return;
        dropFormServerState(form);
        Array.from(form.elements).forEach(window.kkResetField);
    };

    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;

        // STEP 1 of every submission, and it runs for EVERY form - including the
        // ones that opt out of the checks below, and the ones Alpine submits
        // itself. Whatever the last response said is about the last request; the
        // moment a new one starts it is history, and leaving it on screen is how
        // "The provided credentials do not match our records." ended up sitting
        // under an email box that had since been emptied.
        dropFormServerState(form);

        if (optedOut(form)) return;

        const fields = Array.from(form.elements).filter(function (el) {
            return el.willValidate && !el.disabled && el.type !== 'hidden';
        });

        fields.forEach(clearError);
        const invalid = fields.filter(function (el) { return !el.checkValidity(); });
        if (invalid.length === 0) return;

        // STEP 2: the request is NOT sent. stopPropagation is what keeps any
        // other submit listener - a fetch handler, an analytics hook - from
        // firing it behind this module's back, which is the only way a
        // backend error could still arrive for a submission that never left.
        event.preventDefault();
        event.stopPropagation();
        invalid.forEach(function (el) { showError(el, messageFor(el)); });

        const first = invalid[0];
        first.focus({ preventScroll: true });
        first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, true);

    // One submission at a time.
    //
    // Bound in the BUBBLE phase on purpose. By the time an event reaches here,
    // every handler on the form itself has run, so event.defaultPrevented tells
    // us whether the browser is actually about to navigate - which is the
    // difference between a real submission worth guarding and an Alpine
    // @submit.prevent form that manages its own request and must stay usable.
    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        if (event.defaultPrevented || form.hasAttribute('data-no-submit-guard')) return;

        if (form._kkSubmitting) {
            // A second click, or an Enter on top of a click. The first request
            // is already on its way; sending it twice means two orders, two
            // signups, or two contradictory answers to one action.
            event.preventDefault();
            return;
        }

        form._kkSubmitting = true;

        // Disabled on the NEXT tick, never in this handler. A disabled control
        // is omitted from the payload, and a submit button routinely carries the
        // name/value that says which action was chosen ("save" vs "publish"), so
        // disabling it here would quietly drop that from the request the click
        // just started.
        const buttons = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"], button:not([type])'))
            .filter(function (b) { return !b.disabled; });

        window.setTimeout(function () {
            buttons.forEach(function (b) {
                b.disabled = true;
                b.setAttribute('aria-busy', 'true');
                b.classList.add('kk-submitting');
            });
        }, 0);

        form._kkSubmitButtons = buttons;
    });

    // Back button. A page restored from the bfcache comes back exactly as it
    // left - mid-submit, with its button greyed out - and the visitor is then
    // looking at a form they cannot send. Restore it.
    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) return;

        Array.from(document.forms).forEach(function (form) {
            if (!form._kkSubmitting) return;
            form._kkSubmitting = false;
            (form._kkSubmitButtons || []).forEach(function (b) {
                b.disabled = false;
                b.removeAttribute('aria-busy');
                b.classList.remove('kk-submitting');
            });
        });
    });

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

            // The server judged the value that was in this box when it was
            // submitted. That value has just changed, so the verdict is about
            // something that is no longer on screen - "that email is already
            // registered" must not still be sitting under an address the
            // visitor has since rewritten. Retired on the EDIT, deliberately,
            // and not on blur: passing through a field is not correcting it.
            dropServerNotes(field);

            if (optedOut(field.form)) return;
            if (field.classList.contains(INVALID_CLASS) && field.checkValidity()) clearError(field);
        }, true);

        // And again on the way back OUT, in the bubble phase.
        //
        // The pass above runs at document capture, which is before any listener
        // bound on the field itself - and two modules in this app decide a
        // field's validity in exactly such a listener: the compare-at price
        // guard on the product form and the schedule-pair module at the bottom
        // of this file both call setCustomValidity() from their own `input`
        // handler. On the keystroke that FIXES the value, the capture pass
        // therefore reads a custom validity that is one event stale, still sees
        // the field as invalid, and leaves the note up; the message only cleared
        // on the keystroke after the correction, or not at all if that was the
        // last one typed.
        //
        // Running the same check once more after those listeners have had their
        // say costs nothing when nothing changed - clearError() on a field with
        // no note is a no-op - and makes "the message goes when you fix it" true
        // for a custom constraint as well as a native one.
        document.addEventListener(type, function (event) {
            const field = event.target;
            if (!field || !field.willValidate || optedOut(field.form)) return;
            if (field.classList.contains(INVALID_CLASS) && field.checkValidity()) clearError(field);
        }, false);
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
        // A customer's OWN name, as opposed to `letters` above. The contact
        // form was specified as letters and spaces; an account, address or
        // checkout name is the one on the parcel, so the three separators
        // PersonName keeps have to survive the keystroke - dropping them means
        // "O'Connor", "Mary-Anne" and "R. Sharma" cannot be typed at all.
        personName: { allow: /[\p{L}\p{M} \u00A0'\u2019.\-]/u, message: 'Names can only contain letters, spaces, hyphens, apostrophes and periods.' },
    };

    // The autocomplete tokens that describe a PERSON's name, as opposed to a
    // company's. A field that tells the browser's autofill it holds a human
    // name has told this filter the same thing, so the inference below can
    // read it rather than waiting for someone to remember data-kk-chars on
    // each new form - which is exactly how the offer popup's name box ended up
    // as the one signup field on the site that accepted "686876988".
    //
    // `organization` and `nickname` are deliberately absent: a business name
    // legitimately carries digits and an ampersand, and a nickname is not a
    // name on a parcel.
    const NAME_AUTOCOMPLETE = new Set([
        'name', 'given-name', 'additional-name', 'family-name',
        'honorific-prefix', 'honorific-suffix',
        'cc-name', 'cc-given-name', 'cc-additional-name', 'cc-family-name',
    ]);

    function charPolicy(field) {
        const named = field.getAttribute && field.getAttribute('data-kk-chars');
        if (named) return CHAR_POLICIES[named] || null;

        // Inferred, so every existing tel/numeric input is covered without
        // touching thirteen blade files, and new ones get it for free.
        if (field.type === 'tel') return CHAR_POLICIES.phone;

        // Same bargain for names. personName, never `letters`: an inferred
        // policy lands on checkout and address boxes, where the name is the
        // one on the parcel and "O'Connor" has to stay typeable. The two forms
        // specified as letters-and-spaces say so with an explicit
        // data-kk-chars="letters", which is read above and wins over this.
        //
        // autocomplete is a token list - "shipping name", "section-b billing
        // given-name" - and the field type is always its LAST token, so a
        // decorated attribute is matched rather than skipped.
        const auto = (field.getAttribute && field.getAttribute('autocomplete') || '').trim().toLowerCase();
        if (auto && NAME_AUTOCOMPLETE.has(auto.split(/\s+/).pop())) return CHAR_POLICIES.personName;

        const mode = (field.getAttribute && field.getAttribute('inputmode') || '').toLowerCase();
        if (mode === 'numeric') return CHAR_POLICIES.digits;
        if (mode === 'decimal') return CHAR_POLICIES.decimal;
        return null;
    }

    document.addEventListener('input', function (event) {
        const field = event.target;
        if (!field || typeof field.value !== 'string') return;

        const form = field.form;
        if (handsOff(form)) return;

        const policy = charPolicy(field);
        if (!policy) return;

        // An IME is mid-word: the field currently holds a composition buffer,
        // not a value. Rewriting field.value and moving the selection now leaves
        // the IME's own buffer disagreeing with the field, and the next keystroke
        // replays the stale buffer against the wrong offsets - Gboard duplicates
        // the word, iOS Safari commits the half-composed text. The VNI layout is
        // the clearest case: it spells Vietnamese tones as digits, so "Nguyen64"
        // becomes "Nguyễn" only once the composition commits, and stripping the
        // digits as they arrive makes that name untypeable. Nothing is lost by
        // waiting - a committed composition fires its own input event, which this
        // handler then judges normally.
        if (event.isComposing) return;

        const before = field.value;

        // Iterated by code point, not by UTF-16 code unit. A supplementary-plane
        // letter - 𠮟 and 𠀀 are real CJK name characters - is two code units,
        // and testing each half alone matches neither \p{L} nor \p{M}, so the
        // character used to be deleted as it was typed. Both the server rule and
        // the pattern beside it accept those names, which made this filter the
        // strictest layer of the three and the only silent one.
        let cleaned = '';
        let removedBeforeCaret = 0;

        // Put the caret back where it was, minus whatever was dropped ahead of
        // it, so editing the middle of a number does not throw the cursor to
        // the end on every rejected keystroke. Counted in code units, because
        // that is what setSelectionRange takes.
        const caret = typeof field.selectionStart === 'number' ? field.selectionStart : before.length;
        let unitsSeen = 0;

        for (const ch of before) {
            if (policy.allow.test(ch)) cleaned += ch;
            else if (unitsSeen < caret) removedBeforeCaret += ch.length;
            unitsSeen += ch.length;
        }

        if (cleaned === before) return;

        field.value = cleaned;
        try { field.setSelectionRange(caret - removedBeforeCaret, caret - removedBeforeCaret); } catch (e) { /* unsupported on some types */ }

        // The character is refused either way - that is the useful half, and no
        // component duplicates it. The NOTE is this module's to print only when
        // the form has no validator of its own; on one that does, saying so here
        // would put a second sentence under a box the component is already
        // writing to.
        if (optedOut(field.form)) return;

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

    // A password being CHOSEN, judged on the keystroke rather than on the way
    // out of the box.
    //
    // Everything else here waits for blur, and for good reason: half an email
    // address is unfinished, not wrong. A password is the one field where that
    // reasoning fails. Its rule has four separate parts, what is on screen is a
    // row of dots, and a password is invented rather than recalled - so being
    // told at the end that the thing you just made up is a character short means
    // making up another one. Checked live, the message counts down as it is
    // typed and vanishes the moment the rule is satisfied.
    //
    // WHICH boxes, and it is the whole of the care in this module:
    //
    //   * a sign-in box is never judged. An account created under the old
    //     eight-character policy has a password that fails this one, and a sign-in
    //     form that refuses to accept it locks that customer out of the only
    //     screen they could fix it from.
    //   * an admin settings box is never judged either. An SMTP password, a
    //     gateway salt, a webhook secret and an API key are all somebody else's
    //     credential, already minted, and whatever they say it is.
    //
    // They are told apart the way the browser's own password manager tells them
    // apart - autocomplete="new-password" against "current-password" - read
    // together with the field's NAME, because the settings screens label their
    // API keys new-password too (so autofill does not offer the admin their own
    // password into a webhook secret), and the name is what separates
    // `password` from `mail_password` and `shiprocket_password`.
    //
    // data-kk-password overrides both, following data-kk-chars next door:
    //   data-kk-password="new"     - judge it, whatever it is called
    //   data-kk-password="confirm" - match it against the box it repeats
    //   data-kk-password="off"     - hands off; a form with its own validator
    //                                (kkRegisterForm) says this, so the message
    //                                is not printed twice under one box.
    const NEW_PASSWORD_NAMES = new Set([
        'password', 'password_confirmation',
        'new_password', 'new_password_confirmation',
    ]);

    function passwordRole(field) {
        if (!field || typeof field.value !== 'string' || !field.getAttribute) return null;

        const named = field.getAttribute('data-kk-password');
        if (named === 'off') return null;
        if (named === 'new' || named === 'confirm') return named;

        // A token list - "section-blue new-password" is legal - and the field
        // type is always the last token, as in charPolicy() above.
        const auto = (field.getAttribute('autocomplete') || '').trim().toLowerCase();
        if (!auto || auto.split(/\s+/).pop() !== 'new-password') return null;

        const name = (field.getAttribute('name') || '').toLowerCase();
        if (!NEW_PASSWORD_NAMES.has(name)) return null;

        return name.endsWith('_confirmation') ? 'confirm' : 'new';
    }

    // The box a confirmation repeats: same form, same name without the suffix.
    // Falls back to plain `password` for a field named by data-kk-password
    // rather than by convention.
    function passwordPartner(field) {
        const form = field.form;
        if (!form) return null;

        const name = (field.getAttribute('name') || '');
        const base = name ? name.replace(/_confirmation$/, '') : 'password';
        if (!base || base === name) return form.querySelector('[name="password"]');

        return form.querySelector('[name="' + base + '"]');
    }

    function checkPassword(field, live) {
        const role = passwordRole(field);
        if (!role) return;

        const form = field.form;
        if (optedOut(form)) return;

        const value = field.value || '';
        let message = '';

        if (role === 'new') {
            // An empty box is not wrong yet. `required` is what says a password
            // is missing, and on the forms where it is optional - "leave blank
            // to keep current" - nothing is wrong with an empty box at all.
            message = value === '' ? '' : _passwordError(value);
        } else {
            const partner = passwordPartner(field);
            const pw = partner ? (partner.value || '') : '';

            if (value !== '') {
                message = value === pw ? '' : 'The two passwords do not match.';
            } else {
                // Empty. While the customer is still typing that is unfinished
                // rather than wrong, so nothing is said. On the way into a
                // submit it IS wrong once the password box has something in it,
                // and saying so is the difference between catching it here and
                // catching it on a reloaded page - the admin staff form leaves
                // both boxes optional, so `required` does not cover this.
                message = (!live && pw !== '') ? 'Please confirm your password.' : '';
            }
        }

        // setCustomValidity is the join between this and everything above it:
        // it stops the submit, and messageFor() reads it back for the blur,
        // submit and invalid handlers, so the live message and the one printed
        // on submit are the same sentence and can never contradict each other.
        field.setCustomValidity(message);

        if (!live) return;
        if (message) showError(field, message);
        else if (field.checkValidity()) clearError(field);
    }

    document.addEventListener('input', function (event) {
        const field = event.target;
        checkPassword(field, true);

        // Editing the password moves the goalposts for the confirmation, so a
        // confirmation already filled in is re-judged rather than left showing a
        // mismatch that the keystroke has just resolved.
        if (passwordRole(field) === 'new' && field.form) {
            const name = field.getAttribute('name') || 'password';
            const confirm = field.form.querySelector('[name="' + name + '_confirmation"]');
            if (confirm && confirm.value) checkPassword(confirm, true);
        }
    }, true);

    // A password box the customer never typed in has no custom validity set, so
    // a password filled in by the browser's own manager would reach the submit
    // unjudged. Setting it on blur and again on the way into a submit closes
    // that, without printing anything early.
    //
    // Both are bound to WINDOW rather than to the document, and that is load
    // bearing. The submit handler further up this file is document-capture and
    // was registered before anything here, so a document-capture listener added
    // at this point would run after it: it would call checkValidity() while the
    // custom validity is still one event stale and wave through a password this
    // module is about to mark invalid. Capture descends window before document,
    // so binding a step further out puts these ahead of it whatever the
    // registration order - and the same for the blur handler below.
    window.addEventListener('blur', function (event) {
        checkPassword(event.target, false);
    }, true);

    window.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        Array.from(form.elements).forEach(function (el) { checkPassword(el, false); });
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

        return optedOut(field.form);
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
        if (optedOut(form)) return;

        // This is the path a blocked submission actually takes. When a native
        // constraint fails, the browser fires `invalid` on each offending
        // control and abandons the submission WITHOUT firing `submit` - so the
        // step-1 clear-down at the top of the submit handler never runs, and
        // every message from the previous response would survive into this one.
        //
        // Doing it here is what guarantees the promise in both directions: the
        // request is not sent, AND nothing the server said last time is still on
        // screen next to the message explaining why it was not sent. Note that
        // it clears the whole FORM, not just this field: a stale note under a
        // box that is now filled in correctly is just as contradictory as one
        // under this box.
        //
        // Called once per failing control rather than once per pass, and that is
        // deliberate. Gating it on "first of the pass" would mean reading
        // pendingInvalid, which is emptied in a microtask - correct in a browser,
        // where each submission is its own task, and wrong the moment anything
        // drives two passes without yielding. The operation is idempotent and
        // touches only [data-kk-field-error] and [data-kk-form-error], never the
        // notes showError is about to add, so repeating it costs nothing and
        // removes the ordering assumption entirely.
        if (form) dropFormServerState(form);

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

        // Bound to WINDOW in the CAPTURE phase, filtered to this pair - the same
        // shape as the password checks in the validator above, and for the same
        // reason.
        //
        // A listener on the field itself runs in the AT_TARGET phase, which is
        // after every document-capture listener has had the event. One of those
        // is the validator's clear-on-input handler, and it calls
        // checkValidity() - so it was reading a setCustomValidity() written for
        // the PREVIOUS value, and a start or end date the admin had just
        // corrected kept its message until the keystroke AFTER the one that
        // fixed it. Capture descends window before document, so refreshing from
        // here puts the custom validity in step before anything reads it.
        const refreshPair = (event) => {
            if (event.target === start || event.target === end) refresh();
        };
        ['input', 'change', 'blur'].forEach((type) => window.addEventListener(type, refreshPair, true));

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
