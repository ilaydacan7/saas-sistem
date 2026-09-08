@php
    $aktifTenant = app(\App\Tenancy\TenantContext::class)->get();
    $aktifKullanici = auth()->user();

    $menu = [['panel', 'Panel', 'panel', true]];

    foreach ($aktifTenant?->enabledModules() ?? [] as $modul) {
        $menu[] = [
            $modul->routeName(),
            $modul->label(),
            match ($modul) {
                \App\Modules\Module::Customers => 'kisiler',
                \App\Modules\Module::Inventory => 'kutu',
                default => 'grafik',
            },
            true,
        ];
    }

    $menu[] = ['ekip.index', 'Ekip', 'ekip', (bool) $aktifKullanici?->canManageTeam()];
    $menu[] = ['ayarlar.moduller', 'Ayarlar', 'ayarlar', (bool) $aktifKullanici?->canManageTeam()];

    $basHarfler = collect(explode(' ', (string) $aktifKullanici?->name))
        ->filter()
        ->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
        ->implode('');
@endphp

<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('baslik', config('app.name')) — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 font-sans text-marka-900 antialiased dark:bg-marka-950 dark:text-slate-100">

<div class="flex min-h-full">

    <aside class="fixed inset-y-0 left-0 hidden w-60 flex-col border-r border-slate-200 bg-white md:flex dark:border-marka-800 dark:bg-marka-900">
        <div class="flex items-center gap-2.5 px-5 py-5">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-marka-600 text-white">
                <x-ikon name="kalkan" class="h-5 w-5" />
            </span>
            <span class="text-lg font-semibold tracking-tight">{{ config('app.name') }}</span>
        </div>

        <nav class="flex-1 space-y-1 px-3 py-2">
            @foreach ($menu as [$rota, $etiket, $ikon, $gorunur])
                @continue(! $gorunur)
                @php
                    $aktif = request()->routeIs($rota)
                        || request()->routeIs(str_replace('.index', '.*', $rota))
                        || request()->routeIs(str_replace('.moduller', '.*', $rota));
                @endphp
                <a href="{{ route($rota) }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm transition
                          {{ $aktif
                             ? 'bg-marka-50 font-semibold text-marka-700 dark:bg-marka-800 dark:text-white'
                             : 'text-slate-600 hover:bg-slate-50 dark:text-slate-400 dark:hover:bg-marka-800/60' }}">
                    <x-ikon :name="$ikon" class="h-[18px] w-[18px] shrink-0" />
                    {{ $etiket }}
                </a>
            @endforeach
        </nav>

        <div class="space-y-3 px-3 pb-4">
            <div class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2.5 text-sm dark:border-marka-800">
                <x-ikon name="bina" class="h-4 w-4 shrink-0 text-slate-400" />
                <span class="truncate font-medium">{{ $aktifTenant?->name }}</span>
            </div>

            <div class="flex items-center gap-3 px-2">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-marka-600 text-xs font-semibold text-white">
                    {{ $basHarfler }}
                </span>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-sm font-medium">{{ $aktifKullanici?->name }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $aktifKullanici?->role->label() }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Çıkış"
                            class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-marka-700 dark:hover:bg-marka-800 dark:hover:text-white">
                        <x-ikon name="cikis" class="h-4 w-4" />
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col md:pl-60">

        <header class="sticky top-0 z-10 border-b border-slate-200 bg-white/90 backdrop-blur dark:border-marka-800 dark:bg-marka-900/90">
            <div class="flex items-center justify-between gap-4 px-5 py-4 md:px-8">
                <h1 class="text-lg font-semibold tracking-tight">@yield('baslik', 'Panel')</h1>

                <div class="flex items-center gap-2">
                    @yield('baslik-eylem')
                </div>
            </div>

            <nav class="flex gap-1 overflow-x-auto px-3 pb-3 md:hidden">
                @foreach ($menu as [$rota, $etiket, $ikon, $gorunur])
                    @continue(! $gorunur)
                    <a href="{{ route($rota) }}"
                       class="rounded-lg px-3 py-1.5 text-sm whitespace-nowrap {{ request()->routeIs(str_replace('.index', '.*', $rota))
                          ? 'bg-marka-50 font-semibold text-marka-700 dark:bg-marka-800 dark:text-white'
                          : 'text-slate-600 dark:text-slate-400' }}">
                        {{ $etiket }}
                    </a>
                @endforeach
            </nav>
        </header>

        <main class="flex-1 px-5 py-6 md:px-8">
            @if (session('durum'))
                <x-notice tone="basari">{{ session('durum') }}</x-notice>
            @endif

            @yield('icerik')
        </main>
    </div>
</div>
</body>
</html>
