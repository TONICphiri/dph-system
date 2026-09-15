@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex min-h-[44px] items-center rounded-xl bg-dhp-600 px-3.5 text-sm font-bold text-white shadow-sm transition'
            : 'inline-flex min-h-[44px] items-center rounded-xl px-3.5 text-sm font-semibold text-slate-600 transition hover:bg-dhp-50 hover:text-dhp-900';
@endphp

<a {{ $attributes->merge(['class' => $classes, 'aria-current' => ($active ?? false) ? 'page' : null]) }}>
    {{ $slot }}
</a>
