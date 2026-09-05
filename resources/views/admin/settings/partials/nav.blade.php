@php
    $tabs = [
        'general' => [
            'label' => 'General',
            'route' => 'admin.settings.general',
        ],
        'shipping' => [
            'label' => 'Shipping',
            'route' => 'admin.settings.shipping',
        ],
        'tax' => [
            'label' => 'Tax',
            'route' => 'admin.settings.tax',
        ],
        'seo' => [
            'label' => 'SEO',
            'route' => 'admin.settings.seo',
        ],
        'product-card' => [
            'label' => 'Features',
            'route' => 'admin.settings.product-card',
        ],
        'popups' => [
            'label' => 'Popups',
            'route' => 'admin.settings.popups',
        ],
    ];
@endphp

@once
@push('styles')
<style>
/* Settings tab strip.
   The old markup hung the active underline off a -1px bottom margin so it would
   sit on top of the card's border. Two things went wrong with that: the strip
   sets overflow-x, which makes overflow-y compute to auto, so the pixel poking
   past the content box drew a permanent vertical scrollbar; and the card is
   rounded, so the underline it was reaching for is not a straight edge. Both
   are gone here - the rule and the underline are inset shadows painted inside
   the scrollport, so nothing overflows and nothing needs clipping. */
.layout-admin .card.settings-tabs {
    overflow: hidden;              /* beats the mobile `.card { overflow-x: auto }`
                                      override in the layout, which would turn the
                                      card itself into a second scroll container */
    margin-bottom: 1.5rem;
}
.settings-tabs__scroll {
    /* Also makes the strip the offsetParent of its tabs. Without it nothing in
       the ancestor chain is positioned, so the scroll script below measures
       offsetLeft from <body> and picks up the sidebar width. */
    position: relative;
    display: flex;
    overflow-x: auto;
    overflow-y: hidden;            /* pin it. `auto` is the computed default once
                                      overflow-x is set, and that is what drew the
                                      stray scrollbar. */
    scrollbar-width: none;         /* the strip scrolls by wheel/touch; a visible
                                      bar under the tabs just looks broken */
    -ms-overflow-style: none;
    box-shadow: inset 0 -1px 0 #e3e3e3;
}
.settings-tabs__scroll::-webkit-scrollbar { display: none; }

/* app.css styles every admin link with
   `a:not(.btn):not(.admin-nav-item):not(.badge)`, which is specific enough to
   repaint these tabs link-blue. The :not() chain here matches that weight so
   the tab styling wins without reaching for !important. */
.layout-admin .settings-tabs__tab:not(.btn):not(.admin-nav-item):not(.badge) {
    display: inline-flex;
    align-items: center;
    flex: 0 0 auto;
    padding: 0.625rem 0.75rem;
    font-size: 13px;
    font-weight: 500;
    color: #616161;
    text-decoration: none;
    white-space: nowrap;
    transition: color 0.15s, box-shadow 0.15s;
}
.layout-admin .settings-tabs__tab:not(.btn):not(.admin-nav-item):not(.badge):hover {
    color: #303030;
    text-decoration: none;
    box-shadow: inset 0 -2px 0 #c9c9c9;
}
.layout-admin .settings-tabs__tab.is-active:not(.btn):not(.admin-nav-item):not(.badge) {
    color: #303030;
    font-weight: 600;
    box-shadow: inset 0 -2px 0 #303030;
}
.layout-admin .settings-tabs__tab:focus-visible {
    outline: 2px solid #005bd3;
    outline-offset: -2px;
    border-radius: 4px;
}
/* Touch screens: at 13px the tabs stand 39px tall, a hair under the 40px
   target. A minimum height on coarse pointers only leaves the desktop strip
   exactly as it is. */
@media (pointer: coarse) {
    .layout-admin .settings-tabs__tab:not(.btn):not(.admin-nav-item):not(.badge) {
        min-height: 2.75rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
    /* Six tabs do not fit a narrow phone. Bring the current one into view so the
       strip does not open showing "General" when you are on Popups. */
    document.querySelectorAll('.settings-tabs__scroll').forEach(function (strip) {
        var active = strip.querySelector('.is-active');
        if (!active) return;
        strip.scrollLeft = active.offsetLeft - (strip.clientWidth - active.offsetWidth) / 2;
    });
</script>
@endpush
@endonce

<nav class="card settings-tabs" aria-label="Settings sections">
    <div class="settings-tabs__scroll">
        @foreach($tabs as $key => $tab)
            @php($isActive = ($active ?? '') === $key)
            <a href="{{ route($tab['route']) }}"
               class="settings-tabs__tab{{ $isActive ? ' is-active' : '' }}"
               @if($isActive) aria-current="page" @endif>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </div>
</nav>
