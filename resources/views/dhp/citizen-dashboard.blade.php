<x-dhp.layout title="My Passport" :nav="[['label' => 'My Passport', 'url' => route('dhp.citizen.dashboard')]]">
    <div class="panel">
        <div class="panel-header">
            <h1 class="panel-title">My Passport</h1>
        </div>
        <div class="panel-body">
            @if ($citizen)
                <p class="text-sm font-medium">{{ $citizen->full_name }}</p>
                <p class="mt-0.5 text-sm text-muted">Passport ID: <span class="mono">{{ $citizen->passport_id }}</span></p>
                <p class="mt-3 text-sm text-muted">These are your verified health credentials. Show the QR code or print a certificate when requested by an authorized organization.</p>

                @if ($credentials->isEmpty())
                    <p class="mt-4 border border-line bg-paper px-4 py-3 text-sm">You have no credentials yet. Visit a participating health facility to receive your first credential.</p>
                @else
                    @foreach (['active', 'expired', 'revoked', 'superseded'] as $status)
                        @if ($grouped->has($status))
                            <h2 class="mt-6 text-sm font-semibold">{{ ucfirst($status) }}</h2>
                            <div class="mt-2 grid gap-4 sm:grid-cols-2">
                                @foreach ($grouped[$status] as $credential)
                                    <article class="border border-line bg-white p-4" aria-label="{{ $credential->type->label() }} credential">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <p class="text-sm font-semibold">{{ $credential->type->label() }} Credential</p>
                                            <x-badge :tone="$credential->effective_status->tone()">{{ $credential->effective_status->label() }}</x-badge>
                                        </div>
                                        <dl class="mt-2 space-y-1 text-[13px] text-muted">
                                            <div class="flex justify-between gap-2"><dt>Issued</dt><dd class="text-ink">{{ $credential->issue_date?->format('j M Y') }}</dd></div>
                                            <div class="flex justify-between gap-2"><dt>Expires</dt><dd class="text-ink">{{ $credential->expiry_date?->format('j M Y') ?? 'No expiry recorded' }}</dd></div>
                                            <div class="flex justify-between gap-2"><dt>Facility</dt><dd class="text-ink">{{ $credential->facility?->name ?? '—' }}</dd></div>
                                            <div class="flex justify-between gap-2"><dt>Number</dt><dd class="mono text-ink">{{ $credential->credential_number }}</dd></div>
                                        </dl>
                                        <div class="no-print mt-3 flex flex-wrap gap-2">
                                            <a href="{{ route('dhp.citizen.credentials.show', $credential) }}" class="btn-secondary btn-sm">View Credential</a>
                                            @if ($credential->effective_status->value === 'active')
                                                <button type="button" class="btn-secondary btn-sm" data-qr-toggle="qr-{{ $credential->id }}" aria-expanded="false" aria-controls="qr-{{ $credential->id }}">Show QR</button>
                                            @endif
                                            <a href="{{ route('dhp.citizen.credentials.print', $credential) }}" class="btn-secondary btn-sm">Print Certificate</a>
                                        </div>
                                        @if ($credential->effective_status->value === 'active')
                                            <div id="qr-{{ $credential->id }}" class="mt-3 hidden border border-line p-3 text-center">
                                                <div class="mx-auto w-36 [&>svg]:h-auto [&>svg]:w-full">{!! $qrCodes[$credential->id] !!}</div>
                                                <p class="mt-1 text-[12px] text-muted">Present this QR code only when verification is required.</p>
                                            </div>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                @endif
            @else
                <p class="text-sm">No passport profile is linked to this login yet. Ask a health worker to link your passport at a health facility.</p>
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
