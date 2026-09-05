@component('mail::message')
# Reset Your Password

Hi {{ $user->first_name }},

We received a request to reset the password for your {{ config('app.name') }} account. Choose a new password using the button below.

@component('mail::button', ['url' => $url])
Reset My Password
@endcomponent

This link will expire in **{{ $expiresInMinutes }} minutes** and can only be used once.

---

## If You Did Not Request This

You can safely ignore this email. Your password will not change unless you open the link above and choose a new one, and nobody else can see this message.

If you keep receiving these and you did not ask for them, please reply to this email and let us know.

---

If the button does not work, copy and paste this address into your browser:

{{ $url }}

Warm regards,
**{{ config('app.name') }}**
@endcomponent
