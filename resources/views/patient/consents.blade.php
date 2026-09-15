<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">Patient · Consent</p>
            <h2 class="text-2xl font-extrabold leading-tight">Who can view my record</h2>
            <p class="text-sm text-dhp-100">Practitioners see your record only after you grant consent. Every grant is logged.</p>
        </div>
    </x-slot>
    <form method="POST" action="{{ route('patient.consents.store') }}" class="dhp-card dhp-card-pad mb-6 max-w-xl">
        @csrf
        <label class="dhp-label" for="grantee_nin">Practitioner NIN</label>
        <input id="grantee_nin" name="grantee_nin" required class="dhp-input" placeholder="Practitioner National ID Number" />
        <button class="btn-primary mt-4">Grant access</button>
    </form>
    <div class="dhp-table-wrap max-w-3xl">
        <table class="dhp-table">
            <thead><tr><th>Practitioner</th><th>Scope</th><th>Granted</th><th><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
                @forelse ($grants as $g)
                    <tr>
                        <td class="dhp-mono">#{{ $g->grantee_user_id }}</td>
                        <td>{{ $g->scope }}</td>
                        <td class="text-xs">{{ $g->granted_at }}{{ $g->revoked_at ? ' · revoked' : '' }}</td>
                        <td>
                            @if (!$g->revoked_at)
                                <form method="POST" action="{{ route('patient.consents.revoke', $g->id) }}">@csrf<button class="btn-danger !min-h-[36px] !px-3 !py-1 !text-xs">Revoke</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center text-slate-500">No grants yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $grants->links() }}</div>
</x-app-layout>
