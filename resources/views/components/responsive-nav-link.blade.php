@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-teal-500 text-start text-base font-semibold text-sky-900 bg-sky-50 focus:outline-none focus:text-sky-950 focus:bg-sky-100 focus:border-teal-700 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-slate-600 hover:text-sky-900 hover:bg-sky-50 hover:border-teal-300 focus:outline-none focus:text-sky-900 focus:bg-sky-50 focus:border-teal-300 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
