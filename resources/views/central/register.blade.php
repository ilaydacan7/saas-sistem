@extends('layouts.app')

@section('baslik', 'Şirket kaydı')

@section('icerik')
<div class="kart">
    <h1>Şirketinizi kaydedin</h1>
    <p class="alt">
        @if ($trialDays > 0)
            {{ $trialDays }} gün ücretsiz deneme. Kredi kartı istemiyoruz.
        @else
            Hesabınızı oluşturun ve hemen kullanmaya başlayın.
        @endif
    </p>

    <form method="POST" action="{{ route('kayit') }}">
        @csrf

        <div class="alan">
            <label for="company">Şirket adı</label>
            <input id="company" name="company" type="text" value="{{ old('company') }}" required autofocus>
            @error('company')<div class="hata">{{ $message }}</div>@enderror
        </div>

        <div class="alan">
            <label for="slug">Adresiniz</label>
            <div class="adres">
                <input id="slug" name="slug" type="text" value="{{ old('slug') }}" required>
                <span class="son">.{{ $baseDomain }}</span>
            </div>
            <div class="ipucu">Küçük harf, rakam ve tire. Sonradan değiştirilemez.</div>
            @error('slug')<div class="hata">{{ $message }}</div>@enderror
        </div>

        <div class="alan">
            <label for="name">Ad soyad</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required>
            @error('name')<div class="hata">{{ $message }}</div>@enderror
        </div>

        <div class="alan">
            <label for="email">E-posta</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required>
            @error('email')<div class="hata">{{ $message }}</div>@enderror
        </div>

        <div class="alan">
            <label for="password">Parola</label>
            <input id="password" name="password" type="password" required>
            <div class="ipucu">En az 8 karakter, harf ve rakam içermeli.</div>
            @error('password')<div class="hata">{{ $message }}</div>@enderror
        </div>

        <div class="alan">
            <label for="password_confirmation">Parola tekrar</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required>
        </div>

        <button type="submit">Hesabı oluştur</button>
    </form>
</div>
@endsection
