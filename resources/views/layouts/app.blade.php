<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('baslik', config('app.name')) — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
    <div class="mx-auto @yield('genislik', 'max-w-md') px-5 py-12">
        <a href="{{ route('home') }}" class="mb-6 flex items-center justify-center gap-2.5">
            <x-marka-simge class="h-9 w-9" />
            <span class="text-xl font-semibold tracking-tight">{{ config('app.name') }}</span>
        </a>

        @yield('icerik')
    </div>
</body>
</html>
