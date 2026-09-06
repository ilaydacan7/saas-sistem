@props(['product'])

@php
    $miktar = $product->totalStock();
    $tukendi = $miktar <= 0;
    $kritik = ! $tukendi && $miktar <= (float) $product->min_stock;

    $sinif = match (true) {
        $tukendi => 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-950 dark:text-red-300 dark:ring-red-400/30',
        $kritik => 'bg-amber-50 text-amber-800 ring-amber-600/20 dark:bg-amber-950 dark:text-amber-300 dark:ring-amber-400/30',
        default => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950 dark:text-emerald-300 dark:ring-emerald-400/30',
    };
@endphp

<span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $sinif }}">
    <x-miktar :deger="$miktar" :birim="$product->unitLabel()" />
    @if ($tukendi)
        · tükendi
    @elseif ($kritik)
        · kritik
    @endif
</span>
