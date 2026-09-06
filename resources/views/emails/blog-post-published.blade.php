{{--
    New blog post announcement.

    The intro is the post's own excerpt where there is one, and a trimmed,
    tag-stripped opening of the body where there is not - never the whole
    article. The point of the email is the click: a post pasted in full has
    nothing left to go and read, and the images and markup of the editor's
    HTML do not survive an email client intact anyway.
--}}
@php
    $intro = trim((string) $post->excerpt) !== ''
        ? $post->excerpt
        : \Illuminate\Support\Str::limit(trim(html_entity_decode(strip_tags($post->content))), 220);
@endphp

@component('mail::message')
# {{ $post->title }}

Hi {{ $greetingName }},

There is something new on the Karmaa Kulture journal.

{{ $intro }}

@component('mail::button', ['url' => $url])
Read the post
@endcomponent

@if($post->reading_time)
About {{ $post->reading_time }} minute{{ $post->reading_time === 1 ? '' : 's' }} to read.
@endif

Thanks,<br>
{{ config('app.name') }}

@slot('subcopy')
You are getting this because you subscribed to the Karmaa Kulture newsletter.
[Unsubscribe]({{ $unsubscribeUrl }}) and we will stop sending these - no reply needed.

If the button above does not work, copy this address into your browser: {{ $url }}
@endslot
@endcomponent
