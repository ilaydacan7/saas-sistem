@extends('layouts.giris')

@section('baslik', 'Giriş')

@section('icerik')
    <h1 class="text-xl font-semibold tracking-tight">Giriş yapın</h1>
    <p class="mt-1.5 mb-6 text-sm text-slate-500 dark:text-slate-400">
        Her şirketin kendi adresi vardır. Giriş yapmak için şirketinizin adresini yazın.
    </p>

    <form method="POST" action="{{ route('giris') }}">
        @csrf

        <x-text-field
            name="adres"
            label="Şirket adresiniz"
            suffix=".{{ $baseDomain }}"
            :value="old('adres')"
            hint="Örneğin acme yazarsanız acme.{{ $baseDomain }} adresine gidersiniz."
            required
            autofocus />

        <x-button type="submit">Devam et</x-button>
    </form>

    <p class="mt-5 text-center text-sm">
        <a href="{{ route('adresimi.bul') }}" class="text-marka-600 hover:underline dark:text-marka-400">
            Şirket adresimi hatırlamıyorum
        </a>
    </p>

<p class="mt-4 text-center text-sm text-slate-500 dark:text-slate-400">
    Hesabınız yok mu?
    <a href="{{ route('kayit') }}" class="font-medium text-marka-600 hover:underline dark:text-marka-400">
        Şirketinizi kaydedin
    </a>
</p>
@endsection
