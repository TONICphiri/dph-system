@props(['level' => 'Low'])

@php
$class = match ($level) {
    'Emergency' => 'badge-emergency',
    'High' => 'badge-high',
    'Medium' => 'badge-medium',
    default => 'badge-low',
};
@endphp

<span {{ $attributes->merge(['class' => $class]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
    {{ $level }}
</span>
