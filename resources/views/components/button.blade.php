@props(['variant' => 'primary'])

<button {{ $attributes->class([
    'rounded-lg text-sm font-semibold transition focus:outline-2 focus:outline-offset-2 focus:outline-indigo-500',
    'w-full bg-indigo-600 px-4 py-2.5 text-white hover:bg-indigo-500' => $variant === 'primary',
    'w-full border border-indigo-600 px-4 py-2.5 text-indigo-600 hover:bg-indigo-50 dark:border-indigo-400 dark:text-indigo-400 dark:hover:bg-indigo-950' => $variant === 'secondary',
    'border border-slate-300 px-3.5 py-1.5 text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800' => $variant === 'ghost',
]) }}>
    {{ $slot }}
</button>
