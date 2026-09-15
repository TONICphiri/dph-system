<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">Verifier · Audit</p>
            <h2 class="text-2xl font-extrabold leading-tight">Verification history</h2>
            <p class="text-sm text-dhp-100">Every scan is recorded with holder NIN-hash reference only.</p>
        </div>
    </x-slot>
    <div class="dhp-table-wrap">
        <table class="dhp-table">
            <thead><tr><th>When</th><th>Credential</th><th>Result</th><th>Verifier</th><th>IP</th></tr></thead>
            <tbody>
                @forelse ($logs as $l)
                    <tr>
                        <td class="text-xs">{{ $l->scanned_at }}</td>
                        <td class="dhp-mono">#{{ $l->credential_id }}</td>
                        <td><span class="dhp-badge {{ $l->result === 'valid' ? 'badge-active' : 'badge-pending' }}">{{ ucfirst($l->result) }}</span></td>
                        <td class="text-xs">#{{ $l->verifier_user_id ?? 'public' }}</td>
                        <td class="text-xs">{{ $l->ip_address ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-slate-500">No verifications yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
</x-app-layout>
