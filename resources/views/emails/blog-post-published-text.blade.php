{{--
    The plain-text half of the new-post announcement.

    Deliberately the same words as the HTML version rather than a stub. A
    filter that compares the two parts treats a mismatch as a thing worth
    penalising, and the reader who is shown this one is not owed less than the
    reader who is shown the other.
--}}
{{ $post->title }}

Hi {{ $greetingName }},

There is something new on the Karmaa Kulture journal.

{{ $intro }}

Read the post:
{{ $url }}
@if($post->reading_time)

About {{ $post->reading_time }} minute{{ $post->reading_time === 1 ? '' : 's' }} to read.
@endif

Thanks,
{{ config('app.name') }}

--
You are getting this because you subscribed to the Karmaa Kulture newsletter.
To stop receiving these, open this link - no reply needed:
{{ $unsubscribeUrl }}
