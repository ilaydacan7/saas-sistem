<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('baslik', config('app.name')) — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-white font-sans text-marka-900 antialiased dark:bg-marka-950 dark:text-slate-100">

<div class="flex min-h-full">

    <div class="hidden w-1/2 flex-col justify-between bg-marka-900 p-12 text-white lg:flex">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-marka-600">
                <x-ikon name="kalkan" class="h-5 w-5" />
            </span>
            <span class="text-xl font-semibold tracking-tight">{{ config('app.name') }}</span>
        </div>

        <div class="max-w-md">
            <h2 class="text-4xl leading-tight font-bold tracking-tight">
                Farklı işletmelerin ortak ihtiyaçlarını tek yerden yönetin.
            </h2>

            <ul class="mt-10 space-y-5">
                @foreach ([
                    ['kutu', 'Stok ve envanter takibi'],
                    ['kisiler', 'Müşteri ve cari hesap yönetimi'],
                    ['ekip', 'Ekip, roller ve yetkilendirme'],
                ] as [$ikon, $metin])
                    <li class="flex items-center gap-4">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white/10">
                            <x-ikon :name="$ikon" class="h-5 w-5" />
                        </span>
                        <span class="text-[15px] text-slate-200">{{ $metin }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <p class="text-sm text-slate-400">© {{ date('Y') }} {{ config('app.name') }}</p>
    </div>

    <div class="flex w-full items-center justify-center px-5 py-12 lg:w-1/2">
        <div class="w-full max-w-sm">
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-marka-600 text-white">
                    <x-ikon name="kalkan" class="h-5 w-5" />
                </span>
                <span class="text-xl font-semibold tracking-tight">{{ config('app.name') }}</span>
            </div>

            @yield('icerik')
        </div>
    </div>
</div>
</body>
</html>
