@extends('layouts.app')

@section('baslik', 'Ödeme gerekli')

@section('icerik')
<x-card>
    <h1 class="text-xl font-semibold tracking-tight">Ödemeniz görünmüyor</h1>
    <p class="mt-1.5 text-sm text-slate-500 dark:text-slate-400">
        Hesabınız ödeme gecikmesi nedeniyle sınırlandırıldı. Verileriniz duruyor;
        ödemeniz alındığında tüm ekranlara erişiminiz otomatik olarak geri açılır.
    </p>

    @auth
        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <x-button type="submit" variant="ghost">Çıkış yap</x-button>
        </form>
    @endauth
</x-card>
@endsection
