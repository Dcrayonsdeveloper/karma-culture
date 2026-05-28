@props(['disabled' => false, 'error' => null])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'form-input' . ($error ? ' border-error-300 focus:border-error-500 focus:ring-error-500' : '')]) !!}>
@if($error)
    <p class="mt-1 text-sm text-error-600">{{ $error }}</p>
@endif
