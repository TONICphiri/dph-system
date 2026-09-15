@props(['value'])

<label {{ $attributes->merge(['class' => 'dhp-label']) }}>
    {{ $value ?? $slot }}
</label>
