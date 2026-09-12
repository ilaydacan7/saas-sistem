@props(['seri'])

@php
    // Grafik sunucuda HTML olarak üretilir: dış kütüphane, CDN veya JS yok.
    $degerler = array_column($seri, 'kurus');
    $enBuyuk = $degerler === [] ? 0 : max($degerler);
    $toplam = array_sum($degerler);
    $adet = count($seri);

    // Eksenin üst sınırı yuvarlak bir tutara çıkarılır; böylece çizgiler gerçek tutarları gösterir.
    // Adım 1-2-5 dizisinden seçilir ve en az 1 liradır, eksen yazısında kuruş çıkmaz.
    $ustSinir = 0;

    if ($enBuyuk > 0) {
        $hamAdim = max($enBuyuk / 4, 100);
        $basamak = 10 ** (int) floor(log10($hamAdim));
        $adim = $basamak * 10;

        foreach ([1, 2, 5] as $katsayi) {
            if ($katsayi * $basamak >= $hamAdim) {
                $adim = $katsayi * $basamak;
                break;
            }
        }

        $ustSinir = $adim * 4;
    }
@endphp

@if ($adet === 0 || $enBuyuk === 0)
    <div class="flex h-44 items-center justify-center text-sm text-slate-500 dark:text-slate-400">
        Bu dönemde satış kaydı yok.
    </div>
@else
    <div>
        <p class="mb-3 text-sm text-slate-600 dark:text-slate-400">
            Dönem toplamı: <x-para :kurus="$toplam" class="font-semibold text-slate-900 dark:text-slate-100" />
        </p>

        <div class="flex gap-2">
            {{-- Eksen yazıları --}}
            <div class="relative h-44 w-12 shrink-0 text-right text-[11px] text-slate-400" aria-hidden="true">
                @foreach ([0, 1, 2, 3, 4] as $sira)
                    <span class="absolute right-0 translate-y-1/2 tabular-nums" style="bottom: {{ $sira * 25 }}%">
                        {{ number_format($ustSinir * $sira / 4 / 100, 0, ',', '.') }}
                    </span>
                @endforeach
            </div>

            <div class="min-w-0 flex-1">
                <div class="relative h-44">
                    @foreach ([0, 1, 2, 3, 4] as $sira)
                        <div class="absolute inset-x-0 border-t border-slate-200 dark:border-slate-800"
                             style="bottom: {{ $sira * 25 }}%" aria-hidden="true"></div>
                    @endforeach

                    <ul class="absolute inset-0 flex items-end gap-1 sm:gap-1.5" aria-label="Dönem satış grafiği">
                        @foreach ($seri as $sira => $nokta)
                            @php
                                $yuzde = round($nokta['kurus'] / $ustSinir * 100, 2);

                                $renk = match ($nokta['durum']) {
                                    'devam' => 'bg-indigo-200 dark:bg-indigo-800',
                                    default => 'bg-indigo-500 dark:bg-indigo-400',
                                };

                                // Kenardaki sütunların etiketi grafiğin dışına taşmasın.
                                $hiza = match (true) {
                                    $sira < 2 => 'left-0',
                                    $sira >= $adet - 2 => 'right-0',
                                    default => 'left-1/2 -translate-x-1/2',
                                };
                            @endphp

                            <li tabindex="0"
                                class="group relative flex h-full flex-1 items-end rounded-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                                @if ($nokta['kurus'] > 0)
                                    <div class="w-full rounded-t-sm {{ $renk }}"
                                         style="height: max(2px, {{ $yuzde }}%)"></div>
                                @endif

                                {{-- Fareyle üzerine gelince ya da dokununca görünür; ekran okuyucu her zaman okur. --}}
                                <span class="pointer-events-none absolute z-10 mb-1 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-xs text-white opacity-0 shadow transition group-hover:opacity-100 group-focus:opacity-100 dark:bg-slate-100 dark:text-slate-900 {{ $hiza }}"
                                      style="bottom: {{ $yuzde }}%">
                                    {{ $nokta['baslik'] }}:
                                    @if ($nokta['durum'] === 'gelecek')
                                        henüz gelmedi
                                    @else
                                        <x-para :kurus="$nokta['kurus']" />
                                        @if ($nokta['durum'] === 'devam') (devam ediyor) @endif
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="mt-1.5 flex gap-1 text-[11px] sm:gap-1.5" aria-hidden="true">
                    @foreach ($seri as $sira => $nokta)
                        @php
                            // Dar ekranda yalnızca ilk, orta ve son etiket gösterilir.
                            // Diğerleri yerini korur ki etiketler sütunların altında kalsın.
                            $gorunur = in_array($sira, [0, intdiv($adet, 2), $adet - 1], true) ? '' : 'invisible sm:visible';

                            // Dar sütuna sığmayan ilk ve son etiket grafiğin içine doğru taşar.
                            $hiza = match ($sira) {
                                0 => 'justify-start',
                                $adet - 1 => 'justify-end',
                                default => 'justify-center',
                            };
                        @endphp

                        <span class="{{ $gorunur }} {{ $hiza }} flex min-w-0 flex-1 whitespace-nowrap {{ $nokta['durum'] === 'gelecek' ? 'text-slate-300 dark:text-slate-600' : 'text-slate-400' }}">
                            {{ $nokta['etiket'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>

        <p class="mt-3 flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
            <span class="inline-block size-2.5 rounded-sm bg-indigo-200 dark:bg-indigo-800" aria-hidden="true"></span>
            Açık renkli sütun henüz bitmemiş dönemi gösterir.
        </p>
    </div>
@endif
