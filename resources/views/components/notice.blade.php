@props(['tone' => 'info'])

<div {{ $attributes->class([
    'mb-5 rounded-lg border px-4 py-3 text-sm',
    'border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200' => $tone === 'basari',
    'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900/60 dark:bg-amber-950 dark:text-amber-200' => $tone === 'uyari',
    'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300' => $tone === 'info',
]) }}>
    {{ $slot }}
</div>
