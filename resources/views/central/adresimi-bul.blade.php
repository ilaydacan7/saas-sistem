@extends('layouts.giris')

@section('baslik', 'Şirket adresimi bul')

@section('icerik')
    @if (session('durum'))
        <x-notice tone="basari">{{ session('durum') }}</x-notice>
    @endif

    <h1 class="text-xl font-semibold tracking-tight">Şirket adresinizi bulalım</h1>
    <p class="mt-1.5 mb-6 text-sm text-slate-500 dark:text-slate-400">
        Hesabınızın e-posta adresini yazın; kayıtlı olduğunuz şirketlerin adreslerini size e-posta ile gönderelim.
    </p>

    <form method="POST" action="{{ route('adresimi.bul') }}">
        @csrf

        <x-text-field name="email" label="E-posta" type="email" :value="old('email')" required autofocus />

        <x-button type="submit">Adreslerimi gönder</x-button>
    </form>

    <p class="mt-5 text-center text-sm">
        <a href="{{ route('giris') }}" class="text-marka-600 hover:underline dark:text-marka-400">
            Girişe dön
        </a>
    </p>
@endsection
