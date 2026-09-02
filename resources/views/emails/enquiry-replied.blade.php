@component('mail::message')
# Re: {{ $headingSubject }}

Hi {{ $senderName }},

Thank you for getting in touch with {{ config('app.name') }}. Here is our reply to your enquiry:

@foreach($replyLines as $line)
{{ $line }}

@endforeach

@component('mail::panel')
**Your original message -- {{ $enquiry->created_at->format('M d, Y \a\t h:i A') }}**

@foreach($originalLines as $line)
{{ $line }}

@endforeach
@endcomponent

If you need anything else, just reply to this email and we will get back to you.

Warm regards,
**{{ config('app.name') }}**
@endcomponent
