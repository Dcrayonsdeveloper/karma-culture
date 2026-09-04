{{--
    The ONE form-level error banner for the whole site.

    A form-level banner and an inline field message are two different jobs, and
    the trouble with the banners this replaces is that they did the same one
    twice: `@foreach($errors->all())` printed every message in the box, and the
    `@error(...)` blocks a few lines below printed the same sentences again under
    the fields they belonged to. A rejected admin save showed each complaint
    twice, and a rejected signup showed it twice as well.

    So this banner never repeats a message that a field is already showing.
    `handled` names the keys that ARE rendered inline on this form — the same
    list as the <x-field-error> tags below it — and everything in that list is
    left to them. What remains are the ORPHANS: errors with no field to sit
    under, which is exactly what a form-level banner is for. A validation key
    with no box on the page (a nested `variants.0.sku` on a form that only
    renders the parent, a whole-form rule, a business check the controller
    raised) is the one message that would otherwise vanish silently.

    Pass `handled="*"` when every key the form can produce is rendered inline;
    the banner then reduces to its headline, which is the "one form-level error"
    the brief asks for.

        <x-form-errors :handled="['name', 'slug', 'price']" />
        <x-form-errors handled="*" title="We couldn't create your account." />

    `data-kk-form-error` is how app.js retires it: a banner describes the LAST
    response, so it is removed the moment a new submission starts, and can never
    sit next to a fresh client-side message about a field the visitor has since
    changed.

    Wildcards work in `handled`, for the array rows a form renders in a loop:

        <x-form-errors :handled="['variants.*.sku', 'variants.*.price']" />
--}}

@props([
    'handled' => [],
    'title' => 'Please correct the highlighted fields and try again.',
    'bag' => 'default',
    // The id of the form this banner speaks for. Only needed when the banner is
    // neither inside its form nor immediately above it - app.js works those two
    // out on its own.
    'for' => null,
])

@php
    $kkBag = $errors->getBag($bag);
    $kkHandled = $handled === '*' ? ['*'] : (array) $handled;

    // Keys the banner must still speak for. Str::is gives the wildcard, and it
    // is matched against the DOTTED key Laravel uses in the bag.
    $kkOrphans = array_values(array_filter(
        $kkBag->keys(),
        fn (string $key) => ! \Illuminate\Support\Str::is($kkHandled, $key),
    ));
@endphp

@if ($kkBag->any())
    <div {{ $attributes->merge(['class' => 'kk-form-error']) }}
         data-kk-form-error="{{ $bag }}"
         @if ($for) data-kk-form-error-for="{{ $for }}" @endif
         role="alert">
        <p class="kk-form-error__title">{{ $title }}</p>

        @if ($kkOrphans)
            <ul class="kk-form-error__list">
                @foreach ($kkOrphans as $kkKey)
                    {{-- first(), not get(): one line per field here too, for the
                         same reason <x-field-error> shows one message. --}}
                    <li>{{ $kkBag->first($kkKey) }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
