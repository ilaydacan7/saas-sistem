@extends('layouts.uygulama')

@php $yeni = ! $customer->exists; @endphp

@section('baslik', $yeni ? 'Yeni müşteri' : $customer->displayName())

@section('icerik')
<div class="mb-5">
    <a href="{{ route('musteriler.index') }}" class="text-sm text-indigo-600 hover:underline dark:text-indigo-400">← Müşteriler</a>
    <h1 class="mt-1 text-xl font-semibold tracking-tight">
        {{ $yeni ? 'Yeni müşteri' : $customer->displayName() }}
    </h1>
</div>

<x-card>
    <form method="POST" action="{{ $yeni ? route('musteriler.store') : route('musteriler.update', $customer) }}">
        @csrf
        @unless ($yeni)
            @method('PUT')
        @endunless

        @php $tur = old('type', $customer->type?->value ?? 'individual'); @endphp

        <div class="mb-5">
            <span class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Kayıt türü</span>
            <div class="flex gap-2">
                @foreach (\App\Modules\CustomerType::cases() as $secenek)
                    <label class="flex-1 cursor-pointer rounded-lg border px-3 py-2 text-sm
                                  {{ $tur === $secenek->value
                                     ? 'border-indigo-500 bg-indigo-50 font-medium text-indigo-900 dark:bg-indigo-950 dark:text-indigo-200'
                                     : 'border-slate-300 dark:border-slate-700' }}">
                        <input type="radio" name="type" value="{{ $secenek->value }}" class="mr-1.5"
                               @checked($tur === $secenek->value)>
                        {{ $secenek->label() }}
                    </label>
                @endforeach
            </div>
            @error('type')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-x-4 sm:grid-cols-2">
            <x-text-field name="name" label="Ad soyad / yetkili" :value="old('name', $customer->name)" required />
            <x-text-field name="company_name" label="Firma unvanı" :value="old('company_name', $customer->company_name)"
                          hint="Kurumsal kayıtlarda zorunlu." />
            <x-text-field name="phone" label="Telefon" :value="old('phone', $customer->phone)" />
            <x-text-field name="email" label="E-posta" type="email" :value="old('email', $customer->email)" />
            <x-text-field name="tax_office" label="Vergi dairesi" :value="old('tax_office', $customer->tax_office)" />
            <x-text-field name="tax_number" label="Vergi / TC no" :value="old('tax_number', $customer->tax_number)"
                          hint="Vergi no 10, TC no 11 hane." />
            <x-text-field name="city" label="Şehir" :value="old('city', $customer->city)" />
        </div>

        <div class="mb-4">
            <label for="address" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Adres</label>
            <textarea id="address" name="address" rows="2"
                      class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-2 focus:outline-offset-[-1px] focus:outline-indigo-500 dark:border-slate-700 dark:bg-slate-900">{{ old('address', $customer->address) }}</textarea>
            @error('address')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        <div class="mb-4">
            <label for="note" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Not</label>
            <textarea id="note" name="note" rows="3"
                      class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-2 focus:outline-offset-[-1px] focus:outline-indigo-500 dark:border-slate-700 dark:bg-slate-900">{{ old('note', $customer->note) }}</textarea>
            @error('note')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        <label class="mb-5 flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $customer->is_active ?? true))
                   class="rounded border-slate-300 text-indigo-600 dark:border-slate-600 dark:bg-slate-800">
            Aktif kayıt
        </label>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                {{ $yeni ? 'Müşteriyi kaydet' : 'Değişiklikleri kaydet' }}
            </button>

            <a href="{{ route('musteriler.index') }}" class="text-sm text-slate-500 hover:underline dark:text-slate-400">Vazgeç</a>
        </div>
    </form>
</x-card>

@unless ($yeni)
    <x-card class="mt-4">
        <h2 class="text-base font-semibold">Kaydı sil</h2>
        <p class="mt-1 mb-4 text-sm text-slate-500 dark:text-slate-400">
            Kayıt listeden kaldırılır. Geçmiş işlemleri korunur.
        </p>

        <form method="POST" action="{{ route('musteriler.destroy', $customer) }}"
              onsubmit="return confirm('{{ $customer->displayName() }} silinecek. Onaylıyor musunuz?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-lg border border-red-300 px-3.5 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-950">
                Müşteriyi sil
            </button>
        </form>
    </x-card>
@endunless
@endsection
