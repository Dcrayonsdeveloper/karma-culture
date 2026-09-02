@props([
    'label' => 'password',
])

{{--
    Sits inside a `position: relative` wrapper, next to an input that leaves
    `padding-right: 2.75rem` clear for it. The label prop only feeds the
    accessible name: a page with three of these otherwise announces three
    identical "Show password" buttons.
--}}
<button type="button" onclick="toggleAdminPassword(this)"
        aria-label="Show {{ $label }}" data-label="{{ $label }}"
        style="position: absolute; inset: 0 0 0 auto; padding: 0 0.875rem; display: flex; align-items: center; color: #616161; background: none; border: none; cursor: pointer;">
    <svg data-eye="off" style="width: 1.125rem; height: 1.125rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
    <svg data-eye="on" style="width: 1.125rem; height: 1.125rem; display: none;" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
</button>

@once
@push('scripts')
<script>
    function toggleAdminPassword(button) {
        const input = button.parentElement.querySelector('input');
        if (!input) return;

        const reveal = input.type === 'password';
        input.type = reveal ? 'text' : 'password';

        button.querySelector('[data-eye="off"]').style.display = reveal ? 'none' : 'block';
        button.querySelector('[data-eye="on"]').style.display = reveal ? 'block' : 'none';
        button.setAttribute('aria-label', (reveal ? 'Hide ' : 'Show ') + button.dataset.label);

        // Put the caret back where it was; flipping `type` drops focus in Safari
        // and sends the cursor to position 0 in Chrome.
        const caret = input.value.length;
        input.focus();
        input.setSelectionRange(caret, caret);
    }
</script>
@endpush
@endonce
