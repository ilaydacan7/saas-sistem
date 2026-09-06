@php
    $aktifTenant = app(\App\Tenancy\TenantContext::class)->get();
    $aktifKullanici = auth()->user();
@endphp

<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('baslik', config('app.name'))</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
<div class="mx-auto flex min-h-full max-w-6xl gap-6 px-5 py-6">

    <aside class="hidden w-56 shrink-0 md:block">
        <div class="mb-6">
            <div class="text-sm font-semibold">{{ $aktifTenant?->name }}</div>
            <div class="text-xs text-slate-500 dark:text-slate-400">{{ $aktifTenant?->host() }}</div>
        </div>

        <nav class="space-y-0.5 text-sm">
            @php
                $baglantilar = [['panel', 'Panel', true]];

                foreach ($aktifTenant?->enabledModules() ?? [] as $modul) {
                    $baglantilar[] = [$modul->routeName(), $modul->label(), true];
                }

                $baglantilar[] = ['ekip.index', 'Ekip', $aktifKullanici?->canManageTeam()];
                $baglantilar[] = ['ayarlar.moduller', 'Modüller', $aktifKullanici?->canManageTeam()];
            @endphp

            @foreach ($baglantilar as [$rota, $etiket, $gorunur])
                @continue(! $gorunur)
                @php $aktif = request()->routeIs(str_replace('.index', '.*', $rota)) || request()->routeIs($rota); @endphp
                <a href="{{ route($rota) }}"
                   class="block rounded-lg px-3 py-2 {{ $aktif
                        ? 'bg-white font-medium shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800'
                        : 'text-slate-600 hover:bg-white/60 dark:text-slate-400 dark:hover:bg-slate-900/60' }}">
                    {{ $etiket }}
                </a>
            @endforeach
        </nav>

        <div class="mt-6 border-t border-slate-200 pt-4 dark:border-slate-800">
            <div class="mb-2 px-3 text-xs text-slate-500 dark:text-slate-400">
                {{ $aktifKullanici?->name }}<br>
                <span class="text-slate-400">{{ $aktifKullanici?->role->label() }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="px-3">
                @csrf
                <button type="submit" class="text-sm text-slate-500 hover:underline dark:text-slate-400">Çıkış</button>
            </form>
        </div>
    </aside>

    <main class="min-w-0 flex-1">
        <div class="mb-4 flex flex-wrap gap-2 md:hidden">
            <a href="{{ route('panel') }}" class="rounded-lg bg-white px-3 py-1.5 text-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">Panel</a>
            @foreach ($aktifTenant?->enabledModules() ?? [] as $modul)
                <a href="{{ route($modul->routeName()) }}" class="rounded-lg bg-white px-3 py-1.5 text-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800">{{ $modul->label() }}</a>
            @endforeach
        </div>

        @if (session('durum'))
            <x-notice tone="basari">{{ session('durum') }}</x-notice>
        @endif

        @yield('icerik')
    </main>
</div>
</body>
</html>
