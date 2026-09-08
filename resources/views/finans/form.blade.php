@extends('layouts.uygulama')

@php
    $yeni = ! $transaction->exists;
    $tur = old('type', $transaction->type?->value ?? $seciliTur->value);
@endphp

@section('baslik', $yeni ? 'Yeni kayıt' : 'Kaydı düzenle')

@section('icerik')
<div class="mb-5">
    <a href="{{ route('finans.index') }}" class="text-sm text-indigo-600 hover:underline dark:text-indigo-400">← Gelir ve Gider</a>
    <h1 class="mt-1 text-xl font-semibold tracking-tight">{{ $yeni ? 'Yeni kayıt' : 'Kaydı düzenle' }}</h1>
</div>

<x-card>
    <form method="POST" action="{{ $yeni ? route('finans.store') : route('finans.update', $transaction) }}">
        @csrf
        @unless ($yeni)
            @method('PUT')
        @endunless

        <div class="mb-5">
            <span class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Tür</span>
            <div class="flex gap-2">
                @foreach (\App\Modules\TransactionType::cases() as $secenek)
                    <label class="flex-1 cursor-pointer rounded-lg border px-3 py-2 text-sm
                                  {{ $tur === $secenek->value
                                     ? 'border-indigo-500 bg-indigo-50 font-medium text-indigo-900 dark:bg-indigo-950 dark:text-indigo-200'
                                     : 'border-slate-300 dark:border-slate-700' }}">
                        <input type="radio" name="type" value="{{ $secenek->value }}" class="mr-1.5" @checked($tur === $secenek->value)>
                        {{ $secenek->label() }}
                    </label>
                @endforeach
            </div>
            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                Satış tahsilatları kasaya kendiliğinden gelir olarak yazılır; buraya elle eklemeniz gerekmez.
            </p>
            @error('type')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-x-4 sm:grid-cols-2">
            <x-text-field name="amount" label="Tutar" suffix="{{ config('billing.currency') }}"
                          :value="old('amount', $transaction->exists ? number_format($transaction->amount_minor / 100, 2, ',', '.') : '')"
                          required autofocus />

            <x-text-field name="occurred_on" label="Tarih" type="date"
                          :value="old('occurred_on', $transaction->occurred_on?->format('Y-m-d') ?? now()->format('Y-m-d'))"
                          required />

            <div class="mb-4">
                <label for="category_id" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Kategori</label>
                <select id="category_id" name="category_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                    <option value="">Kategorisiz</option>
                    @foreach ($kategoriler as $kategori)
                        <option value="{{ $kategori->id }}"
                                data-tur="{{ $kategori->type->value }}"
                                @selected((int) old('category_id', $transaction->category_id) === $kategori->id)>
                            {{ $kategori->name }} ({{ $kategori->type->label() }})
                        </option>
                    @endforeach
                </select>
                @error('category_id')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            </div>

            <div class="mb-4">
                <label for="method" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Ödeme yöntemi</label>
                <select id="method" name="method"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
                    <option value="">Belirtilmedi</option>
                    @foreach (\App\Modules\PaymentMethod::cases() as $secenek)
                        <option value="{{ $secenek->value }}" @selected(old('method', $transaction->method?->value) === $secenek->value)>
                            {{ $secenek->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <x-text-field name="description" label="Açıklama" :value="old('description', $transaction->description)"
                      hint="Örneğin “Eylül ayı dükkân kirası”." />

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                {{ $yeni ? 'Kaydet' : 'Değişiklikleri kaydet' }}
            </button>
            <a href="{{ route('finans.index') }}" class="text-sm text-slate-500 hover:underline dark:text-slate-400">Vazgeç</a>
        </div>
    </form>
</x-card>

@unless ($yeni)
    <x-card class="mt-4">
        <h2 class="text-base font-semibold">Kaydı sil</h2>
        <p class="mt-1 mb-4 text-sm text-slate-500 dark:text-slate-400">Kayıt kalıcı olarak silinir.</p>

        <form method="POST" action="{{ route('finans.destroy', $transaction) }}"
              onsubmit="return confirm('Bu kayıt silinecek. Onaylıyor musunuz?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="rounded-lg border border-red-300 px-3.5 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-900 dark:text-red-400 dark:hover:bg-red-950">
                Sil
            </button>
        </form>
    </x-card>
@endunless
@endsection
