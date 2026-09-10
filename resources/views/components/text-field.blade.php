@props([
    'name',
    'label',
    'type' => 'text',
    'hint' => null,
    'value' => null,
    'suffix' => null,
    'required' => false,
    'autofocus' => false,
])

@php
    $hata = $errors->first($name);
    $parola = $type === 'password';
@endphp

<div class="mb-4">
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">
        {{ $label }}
    </label>

    <div class="relative flex">
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ $parola ? '' : old($name, $value) }}"
            @required($required)
            @if ($autofocus) autofocus @endif
            {{ $attributes->class([
                'w-full rounded-lg border px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400',
                'bg-white dark:bg-slate-900 dark:text-slate-100',
                'focus:outline-2 focus:outline-offset-[-1px] focus:outline-indigo-500',
                'border-slate-300 dark:border-slate-700' => ! $hata,
                'border-red-500 dark:border-red-500' => $hata,
                'rounded-r-none' => $suffix,
                'pr-10' => $parola,
            ]) }}>

        @if ($parola)
            {{-- Parolayı göster/gizle: yalnızca görüntüleme, alanın adı ve değeri değişmez. --}}
            <button type="button"
                    data-parola-goster="{{ $name }}"
                    aria-controls="{{ $name }}"
                    aria-pressed="false"
                    aria-label="Parolayı göster"
                    title="Parolayı göster"
                    class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 transition hover:text-slate-600 dark:hover:text-slate-200">
                <svg data-durum="kapali" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                     class="h-5 w-5" aria-hidden="true">
                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z" />
                    <circle cx="12" cy="12" r="3" />
                </svg>

                <svg data-durum="acik" hidden xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                     class="h-5 w-5" aria-hidden="true">
                    <path d="M10.7 5.1A10.9 10.9 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-2.4 3.4" />
                    <path d="M6.6 6.6A17.4 17.4 0 0 0 2 12s3.6 7 10 7a10.7 10.7 0 0 0 5.4-1.4" />
                    <path d="M9.9 9.9a3 3 0 0 0 4.2 4.2" />
                    <path d="m2 2 20 20" />
                </svg>
            </button>
        @endif

        @if ($suffix)
            <span class="inline-flex items-center rounded-r-lg border border-l-0 border-slate-300 bg-slate-50 px-3 text-sm whitespace-nowrap text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400">
                {{ $suffix }}
            </span>
        @endif
    </div>

    @if ($hint && ! $hata)
        <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif

    @if ($hata)
        <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $hata }}</p>
    @endif
</div>
