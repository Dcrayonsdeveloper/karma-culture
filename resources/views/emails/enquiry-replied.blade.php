@component('mail::message')
# Re: {{ $enquiry->subject }}

Hi {{ $enquiry->name }},

Thank you for getting in touch with {{ config('app.name') }}. Here is our reply to your enquiry:

@foreach(preg_split('/\R/', trim($replyMessage)) as $line)
{{ $line }}

@endforeach

@component('mail::panel')
**Your original message -- {{ $enquiry->created_at->format('M d, Y \a\t h:i A') }}**

@foreach(preg_split('/\R/', trim($enquiry->message)) as $line)
{{ $line }}

@endforeach
@endcomponent

If you need anything else, just reply to this email and we will get back to you.

Warm regards,
**{{ config('app.name') }}**
@endcomponent
