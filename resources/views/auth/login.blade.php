@extends('layouts.app')

@section('baslik', $tenant->name.' — Giriş')

@section('icerik')
<x-card>
    @if ($justRegistered)
        <x-notice tone="basari">
            Hesabınız hazır. Kayıt sırasında belirlediğiniz bilgilerle giriş yapın.
        </x-notice>
    @endif

    <h1 class="text-xl font-semibold tracking-tight">{{ $tenant->name }}</h1>
    <p class="mt-1.5 mb-6 text-sm text-slate-500 dark:text-slate-400">
        {{ $tenant->host() }} hesabınıza giriş yapın.
    </p>

    <form method="POST" action="{{ route('giris') }}">
        @csrf

        <x-text-field name="email" label="E-posta" type="email" required autofocus />

        <x-text-field name="password" label="Parola" type="password" required />

        <label class="mb-5 flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
            <input type="checkbox" name="remember" value="1"
                   class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800">
            Beni hatırla
        </label>

        <x-button type="submit">Giriş yap</x-button>
    </form>
</x-card>
@endsection
