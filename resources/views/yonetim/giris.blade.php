@extends('layouts.app')

@section('baslik', 'Yönetim girişi')

@section('icerik')
<x-card>
    <h1 class="text-xl font-semibold tracking-tight">Yönetim</h1>
    <p class="mt-1.5 mb-6 text-sm text-slate-500 dark:text-slate-400">
        Sistem yöneticisi hesabınızla giriş yapın.
    </p>

    <form method="POST" action="{{ route('yonetim.giris') }}">
        @csrf

        <x-text-field name="email" label="E-posta" type="email" required autofocus />
        <x-text-field name="password" label="Parola" type="password" required />

        <x-button type="submit">Giriş yap</x-button>
    </form>
</x-card>
@endsection
