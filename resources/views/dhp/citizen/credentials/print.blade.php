@php $isActive = $credential->effective_status->value === 'active'; @endphp
<x-dhp.layout title="Print Certificate" :nav="[['label' => 'My Passport', 'url' => route('dhp.citizen.dashboard')]]">
    <div class="panel mx-auto max-w-xl print:border-0 print:shadow-none">
        <div class="panel-header">
            <h1 class="panel-title">Digital Health Passport — Certificate</h1>
            <button type="button" onclick="window.print()" class="btn-secondary btn-sm no-print">Print Certificate</button>
        </div>
        <div class="panel-body">
            @if (! $isActive)
                <p class="mb-4 border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800" role="alert">
                    Warning: this credential is {{ $credential->effective_status->label() }} and must not be used for verification.
                </p>
            @endif
            <div class="flex flex-wrap items-start justify-between gap-6">
                <dl class="detail-list min-w-0 flex-1">
                    <div><dt>Citizen name</dt><dd>{{ $credential->citizen->full_name }}</dd></div>
                    <div><dt>Passport ID</dt><dd class="mono">{{ $credential->citizen->passport_id }}</dd></div>
                    <div><dt>Credential number</dt><dd class="mono">{{ $credential->credential_number }}</dd></div>
                    <div><dt>Credential type</dt><dd>{{ $credential->type->label() }}</dd></div>
                    <div><dt>Status</dt><dd>{{ $credential->effective_status->label() }}</dd></div>
                    <div><dt>Issue date</dt><dd>{{ $credential->issue_date?->format('j M Y') }}</dd></div>
                    <div><dt>Expiry date</dt><dd>{{ $credential->expiry_date?->format('j M Y') ?? 'No expiry recorded' }}</dd></div>
                    <div><dt>Issuing facility</dt><dd>{{ $credential->facility?->name ?? '—' }}</dd></div>
                    <div><dt>Print date</dt><dd>{{ now()->format('j M Y') }}</dd></div>
                </dl>
                @if ($isActive)
                    <div class="w-40 shrink-0 text-center">
                        <div class="[&>svg]:h-auto [&>svg]:w-full" role="img" aria-label="QR code for credential verification">{!! $qrCode !!}</div>
                        <p class="mt-1 text-[12px] text-muted">Present this QR code only when verification is required.</p>
                    </div>
                @endif
            </div>
            <p class="mt-4 text-[13px] text-muted">This certificate contains a QR code that can be checked for validity.</p>
        </div>
    </div>
</x-dhp.layout>
