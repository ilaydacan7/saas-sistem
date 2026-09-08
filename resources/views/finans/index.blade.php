@extends('layouts.uygulama')

@section('baslik', 'Gelir ve Gider')

@section('icerik')
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
        <h1 class="text-xl font-semibold tracking-tight">Gelir ve Gider</h1>
        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
            {{ $aralik['baslangic']->format('d.m.Y') }} – {{ $aralik['bitis']->format('d.m.Y') }}
        </p>
    </div>

    <div class="flex gap-2">
        <a href="{{ route('finans.create', ['tur' => 'income']) }}"
           class="rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
            Gelir ekle
        </a>
        <a href="{{ route('finans.create', ['tur' => 'expense']) }}"
           class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
            Gider ekle
        </a>
    </div>
</div>

<div class="mb-4 grid gap-4 sm:grid-cols-3">
    @foreach ([
        ['Gelir', $ozet['gelir'], 'text-emerald-700 dark:text-emerald-400'],
        ['Gider', $ozet['gider'], 'text-red-600 dark:text-red-400'],
        ['Net', $ozet['net'], $ozet['net'] >= 0 ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'],
    ] as [$etiket, $tutar, $renk])
        <x-card class="!p-5">
            <div class="text-sm text-slate-500 dark:text-slate-400">{{ $etiket }}</div>
            <div class="mt-2 text-2xl font-semibold tracking-tight {{ $renk }}">
                @if ($etiket === 'Net' && $tutar < 0) − @endif<x-para :kurus="abs($tutar)" />
            </div>
        </x-card>
    @endforeach
</div>

<x-card class="mb-4 !p-4">
    <form method="GET" action="{{ route('finans.index') }}" class="flex flex-wrap gap-3">
        <select name="donem" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
            @foreach (['ay' => 'Bu ay', 'gecenay' => 'Geçen ay', 'hafta' => 'Bu hafta', 'yil' => 'Bu yıl'] as $anahtar => $etiket)
                <option value="{{ $anahtar }}" @selected($aralik['anahtar'] === $anahtar)>{{ $etiket }}</option>
            @endforeach
        </select>

        <select name="tur" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
            <option value="">Gelir ve gider</option>
            @foreach (\App\Modules\TransactionType::cases() as $secenek)
                <option value="{{ $secenek->value }}" @selected($tur === $secenek)>Sadece {{ mb_strtolower($secenek->label()) }}</option>
            @endforeach
        </select>

        <select name="kategori" class="min-w-40 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-900">
            <option value="">Tüm kategoriler</option>
            @foreach ($kategoriler as $kategori)
                <option value="{{ $kategori->id }}" @selected($kategoriId === $kategori->id)>
                    {{ $kategori->name }} ({{ $kategori->type->label() }})
                </option>
            @endforeach
        </select>

        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700 dark:bg-slate-100 dark:text-slate-900">
            Filtrele
        </button>
    </form>
</x-card>

<div class="grid gap-4 lg:grid-cols-3">
    <x-card class="!p-0 overflow-hidden lg:col-span-2">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                    <tr>
                        <th class="px-5 py-3 font-medium">Tarih</th>
                        <th class="px-5 py-3 font-medium">Açıklama</th>
                        <th class="px-5 py-3 font-medium">Kategori</th>
                        <th class="px-5 py-3 text-right font-medium">Tutar</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($transactions as $islem)
                        @php $gelir = $islem->type === \App\Modules\TransactionType::Income; @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <td class="px-5 py-3 whitespace-nowrap text-slate-500 dark:text-slate-400">
                                {{ $islem->occurred_on->format('d.m.Y') }}
                            </td>
                            <td class="px-5 py-3">
                                {{ $islem->description ?: ($gelir ? 'Gelir' : 'Gider') }}
                                @if ($islem->isAutomatic())
                                    <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                        satıştan
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-500 dark:text-slate-400">{{ $islem->category?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-right font-medium {{ $gelir ? 'text-emerald-700 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $gelir ? '+' : '−' }}<x-para :kurus="$islem->amount_minor" />
                            </td>
                            <td class="px-5 py-3 text-right">
                                @unless ($islem->isAutomatic())
                                    <a href="{{ route('finans.edit', $islem) }}"
                                       class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">Düzenle</a>
                                @endunless
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-500 dark:text-slate-400">
                                Bu dönemde kayıt yok.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

    <x-card class="!p-0 overflow-hidden">
        <h2 class="border-b border-slate-100 px-6 py-4 text-base font-semibold dark:border-slate-800">Gider dağılımı</h2>

        <ul class="divide-y divide-slate-50 dark:divide-slate-800/60">
            @forelse ($kategoriDagilimi as $satir)
                @php $oran = $ozet['gider'] > 0 ? round($satir['tutar'] / $ozet['gider'] * 100) : 0; @endphp
                <li class="px-6 py-3">
                    <div class="flex items-center justify-between gap-3 text-sm">
                        <span>{{ $satir['ad'] }}</span>
                        <span class="font-medium"><x-para :kurus="$satir['tutar']" /></span>
                    </div>
                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                        <div class="h-full rounded-full bg-slate-400 dark:bg-slate-500" style="width: {{ $oran }}%"></div>
                    </div>
                </li>
            @empty
                <li class="px-6 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                    Bu dönemde gider yok.
                </li>
            @endforelse
        </ul>
    </x-card>
</div>

@if ($transactions->hasPages())
    <div class="mt-4">{{ $transactions->onEachSide(1)->links() }}</div>
@endif
@endsection
