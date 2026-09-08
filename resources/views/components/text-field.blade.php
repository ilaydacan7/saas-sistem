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

@php($hata = $errors->first($name))

<div class="mb-4">
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-200">
        {{ $label }}
    </label>

    <div class="flex">
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ $type === 'password' ? '' : old($name, $value) }}"
            @required($required)
            @if ($autofocus) autofocus @endif
            {{ $attributes->class([
                'w-full rounded-lg border px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400',
                'bg-white dark:bg-slate-900 dark:text-slate-100',
                'focus:outline-2 focus:outline-offset-[-1px] focus:outline-marka-500',
                'border-slate-300 dark:border-slate-700' => ! $hata,
                'border-red-500 dark:border-red-500' => $hata,
                'rounded-r-none' => $suffix,
            ]) }}>

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
