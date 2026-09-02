{{-- Most settings tabs rendered no validation feedback at all: a rejected save
     redirected back looking exactly like a successful one, so a bad API key or
     a missing required field just silently did nothing. --}}
@if($errors->any())
    <div role="alert"
         style="display: flex; align-items: flex-start; gap: 0.75rem; padding: 0.875rem 1rem; background: #fff0f0; border: 1px solid #f0c2bb; border-radius: 0.75rem; margin-bottom: 1rem;">
        <svg style="width: 18px; height: 18px; color: #d72c0d; flex-shrink: 0; margin-top: 1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        <div>
            <p style="font-size: 13px; font-weight: 600; color: #b71c00; margin: 0;">
                {{ $errors->count() === 1 ? 'Your changes were not saved' : 'Your changes were not saved (' . $errors->count() . ' problems)' }}
            </p>
            <ul style="margin: 0.375rem 0 0 0; padding-left: 1.125rem; font-size: 12px; color: #b71c00;">
                @foreach($errors->all() as $error)
                    <li style="margin-top: 0.125rem;">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
