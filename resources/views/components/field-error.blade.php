{{--
    The ONE inline field-error renderer for the whole site.

    Before this existed there were 365 hand-rolled `@error(...)` paragraphs across
    60 blade files, each with its own markup, and none of them known to the
    site-wide validator in resources/js/app.js. That validator inserts its own
    <p class="kk-field-error"> and removes it again through clearError(), which
    tracks the note on the field as `field._kkErrorNote` — so it could never
    reach a paragraph Blade had printed. The two renderers therefore stacked:

        [server, from the last POST]  The provided credentials do not match our records.
        [client, from this attempt]   Email Address is required.

    both under one box, for one action. That is the duplicate-error bug.

    This component closes it by making the server's message the SAME kind of
    element the client validator owns:

      * `.kk-field-error`      — one class, one stylesheet rule, so a server
                                 message and a client message are visually
                                 identical instead of being two different reds.
      * `data-kk-field-error`  — carries the field key, which is how app.js finds
                                 this note and retires it: on a new submit (the
                                 previous response no longer describes what is
                                 being sent) and on the first edit of the field
                                 (the verdict was about the old value).

    ONE message, never a list: `first()` is deliberate. A field that breaks two
    rules at once has one thing wrong with it as far as the person filling it in
    is concerned, and Laravel already orders the bag by rule declaration, which
    puts `required` ahead of `email` ahead of `max` — the priority the brief asks
    for, without a second ranking pass here.

    Usage — directly after the input (or after its decoration wrapper, so the
    "+91" prefix and password eyes stay centred on the box):

        <x-field-error field="email" />
        <x-field-error field="variants.0.sku" />
        <x-field-error field="email" bag="checkout" />
--}}

@props([
    'field',
    'bag' => 'default',
])

@php
    $kkBag = $errors->getBag($bag);

    // Laravel keys array fields with dots ("variants.0.sku") while the input
    // that produced them is named "variants[0][sku]". app.js normalises the
    // input's name the same way before matching, so the two always meet.
    $kkKey = str_replace(['[', ']'], ['.', ''], (string) $field);
    $kkMessage = $kkBag->first($kkKey);
@endphp

@if ($kkMessage)
    <p {{ $attributes->merge(['class' => 'kk-field-error']) }}
       data-kk-field-error="{{ $kkKey }}"
       id="kk-srv-err-{{ \Illuminate\Support\Str::slug($kkKey, '-') }}"
       {{-- Announced on arrival: this message is already on screen when the page
            loads after a failed POST, so there is no change for aria-live to
            catch. app.js links it to its input with aria-describedby once it
            boots; role=alert is what carries it when JS never does. --}}
       role="alert">{{ $kkMessage }}</p>
@endif
