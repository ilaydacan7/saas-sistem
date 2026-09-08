@extends('layouts.uygulama')

@section('baslik', 'Stok hareketi')

@section('icerik')
<div class="mb-5">
    <a href="{{ route('stok.index') }}" class="text-sm text-marka-600 hover:underline dark:text-marka-400">← Ürünler</a>
    <h1 class="mt-1 text-xl font-semibold tracking-tight">Stok hareketi</h1>
</div>

@if ($products->isEmpty())
    <x-card>
        <p class="text-sm text-slate-500 dark:text-slate-400">
            Stok takibi yapılan aktif ürün yok.
            <a href="{{ route('stok.create') }}" class="text-marka-600 hover:underline dark:text-marka-400">Önce bir ürün ekleyin.</a>
        </p>
    </x-card>
@else
    <x-card>
        <form method="POST" action="{{ route('stok.hareket.kaydet') }}">
            @csrf

            @php $tip = old('type', $seciliTip->value); @endphp

            <div class="mb-5">
                <span class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Hareket türü</span>
                <div class="grid gap-2 sm:grid-cols-3">
                    @foreach (\App\Modules\StockMovementType::cases() as $secenek)
                        <label class="cursor-pointer rounded-lg border px-3 py-2 text-sm
                                      {{ $tip === $secenek->value
                                         ? 'border-marka-500 bg-marka-50 font-medium text-marka-900 dark:bg-marka-900 dark:text-marka-200'
                                         : 'border-slate-300 dark:border-slate-700' }}">
                            <input type="radio" name="type" value="{{ $secenek->value }}" class="mr-1.5" @checked($tip === $secenek->value)>
                            {{ $secenek->label() }}
                        </label>
                    @endforeach
                </div>
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                    Düzeltmede miktar, sayım sonucu bulunan <strong>yeni stok</strong> değeridir.
                </p>
                @error('type')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <div class="mb-4">
                <label for="product_id" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Ürün</label>
                <select id="product_id" name="product_id" required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected((int) old('product_id', $seciliUrun) === $product->id)>
                            {{ $product->name }}@if ($product->sku) ({{ $product->sku }})@endif
                        </option>
                    @endforeach
                </select>
                @error('product_id')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-x-4 sm:grid-cols-2">
                <div class="mb-4">
                    <label for="warehouse_id" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Depo</label>
                    <select id="warehouse_id" name="warehouse_id" required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                        @foreach ($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" @selected((int) old('warehouse_id') === $warehouse->id)>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('warehouse_id')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                </div>

                <x-text-field name="quantity" label="Miktar" :value="old('quantity')" required />
            </div>

            <x-text-field name="note" label="Açıklama" :value="old('note')"
                          hint="Örneğin fatura numarası veya sayım notu. Zorunlu değil." />

            <div class="flex items-center gap-3">
                <button type="submit" class="rounded-lg bg-marka-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-marka-500">
                    Hareketi kaydet
                </button>
                <a href="{{ route('stok.index') }}" class="text-sm text-slate-500 hover:underline dark:text-slate-400">Vazgeç</a>
            </div>
        </form>
    </x-card>
@endif
@endsection
