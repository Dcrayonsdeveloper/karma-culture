{{--
    Admin alias for <x-form-errors>.

    This used to be its own renderer, printing `@foreach($errors->all())` in a
    box above the form while the `@error(...)` blocks a few lines below printed
    the same sentences again under the fields they belonged to - so a rejected
    admin save reported each problem twice. It now forwards to the single
    form-level banner, which prints only what no field is already showing, and
    which app.js retires the moment a new submission starts.

    Kept as a name of its own because thirty-odd admin views say
    <x-admin.form-errors> and the wording of the heading is admin-specific.
    New forms should use <x-form-errors> directly.

    Pass `handled` with the field keys rendered inline below it:

        <x-admin.form-errors :handled="['name', 'slug', 'price']" />
--}}

@props([
    'title' => 'This form was not saved',
    'handled' => [],
    'bag' => 'default',
    'for' => null,
])

<x-form-errors :title="$title" :handled="$handled" :bag="$bag" :for="$for" {{ $attributes }} />
