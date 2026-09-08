@extends('layouts.app')

@section('baslik', config('app.name'))

@section('icerik')
<x-card>
    <h1 class="text-xl font-semibold tracking-tight">{{ config('app.name') }}</h1>
    <p class="mt-1.5 mb-6 text-sm text-slate-500 dark:text-slate-400">
        Müşteri, stok, satış ve tahsilat takibi tek yerde. Her şirket kendi adresinde, kendi verisiyle çalışır.
    </p>

    <a href="{{ route('kayit') }}" class="block">
        <x-button type="button">Şirketinizi kaydedin</x-button>
    </a>

    <a href="{{ route('giris') }}" class="mt-3 block">
        <x-button type="button" variant="secondary">Giriş yap</x-button>
    </a>

    <p class="mt-6 text-center text-xs text-slate-500 dark:text-slate-400">
        14 gün ücretsiz deneme · Kredi kartı istemiyoruz
    </p>
</x-card>
@endsection
