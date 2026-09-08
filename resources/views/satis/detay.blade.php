@extends('layouts.uygulama')

@section('baslik', $sale->number)

@php
    $odeme = $sale->paymentStatus();
    $iptal = $sale->status === \App\Modules\SaleStatus::Cancelled;
@endphp

@section('icerik')
<div class="mb-5 flex flex-wrap items-start justify-between gap-3">
    <div>
        <a href="{{ route('satis.index') }}" class="text-sm text-indigo-600 hover:underline dark:text-indigo-400">← Satışlar</a>
        <h1 class="mt-1 text-xl font-semibold tracking-tight">{{ $sale->number }}</h1>
        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
            {{ $sale->customerLabel() }} · {{ $sale->sold_at->format('d.m.Y H:i') }}
            @if ($sale->createdBy) · {{ $sale->createdBy->name }} @endif
        </p>
    </div>

    <span class="rounded-full px-3 py-1 text-sm font-medium
        {{ $iptal ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'
           : ($odeme === \App\Modules\PaymentStatus::Paid ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
           : ($odeme === \App\Modules\PaymentStatus::Partial ? 'bg-amber-50 text-amber-800 dark:bg-amber-950 dark:text-amber-300'
           : 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300')) }}">
        {{ $iptal ? 'İptal edildi' : $odeme->label() }}
    </span>
</div>

@error('tutar')<x-notice tone="uyari">{{ $message }}</x-notice>@enderror
@error('iptal')<x-notice tone="uyari">{{ $message }}</x-notice>@enderror

<x-card class="mb-4 !p-0 overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead class="border-b border-slate-100 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
            <tr>
                <th class="px-6 py-3 font-medium">Ürün</th>
                <th class="px-3 py-3 text-right font-medium">Miktar</th>
                <th class="px-3 py-3 text-right font-medium">Birim fiyat</th>
                <th class="px-6 py-3 text-right font-medium">Tutar</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            @foreach ($sale->lines as $satir)
                <tr>
                    <td class="px-6 py-3">
                        <div class="font-medium">{{ $satir->name }}</div>
                        @if ($satir->vat_rate > 0)
                            <div class="text-xs text-slate-500 dark:text-slate-400">%{{ $satir->vat_rate }} KDV</div>
                        @endif
                    </td>
                    <td class="px-3 py-3 text-right"><x-miktar :deger="$satir->quantity" :birim="$satir->unit_label" /></td>
                    <td class="px-3 py-3 text-right text-slate-500 dark:text-slate-400"><x-para :kurus="$satir->unit_price_minor" /></td>
                    <td class="px-6 py-3 text-right font-medium"><x-para :kurus="$satir->line_total_minor" /></td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="border-t border-slate-100 text-sm dark:border-slate-800">
            <tr>
                <td colspan="3" class="px-6 py-2 text-right text-slate-500 dark:text-slate-400">Ara toplam</td>
                <td class="px-6 py-2 text-right"><x-para :kurus="$sale->subtotal_minor" /></td>
            </tr>
            @if ($sale->discount_minor > 0)
                <tr>
                    <td colspan="3" class="px-6 py-2 text-right text-slate-500 dark:text-slate-400">İndirim</td>
                    <td class="px-6 py-2 text-right text-red-600 dark:text-red-400">− <x-para :kurus="$sale->discount_minor" /></td>
                </tr>
            @endif
            <tr>
                <td colspan="3" class="px-6 py-2 text-right text-slate-500 dark:text-slate-400">KDV</td>
                <td class="px-6 py-2 text-right"><x-para :kurus="$sale->vat_minor" /></td>
            </tr>
            <tr class="text-base">
                <td colspan="3" class="px-6 py-3 text-right font-semibold">Genel toplam</td>
                <td class="px-6 py-3 text-right font-semibold"><x-para :kurus="$sale->total_minor" /></td>
            </tr>
        </tfoot>
    </table>
</x-card>

<div class="grid gap-4 lg:grid-cols-2">
    <x-card>
        <h2 class="mb-1 text-base font-semibold">Tahsilat</h2>
        <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">
            Tahsil edilen <x-para :kurus="$sale->paid_minor" /> ·
            kalan <strong class="font-semibold {{ $sale->remainingMinor() > 0 ? 'text-amber-700 dark:text-amber-400' : '' }}">
                <x-para :kurus="$sale->remainingMinor()" />
            </strong>
        </p>

        @if ($sale->payments->isNotEmpty())
            <ul class="mb-4 divide-y divide-slate-100 text-sm dark:divide-slate-800">
                @foreach ($sale->payments as $tahsilat)
                    <li class="flex items-center justify-between gap-3 py-2">
                        <span>
                            <span class="font-medium"><x-para :kurus="$tahsilat->amount_minor" /></span>
                            <span class="text-slate-500 dark:text-slate-400">· {{ $tahsilat->method->label() }}</span>
                        </span>
                        <span class="text-xs text-slate-400">{{ $tahsilat->paid_at->format('d.m.Y') }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        @if (! $iptal && $sale->remainingMinor() > 0)
            <form method="POST" action="{{ route('satis.tahsilat', $sale) }}">
                @csrf
                <div class="grid gap-x-3 sm:grid-cols-2">
                    <x-text-field name="tutar" label="Tutar" suffix="{{ config('billing.currency') }}"
                                  :value="number_format($sale->remainingMinor() / 100, 2, ',', '.')" required />

                    <div class="mb-4">
                        <label for="method" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Yöntem</label>
                        <select id="method" name="method"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                            @foreach (\App\Modules\PaymentMethod::cases() as $secenek)
                                <option value="{{ $secenek->value }}">{{ $secenek->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                    Tahsilatı kaydet
                </button>
            </form>
        @elseif (! $iptal)
            <p class="text-sm text-emerald-700 dark:text-emerald-400">Bu satışın tahsilatı tamamlandı.</p>
        @endif
    </x-card>

    <x-card>
        <h2 class="mb-4 text-base font-semibold">Bilgiler</h2>

        <dl class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
            @foreach ([
                'Depo' => $sale->warehouse?->name ?? 'Stoktan düşülmedi',
                'Vade' => $sale->due_on?->format('d.m.Y') ?? '—',
                'Not' => $sale->note ?: '—',
            ] as $baslik => $deger)
                <div class="flex justify-between gap-4 py-2.5">
                    <dt class="text-slate-500 dark:text-slate-400">{{ $baslik }}</dt>
                    <dd class="text-right font-medium">{{ $deger }}</dd>
                </div>
            @endforeach
        </dl>

        @if (! $iptal)
            <form method="POST" action="{{ route('satis.iptal', $sale) }}" class="mt-5"
                  onsubmit="return confirm('{{ $sale->number }} iptal edilecek ve satılan ürünler stoğa geri eklenecek. Onaylıyor musunuz?')">
                @csrf
                <button type="submit" class="rounded-lg border border-red-300 px-3.5 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-950">
                    Satışı iptal et
                </button>
            </form>
        @endif
    </x-card>
</div>
@endsection
