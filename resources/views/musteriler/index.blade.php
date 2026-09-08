@extends('layouts.uygulama')

@section('baslik', 'Müşteriler')

@section('icerik')
<div class="mb-5 flex items-center justify-between gap-4">
    <div>
        <h1 class="text-xl font-semibold tracking-tight">Müşteriler</h1>
        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
            {{ $customers->total() }} kayıt listeleniyor
            @if ($toplam !== $customers->total())
                · toplam {{ $toplam }}
            @endif
        </p>
    </div>

    <a href="{{ route('musteriler.create') }}"
       class="rounded-lg bg-marka-600 px-4 py-2 text-sm font-semibold text-white hover:bg-marka-500">
        Yeni müşteri
    </a>
</div>

<x-card class="mb-4 !p-4">
    <form method="GET" action="{{ route('musteriler.index') }}" class="flex flex-wrap gap-3">
        <input type="text" name="q" value="{{ $arama }}" placeholder="Ad, firma, telefon, e-posta veya vergi no"
               class="min-w-56 flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm focus:outline-2 focus:outline-offset-[-1px] focus:outline-marka-500 dark:border-slate-700 dark:bg-slate-900">

        <select name="tur" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
            <option value="">Tüm türler</option>
            @foreach (\App\Modules\CustomerType::cases() as $secenek)
                <option value="{{ $secenek->value }}" @selected($tur === $secenek)>{{ $secenek->label() }}</option>
            @endforeach
        </select>

        <select name="durum" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
            <option value="">Aktifler</option>
            <option value="pasif" @selected($durum === 'pasif')>Pasifler</option>
        </select>

        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 dark:bg-slate-100 dark:text-slate-900">
            Filtrele
        </button>
    </form>
</x-card>

<x-card class="!p-0 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-200 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                <tr>
                    <th class="px-5 py-3 font-medium">Müşteri</th>
                    <th class="px-5 py-3 font-medium">Tür</th>
                    <th class="px-5 py-3 font-medium">İletişim</th>
                    <th class="px-5 py-3 font-medium">Şehir</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($customers as $customer)
                    <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                        <td class="px-5 py-3">
                            <div class="font-medium">{{ $customer->displayName() }}</div>
                            @if ($customer->type === \App\Modules\CustomerType::Company && $customer->name)
                                <div class="text-xs text-slate-500 dark:text-slate-400">Yetkili: {{ $customer->name }}</div>
                            @endif
                            @unless ($customer->is_active)
                                <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">Pasif</span>
                            @endunless
                        </td>
                        <td class="px-5 py-3 text-slate-500 dark:text-slate-400">{{ $customer->type->label() }}</td>
                        <td class="px-5 py-3 text-slate-500 dark:text-slate-400">
                            @if ($customer->phone)<div>{{ $customer->phone }}</div>@endif
                            @if ($customer->email)<div class="text-xs">{{ $customer->email }}</div>@endif
                            @if (! $customer->phone && ! $customer->email)—@endif
                        </td>
                        <td class="px-5 py-3 text-slate-500 dark:text-slate-400">{{ $customer->city ?: '—' }}</td>
                        <td class="px-5 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('musteriler.edit', $customer) }}"
                               class="font-medium text-marka-600 hover:underline dark:text-marka-400">Düzenle</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-12 text-center text-slate-500 dark:text-slate-400">
                            @if ($arama !== '')
                                "{{ $arama }}" için kayıt bulunamadı.
                            @else
                                Henüz müşteri eklenmemiş.
                                <a href="{{ route('musteriler.create') }}" class="text-marka-600 hover:underline dark:text-marka-400">İlkini ekleyin.</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-card>

@if ($customers->hasPages())
    <div class="mt-4">{{ $customers->onEachSide(1)->links() }}</div>
@endif
@endsection
