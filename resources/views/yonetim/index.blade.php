@extends('layouts.app')

@section('baslik', 'Kiracılar')
@section('genislik', 'max-w-5xl')

@section('icerik')
<div class="mb-6 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-xl font-semibold tracking-tight">Kiracılar</h1>
        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
            {{ $tenants->total() }} kayıt
            @foreach ($sayilar as $deger => $adet)
                @if ($tip = \App\Tenancy\TenantStatus::tryFrom($deger))
                    · {{ $tip->label() }}: {{ $adet }}
                @endif
            @endforeach
        </p>
    </div>

    <form method="POST" action="{{ route('yonetim.cikis') }}">
        @csrf
        <x-button type="submit" variant="ghost">Çıkış</x-button>
    </form>
</div>

@if (session('durum'))
    <x-notice tone="basari">{{ session('durum') }}</x-notice>
@endif

<x-card class="mb-4 !p-4">
    <form method="GET" action="{{ route('yonetim.index') }}" class="flex flex-wrap gap-3">
        <input type="text" name="q" value="{{ $arama }}" placeholder="Ad veya adres ara"
               class="min-w-48 flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-2 focus:outline-offset-[-1px] focus:outline-indigo-500 dark:border-slate-700 dark:bg-slate-900">

        <select name="durum"
                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
            <option value="">Tüm durumlar</option>
            @foreach (\App\Tenancy\TenantStatus::cases() as $secenek)
                <option value="{{ $secenek->value }}" @selected($durum === $secenek->value)>
                    {{ $secenek->label() }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
            Filtrele
        </button>
    </form>
</x-card>

<x-card class="!p-0 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-200 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                <tr>
                    <th class="px-5 py-3 font-medium">Kiracı</th>
                    <th class="px-5 py-3 font-medium">Durum</th>
                    <th class="px-5 py-3 font-medium">Bitiş</th>
                    <th class="px-5 py-3 font-medium">Kullanıcı</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($tenants as $tenant)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="px-5 py-3">
                            <div class="font-medium">{{ $tenant->name }}</div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $tenant->host() }}</div>
                        </td>
                        <td class="px-5 py-3"><x-status-badge :status="$tenant->status" /></td>
                        <td class="px-5 py-3 text-slate-500 dark:text-slate-400">
                            @if ($tenant->paid_until)
                                {{ $tenant->paid_until->format('d.m.Y') }}
                            @elseif ($tenant->trial_ends_at)
                                {{ $tenant->trial_ends_at->format('d.m.Y') }} <span class="text-xs">(deneme)</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-5 py-3 text-slate-500 dark:text-slate-400">{{ $tenant->users_count }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('yonetim.detay', $tenant) }}"
                               class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">Aç</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-slate-500 dark:text-slate-400">
                            Kayıt bulunamadı.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

@if ($tenants->hasPages())
    <div class="mt-4">{{ $tenants->onEachSide(1)->links() }}</div>
@endif
@endsection
