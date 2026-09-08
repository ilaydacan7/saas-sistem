@extends('layouts.uygulama')

@section('baslik', 'Stok hareketleri')

@section('icerik')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <a href="{{ route('stok.index') }}" class="text-sm text-indigo-600 hover:underline dark:text-indigo-400">← Ürünler</a>
        <h1 class="mt-1 text-xl font-semibold tracking-tight">Stok hareketleri</h1>
        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $movements->total() }} kayıt</p>
    </div>

    <a href="{{ route('stok.hareket') }}"
       class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
        Yeni hareket
    </a>
</div>

<x-card class="mb-4 !p-4">
    <form method="GET" action="{{ route('stok.hareketler') }}" class="flex flex-wrap gap-3">
        <select name="urun" class="min-w-48 flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
            <option value="">Tüm ürünler</option>
            @foreach ($products as $product)
                <option value="{{ $product->id }}" @selected($seciliUrun === $product->id)>{{ $product->name }}</option>
            @endforeach
        </select>

        <select name="tip" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
            <option value="">Tüm hareketler</option>
            @foreach (\App\Modules\StockMovementType::cases() as $secenek)
                <option value="{{ $secenek->value }}" @selected($seciliTip === $secenek->value)>{{ $secenek->label() }}</option>
            @endforeach
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
                    <th class="px-5 py-3 font-medium">Tarih</th>
                    <th class="px-5 py-3 font-medium">Ürün</th>
                    <th class="px-5 py-3 font-medium">Hareket</th>
                    <th class="px-5 py-3 font-medium text-right">Miktar</th>
                    <th class="px-5 py-3 font-medium text-right">Kalan</th>
                    <th class="px-5 py-3 font-medium">Açıklama</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($movements as $movement)
                    @php $artis = (float) $movement->quantity >= 0; @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="px-5 py-3 whitespace-nowrap text-slate-500 dark:text-slate-400">
                            {{ $movement->occurred_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-5 py-3">
                            <div class="font-medium">{{ $movement->product->name }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $movement->warehouse->name }}</div>
                        </td>
                        <td class="px-5 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset
                                {{ $artis
                                   ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950 dark:text-emerald-300 dark:ring-emerald-400/30'
                                   : 'bg-slate-100 text-slate-600 ring-slate-500/20 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-400/30' }}">
                                {{ $movement->type->label() }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right font-medium {{ $artis ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $artis ? '+' : '−' }}<x-miktar :deger="abs((float) $movement->quantity)" />
                        </td>
                        <td class="px-5 py-3 text-right text-slate-500 dark:text-slate-400">
                            <x-miktar :deger="$movement->balance_after" :birim="$movement->product->unitLabel()" />
                        </td>
                        <td class="px-5 py-3 text-slate-500 dark:text-slate-400">
                            {{ $movement->note ?: '—' }}
                            @if ($movement->createdBy)
                                <div class="text-xs">{{ $movement->createdBy->name }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-slate-500 dark:text-slate-400">
                            Henüz stok hareketi yok.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

@if ($movements->hasPages())
    <div class="mt-4">{{ $movements->onEachSide(1)->links() }}</div>
@endif
@endsection
