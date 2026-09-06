@extends('layouts.uygulama')

@section('baslik', $tenant->name.' — Panel')

@section('icerik')
<div class="mb-5">
    <h1 class="text-xl font-semibold tracking-tight">Panel</h1>
    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
        {{ auth()->user()->name }} olarak giriş yapıldı
    </p>
</div>

@if ($tenant->status === \App\Tenancy\TenantStatus::Trialing && $kalanGun !== null)
    <x-notice tone="uyari">
        Deneme sürenizin bitmesine <strong class="font-semibold">{{ $kalanGun }} gün</strong> kaldı
        ({{ $tenant->trial_ends_at->format('d.m.Y') }}).
    </x-notice>
@endif

@if ($moduller !== [])
    <div class="mb-4 grid gap-3 sm:grid-cols-2">
        @foreach ($moduller as $modul)
            <a href="{{ route($modul->routeName()) }}"
               class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-700">
                <div class="font-medium">{{ $modul->label() }}</div>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $modul->description() }}</p>
            </a>
        @endforeach
    </div>
@endif

<x-card>
    <h2 class="mb-4 text-base font-semibold">Hesap</h2>

    <dl class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
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
