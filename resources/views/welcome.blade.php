@extends('layouts.app')

@section('baslik', config('app.name'))

@section('icerik')
<x-card>
    <h1 class="text-xl font-semibold tracking-tight">{{ config('app.name') }}</h1>
    <p class="mt-1.5 mb-6 text-sm text-slate-500 dark:text-slate-400">
        Her şirket kendi adresinde, kendi verisiyle çalışır.
    </p>

    <a href="{{ route('kayit') }}" class="block">
        <x-button type="button">Şirketinizi kaydedin</x-button>
    </a>

    <p class="mt-5 text-center text-sm text-slate-500 dark:text-slate-400">
        Zaten hesabınız var mı? Şirketinizin adresinden giriş yapın:<br>
        <span class="font-medium text-slate-700 dark:text-slate-300">sirketiniz.{{ config('tenancy.base_domain') }}</span>
    </p>
</x-card>
@endsection
