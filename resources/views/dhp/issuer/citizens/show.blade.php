<x-dhp.layout title="Citizen Passport" :nav="[['label' => 'Search Citizen', 'url' => route('dhp.issuer.citizens.search')], ['label' => 'Register Citizen', 'url' => route('dhp.issuer.citizens.create')]]">
    <div class="panel">
        <div class="panel-header">
            <h1 class="panel-title">{{ $citizen->full_name }}</h1>
            <div class="no-print flex flex-wrap gap-2">
                <a href="{{ route('dhp.issuer.credentials.create', $citizen) }}" class="btn-primary btn-sm">Issue Credential</a>
                <a href="{{ route('dhp.issuer.citizens.registration-slip', $citizen) }}" class="btn-secondary btn-sm">Print Registration Slip</a>
            </div>
        </div>
        <div class="panel-body">
            <dl class="detail-list">
                <div><dt>Passport ID</dt><dd class="mono">{{ $citizen->passport_id }}</dd></div>
                <div><dt>National ID</dt><dd class="mono">{{ $citizen->maskedNationalId() ?? 'Not recorded' }}</dd></div>
                <div><dt>Sex</dt><dd class="capitalize">{{ $citizen->sex }}</dd></div>
                <div><dt>Date of birth</dt><dd>{{ $citizen->date_of_birth?->format('j M Y') }}</dd></div>
                <div><dt>District</dt><dd>{{ $citizen->district }}</dd></div>
                <div><dt>Village</dt><dd>{{ $citizen->village ?? '—' }}</dd></div>
                <div><dt>Portal account</dt><dd>{{ $citizen->user_id ? 'Enabled' : 'Not enabled' }}</dd></div>
            </dl>

            <h2 class="mt-6 text-sm font-semibold">Credentials ({{ $citizen->credentials->count() }})</h2>
            @if ($citizen->credentials->isEmpty())
                <p class="mt-2 text-sm text-muted">No credentials issued yet.</p>
            @else
                @foreach (['active', 'expired', 'revoked', 'superseded'] as $status)
                    @if ($grouped->has($status))
                        <h3 class="mt-4 text-[13px] font-semibold uppercase tracking-wide text-muted">{{ $status === 'superseded' ? 'Replaced' : $status }}</h3>
                        <ul class="mt-1 divide-y divide-line border border-line">
                            @foreach ($grouped[$status] as $credential)
                                <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 text-sm">
                                    <span><span class="mono">{{ $credential->credential_number }}</span> · {{ $credential->type->label() }} · issued {{ $credential->issue_date?->format('j M Y') }}@if ($credential->expiry_date) · expires {{ $credential->expiry_date->format('j M Y') }}@endif</span>
                                    <span class="no-print flex gap-2">
                                        <a href="{{ route('dhp.issuer.credentials.print', $credential) }}" class="link text-[13px]">Print Certificate</a>
                                        @if ($credential->status->value === 'active')
                                            <a href="{{ route('dhp.issuer.credentials.edit', $credential) }}" class="link text-[13px]">Edit</a>
                                            <a href="{{ route('dhp.issuer.credentials.revoke-form', $credential) }}" class="link text-[13px]">Revoke / Replace</a>
                                        @endif
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @endforeach
            @endif
        </div>
    </div>
</x-dhp.layout>
