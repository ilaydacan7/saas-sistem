@extends('layouts.app')

@section('baslik', $tenant->name)
@section('genislik', 'max-w-3xl')

@section('icerik')
<div class="mb-6 flex items-start justify-between gap-4">
    <div>
        <a href="{{ route('yonetim.index') }}" class="text-sm text-indigo-600 hover:underline dark:text-indigo-400">
            ← Kiracılar
        </a>
        <h1 class="mt-1 text-xl font-semibold tracking-tight">{{ $tenant->name }}</h1>
        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $tenant->host() }}</p>
    </div>
    <x-status-badge :status="$tenant->status" class="mt-1" />
</div>

@if (session('durum'))
    <x-notice tone="basari">{{ session('durum') }}</x-notice>
@endif

@if ($errors->any())
    <x-notice tone="uyari">{{ $errors->first() }}</x-notice>
@endif

<x-card class="mb-4">
    <dl class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
        @foreach ([
            'Adres' => $tenant->host(),
            'Kullanıcı sayısı' => $tenant->users_count,
            'Deneme bitişi' => $tenant->trial_ends_at?->format('d.m.Y') ?? '—',
            'Ödenmiş dönem sonu' => $tenant->paid_until?->format('d.m.Y') ?? '—',
            'Kayıt tarihi' => $tenant->created_at->format('d.m.Y H:i'),
        ] as $baslik => $deger)
            <div class="flex justify-between gap-4 py-2.5">
                <dt class="text-slate-500 dark:text-slate-400">{{ $baslik }}</dt>
                <dd class="font-medium">{{ $deger }}</dd>
            </div>
        @endforeach
    </dl>
</x-card>

<x-card class="mb-4">
    <h2 class="mb-4 text-base font-semibold">Ödeme kaydet</h2>

    <form method="POST" action="{{ route('yonetim.odeme', $tenant) }}">
        @csrf
        <div class="grid gap-x-4 sm:grid-cols-2">
            <x-text-field name="tutar" label="Tutar" suffix="{{ config('billing.currency') }}" required />
            <x-text-field name="ay" label="Süre" suffix="ay" hint="Ödenmiş dönem bu kadar uzar." required />
        </div>
        <x-text-field name="not" label="Not" hint="Örneğin havale referansı. Zorunlu değil." />
        <x-button type="submit">Ödemeyi kaydet</x-button>
    </form>
</x-card>

<x-card class="mb-4">
    <h2 class="mb-4 text-base font-semibold">İşlemler</h2>

    <div class="flex flex-wrap items-end gap-3">
        @if ($tenant->status === \App\Tenancy\TenantStatus::Suspended)
            <form method="POST" action="{{ route('yonetim.aktiflestir', $tenant) }}">
                @csrf
                <x-button type="submit" variant="ghost">Askıyı kaldır</x-button>
            </form>
        @else
            <form method="POST" action="{{ route('yonetim.askiya-al', $tenant) }}"
                  onsubmit="return confirm('{{ $tenant->name }} askıya alınacak ve kullanıcıları sisteme giremeyecek. Onaylıyor musunuz?')">
                @csrf
                <x-button type="submit" variant="ghost">Askıya al</x-button>
            </form>
        @endif

        <form method="POST" action="{{ route('yonetim.deneme-uzat', $tenant) }}" class="flex items-end gap-2">
            @csrf
            <div class="w-28">
                <label for="gun" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">Gün</label>
                <input id="gun" name="gun" type="number" min="1" max="365" value="14" required
                       class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
            </div>
            <x-button type="submit" variant="ghost">Denemeyi uzat</x-button>
        </form>
    </div>
</x-card>

<x-card class="!p-0 overflow-hidden">
    <h2 class="border-b border-slate-100 px-7 py-4 text-base font-semibold dark:border-slate-800">Ödeme geçmişi</h2>

    <table class="w-full text-left text-sm">
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse ($payments as $payment)
                <tr>
                    <td class="px-7 py-3 font-medium">{{ $payment->formattedAmount() }}</td>
                    <td class="px-7 py-3 text-slate-500 dark:text-slate-400">
                        {{ $payment->period_starts_at->format('d.m.Y') }} – {{ $payment->period_ends_at->format('d.m.Y') }}
                    </td>
                    <td class="px-7 py-3 text-slate-500 dark:text-slate-400">
                        {{ $payment->recordedBy?->name ?? 'sistem' }}
                        @if ($payment->note)
                            <span class="block text-xs">{{ $payment->note }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="px-7 py-8 text-center text-slate-500 dark:text-slate-400">Henüz ödeme kaydı yok.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</x-card>
@endsection
