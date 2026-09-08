@extends('layouts.giris')

@section('baslik', 'Parolamı unuttum')

@section('icerik')
    @if (session('durum'))
        <x-notice tone="basari">{{ session('durum') }}</x-notice>
    @endif

    <h1 class="text-xl font-semibold tracking-tight">Parolanızı mı unuttunuz?</h1>
    <p class="mt-1.5 mb-6 text-sm text-slate-500 dark:text-slate-400">
        {{ $tenant->name }} hesabınızın e-posta adresini girin, sıfırlama bağlantısını gönderelim.
    </p>

    <form method="POST" action="{{ route('parola.unuttum') }}">
        @csrf

        <x-text-field name="email" label="E-posta" type="email" required autofocus />

        <x-button type="submit">Sıfırlama bağlantısı gönder</x-button>
    </form>

    <p class="mt-5 text-center text-sm">
        <a href="{{ route('giris') }}" class="text-marka-600 hover:underline dark:text-marka-400">
            Girişe dön
        </a>
    </p>
@endsection
