@extends('layouts.app')

@section('baslik', $tenant->name.' — Panel')
@section('kabuk', 'genis')

@section('icerik')
<div class="kart">
    <div class="satir" style="margin-bottom:20px">
        <div>
            <h1 style="margin-bottom:2px">{{ $tenant->name }}</h1>
            <div class="kucuk">{{ auth()->user()->name }} olarak giriş yapıldı</div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="cikis">Çıkış</button>
        </form>
    </div>

    @if ($tenant->status === \App\Tenancy\TenantStatus::Trialing && $kalanGun !== null)
        <div class="kutu">
            Deneme sürenizin bitmesine <strong>{{ $kalanGun }} gün</strong> kaldı
            ({{ $tenant->trial_ends_at->format('d.m.Y') }}).
        </div>
    @endif

    <table>
        <tr><td>Adres</td><td>{{ $tenant->host() }}</td></tr>
        <tr><td>Durum</td><td>{{ $tenant->status->label() }}</td></tr>
        <tr><td>Kullanıcı sayısı</td><td>{{ $tenant->users()->count() }}</td></tr>
        <tr><td>Oluşturulma</td><td>{{ $tenant->created_at->format('d.m.Y H:i') }}</td></tr>
    </table>
</div>
@endsection
