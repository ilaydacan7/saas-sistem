@props(['kurus', 'birim' => null])

@php
    $tutar = ((int) $kurus) / 100;
    $simge = match ($birim ?? config('billing.currency')) {
        'TRY' => '₺',
        'USD' => '$',
        'EUR' => '€',
        default => '',
    };
@endphp

<span {{ $attributes->merge(['class' => 'whitespace-nowrap tabular-nums']) }}>{{ $simge }}{{ number_format($tutar, 2, ',', '.') }}</span>
