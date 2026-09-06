@extends('layouts.app')

@section('baslik', config('app.name'))

@section('icerik')
<div class="kart">
    <h1>{{ config('app.name') }}</h1>
    <p class="alt">
        Her şirket kendi adresinde, kendi verisiyle çalışır.
    </p>

    <a href="{{ route('kayit') }}"><button type="button">Şirketinizi kaydedin</button></a>

    <div class="baglantilar kucuk">
        Zaten hesabınız var mı? Şirketinizin adresinden giriş yapın:
        <br><strong>sirketiniz.{{ config('tenancy.base_domain') }}</strong>
    </div>
</div>
@endsection
