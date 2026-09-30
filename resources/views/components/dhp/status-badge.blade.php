@props(['status' => 'active'])
@php
    // Single text+colour mapping for credential and verification states.
    // Text is never colour-only; superseded reads as Replaced everywhere.
    $map = [
        'active' => ['Active', 'success'],
        'expired' => ['Expired', 'warning'],
        'revoked' => ['Revoked', 'danger'],
        'superseded' => ['Replaced', 'neutral'],
        'valid' => ['Valid', 'success'],
        'invalid' => ['Invalid', 'danger'],
        'not_found' => ['Not Found', 'danger'],
    ];
    [$label, $tone] = $map[$status] ?? [ucfirst(str_replace('_', ' ', $status)), 'neutral'];
@endphp
<span {{ $attributes->merge(['class' => 'badge-'.$tone]) }}>{{ $label }}</span>
