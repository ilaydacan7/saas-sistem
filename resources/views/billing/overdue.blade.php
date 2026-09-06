@extends('layouts.app')

@section('baslik', 'Ödeme gerekli')

@section('icerik')
<div class="kart">
    <h1>Ödemeniz görünmüyor</h1>
    <p class="alt">
        Hesabınız ödeme gecikmesi nedeniyle sınırlandırıldı. Verileriniz duruyor;
        ödemeniz alındığında tüm ekranlara erişiminiz otomatik olarak geri açılır.
    </p>

    @auth
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="cikis">Çıkış yap</button>
        </form>
    @endauth
</div>
@endsection
