@props(['type' => 'info', 'title' => null])

@php
$classes = match ($type) {
    'success' => 'dhp-alert-success',
    'error' => 'dhp-alert-error',
    'warning' => 'dhp-alert-warning',
    default => 'dhp-alert-info',
};
$icon = match ($type) {
    'success' => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    'error' => 'M12 9v3.75m0 3.75h.008v.008H12v-.008ZM21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    'warning' => 'M12 9v3.75m-.867 5.933.04.04a1.5 1.5 0 0 0 2.12 0l.04-.04m-3.2-3.2.04.04a1.5 1.5 0 0 0 2.12 0l.04-.04M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
    default => 'M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z',
};
@endphp

<div {{ $attributes->merge(['class' => $classes, 'role' => $type === 'error' ? 'alert' : 'status']) }}>
    <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
    </svg>
    <div class="min-w-0">
        @if($title)
            <p class="font-bold">{{ $title }}</p>
        @endif
        <div class="leading-relaxed">{{ $slot }}</div>
    </div>
</div>
