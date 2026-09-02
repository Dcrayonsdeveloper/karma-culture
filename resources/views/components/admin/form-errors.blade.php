{{--
    Server-side validation feedback for admin forms.

    The admin layout only raises a toast for session('success'|'error'|...), and a
    Laravel validation failure populates the $errors bag instead - so across the
    Homepage Manager a rejected save bounced back to a silent page with the form
    reset to whatever was in the database. The admin saw their edit vanish with no
    explanation and no clue which field was at fault.

    Drop this immediately above a form. Pair it with old() on the inputs so the
    typing survives the round trip as well.
--}}
@props(['title' => 'This form was not saved'])

@if($errors->any())
    <div role="alert" style="margin-bottom: 1rem; padding: 0.75rem 1rem; background: #fff0f0; border: 1px solid #f0c2bd; border-radius: 0.5rem;">
        <div style="font-size: 13px; font-weight: 600; color: #8e1f0b; margin-bottom: 0.25rem;">{{ $title }}</div>
        <ul style="margin: 0; padding-left: 1.1rem; font-size: 13px; color: #8e1f0b;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
