@component('mail::message')
# Verify your email address

Thanks for signing up with {{ config('app.name') }}.

Click the button below to confirm that **{{ $email }}** is your address. Once it
is verified you can go back to the signup form and finish creating your account.

@component('mail::button', ['url' => $verificationUrl])
Verify Email
@endcomponent

This link expires in {{ $expiresInMinutes }} minutes and can only be used for
this address.

{{-- Plain text, not a second button: some mail clients strip the anchor out of
     a styled button, and a customer whose client does that is left with a
     message that asks them to click something that is not there. --}}
If the button does not work, copy this address into your browser:

@component('mail::subcopy')
{{ $verificationUrl }}
@endcomponent

If you did not request this, you can safely ignore this email - no account has
been created and nothing will happen.

Warm regards,
**{{ config('app.name') }}**
@endcomponent
