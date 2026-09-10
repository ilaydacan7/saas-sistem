@props(['seri', 'yukseklik' => 180])

@php
    // Grafik sunucuda SVG olarak üretilir: dış kütüphane, CDN veya JS yok.
    $degerler = array_map(fn ($nokta) => (int) $nokta['kurus'], $seri);
    $enBuyuk = $degerler === [] ? 0 : max($degerler);
    $adet = count($seri);

    $sutunGenisligi = $adet > 0 ? 100 / $adet : 0;
    $bosluk = $adet > 24 ? 0.15 : 0.3;
@endphp

@if ($adet === 0 || $enBuyuk === 0)
    <div class="flex h-44 items-center justify-center text-sm text-slate-500 dark:text-slate-400">
        Bu dönemde satış kaydı yok.
    </div>
@else
    <div>
        <div class="mb-1 flex justify-between text-xs text-slate-400">
            <span>En yüksek: <x-para :kurus="$enBuyuk" /></span>
        </div>

        <svg viewBox="0 0 100 {{ $yukseklik }}" preserveAspectRatio="none"
             class="h-44 w-full" role="img"
             aria-label="Dönem satış grafiği">
            @foreach ([0.25, 0.5, 0.75] as $oran)
                <line x1="0" y1="{{ $yukseklik * $oran }}" x2="100" y2="{{ $yukseklik * $oran }}"
                      stroke="currentColor" stroke-width="0.3" class="text-slate-200 dark:text-slate-800" />
            @endforeach

            @foreach ($seri as $sira => $nokta)
                @php
                    $oran = $enBuyuk > 0 ? $nokta['kurus'] / $enBuyuk : 0;
                    $yukseklikPx = max($oran * ($yukseklik - 8), $nokta['kurus'] > 0 ? 2 : 0);
                    $x = $sira * $sutunGenisligi + ($sutunGenisligi * $bosluk / 2);
                    $genislik = $sutunGenisligi * (1 - $bosluk);
                @endphp

                @if ($yukseklikPx > 0)
                    <rect x="{{ round($x, 3) }}" y="{{ round($yukseklik - $yukseklikPx, 3) }}"
                          width="{{ round($genislik, 3) }}" height="{{ round($yukseklikPx, 3) }}"
                          rx="0.6" class="fill-indigo-500 dark:fill-indigo-400">
                        <title>{{ $nokta['etiket'] }}: {{ number_format($nokta['kurus'] / 100, 2, ',', '.') }} {{ config('billing.currency') }}</title>
                    </rect>
                @endif
            @endforeach
        </svg>

        <div class="mt-1.5 flex justify-between text-xs text-slate-400">
            <span>{{ $seri[0]['etiket'] }}</span>
            @if ($adet > 2)
                <span class="hidden sm:inline">{{ $seri[intdiv($adet, 2)]['etiket'] }}</span>
            @endif
            <span>{{ $seri[$adet - 1]['etiket'] }}</span>
        </div>
    </div>
@endif
