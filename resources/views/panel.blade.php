@extends('layouts.uygulama')

@section('baslik', $tenant->name.' — Panel')

@section('icerik')
<div class="mb-5">
    <h1 class="text-xl font-semibold tracking-tight">Panel</h1>
    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
        {{ auth()->user()->name }} olarak giriş yapıldı
    </p>
</div>

@if ($tenant->status === \App\Tenancy\TenantStatus::Trialing && $kalanGun !== null)
    <x-notice tone="uyari">
        Deneme sürenizin bitmesine <strong class="font-semibold">{{ $kalanGun }} gün</strong> kaldı
        ({{ $tenant->trial_ends_at->format('d.m.Y') }}).
    </x-notice>
@endif

@if ($kartlar !== [])
    <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kartlar as $kart)
            @php
                $renk = match ($kart['ton']) {
                    'basari' => 'text-emerald-700 dark:text-emerald-400',
                    'uyari' => 'text-amber-700 dark:text-amber-400',
                    'tehlike' => 'text-red-600 dark:text-red-400',
                    default => '',
                };
            @endphp

            <a href="{{ $kart['rota'] }}"
               class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-700">
                <div class="text-sm text-slate-500 dark:text-slate-400">{{ $kart['etiket'] }}</div>

                <div class="mt-2 text-2xl font-semibold tracking-tight tabular-nums {{ $renk }}">
                    @if ($kart['kurus'] !== null)
                        @if ($kart['kurus'] < 0) − @endif<x-para :kurus="abs($kart['kurus'])" />
                    @else
                        {{ $kart['sayi'] }}
                    @endif
                </div>

                @if ($kart['ipucu'])
                    <div class="mt-1 text-xs text-slate-400">{{ $kart['ipucu'] }}</div>
                @endif
            </a>
        @endforeach
    </div>
@endif

@if ($tenant->hasModule(\App\Modules\Module::Sales))
    <x-card class="mb-4">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold">Satış grafiği</h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ $donem['etiket'] }}</p>
            </div>

            <div class="flex rounded-lg border border-slate-200 p-0.5 dark:border-slate-800">
                @foreach (['gun' => 'Gün', 'hafta' => 'Hafta', 'yil' => 'Yıl'] as $anahtar => $etiket)
                    <a href="{{ route('panel', ['donem' => $anahtar]) }}"
                       class="rounded-[6px] px-3 py-1.5 text-sm transition
                              {{ $donem['anahtar'] === $anahtar
                                 ? 'bg-indigo-600 font-semibold text-white'
                                 : 'text-slate-600 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-slate-800' }}">
                        {{ $etiket }}
                    </a>
                @endforeach
            </div>
        </div>

        <x-sutun-grafik :seri="$seri" />
    </x-card>
@endif

<div class="grid gap-4 lg:grid-cols-2">
    @if ($tenant->hasModule(\App\Modules\Module::Sales))
        <x-card class="!p-0 overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                <h2 class="text-base font-semibold">Bekleyen tahsilat</h2>
                @if ($bekleyenToplam > 0)
                    <span class="text-sm font-semibold text-amber-700 dark:text-amber-400">
                        <x-para :kurus="$bekleyenToplam" />
                    </span>
                @endif
            </div>

            <ul class="divide-y divide-slate-50 dark:divide-slate-800/60">
                @forelse ($bekleyenler as $satis)
                    <li class="flex items-center justify-between gap-3 px-6 py-3 text-sm">
                        <div class="min-w-0">
                            <a href="{{ route('satis.show', $satis) }}" class="font-medium hover:underline">
                                {{ $satis->customerLabel() }}
                            </a>
                            <div class="text-xs {{ $satis->isOverdue() ? 'font-medium text-red-600 dark:text-red-400' : 'text-slate-400' }}">
                                {{ $satis->number }}
                                @if ($satis->isOverdue())
                                    · vadesi {{ $satis->due_on->format('d.m.Y') }} tarihinde geçti
                                @elseif ($satis->due_on)
                                    · vade {{ $satis->due_on->format('d.m.Y') }}
                                @endif
                            </div>
                        </div>
                        <span class="shrink-0 font-medium"><x-para :kurus="$satis->remainingMinor()" /></span>
                    </li>
                @empty
                    <li class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                        Bekleyen tahsilat yok.
                    </li>
                @endforelse
            </ul>
        </x-card>

        <x-card class="!p-0 overflow-hidden">
            <h2 class="border-b border-slate-100 px-6 py-4 text-base font-semibold dark:border-slate-800">
                Bu ayın çok satanları
            </h2>

            <ul class="divide-y divide-slate-50 dark:divide-slate-800/60">
                @forelse ($cokSatanlar as $urun)
                    <li class="flex items-center justify-between gap-3 px-6 py-3 text-sm">
                        <div class="min-w-0">
                            <div class="truncate font-medium">{{ $urun['ad'] }}</div>
                            <div class="text-xs text-slate-400">
                                <x-miktar :deger="$urun['adet']" :birim="$urun['birim']" /> satıldı
                            </div>
                        </div>
                        <span class="shrink-0 font-medium"><x-para :kurus="$urun['kurus']" /></span>
                    </li>
                @empty
                    <li class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                        Bu ay satış yok.
                    </li>
                @endforelse
            </ul>
        </x-card>
    @endif

    @if ($tenant->hasModule(\App\Modules\Module::Inventory))
        <x-card class="!p-0 overflow-hidden">
            <h2 class="border-b border-slate-100 px-6 py-4 text-base font-semibold dark:border-slate-800">
                Kritik stok
            </h2>

            <ul class="divide-y divide-slate-50 dark:divide-slate-800/60">
                @forelse ($kritikStok as $urun)
                    @php $tukendi = $urun->totalStock() <= 0; @endphp
                    <li class="flex items-center justify-between gap-3 px-6 py-3 text-sm">
                        <a href="{{ route('stok.hareket', ['urun' => $urun->id]) }}" class="min-w-0 flex-1 truncate hover:underline">
                            {{ $urun->name }}
                        </a>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium
                            {{ $tukendi
                               ? 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300'
                               : 'bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-300' }}">
                            {{ $tukendi ? 'Tükendi' : rtrim(rtrim(number_format($urun->totalStock(), 3, ',', '.'), '0'), ',').' '.$urun->unitLabel().' kaldı' }}
                        </span>
                    </li>
                @empty
                    <li class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                        Kritik seviyede ürün yok.
                    </li>
                @endforelse
            </ul>
        </x-card>
    @endif

    @if ($moduller !== [])
        <x-card>
            <h2 class="mb-4 text-base font-semibold">Modüller</h2>

            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($moduller as $modul)
                    <a href="{{ route($modul->routeName()) }}"
                       class="rounded-lg border border-slate-200 px-4 py-3 text-sm font-medium transition hover:border-indigo-300 dark:border-slate-800 dark:hover:border-indigo-700">
                        {{ $modul->label() }}
                    </a>
                @endforeach
            </div>
        </x-card>
    @endif
</div>
@endsection
