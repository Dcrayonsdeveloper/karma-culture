/**
 * kk-select — one dropdown for every <select> on the site.
 *
 * A native select paints its option list with the operating system: square
 * corners, a system-blue highlight and the system font. On a cream-and-brown
 * storefront that list is the one piece of the page that still looks like
 * Windows, and no amount of CSS can reach inside it — `option` takes a colour
 * and nothing else. The only way to style the list is to stop it opening and
 * draw our own.
 *
 * What this deliberately does NOT do is replace the select. The element stays
 * exactly where it was, with its classes, its inline styles, its name, its
 * label, its required attribute and its place in the form, and it keeps
 * painting its own closed state. That is what makes the change safe across 93
 * of them:
 *
 *   - every rule already written for `.form-select` / `.layout-admin select`
 *     still styles the control, in both themes, with no duplication here;
 *   - constraint validation still has a real, visible control to focus — a
 *     display:none select fails `required` with "not focusable" and silently
 *     aborts the whole submit;
 *   - autofill and back/forward restore write `.value` with no event at all,
 *     and the browser redraws the label for us, so the control can never show
 *     a stale value;
 *   - it prints, and `<label for>` still works;
 *   - and if this file fails to load, every select is simply a normal select.
 *
 * Only two things are taken away. The pointer, via `pointer-events: none`
 * (set from here, never from the stylesheet, so a JS failure cannot strand a
 * dead control) — that is what guarantees the OS list can never open, on every
 * browser and on iOS, where preventDefault on mousedown does not reliably
 * suppress the picker. And the keys that would open that list, intercepted
 * only while our own panel is up.
 *
 * Everything else stays native. A closed select keeps its own arrow keys and
 * its own type-ahead. Choosing an option writes `selectedIndex` on the real
 * element and fires a bubbling `change` — the event Alpine's x-model listens
 * for on a select (it listens for `input` on everything except select,
 * checkbox and radio), and the one `onchange="this.form.submit()"` needs.
 */
