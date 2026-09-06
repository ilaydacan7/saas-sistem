@extends('layouts.app')

@section('baslik', 'Şirket kaydı')

@section('icerik')
<x-card>
    <h1 class="text-xl font-semibold tracking-tight">Şirketinizi kaydedin</h1>
    <p class="mt-1.5 mb-6 text-sm text-slate-500 dark:text-slate-400">
        @if ($trialDays > 0)
            {{ $trialDays }} gün ücretsiz deneme. Kredi kartı istemiyoruz.
        @else
            Hesabınızı oluşturun ve hemen kullanmaya başlayın.
        @endif
    </p>

    <form method="POST" action="{{ route('kayit') }}">
        @csrf

        <x-text-field name="company" label="Şirket adı" required autofocus />

        <x-text-field
            name="slug"
            label="Adresiniz"
            suffix=".{{ $baseDomain }}"
            hint="Küçük harf, rakam ve tire. Sonradan değiştirilemez."
            required />

        <x-text-field name="name" label="Ad soyad" required />

        <x-text-field name="email" label="E-posta" type="email" required />

        <x-text-field
            name="password"
            label="Parola"
            type="password"
            hint="En az 8 karakter, harf ve rakam içermeli."
            required />

        <x-text-field name="password_confirmation" label="Parola tekrar" type="password" required />

        <x-button type="submit">Hesabı oluştur</x-button>
    </form>
</x-card>
@endsection
