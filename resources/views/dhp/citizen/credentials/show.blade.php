@php
    $statusMessages = [
        'active' => 'This credential is active and can be presented for verification.',
        'expired' => 'This credential has expired and may no longer be accepted.',
        'revoked' => 'This credential has been revoked and must not be used.',
        'superseded' => 'This credential has been replaced by a newer credential and must not be used.',
    ];
    $isActive = $credential->effective_status->value === 'active';
@endphp
<x-dhp.layout title="Credential" :nav="[['label' => 'My Passport', 'url' => route('dhp.citizen.dashboard')]]">
    <div class="panel mx-auto max-w-xl">
        <div class="panel-header">
            <h1 class="panel-title">{{ $credential->type->label() }} Credential</h1>
            <x-badge :tone="$credential->effective_status->tone()">{{ $credential->effective_status->label() }}</x-badge>
        </div>
        <div class="panel-body">
            <p class="border px-4 py-3 text-sm {{ $isActive ? 'border-brand-200 bg-brand-50 text-brand-800' : 'border-red-200 bg-red-50 text-red-800' }}">{{ $statusMessages[$credential->effective_status->value] }}</p>
            <dl class="detail-list mt-4">
                <div><dt>Credential number</dt><dd class="mono">{{ $credential->credential_number }}</dd></div>
                <div><dt>Status</dt><dd>{{ $credential->effective_status->label() }}</dd></div>
                <div><dt>Issue date</dt><dd>{{ $credential->issue_date?->format('j M Y') }}</dd></div>
                <div><dt>Expiry date</dt><dd>{{ $credential->expiry_date?->format('j M Y') ?? 'No expiry recorded' }}</dd></div>
                <div><dt>Issuing facility</dt><dd>{{ $credential->facility?->name ?? '—' }}</dd></div>
            </dl>

            @if ($credential->type->value === 'vaccination' && $credential->vaccinationDetail)
                <h2 class="mt-4 text-sm font-semibold">Vaccination</h2>
                <dl class="detail-list mt-2">
                    <div><dt>Vaccine</dt><dd>{{ $credential->vaccinationDetail->vaccine_name }}</dd></div>
                    <div><dt>Dose number</dt><dd>{{ $credential->vaccinationDetail->dose_number }}</dd></div>
                    <div><dt>Administration date</dt><dd>{{ $credential->vaccinationDetail->administration_date?->format('j M Y') }}</dd></div>
                </dl>
            @elseif ($credential->type->value === 'lab_test')
                <p class="mt-4 border border-line bg-paper px-4 py-3 text-sm">Laboratory credential issued</p>
            @endif

            <div class="no-print mt-4 flex flex-wrap gap-2">
                @if ($isActive)
                    <button type="button" class="btn-secondary btn-sm" data-qr-toggle="qr-detail" aria-expanded="false" aria-controls="qr-detail">Show QR</button>
                @endif
                <a href="{{ route('dhp.citizen.credentials.print', $credential) }}" class="btn-primary btn-sm">Print Certificate</a>
            </div>
            @if ($isActive)
                <div id="qr-detail" class="mt-3 hidden border border-line p-3 text-center">
                    <div class="mx-auto w-36 [&>svg]:h-auto [&>svg]:w-full">{!! $qrCode !!}</div>
                    <p class="mt-1 text-[12px] text-muted">Present this QR code only when verification is required.</p>
                </div>
            @endif
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-qr-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var panel = document.getElementById(btn.dataset.qrToggle);
                if (!panel) return;
                var hidden = panel.classList.toggle('hidden');
                btn.setAttribute('aria-expanded', hidden ? 'false' : 'true');
                btn.textContent = hidden ? 'Show QR' : 'Hide QR';
            });
        });
    </script>
</x-dhp.layout>
