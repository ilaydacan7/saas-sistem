@extends('layouts.uygulama')

@section('baslik', 'Yeni satış')

@section('icerik')
<div class="mb-5">
    <a href="{{ route('satis.index') }}" class="text-sm text-indigo-600 hover:underline dark:text-indigo-400">← Satışlar</a>
    <h1 class="mt-1 text-xl font-semibold tracking-tight">Yeni satış</h1>
</div>

@if ($products->isEmpty())
    <x-card>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Satılacak aktif ürün yok.
            <a href="{{ route('stok.create') }}" class="text-indigo-600 hover:underline dark:text-indigo-400">Önce bir ürün ekleyin.</a>
        </p>
    </x-card>
@else
    @error('satirlar')<x-notice tone="uyari">{{ $message }}</x-notice>@enderror

    <form method="POST" action="{{ route('satis.store') }}" id="satis-formu">
        @csrf

        <x-card class="mb-4">
            <div class="grid gap-x-4 sm:grid-cols-2">
                <div class="mb-4">
                    <label for="customer_id" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Müşteri</label>
                    <select id="customer_id" name="customer_id"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                        <option value="">Perakende satış (müşterisiz)</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((int) old('customer_id') === $customer->id)>
                                {{ $customer->displayName() }}
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <div class="mb-4">
                    <label for="warehouse_id" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Depo</label>
                    <select id="warehouse_id" name="warehouse_id"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                        <option value="">Stoktan düşme</option>
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((int) old('warehouse_id', $warehouses->first()?->id) === $warehouse->id)>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">Depo seçiliyse satılan ürünler stoktan düşer.</p>
                </div>

                <x-text-field name="sold_at" label="Satış tarihi" type="date"
                              :value="old('sold_at', now()->format('Y-m-d'))" required />

                <x-text-field name="due_on" label="Vade tarihi" type="date" :value="old('due_on')"
                              hint="Ödeme sonra alınacaksa doldurun." />
            </div>
        </x-card>

        <x-card class="mb-4 !p-0 overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4 dark:border-slate-800">
                <h2 class="text-base font-semibold">Satılan ürünler</h2>
                <button type="button" id="satir-ekle"
                        class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                    + Satır ekle
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-slate-100 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                        <tr>
                            <th class="px-6 py-3 font-medium">Ürün</th>
                            <th class="w-32 px-3 py-3 font-medium">Miktar</th>
                            <th class="w-40 px-3 py-3 font-medium">Birim fiyat</th>
                            <th class="w-32 px-3 py-3 text-right font-medium">Tutar</th>
                            <th class="w-12 px-3 py-3"></th>
                        </tr>
                    </thead>
                    <tbody id="satir-govdesi" class="divide-y divide-slate-100 dark:divide-slate-800"></tbody>
                </table>
            </div>
        </x-card>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-card>
                <h2 class="mb-4 text-base font-semibold">Tahsilat</h2>

                <x-text-field name="tahsilat" label="Şimdi alınan tutar" suffix="{{ config('billing.currency') }}"
                              :value="old('tahsilat', '0')"
                              hint="Tamamı sonra tahsil edilecekse 0 bırakın." required />

                <div class="mb-4">
                    <label for="method" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Ödeme yöntemi</label>
                    <select id="method" name="method"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                        @foreach (\App\Modules\PaymentMethod::cases() as $secenek)
                            <option value="{{ $secenek->value }}" @selected(old('method') === $secenek->value)>{{ $secenek->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <x-text-field name="note" label="Not" :value="old('note')" />
            </x-card>

            <x-card>
                <h2 class="mb-4 text-base font-semibold">Özet</h2>

                <x-text-field name="indirim" label="İndirim" suffix="{{ config('billing.currency') }}"
                              :value="old('indirim', '0')" required />

                <dl class="mb-5 divide-y divide-slate-100 text-sm dark:divide-slate-800">
                    <div class="flex justify-between py-2">
                        <dt class="text-slate-500 dark:text-slate-400">Ara toplam</dt>
                        <dd class="font-medium tabular-nums" id="ozet-ara-toplam">0,00</dd>
                    </div>
                    <div class="flex justify-between py-2">
                        <dt class="text-slate-500 dark:text-slate-400">KDV</dt>
                        <dd class="font-medium tabular-nums" id="ozet-kdv">0,00</dd>
                    </div>
                    <div class="flex justify-between py-2 text-base">
                        <dt class="font-semibold">Genel toplam</dt>
                        <dd class="font-semibold tabular-nums" id="ozet-toplam">0,00</dd>
                    </div>
                </dl>

                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                    Satışı kaydet
                </button>
            </x-card>
        </div>
    </form>

    <template id="satir-sablonu">
        <tr class="satir">
            <td class="px-6 py-3">
                <select name="satirlar[__INDEX__][product_id]" data-alan="urun" required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                    <option value="">Ürün seçin</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}"
                                data-fiyat="{{ number_format($product->salePrice(), 2, ',', '') }}"
                                data-kdv="{{ $product->vat_rate }}">
                            {{ $product->name }}@if ($product->sku) ({{ $product->sku }})@endif
                        </option>
                    @endforeach
                </select>
            </td>
            <td class="px-3 py-3">
                <input type="text" name="satirlar[__INDEX__][quantity]" value="1" data-alan="miktar" inputmode="decimal"
                       class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums dark:border-slate-700 dark:bg-slate-900">
            </td>
            <td class="px-3 py-3">
                <input type="text" name="satirlar[__INDEX__][unit_price]" value="0,00" data-alan="fiyat" inputmode="decimal"
                       class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm tabular-nums dark:border-slate-700 dark:bg-slate-900">
            </td>
            <td class="px-3 py-3 text-right font-medium tabular-nums" data-alan="tutar">0,00</td>
            <td class="px-3 py-3 text-right">
                <button type="button" data-alan="sil" title="Satırı sil"
                        class="rounded-lg px-2 py-1 text-slate-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950">
                    ×
                </button>
            </td>
        </tr>
    </template>
@endif
@endsection
