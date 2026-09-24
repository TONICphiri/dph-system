@props(['name', 'label', 'type' => 'text', 'value' => null, 'help' => null, 'required' => false])
@php
    $key = str_replace(['[', ']'], ['.', ''], $name);
    $id = $attributes->get('id', str_replace('.', '_', $key));
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="label">{{ $label }}@if ($required)<span class="text-red-700"> *</span>@endif</label>
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' ? '' : old($key, $value) }}"
        @if ($required) required @endif
        {{ $attributes->except(['class', 'id'])->merge(['class' => 'input'.($errors->has($key) ? ' input-error' : '')]) }}
        @error($key) aria-invalid="true" aria-describedby="{{ $id }}_error" @enderror>
    @error($key)
        <p id="{{ $id }}_error" class="field-error">{{ $message }}</p>
    @elseif ($help)
        <p class="field-help">{{ $help }}</p>
    @enderror
</div>
