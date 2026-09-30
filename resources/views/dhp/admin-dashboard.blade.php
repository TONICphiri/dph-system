<x-dhp.layout title="Passport Administration" :nav="[['label' => 'Administration', 'url' => route('dhp.admin.dashboard')], ['label' => 'Users', 'url' => route('dhp.admin.users.index')], ['label' => 'Facilities', 'url' => route('dhp.admin.facilities.index')], ['label' => 'Audit Log', 'url' => route('dhp.admin.audit-logs.index')], ['label' => 'Backups', 'url' => route('dhp.admin.backups.index')]]">
    <div class="panel">
        <div class="panel-header"><h1 class="panel-title">Digital Health Passport Administration</h1></div>
        <div class="panel-body space-y-6">
            <section>
                <h2 class="text-sm font-semibold">Active users by role</h2>
                <dl class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach (['citizen' => 'Citizens', 'issuer' => 'Issuers', 'verifier' => 'Verifiers', 'admin' => 'Administrators'] as $role => $label)
                        <div class="border border-line px-4 py-3"><dt class="text-[12px] uppercase tracking-wide text-muted">{{ $label }}</dt><dd class="text-xl font-semibold">{{ $usersByRole[$role] ?? 0 }}</dd></div>
                    @endforeach
                </dl>
            </section>
            <section class="grid gap-3 sm:grid-cols-3">
                <div class="border border-line px-4 py-3"><p class="text-[12px] uppercase tracking-wide text-muted">Active facilities</p><p class="text-xl font-semibold">{{ $activeFacilities }}</p></div>
                <div class="border border-line px-4 py-3"><p class="text-[12px] uppercase tracking-wide text-muted">Verifications today</p><p class="text-xl font-semibold">{{ $verificationsToday }}</p></div>
                <div class="border border-line px-4 py-3"><p class="text-[12px] uppercase tracking-wide text-muted">Verifications, last 7 days</p><p class="text-xl font-semibold">{{ $verificationsWeek }}</p></div>
            </section>
            <section>
                <h2 class="text-sm font-semibold">Credentials</h2>
                <dl class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach (['active' => 'Active', 'expired' => 'Expired', 'revoked' => 'Revoked', 'superseded' => 'Superseded'] as $status => $label)
                        <div class="border border-line px-4 py-3"><dt class="text-[12px] uppercase tracking-wide text-muted">{{ $label }}</dt><dd class="text-xl font-semibold">{{ $credentialCounts[$status] ?? 0 }}</dd></div>
                    @endforeach
                </dl>
            </section>
            <section class="border border-line bg-paper px-4 py-3 text-sm">
                <p>Credentials expiring within 7 days: <strong>{{ $expiringSoon }}</strong></p>
                <p class="mt-1 text-muted">Last expiry job: {{ $lastExpiryJob?->created_at?->format('j M Y H:i') ?? 'never run' }}</p>
            </section>
            <section>
                <h2 class="text-sm font-semibold">Recent activity</h2>
                <ul class="mt-2 divide-y divide-line border border-line text-sm">
                    @forelse ($recentAudits as $log)
                        <li class="flex flex-wrap justify-between gap-2 px-4 py-2">
                            <span>{{ $log->created_at?->format('j M Y H:i') }} · {{ $log->action }} · {{ $log->user?->name ?? 'System' }}</span>
                            <span class="mono text-muted">{{ $log->entity_type ?? '—' }}</span>
                        </li>
                    @empty
                        <li class="px-4 py-2 text-muted">No activity yet.</li>
                    @endforelse
                </ul>
            </section>
        </div>
    </div>
</x-dhp.layout>
