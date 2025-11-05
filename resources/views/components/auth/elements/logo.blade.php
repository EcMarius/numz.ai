@php
    $height = $height ?? '40';
@endphp

<a href="/" style="height:{{ $height }}px; width:auto; display:block" aria-label="{{ config('app.name') }} Logo">
    <x-logo class="w-auto" style="height:100%" />
</a>
