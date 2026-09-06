@props(['deger', 'birim' => null])

@php
    $metin = rtrim(rtrim(number_format((float) $deger, 3, ',', '.'), '0'), ',');
@endphp

<span {{ $attributes->merge(['class' => 'tabular-nums']) }}>{{ $metin }}@if ($birim) {{ $birim }}@endif</span>
