@php
    use App\Enums\VerificationResult;

    $messages = [
        'valid' => 'This credential is active and was issued by an authorized facility.',
        'expired' => 'This credential has expired and may no longer be accepted.',
        'revoked' => 'This credential has been revoked and must not be used.',
        'superseded' => 'This credential has been replaced and must not be used.',
        'invalid' => 'This credential could not be verified.',
        'not_found' => 'This credential could not be verified.',
    ];
    $key = $result->value;
@endphp
<x-dhp.layout title="Verification Result" :nav="[['label' => 'Verify Certificate', 'url' => route('dhp.verifier.verify')]]">
    <div class="panel">
        <div class="panel-header">
            <h1 class="panel-title">Verification Result</h1>
            <x-dhp.status-badge :status="$result->value" />
        </div>
        <div class="panel-body space-y-3">
            <p class="text-sm">{{ $messages[$key] }}</p>

            @if ($result === VerificationResult::Valid)
                <dl class="detail-list">
                    <div><dt>Citizen name</dt><dd>{{ $credential->citizen->full_name }}</dd></div>
                    <div><dt>Passport ID</dt><dd class="mono">{{ $credential->citizen->passport_id }}</dd></div>
                    <div><dt>Credential type</dt><dd>{{ $credential->type->label() }}</dd></div>
                    <div><dt>Issue date</dt><dd>{{ $credential->issue_date?->format('j M Y') }}</dd></div>
                    <div><dt>Expiry date</dt><dd>{{ $credential->expiry_date?->format('j M Y') ?? 'No expiry recorded' }}</dd></div>
                    <div><dt>Issuing facility</dt><dd>{{ $credential->facility?->name }}</dd></div>
                    <div><dt>Verified at</dt><dd>{{ $verifiedAt->format('j M Y H:i') }}</dd></div>
                </dl>
            @else
                <p class="text-sm text-muted">Credential type: {{ $credential?->type?->label() ?? '—' }}</p>
            @endif

            <p class="text-[13px] text-muted">Verification results are based on the credential status and issuing facility at the time of verification.</p>
            <p><a href="{{ route('dhp.verifier.verify') }}" class="link text-sm">Verify another credential</a></p>
        </div>
    </div>
</x-dhp.layout>
