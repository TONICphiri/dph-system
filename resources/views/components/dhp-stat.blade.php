@props(['label', 'value', 'hint' => null, 'tone' => 'default'])

@php
$valueClass = match ($tone) {
    'danger' => 'mt-1 text-3xl font-extrabold tabular-nums text-rose-700',
    'warning' => 'mt-1 text-3xl font-extrabold tabular-nums text-amber-700',
    'success' => 'mt-1 text-3xl font-extrabold tabular-nums text-emerald-700',
    default => 'dhp-stat-value',
};
@endphp

<div {{ $attributes->merge(['class' => 'dhp-stat']) }}>
    <p class="dhp-stat-label">{{ $label }}</p>
    <p class="{{ $valueClass }}">{{ $value }}</p>
    @if($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
