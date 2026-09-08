@extends('layouts.giris')

@section('baslik', $tenant->name.' ekibine katıl')

@section('icerik')
    <h1 class="text-xl font-semibold tracking-tight">{{ $tenant->name }} ekibine katılın</h1>
    <p class="mt-1.5 mb-6 text-sm text-slate-500 dark:text-slate-400">
        <strong class="font-medium text-slate-700 dark:text-slate-300">{{ $davet->email }}</strong>
        adresi {{ $davet->role->label() }} olarak davet edildi. Hesabınızı oluşturmak için adınızı ve bir parola belirleyin.
    </p>

    <form method="POST" action="{{ route('davet.kabul', ['token' => $davet->token]) }}">
        @csrf

        <x-text-field name="name" label="Ad soyad" required autofocus />

        <x-text-field
            name="password"
            label="Parola"
            type="password"
            hint="En az 8 karakter, harf ve rakam içermeli."
            required />

        <x-text-field name="password_confirmation" label="Parola tekrar" type="password" required />

        <x-button type="submit">Hesabımı oluştur</x-button>
    </form>
@endsection
