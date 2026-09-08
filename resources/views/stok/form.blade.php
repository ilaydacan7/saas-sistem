@extends('layouts.uygulama')

@php $yeni = ! $product->exists; @endphp

@section('baslik', $yeni ? 'Yeni ürün' : $product->name)

@section('icerik')
<div class="mb-5">
    <a href="{{ route('stok.index') }}" class="text-sm text-marka-600 hover:underline dark:text-marka-400">← Ürünler</a>
    <h1 class="mt-1 text-xl font-semibold tracking-tight">{{ $yeni ? 'Yeni ürün' : $product->name }}</h1>

    @unless ($yeni)
        @if ($product->tracks_stock)
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Mevcut stok: <x-stok-rozeti :product="$product" />
            </p>
        @endif
    @endunless
</div>

<x-card>
    <form method="POST" action="{{ $yeni ? route('stok.store') : route('stok.update', $product) }}">
        @csrf
        @unless ($yeni)
            @method('PUT')
        @endunless

        @php $tur = old('type', $product->type?->value ?? 'product'); @endphp

        <div class="mb-5">
            <span class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Kayıt türü</span>
            <div class="flex gap-2">
                @foreach (\App\Modules\ProductType::cases() as $secenek)
                    <label class="flex-1 cursor-pointer rounded-lg border px-3 py-2 text-sm
                                  {{ $tur === $secenek->value
                                     ? 'border-marka-500 bg-marka-50 font-medium text-marka-900 dark:bg-marka-900 dark:text-marka-200'
                                     : 'border-slate-300 dark:border-slate-700' }}">
                        <input type="radio" name="type" value="{{ $secenek->value }}" class="mr-1.5" @checked($tur === $secenek->value)>
                        {{ $secenek->label() }}
                    </label>
                @endforeach
            </div>
            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                Hizmet kayıtlarında stok takibi yapılmaz.
            </p>
            @error('type')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-x-4 sm:grid-cols-2">
            <x-text-field name="name" label="Ad" :value="old('name', $product->name)" required />
            <x-text-field name="sku" label="Stok kodu" :value="old('sku', $product->sku)" hint="Boş bırakılabilir." />

            <div class="mb-4">
                <label for="unit_id" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Birim</label>
                <select id="unit_id" name="unit_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                    <option value="">Seçilmedi</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected((int) old('unit_id', $product->unit_id) === $unit->id)>
                            {{ $unit->name }} ({{ $unit->short_name }})
                        </option>
                    @endforeach
                </select>
                @error('unit_id')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <x-text-field name="vat_rate" label="KDV oranı" suffix="%"
                          :value="old('vat_rate', $product->vat_rate ?? 20)" required />

            <x-text-field name="purchase_price" label="Alış fiyatı" suffix="{{ config('billing.currency') }}"
                          :value="old('purchase_price', number_format($product->purchasePrice(), 2, ',', '.'))" required />

            <x-text-field name="sale_price" label="Satış fiyatı" suffix="{{ config('billing.currency') }}"
                          :value="old('sale_price', number_format($product->salePrice(), 2, ',', '.'))" required />

            <x-text-field name="min_stock" label="Minimum stok"
                          :value="old('min_stock', $product->min_stock ?? 0)"
                          hint="Stok bu seviyenin altına inince kritik sayılır." required />
        </div>

        <div class="mb-4">
            <label for="note" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Not</label>
            <textarea id="note" name="note" rows="2"
                      class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-2 focus:outline-offset-[-1px] focus:outline-marka-500 dark:border-slate-700 dark:bg-slate-900">{{ old('note', $product->note) }}</textarea>
            @error('note')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        <label class="mb-5 flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))
                   class="rounded border-slate-300 text-marka-600 dark:border-slate-600 dark:bg-slate-800">
            Aktif kayıt
        </label>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-marka-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-marka-500">
                {{ $yeni ? 'Ürünü kaydet' : 'Değişiklikleri kaydet' }}
            </button>
            <a href="{{ route('stok.index') }}" class="text-sm text-slate-500 hover:underline dark:text-slate-400">Vazgeç</a>
        </div>
    </form>
</x-card>

@unless ($yeni)
    <x-card class="mt-4">
        <h2 class="text-base font-semibold">Kaydı sil</h2>
        <p class="mt-1 mb-4 text-sm text-slate-500 dark:text-slate-400">
            Ürün listeden kaldırılır, geçmiş stok hareketleri korunur.
        </p>

        <form method="POST" action="{{ route('stok.destroy', $product) }}"
              onsubmit="return confirm('{{ $product->name }} silinecek. Onaylıyor musunuz?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-lg border border-red-300 px-3.5 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-950">
                Ürünü sil
            </button>
        </form>
    </x-card>
@endunless
@endsection