(function () {
    'use strict';

    if (typeof window === 'undefined' || !window.document) return;

    // Above every z-index the site declares. The highest is the cookie-consent
    // bar at z-[100]; the sticky header is 40, admin overlays 50, the existing
    // product-picker menu 60. The panel is appended to <body>, which is also
    // how it escapes the admin shell's three nested overflow clips and
    // .kk-modal__card's scrollport — an absolutely positioned menu is sliced by
    // all of them.
    const Z_INDEX = 10000;

    // Gap between the control and the panel, and the breathing room kept
    // against the viewport edges.
    const GAP = 6;
    const EDGE = 8;

    // A panel taller than this scrolls instead of growing.
    const MAX_HEIGHT = 320;

    // How long a type-ahead buffer survives without another keystroke, matching
    // the native select.
    const TYPEAHEAD_MS = 500;

    // Below this width the panel is a bottom sheet rather than an anchored
    // menu: an inline menu near the foot of a phone viewport lands under the
    // PDP buy bar, the WhatsApp button and the cookie banner. The bound is the
    // stylesheet's to the decimal — at 639.5px a rounder number here would put
    // the sheet's CSS up around a panel positioned as a menu.
    const SHEET_MQ = window.matchMedia('(max-width: 639.98px)');

    const TICK = '<svg viewBox="0 0 20 20" aria-hidden="true" focusable="false"><path d="M4.5 10.5l3.5 3.5 7.5-8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    /** The open instance, or null. */
    let live = null;

    /** The select the pointer is currently over, so it can be given the hover tell. */
    let hovered = null;

    let hoverFrame = 0;

    /* ------------------------------------------------------------------ *
     * Which selects we take over
     * ------------------------------------------------------------------ */

    // A multi-line or multiple select is a list box, not a menu — it has no
    // popup to replace. `size` reports 0 for a plain single-line select.
    function eligible(el) {
        return el instanceof HTMLSelectElement
            && !el.multiple
            && (!el.size || el.size <= 1)
            && !el.closest('[data-kk-select-skip]');
    }

    function adopt(el) {
        if (!eligible(el) || el.dataset.kkSelect) return;
        el.dataset.kkSelect = 'on';
    }

    function scan(root) {
        if (!root || root.nodeType !== 1) return;
        if (root.tagName === 'SELECT') adopt(root);
        if (root.querySelectorAll) root.querySelectorAll('select').forEach(adopt);
    }

    /* ------------------------------------------------------------------ *
     * Hit testing
     * ------------------------------------------------------------------ */

    // The select is transparent to the pointer, so the hit lands on whatever is
    // behind it — normally its own parent. Starting from the topmost element at
    // the point and looking *down* for a select whose box contains it gets the
    // z-order right for free: if a modal or an overlay is covering the control,
    // the topmost element is that overlay and it has no select underneath.
    function selectAt(x, y) {
        const top = document.elementFromPoint(x, y);
        if (!top || !top.querySelectorAll) return null;

        // Over open page, the hit lands on <body> and a descendant query would
        // walk every select on the page — once per animation frame, on the
        // hover path. A select's own parent is what takes its hit, so from the
        // root only direct children can be the answer.
        const deep = top !== document.body && top !== document.documentElement;
        const candidates = top.querySelectorAll(deep ? 'select[data-kk-select]' : ':scope > select[data-kk-select]');
        for (let i = 0; i < candidates.length; i++) {
            const el = candidates[i];
            const r = el.getBoundingClientRect();
            if (x >= r.left && x <= r.right && y >= r.top && y <= r.bottom) return el;
        }
        return null;
    }

    /* ------------------------------------------------------------------ *
     * Hover
     * ------------------------------------------------------------------ *
     *
     * `pointer-events: none` also stops the select matching :hover, which would
     * lose the admin's border-colour tell — the one thing that marks a select
     * beside a row of buttons as something you press — and the pointer cursor
     * along with it. Both are put back by hand: a class on the select for the
     * border, and one on whatever element is actually taking the hit for the
     * cursor.
     */

    function setHover(el) {
        if (hovered === el) return;

        if (hovered) {
            hovered.classList.remove('kk-select-hover');
            if (hovered.parentElement) hovered.parentElement.classList.remove('kk-select-hovering');
        }

        hovered = el;

        if (hovered && !hovered.disabled) {
            hovered.classList.add('kk-select-hover');
            if (hovered.parentElement) hovered.parentElement.classList.add('kk-select-hovering');
        }
    }

    document.addEventListener('pointermove', (event) => {
        if (event.pointerType === 'touch') return;
        if (hoverFrame) return;

        const x = event.clientX;
        const y = event.clientY;

        hoverFrame = window.requestAnimationFrame(() => {
            hoverFrame = 0;
            setHover(selectAt(x, y));
        });
    }, { passive: true });

    document.addEventListener('pointerleave', () => setHover(null), { passive: true });

    /* ------------------------------------------------------------------ *
     * Reading the options
     * ------------------------------------------------------------------ */

    // Rows in render order: either a group heading or an option carrying its
    // index into `select.options`, which is what selectedIndex takes.
    function rows(select) {
        const out = [];

        Array.from(select.children).forEach((child) => {
            if (child.tagName === 'OPTGROUP') {
                if (child.label) out.push({ group: child.label });
                Array.from(child.children).forEach((opt) => {
                    if (opt.tagName === 'OPTION') {
                        out.push({ option: opt, disabled: opt.disabled || child.disabled });
                    }
                });
            } else if (child.tagName === 'OPTION') {
                out.push({ option: child, disabled: child.disabled });
            }
        });

        return out;
    }

    /* ------------------------------------------------------------------ *
     * Building the panel
     * ------------------------------------------------------------------ */

    let seq = 0;

    function build(inst) {
        const select = inst.select;

        const panel = document.createElement('div');
        panel.className = 'kk-sel' + (inst.admin ? ' kk-sel--admin' : '');
        panel.style.zIndex = String(Z_INDEX);

        // The panel is decoration. The select keeps the focus, keeps its role,
        // keeps its value and keeps announcing itself, so a screen reader gets
        // the untouched native control rather than a hand-rolled combobox that
        // only approximates one.
        panel.setAttribute('aria-hidden', 'true');

        // Match whatever the control is set in — Montserrat on the storefront,
        // 13px system on admin — so the list reads as the same widget.
        const cs = window.getComputedStyle(select);
        panel.style.fontFamily = cs.fontFamily;
        panel.style.fontWeight = cs.fontWeight;
        panel.style.letterSpacing = cs.letterSpacing;
        panel.style.fontSize = inst.sheet
            ? Math.max(parseFloat(cs.fontSize) || 14, 15) + 'px'
            : cs.fontSize;

        let html = '';

        if (inst.sheet) {
            html += '<div class="kk-sel__grip"></div>';
            const title = headingFor(select);
            if (title) html += '<div class="kk-sel__title"></div>';
            inst.title = title;
        }

        html += '<ul class="kk-sel__list" role="presentation"></ul>';
        panel.innerHTML = html;

        if (inst.sheet && inst.title) {
            panel.querySelector('.kk-sel__title').textContent = inst.title;
        }

        inst.panel = panel;
        inst.list = panel.querySelector('.kk-sel__list');
        inst.id = 'kk-sel-' + (++seq);

        fill(inst);

        if (inst.sheet) {
            const backdrop = document.createElement('div');
            backdrop.className = 'kk-sel__backdrop';
            backdrop.style.zIndex = String(Z_INDEX - 1);
            inst.backdrop = backdrop;
            document.body.appendChild(backdrop);
        }

        document.body.appendChild(panel);
    }

    // The sheet gets a heading so a phone user can see what they are choosing
    // once the control itself is behind the sheet. The <label> is the honest
    // source; on the sort bar it is `hidden sm:inline`, so fall back to the
    // aria-label the control already carries.
    function headingFor(select) {
        if (select.labels && select.labels.length) {
            const text = select.labels[0].textContent.replace(/\*/g, '').trim().replace(/:$/, '');
            if (text) return text;
        }
        return select.getAttribute('aria-label') || '';
    }

    function fill(inst) {
        const list = inst.list;
        list.textContent = '';
        inst.items = [];

        rows(inst.select).forEach((row) => {
            if (row.group) {
                const head = document.createElement('li');
                head.className = 'kk-sel__group';
                head.textContent = row.group;
                list.appendChild(head);
                return;
            }

            const li = document.createElement('li');
            li.className = 'kk-sel__opt';
            li.dataset.index = String(row.option.index);
            if (row.disabled) li.classList.add('is-disabled');

            const label = document.createElement('span');
            label.className = 'kk-sel__label';
            // An option with no text still needs a row of the right height,
            // and a placeholder like "-- none --" must show exactly as authored.
            label.textContent = row.option.textContent.trim() || ' ';
            li.appendChild(label);

            const tick = document.createElement('span');
            tick.className = 'kk-sel__tick';
            tick.innerHTML = TICK;
            li.appendChild(tick);

            list.appendChild(li);
            if (!row.disabled) inst.items.push(li);
        });

        // Nothing to choose from — a variant select before a product is picked,
        // for one. Say so rather than opening an empty box.
        if (!inst.items.length) {
            const empty = document.createElement('li');
            empty.className = 'kk-sel__empty';
            empty.textContent = 'No options available';
            list.appendChild(empty);
        }

        paint(inst);
    }

    // The chosen row carries the tick; the active row is the one the keyboard
    // or the pointer is on. They are separate on purpose: the repo's two older
    // pickers bind aria-selected to the highlight, which tells a screen reader
    // every stop is "selected" and never which value is real.
    function paint(inst) {
        const chosen = inst.select.selectedIndex;

        inst.list.querySelectorAll('.kk-sel__opt').forEach((li) => {
            const index = Number(li.dataset.index);
            li.classList.toggle('is-chosen', index === chosen);
            li.classList.toggle('is-active', li === inst.active);
        });
    }

    function setActive(inst, li, scroll) {
        if (!li || li === inst.active) {
            if (li && scroll) reveal(inst, li);
            return;
        }
        inst.active = li;
        paint(inst);
        if (scroll) reveal(inst, li);
    }

    function reveal(inst, li) {
        const list = inst.list;
        const top = li.offsetTop;
        const bottom = top + li.offsetHeight;

        if (top < list.scrollTop) list.scrollTop = top;
        else if (bottom > list.scrollTop + list.clientHeight) list.scrollTop = bottom - list.clientHeight;
    }

    function itemFor(inst, index) {
        return inst.items.find((li) => Number(li.dataset.index) === index) || null;
    }

    // Rebuild the rows and settle the panel again, in the one order that works:
    // the list has no scrollport until place() has given it a height, so the
    // chosen row cannot be scrolled to before then.
    function refresh(inst) {
        fill(inst);
        inst.active = itemFor(inst, inst.select.selectedIndex) || inst.items[0] || null;
        paint(inst);
        place(inst);
        if (inst.active) reveal(inst, inst.active);
    }

    /* ------------------------------------------------------------------ *
     * Placing the panel
     * ------------------------------------------------------------------ */

    function place(inst) {
        if (inst.sheet) return;

        const select = inst.select;
        const panel = inst.panel;
        const list = inst.list;

        const r = select.getBoundingClientRect();
        const vw = document.documentElement.clientWidth;
        const vh = document.documentElement.clientHeight;

        // Never narrower than the control, never wider than the viewport, and
        // allowed to grow past the control for the long option text the admin
        // has (hierarchical category paths, IANA timezones).
        panel.style.minWidth = r.width + 'px';
        panel.style.maxWidth = Math.max(160, vw - EDGE * 2) + 'px';

        // Measure at full height first, so the choice of direction is made
        // against how tall the list actually wants to be.
        list.style.maxHeight = '';
        const wanted = panel.offsetHeight;

        // `below` and `above` are budgets for the whole panel, but the cap is
        // spent on the list inside it. Without taking off the panel's own
        // padding and border it overshoots by exactly that much, and a
        // flipped-up panel ends up sitting over the control it belongs to.
        const chrome = Math.max(0, Math.round(
            panel.getBoundingClientRect().height - list.getBoundingClientRect().height
        ));

        const below = vh - r.bottom - GAP - EDGE;
        const above = r.top - GAP - EDGE;
        const down = wanted <= below || below >= above;
        const room = Math.max(80, Math.min((down ? below : above) - chrome, MAX_HEIGHT));

        list.style.maxHeight = room + 'px';
        panel.classList.toggle('is-up', !down);

        const height = panel.offsetHeight;
        const top = down ? r.bottom + GAP : Math.max(EDGE, r.top - GAP - height);

        let left = r.left;
        const width = panel.offsetWidth;
        if (left + width > vw - EDGE) left = vw - EDGE - width;
        if (left < EDGE) left = EDGE;

        panel.style.top = top + 'px';
        panel.style.left = left + 'px';
    }

    /* ------------------------------------------------------------------ *
     * Opening, committing, closing
     * ------------------------------------------------------------------ */

    function openFor(select) {
        if (select.disabled || !select.isConnected) return;
        if (live && live.select === select) return;

        close(true);

        const inst = {
            select: select,
            // The value to fall back to. Escape and a click outside cancel, the
            // way dismissing a native popup does; only Enter, Tab and a click on
            // a row commit.
            snapshot: select.selectedIndex,
            admin: !!(select.closest('.layout-admin') || document.body.classList.contains('layout-admin')),
            sheet: SHEET_MQ.matches,
            active: null,
            typed: '',
            typedAt: 0,
        };

        live = inst;
        build(inst);
        refresh(inst);

        // Focus stays on the real control for the whole life of the panel. That
        // is what keeps the keyboard and the screen reader on native rails, and
        // it is why there is no search box in here: the select's own type-ahead
        // already works, and stealing focus for an input would take it away.
        try { select.focus({ preventScroll: true }); } catch (e) { select.focus(); }

        select.classList.add('kk-select-open');
        if (inst.sheet) {
            // overflow:hidden on the root resets scrollTop in some engines, so
            // where the shopper was reading is put back on the way out.
            inst.scrollY = window.scrollY;
            document.documentElement.classList.add('kk-sel-locked');
        }

        // The option list is not always static: a variant select is rebuilt from
        // an x-for whenever the product changes, and a select can be removed
        // outright when its row is spliced away.
        inst.watcher = new MutationObserver(() => {
            // The row this select lives in can be spliced away under it, and a
            // variant select is disabled the moment its product loses variants.
            if (!select.isConnected || select.disabled) { close(true); return; }
            refresh(inst);
        });
        inst.watcher.observe(select, {
            childList: true,
            subtree: true,
            characterData: true,
            attributes: true,
            attributeFilter: ['disabled'],
        });

        window.requestAnimationFrame(() => {
            if (live === inst) inst.panel.classList.add('is-in');
        });
    }

    function close(revert) {
        const inst = live;
        if (!inst) return;
        live = null;

        if (inst.watcher) inst.watcher.disconnect();

        if (revert && inst.select.selectedIndex !== inst.snapshot) {
            inst.select.selectedIndex = inst.snapshot;
        }

        inst.select.classList.remove('kk-select-open');

        if (inst.sheet) {
            document.documentElement.classList.remove('kk-sel-locked');
            if (typeof inst.scrollY === 'number' && window.scrollY !== inst.scrollY) {
                window.scrollTo(0, inst.scrollY);
            }
        }

        if (inst.panel) inst.panel.remove();
        if (inst.backdrop) inst.backdrop.remove();
    }

    function commit(inst, index) {
        const select = inst.select;

        // Enter, Tab and Space arrive bare - only a click passes an index - so
        // they have to read the highlight rather than the control. The two
        // drift apart the moment the pointer moves over the list, because
        // hovering deliberately does not write selectedIndex.
        if (typeof index !== 'number' && inst.active) index = Number(inst.active.dataset.index);
        if (typeof index === 'number' && !Number.isNaN(index)) select.selectedIndex = index;

        const changed = select.selectedIndex !== inst.snapshot;
        close(false);

        // Nothing downstream hears a programmatic write. `input` then `change`
        // is the order a real selection fires them in, and `change` is the one
        // that matters: Alpine's x-model binds it for selects, and it is what
        // onchange="this.form.submit()" and every x-on:change handler reads.
        // It has to be dispatched on the select itself — the inventory modal
        // reaches for $event.target.selectedOptions[0].dataset.
        if (changed) {
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function toggle(select) {
        if (live && live.select === select) close(true);
        else openFor(select);
    }

    /* ------------------------------------------------------------------ *
     * Pointer
     * ------------------------------------------------------------------ */

    let downOn = null;
    let downAt = null;

    // The pointer that opened the panel, so its own release cannot be read as a
    // pick. Normally it cannot be - place() puts the menu beside the control,
    // never under it - but a sheet is pinned to the foot of the viewport and on
    // a narrow window opens directly beneath the cursor.
    let openedBy = null;

    /* A tap is followed by a synthesized mouse sequence - mousemove, mousedown,
     * mouseup, click - hit-tested afresh at the finger, after the DOM under it
     * has changed. Both halves of that are ours to answer for: the tap that
     * opens a sheet lands on the backdrop we just inserted, which closes it
     * again and blurs the select on the way, so the sheet dies before it is
     * ever seen; and the tap that picks a row lands on whatever the sheet was
     * covering - on the PDP, the buy buttons.
     *
     * Cancelling touchend is the one thing that reliably suppresses the whole
     * sequence in Blink and WebKit. The window below is the belt to that
     * braces, for an engine that delivers the compatibility events regardless.
     */
    let swallowTouchEnd = false;
    let swallowUntil = 0;

    function swallowTap() {
        swallowTouchEnd = true;
        swallowUntil = Date.now() + 500;
    }

    document.addEventListener('touchend', (event) => {
        if (!swallowTouchEnd) return;
        swallowTouchEnd = false;
        if (event.cancelable) event.preventDefault();
    }, { capture: true, passive: false });

    // Registered ahead of every other pointer listener in this file, so
    // stopImmediatePropagation keeps the rest of them from seeing these.
    ['mousedown', 'mouseup', 'click'].forEach((type) => {
        document.addEventListener(type, (event) => {
            if (!swallowUntil || Date.now() > swallowUntil) { swallowUntil = 0; return; }
            event.preventDefault();
            event.stopImmediatePropagation();
            if (event.type === 'click') swallowUntil = 0;
        }, true);
    });

    // Pressing on a plain <div> blurs whatever had focus, and the panel is a
    // plain div. Without this the press on an option fires focusout on the
    // select, which closes and reverts before pointerup can commit — the row
    // would look clickable and do nothing. Cancelling mousedown keeps the focus
    // where it is; it is left on pointerdown so a finger can still scroll the
    // list, because the synthesized mousedown only arrives once the gesture is
    // over.
    document.addEventListener('mousedown', (event) => {
        if (live && live.panel.contains(event.target)) event.preventDefault();
    }, true);

    document.addEventListener('pointerdown', (event) => {
        // A fresh gesture: whatever compatibility events the last tap was going
        // to produce have either arrived by now or been cancelled at touchend.
        // Leaving the window armed would cost the next real click.
        swallowUntil = 0;

        if (event.button !== undefined && event.button !== 0) return;

        // Inside the open panel: the row handles itself on pointerup, and a
        // drag on the scrollbar must not count as a click outside.
        if (live && live.panel.contains(event.target)) return;

        const select = selectAt(event.clientX, event.clientY);

        if (!select) {
            if (live) close(true);
            return;
        }

        if (select.disabled) return;

        downOn = select;
        downAt = { x: event.clientX, y: event.clientY };

        // A mouse expects the menu on the press. A finger does not: the same
        // press may be the start of a scroll, so it waits for the release.
        if (event.pointerType !== 'touch') {
            event.preventDefault();
            openedBy = (live && live.select === select) ? null : event.pointerId;
            toggle(select);
        }
    }, true);

    document.addEventListener('pointerup', (event) => {
        const select = downOn;
        const from = downAt;
        const opening = openedBy !== null && openedBy === event.pointerId;
        downOn = null;
        downAt = null;
        openedBy = null;

        // Only the primary button chooses. The mousedown guard above cancels a
        // right- or middle-press inside the panel, which leaves focus on the
        // select and the panel up - so without this the release would pick
        // whichever row the context menu happened to open over.
        if (event.button !== undefined && event.button !== 0) return;

        if (live && live.panel.contains(event.target)) {
            if (opening) return;
            const li = event.target.closest ? event.target.closest('.kk-sel__opt') : null;
            if (li && !li.classList.contains('is-disabled')) {
                // The sheet covers the lower two-thirds of a phone - on the PDP,
                // the buy buttons. Its tap must not reach them on the way out.
                if (event.pointerType === 'touch') swallowTap();
                commit(live, Number(li.dataset.index));
            }
            return;
        }

        if (!select || event.pointerType !== 'touch' || !from) return;

        // Treat it as a tap only if the finger stayed put; anything further was
        // a scroll that happened to start on the control.
        if (Math.abs(event.clientX - from.x) > 10 || Math.abs(event.clientY - from.y) > 10) return;
        if (selectAt(event.clientX, event.clientY) !== select) return;

        event.preventDefault();
        swallowTap();
        toggle(select);
    }, true);

    // A gesture the browser takes over (a scroll it decided to own, a system
    // swipe) never reaches pointerup, and a stale press must not be honoured by
    // the next one.
    document.addEventListener('pointercancel', () => {
        downOn = null;
        downAt = null;
        openedBy = null;
        swallowTouchEnd = false;
    }, true);

    // The backdrop is a real element rather than a document listener because
    // iOS does not deliver click to a bare non-interactive element.
    document.addEventListener('click', (event) => {
        if (live && live.backdrop && event.target === live.backdrop) close(true);
    }, true);

    document.addEventListener('pointerover', (event) => {
        if (!live || !live.panel.contains(event.target)) return;
        const li = event.target.closest ? event.target.closest('.kk-sel__opt') : null;
        // Hovering only moves the highlight. It must not write selectedIndex,
        // or dragging the pointer down a list would rewrite the control's
        // visible value on the way past.
        if (li && !li.classList.contains('is-disabled')) setActive(live, li, false);
    }, true);

    /* ------------------------------------------------------------------ *
     * Keyboard
     * ------------------------------------------------------------------ *
     *
     * While the panel is closed nothing is intercepted, so a focused select
     * keeps every key it has today. While it is open we take the lot, because
     * that is what a native popup does: arrows move the highlight and the
     * closed value follows them (so a screen reader still announces each stop),
     * but `change` waits for Enter.
     *
     * That last part matters most on the sort bar, where the select carries
     * onchange="this.form.submit()" — committing on every arrow key would
     * reload the page under the shopper mid-list.
     */

    // Sets and Maps, not object literals: `event.key` is attacker-free but
    // "constructor" and "toString" would both come back truthy off the
    // prototype of a plain object, and STEP would hand move() a function.
    const OPENERS = new Set(['ArrowDown', 'ArrowUp', ' ', 'Spacebar']);
    // Left and Right are in here because a select answers them too: as far as
    // the browser is concerned the control is closed, so an unclaimed
    // ArrowRight moves the value and fires a real `change` - which on the sort
    // bar submits the form out from under the shopper mid-list.
    const STEP = new Map([
        ['ArrowDown', 1], ['ArrowUp', -1],
        ['ArrowRight', 1], ['ArrowLeft', -1],
        ['PageDown', 10], ['PageUp', -10],
    ]);

    document.addEventListener('keydown', (event) => {
        const select = event.target;
        if (!(select instanceof HTMLSelectElement) || !select.dataset.kkSelect) return;

        // Closed, nothing is intercepted beyond the keys that would raise the
        // OS list — so a focused select keeps its own arrows and its own
        // type-ahead, exactly as it behaves today. Enter in particular stays
        // the browser's submit shortcut.
        if (!live || live.select !== select) {
            if (event.altKey && event.key === 'ArrowDown') { event.preventDefault(); openFor(select); return; }
            if (event.ctrlKey || event.metaKey || event.altKey) return;
            if (OPENERS.has(event.key)) {
                event.preventDefault();
                openFor(select);
            }
            return;
        }

        const inst = live;
        const key = event.key;

        // Open, the panel takes the key outright. Propagation is stopped too:
        // Enter and Escape both mean something to handlers further up (a form
        // submit, an overlay's @keydown.escape.window) and neither should hear
        // a keystroke that was aimed at the menu.
        const claim = () => { event.preventDefault(); event.stopPropagation(); };

        if (key === 'Escape' || key === 'Esc') { claim(); close(true); return; }
        if (key === 'Tab') { commit(inst); return; }
        if (key === 'Enter') { claim(); commit(inst); return; }
        if (key === ' ' || key === 'Spacebar') {
            claim();
            // A space partway through a type-ahead run belongs to the phrase
            // being typed ("price: low"), not to the menu.
            if (inst.typed && Date.now() - inst.typedAt < TYPEAHEAD_MS) typeahead(inst, ' ');
            else commit(inst);
            return;
        }
        if (event.altKey && key === 'ArrowUp') { claim(); commit(inst); return; }

        if (STEP.has(key)) { claim(); move(inst, STEP.get(key)); return; }
        if (key === 'Home') { claim(); jump(inst, 0); return; }
        if (key === 'End') { claim(); jump(inst, inst.items.length - 1); return; }

        if (key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
            claim();
            typeahead(inst, key);
        }
    }, true);

    function move(inst, by) {
        if (!inst.items.length) return;
        const at = inst.items.indexOf(inst.active);
        const next = Math.max(0, Math.min(inst.items.length - 1, (at < 0 ? 0 : at) + by));
        jump(inst, next);
    }

    function jump(inst, index) {
        const li = inst.items[index];
        if (!li) return;
        setActive(inst, li, true);
        // Follow the highlight on the control itself. A native popup does the
        // same, and it is what lets a screen reader read each stop out without
        // us inventing an aria-activedescendant the select has no slot for.
        inst.select.selectedIndex = Number(li.dataset.index);
        paint(inst);
    }

    function typeahead(inst, ch) {
        const now = Date.now();
        if (now - inst.typedAt > TYPEAHEAD_MS) inst.typed = '';
        inst.typedAt = now;
        inst.typed += ch.toLowerCase();

        const buffer = inst.typed;
        const labels = inst.items.map((li) => li.textContent.trim().toLowerCase());
        const at = inst.items.indexOf(inst.active);

        // A repeated single character cycles through everything starting with
        // it — "p", "p" walks Price: Low then Price: High. A longer buffer is a
        // real prefix and must not cycle.
        const repeat = buffer.length > 1 && /^(.)\1*$/.test(buffer);
        const needle = repeat ? buffer[0] : buffer;
        const from = (repeat || buffer.length === 1) ? at + 1 : 0;

        for (let i = 0; i < labels.length; i++) {
            const index = (from + i + labels.length) % labels.length;
            if (labels[index].startsWith(needle)) { jump(inst, index); return; }
        }
    }

    /* ------------------------------------------------------------------ *
     * Staying put
     * ------------------------------------------------------------------ */

    let placeFrame = 0;

    function reposition() {
        if (!live || placeFrame) return;
        placeFrame = window.requestAnimationFrame(() => {
            placeFrame = 0;
            if (!live) return;
            if (!live.select.isConnected) { close(true); return; }

            // Scrolled out of sight: a panel left hanging over a control that
            // is no longer on screen is worse than one that closed.
            const r = live.select.getBoundingClientRect();
            const vh = document.documentElement.clientHeight;
            if (r.bottom < 0 || r.top > vh) { close(true); return; }

            place(live);
        });
    }

    // Which mode a panel is in is decided once, at open, and the two halves of
    // it are split between this file and the stylesheet: place() writes inline
    // coordinates for a menu and nothing at all for a sheet, while the CSS
    // keeps deciding from the media query. Cross the breakpoint with one open -
    // rotate a phone, drag a desktop window narrow - and the panel is stranded
    // in the corner with a backdrop over the page and the scroll still locked.
    // Rather than rebuild it mid-gesture, close it: a native popup does not
    // survive a rotation either.
    const onModeFlip = () => close(true);
    if (SHEET_MQ.addEventListener) SHEET_MQ.addEventListener('change', onModeFlip);
    else if (SHEET_MQ.addListener) SHEET_MQ.addListener(onModeFlip);

    window.addEventListener('scroll', reposition, true);
    window.addEventListener('resize', reposition, { passive: true });
    window.addEventListener('blur', () => close(true));

    // Anything that moves focus off the control closes it — including the
    // validation layer in app.js, which focuses the first invalid field.
    document.addEventListener('focusout', (event) => {
        if (live && event.target === live.select) close(true);
    }, true);

    /* ------------------------------------------------------------------ *
     * Keeping up with the page
     * ------------------------------------------------------------------ */

    // Alpine writes to a select from outside this file — x-model bound to a
    // watcher, a $nextTick restore — and so do autofill and a back-navigation
    // restore. The closed control redraws itself for free; this only keeps an
    // open panel's tick in step.
    document.addEventListener('change', (event) => {
        if (live && event.target === live.select) paint(live);
    }, true);

    function start() {
        scan(document.body);

        // Selects arrive after first paint all over the admin: the flash-sale
        // rows are an x-for whose <select> is inside a <template>, so nothing
        // exists to adopt on load.
        new MutationObserver((records) => {
            for (const record of records) {
                for (const node of record.addedNodes) scan(node);
            }
        }).observe(document.documentElement, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
