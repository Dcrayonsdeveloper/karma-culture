{{--
    Settings-tab alias for <x-form-errors>.

    Included with @include('admin.settings.partials.errors') from most settings
    tabs. It used to print the whole bag as its own list, which duplicated every
    message a field below was already showing; it now forwards to the single
    banner, which prints only the errors no field is speaking for and which
    app.js retires when the next save starts.

    A tab that renders <x-field-error> under its inputs should say which keys
    those are, so the banner does not repeat them:

        @include('admin.settings.partials.errors', ['handled' => ['mail_host', 'mail_port']])
--}}

@php
    $kkTitle = $errors->count() === 1
        ? 'Your changes were not saved'
        : 'Your changes were not saved ('.$errors->count().' problems)';
@endphp

<x-form-errors :handled="$handled ?? []" :title="$kkTitle" />
