@extends('layouts.uygulama')

@section('baslik', 'Ürünler ve Stok')

@section('icerik')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-xl font-semibold tracking-tight">Ürünler ve Stok</h1>
        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
            {{ $products->total() }} kayıt
            @if ($kritikSayisi > 0)
                · <a href="{{ route('stok.index', ['stok' => 'kritik']) }}"
                     class="font-medium text-amber-700 hover:underline dark:text-amber-400">{{ $kritikSayisi }} kritik stok</a>
            @endif
        </p>
    </div>

    <div class="flex gap-2">
        <a href="{{ route('stok.hareketler') }}"
           class="rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
            Hareketler
        </a>
        <a href="{{ route('stok.hareket') }}"
           class="rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
            Stok girişi
        </a>
        <a href="{{ route('stok.create') }}"
           class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
            Yeni ürün
        </a>
    </div>
</div>

<x-card class="mb-4 !p-4">
    <form method="GET" action="{{ route('stok.index') }}" class="flex flex-wrap gap-3">
        <input type="text" name="q" value="{{ $arama }}" placeholder="Ad veya stok kodu"
               class="min-w-48 flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-2 focus:outline-offset-[-1px] focus:outline-indigo-500 dark:border-slate-700 dark:bg-slate-900">

        <select name="tur" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
            <option value="">Tümü</option>
            @foreach (\App\Modules\ProductType::cases() as $secenek)
                <option value="{{ $secenek->value }}" @selected($tur === $secenek)>{{ $secenek->label() }}</option>
            @endforeach
        </select>

        <select name="stok" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
            <option value="">Tüm stok durumları</option>
            <option value="kritik" @selected($stokDurumu === 'kritik')>Kritik seviye</option>
            <option value="tukendi" @selected($stokDurumu === 'tukendi')>Tükenmiş</option>
        </select>

        <select name="durum" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
            <option value="">Aktifler</option>
            <option value="pasif" @selected($durum === 'pasif')>Pasifler</option>
        </select>

        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 dark:bg-slate-100 dark:text-slate-900">
            Filtrele
        </button>
    </form>
</x-card>

<x-card class="!p-0 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-200 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                <tr>
                    <th class="px-5 py-3 font-medium">Ürün</th>
                    <th class="px-5 py-3 font-medium">Alış</th>
                    <th class="px-5 py-3 font-medium">Satış</th>
                    <th class="px-5 py-3 font-medium">Stok</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($products as $product)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="px-5 py-3">
                            <div class="font-medium">{{ $product->name }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $product->type->label() }}
                                @if ($product->sku) · {{ $product->sku }} @endif
                                @unless ($product->is_active) · pasif @endunless
                            </div>
                        </td>
                        <td class="px-5 py-3 whitespace-nowrap text-slate-500 dark:text-slate-400">
                            <x-para :kurus="$product->purchase_price_minor" />
                        </td>
                        <td class="px-5 py-3 whitespace-nowrap font-medium">
                            <x-para :kurus="$product->sale_price_minor" />
                        </td>
                        <td class="px-5 py-3 whitespace-nowrap">
                            @if (! $product->tracks_stock)
                                <span class="text-slate-400">—</span>
                            @else
                                <x-stok-rozeti :product="$product" />
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right whitespace-nowrap">
                            @if ($product->tracks_stock)
                                <a href="{{ route('stok.hareket', ['urun' => $product->id]) }}"
                                   class="mr-3 text-slate-500 hover:underline dark:text-slate-400">Hareket</a>
                            @endif
                            <a href="{{ route('stok.edit', $product) }}"
                               class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">Düzenle</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-slate-500 dark:text-slate-400">
                            @if ($arama !== '' || $stokDurumu !== '')
                                Bu filtreye uyan kayıt yok.
                            @else
                                Henüz ürün eklenmemiş.
                                <a href="{{ route('stok.create') }}" class="text-indigo-600 hover:underline dark:text-indigo-400">İlkini ekleyin.</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

@if ($products->hasPages())
    <div class="mt-4">{{ $products->onEachSide(1)->links() }}</div>
@endif
@endsection
