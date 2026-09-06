@extends('layouts.app')

@section('baslik', $tenant->name.' — Panel')
@section('genislik', 'max-w-2xl')

@section('icerik')
@if (session('durum'))
    <x-notice tone="basari">{{ session('durum') }}</x-notice>
@endif

<x-card>
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold tracking-tight">{{ $tenant->name }}</h1>
            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                {{ auth()->user()->name }} olarak giriş yapıldı
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if (auth()->user()->canManageTeam())
                <a href="{{ route('ekip.index') }}">
                    <x-button type="button" variant="ghost">Ekip</x-button>
                </a>
            @endif

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <x-button type="submit" variant="ghost">Çıkış</x-button>
            </form>
        </div>
    </div>

    @if ($tenant->status === \App\Tenancy\TenantStatus::Trialing && $kalanGun !== null)
        <x-notice tone="uyari">
            Deneme sürenizin bitmesine <strong class="font-semibold">{{ $kalanGun }} gün</strong> kaldı
            ({{ $tenant->trial_ends_at->format('d.m.Y') }}).
        </x-notice>
    @endif

    <dl class="divide-y divide-slate-200 text-sm dark:divide-slate-800">
        @foreach ([
            'Adres' => $tenant->host(),
            'Durum' => $tenant->status->label(),
            'Rolünüz' => auth()->user()->role->label(),
            'Kullanıcı sayısı' => $tenant->users()->count(),
            'Oluşturulma' => $tenant->created_at->format('d.m.Y H:i'),
        ] as $baslik => $deger)
            <div class="flex justify-between gap-4 py-2.5">
                <dt class="text-slate-500 dark:text-slate-400">{{ $baslik }}</dt>
                <dd class="font-medium">{{ $deger }}</dd>
            </div>
        @endforeach
    </dl>
</x-card>
@endsection
