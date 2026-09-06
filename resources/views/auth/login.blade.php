@extends('layouts.app')

@section('baslik', $tenant->name.' — Giriş')

@section('icerik')
<div class="kart">
    @if ($justRegistered)
        <div class="kutu">
            Hesabınız hazır. Kayıt sırasında belirlediğiniz bilgilerle giriş yapın.
        </div>
    @endif

    <h1>{{ $tenant->name }}</h1>
    <p class="alt">{{ $tenant->host() }} hesabınıza giriş yapın.</p>

    <form method="POST" action="{{ route('giris') }}">
        @csrf

        <div class="alan">
            <label for="email">E-posta</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            @error('email')<div class="hata">{{ $message }}</div>@enderror
        </div>

        <div class="alan">
            <label for="password">Parola</label>
            <input id="password" name="password" type="password" required>
            @error('password')<div class="hata">{{ $message }}</div>@enderror
        </div>

        <div class="alan">
            <label class="kucuk" style="font-weight:400">
                <input type="checkbox" name="remember" value="1"> Beni hatırla
            </label>
        </div>

        <button type="submit">Giriş yap</button>
    </form>
</div>
@endsection
