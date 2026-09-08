@extends('layouts.app')

@section('baslik', 'Yeni parola')

@section('icerik')
<x-card>
    <h1 class="text-xl font-semibold tracking-tight">Yeni parolanızı belirleyin</h1>
    <p class="mt-1.5 mb-6 text-sm text-slate-500 dark:text-slate-400">
        {{ $tenant->name }} hesabınız için yeni bir parola girin.
    </p>

    <form method="POST" action="{{ route('parola.sifirla', ['token' => $token]) }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-4">
            <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">E-posta</label>
            <input id="email" name="email" type="email" required value="{{ old('email', $email) }}"
                   class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-2 focus:outline-offset-[-1px] focus:outline-indigo-500 dark:border-slate-700 dark:bg-slate-900">
            @error('email')<p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>

        <x-text-field
            name="password"
            label="Yeni parola"
            type="password"
            hint="En az 8 karakter, harf ve rakam içermeli."
            required />

        <x-text-field name="password_confirmation" label="Yeni parola tekrar" type="password" required />

        <x-button type="submit">Parolayı güncelle</x-button>
    </form>
</x-card>
@endsection
