<x-dhp.layout title="Credential Certificate" :nav="[['label' => 'Search Citizen', 'url' => route('dhp.issuer.citizens.search')]]">
    <div class="panel mx-auto max-w-xl print:border-0 print:shadow-none">
        <div class="panel-header">
            <h1 class="panel-title">Digital Health Passport — Certificate</h1>
            <button type="button" onclick="window.print()" class="btn-secondary btn-sm no-print">Print Certificate</button>
        </div>
        <div class="panel-body">
            <div class="flex flex-wrap items-start justify-between gap-6">
                <dl class="detail-list min-w-0 flex-1">
                    <div><dt>Citizen name</dt><dd>{{ $credential->citizen->full_name }}</dd></div>
                    <div><dt>Passport ID</dt><dd class="mono">{{ $credential->citizen->passport_id }}</dd></div>
                    <div><dt>Credential number</dt><dd class="mono">{{ $credential->credential_number }}</dd></div>
                    <div><dt>Credential type</dt><dd>{{ $credential->type->label() }}</dd></div>
                    <div><dt>Issue date</dt><dd>{{ $credential->issue_date?->format('j M Y') }}</dd></div>
                    <div><dt>Expiry date</dt><dd>{{ $credential->expiry_date?->format('j M Y') ?? 'No expiry recorded' }}</dd></div>
                    <div><dt>Issuing facility</dt><dd>{{ $credential->facility?->name ?? '—' }}</dd></div>
                    <div><dt>Status at printing</dt><dd><x-dhp.status-badge :status="$credential->effective_status->value" /></dd></div>
                    <div><dt>Print date</dt><dd>{{ now()->format('j M Y') }}</dd></div>
                </dl>
                <div class="w-40 shrink-0 text-center">
                    <div class="[&>svg]:h-auto [&>svg]:w-full" role="img" aria-label="QR code for credential verification">{!! $qrCode !!}</div>
                    <p class="mono mt-1 break-all text-[11px] text-muted">{{ $verifyUrl }}</p>
                </div>
            </div>
            <p class="mt-4 text-[13px] text-muted">This certificate is one passport entry, not a complete medical record. Scan the code or open the address above to verify it. This certificate contains a QR code that can be checked for validity.</p>
        </div>
    </div>
</x-dhp.layout>
