@extends('layouts.uygulama')

@section('baslik', 'Panel')

@section('icerik')
@if ($tenant->status === \App\Tenancy\TenantStatus::Trialing && $kalanGun !== null)
    <x-notice tone="uyari">
        Deneme sürenizin bitmesine <strong class="font-semibold">{{ $kalanGun }} gün</strong> kaldı
        ({{ $tenant->trial_ends_at->format('d.m.Y') }}).
    </x-notice>
@endif

<div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <h2 class="text-sm font-medium text-slate-500 dark:text-slate-400">Genel bakış</h2>

    <div class="flex rounded-lg border border-slate-200 bg-white p-0.5 dark:border-marka-800 dark:bg-marka-900">
        @foreach (['bugun' => 'Bugün', 'hafta' => 'Bu Hafta', 'ay' => 'Bu Ay'] as $anahtar => $etiket)
            <a href="{{ route('panel', ['donem' => $anahtar]) }}"
               class="rounded-[6px] px-3.5 py-1.5 text-sm transition
                      {{ $donem === $anahtar
                         ? 'bg-marka-600 font-semibold text-white'
                         : 'text-slate-600 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-marka-800' }}">
                {{ $etiket }}
            </a>
        @endforeach
    </div>
</div>

<div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($kartlar as $kart)
        @php
            $tonSinifi = match ($kart['ton']) {
                'uyari' => 'bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-400',
                'basari' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400',
                default => 'bg-slate-100 text-slate-500 dark:bg-marka-800 dark:text-slate-300',
            };
        @endphp

        <{{ $kart['rota'] ? 'a' : 'div' }}
            @if ($kart['rota']) href="{{ $kart['rota'] }}" @endif
            class="rounded-xl border border-slate-200 bg-white p-5 transition dark:border-marka-800 dark:bg-marka-900 {{ $kart['rota'] ? 'hover:border-marka-300 dark:hover:border-marka-600' : '' }}">
            <div class="flex items-start justify-between gap-3">
                <span class="text-sm text-slate-500 dark:text-slate-400">{{ $kart['etiket'] }}</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg {{ $tonSinifi }}">
                    <x-ikon :name="$kart['ikon']" class="h-4 w-4" />
                </span>
            </div>
            <div class="mt-3 text-3xl font-semibold tracking-tight tabular-nums">{{ $kart['deger'] }}</div>
        </{{ $kart['rota'] ? 'a' : 'div' }}>
    @endforeach
</div>

<div class="grid gap-4 lg:grid-cols-3">
    <div class="rounded-xl border border-slate-200 bg-white lg:col-span-2 dark:border-marka-800 dark:bg-marka-900">
        <h2 class="border-b border-slate-100 px-6 py-4 text-base font-semibold dark:border-marka-800">Son aktiviteler</h2>

        <ul class="divide-y divide-slate-50 dark:divide-marka-800/60">
            @forelse ($aktiviteler as $aktivite)
                @php
                    $nokta = match ($aktivite['ton']) {
                        'basari' => 'bg-emerald-500',
                        'bilgi' => 'bg-marka-400',
                        default => 'bg-amber-500',
                    };
                @endphp
                <li class="flex items-center gap-3 px-6 py-3.5 text-sm">
                    <span class="h-2 w-2 shrink-0 rounded-full {{ $nokta }}"></span>
                    <span class="min-w-0 flex-1 truncate">{{ $aktivite['metin'] }}</span>
                    <span class="shrink-0 text-xs text-slate-400">{{ $aktivite['zaman']->diffForHumans(short: true) }}</span>
                </li>
            @empty
                <li class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                    Henüz hareket yok.
                </li>
            @endforelse
        </ul>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white dark:border-marka-800 dark:bg-marka-900">
        <h2 class="border-b border-slate-100 px-6 py-4 text-base font-semibold dark:border-marka-800">Kritik stok</h2>

        <ul class="divide-y divide-slate-50 dark:divide-marka-800/60">
            @forelse ($kritikUrunler as $urun)
                @php $tukendi = $urun->totalStock() <= 0; @endphp
                <li class="flex items-center justify-between gap-3 px-6 py-3.5 text-sm">
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
                    @if ($tenant->hasModule(\App\Modules\Module::Inventory))
                        Kritik seviyede ürün yok.
                    @else
                        Stok modülü kapalı.
                    @endif
                </li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
