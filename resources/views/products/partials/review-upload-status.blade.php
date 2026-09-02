{{--
    Selected-file list and rejection notices for one review upload input.
    Rendered inside the kkReviewForm Alpine scope; $kind is 'images' or 'videos'.
--}}
@php $kind = $kind ?? 'images'; @endphp

<ul class="kk-revform__files" x-show="picked.{{ $kind }}.length" x-cloak>
    <template x-for="(file, i) in picked.{{ $kind }}" :key="i">
        <li><b x-text="file.name"></b><span x-text="human(file.size)"></span></li>
    </template>
</ul>

<button type="button" class="kk-revform__clear" x-show="picked.{{ $kind }}.length" x-cloak
        @click="clear('{{ $kind }}')">Remove all</button>

<ul class="kk-revform__reject" x-show="notices.{{ $kind }}.length" x-cloak>
    <template x-for="(note, i) in notices.{{ $kind }}" :key="i">
        <li x-text="note"></li>
    </template>
</ul>
