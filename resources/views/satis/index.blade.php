@extends('layouts.uygulama')

@section('baslik', 'Satışlar')

@section('icerik')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-xl font-semibold tracking-tight">Satışlar</h1>
        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
            Bu ay ciro <strong class="font-semibold text-slate-700 dark:text-slate-200"><x-para :kurus="$buAyCiro" /></strong>
            @if ($bekleyenTutar > 0)
                · bekleyen tahsilat
                <a href="{{ route('satis.index', ['odeme' => 'bekliyor']) }}" class="font-semibold text-amber-700 hover:underline dark:text-amber-400">
                    <x-para :kurus="$bekleyenTutar" />
                </a>
            @endif
        </p>
    </div>

    <a href="{{ route('satis.create') }}"
       class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
        Yeni satış
    </a>
</div>

<x-card class="mb-4 !p-4">
    <form method="GET" action="{{ route('satis.index') }}" class="flex flex-wrap gap-3">
        <input type="text" name="q" value="{{ $arama }}" placeholder="Belge no veya müşteri ara"
               class="min-w-56 flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-2 focus:outline-offset-[-1px] focus:outline-indigo-500 dark:border-slate-700 dark:bg-slate-900">

        <select name="odeme" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
            <option value="">Tüm satışlar</option>
            <option value="bekliyor" @selected($durum === 'bekliyor')>Tahsilat bekleyen</option>
            <option value="odendi" @selected($durum === 'odendi')>Tahsil edilmiş</option>
            <option value="iptal" @selected($durum === 'iptal')>İptal edilmiş</option>
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
                    <th class="px-5 py-3 font-medium">Belge</th>
                    <th class="px-5 py-3 font-medium">Müşteri</th>
                    <th class="px-5 py-3 font-medium">Tarih</th>
                    <th class="px-5 py-3 text-right font-medium">Tutar</th>
                    <th class="px-5 py-3 text-right font-medium">Kalan</th>
                    <th class="px-5 py-3 font-medium">Durum</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($sales as $sale)
                    @php
                        $odeme = $sale->paymentStatus();
                        $iptal = $sale->status === \App\Modules\SaleStatus::Cancelled;
                        $rozet = match (true) {
                            $iptal => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                            $odeme === \App\Modules\PaymentStatus::Paid => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
                            $odeme === \App\Modules\PaymentStatus::Partial => 'bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
                            default => 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300',
                        };
                    @endphp
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="px-5 py-3">
                            <a href="{{ route('satis.show', $sale) }}" class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">
                                {{ $sale->number }}
                            </a>
                        </td>
                        <td class="px-5 py-3">{{ $sale->customerLabel() }}</td>
                        <td class="px-5 py-3 whitespace-nowrap text-slate-500 dark:text-slate-400">
                            {{ $sale->sold_at->format('d.m.Y') }}
                            @if ($sale->isOverdue())
                                <span class="block text-xs font-medium text-red-600 dark:text-red-400">vadesi geçti</span>
                            @elseif ($sale->due_on && $sale->remainingMinor() > 0)
                                <span class="block text-xs">vade {{ $sale->due_on->format('d.m.Y') }}</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right font-medium"><x-para :kurus="$sale->total_minor" /></td>
                        <td class="px-5 py-3 text-right {{ $sale->remainingMinor() > 0 && ! $iptal ? 'font-medium text-amber-700 dark:text-amber-400' : 'text-slate-400' }}">
                            @if ($iptal) — @else <x-para :kurus="$sale->remainingMinor()" /> @endif
                        </td>
                        <td class="px-5 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $rozet }}">
                                {{ $iptal ? 'İptal' : $odeme->label() }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-5 py-12 text-center text-slate-500 dark:text-slate-400">
                            @if ($arama !== '' || $durum !== '')
                                Bu filtreye uyan satış yok.
                            @else
                                Henüz satış kaydedilmemiş.
                                <a href="{{ route('satis.create') }}" class="text-indigo-600 hover:underline dark:text-indigo-400">İlkini oluşturun.</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

@if ($sales->hasPages())
    <div class="mt-4">{{ $sales->onEachSide(1)->links() }}</div>
@endif
@endsection
