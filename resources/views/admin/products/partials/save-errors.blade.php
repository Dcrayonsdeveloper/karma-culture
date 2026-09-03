{{-- Why the save was refused, at the top of the form.

     Save sits at the bottom of a very long form, and a refused save bounces the
     admin back to the *top* of it. Every message was rendered against its own
     field - a missing size and a colour without a swatch report against the
     Sizes and Colours cards, half a page down - so the page came back looking
     untouched and the button looked like it had done nothing at all.

     Identical messages are collapsed: three colour rows missing a swatch is one
     thing to fix, not three lines of the same sentence. --}}
@if($errors->any())
    <div class="card p-4 mb-4" role="alert" aria-live="assertive"
         style="border-left: 3px solid #d72c0d; background: #fff4f4;">
        <p class="text-[13px] font-semibold" style="color: #8e1f0b;">{{ $title ?? 'This product was not saved' }}</p>
        <ul style="margin: 6px 0 0; padding-left: 18px; color: #8e1f0b; font-size: 12px; line-height: 1.7;">
            @foreach(collect($errors->all())->unique() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
    <script>
        // Every other failure in this admin arrives as a toast, so a save that
        // only wrote a panel into the page still read as "nothing happened".
        // The message is a fixed sentence: toastr renders its argument as HTML,
        // and the reasons themselves belong in the panel above where they can
        // be escaped. Guarded, because the toastr CDN is blocked on some
        // networks and an unguarded call would take the rest of the page's
        // scripts down with it.
        document.addEventListener('DOMContentLoaded', function () {
            if (window.toastr) {
                toastr.error('Not saved - the reasons are listed at the top of the form.');
            }
        });
    </script>
@endif
