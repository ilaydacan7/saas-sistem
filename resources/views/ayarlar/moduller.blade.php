@extends('layouts.uygulama')

@section('baslik', 'Modüller')

@section('icerik')
<div class="mb-5">
    <h1 class="text-xl font-semibold tracking-tight">Modüller</h1>
    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
        İşinize yarayanları açın. Kapalı modüller menüde görünmez.
    </p>
</div>

@error('module')<x-notice tone="uyari">{{ $message }}</x-notice>@enderror

<div class="space-y-3">
    @foreach ($moduller as $modul)
        @php $acik = $tenant->hasModule($modul); @endphp

        <x-card class="flex items-start justify-between gap-4 !p-5">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <h2 class="font-medium">{{ $modul->label() }}</h2>

                    @if ($modul->isCore())
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            Temel
                        </span>
                    @elseif (! $modul->isAvailable())
                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-xs text-amber-800 dark:bg-amber-950 dark:text-amber-300">
                            Yakında
                        </span>
                    @endif
                </div>

                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $modul->description() }}</p>
            </div>

            <div class="shrink-0">
                @if (! $modul->isAvailable())
                    <span class="text-sm text-slate-400">—</span>
                @elseif ($modul->isCore())
                    <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Açık</span>
                @else
                    <form method="POST" action="{{ route('ayarlar.moduller.guncelle') }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="module" value="{{ $modul->value }}">
                        <input type="hidden" name="acik" value="{{ $acik ? 0 : 1 }}">
                        <button type="submit"
                                class="rounded-lg border px-3.5 py-1.5 text-sm font-medium
                                       {{ $acik
                                          ? 'border-slate-300 text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800'
                                          : 'border-marka-600 bg-marka-600 text-white hover:bg-marka-500' }}">
                            {{ $acik ? 'Kapat' : 'Aç' }}
                        </button>
                    </form>
                @endif
            </div>
        </x-card>
    @endforeach
</div>
@endsection
