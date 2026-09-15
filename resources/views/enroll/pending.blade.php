<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">Facility · Approvals queue</p>
            <h2 class="text-2xl font-extrabold leading-tight">Pending enrollments</h2>
            <p class="text-sm text-dhp-100">Approve or reject with reason. Every decision is audit-logged.</p>
        </div>
    </x-slot>

    <div class="dhp-table-wrap">
        <table class="dhp-table">
            <thead><tr><th>Masked NIN</th><th>Name</th><th>Contact</th><th>Enrolled by</th><th>Submitted</th><th>Linked file</th><th><span class="sr-only">Actions</span></th></tr></thead>
            <tbody>
                @forelse ($pending as $u)
                    <tr>
                        <td class="dhp-mono">{{ $u->masked_nin }}</td>
                        <td class="font-semibold">{{ $u->display_name }}</td>
                        <td class="text-xs text-slate-600">{{ $u->phone ?? '—' }}<br>{{ $u->email ?? '' }}</td>
                        <td class="text-xs">#{{ $u->enrolled_by ?? '—' }}</td>
                        <td class="text-xs text-slate-500">{{ $u->created_at?->format('d M Y H:i') }}</td>
                        <td class="text-xs">
                            @if ($u->patient_id)
                                <span class="dhp-mono">#{{ $u->patient_id }}</span>
                            @else
                                <form method="POST" action="{{ route('facility.users.link-file', $u) }}" class="flex gap-1">
                                    @csrf
                                    <input type="text" name="dhp_id" required maxlength="64" placeholder="DHP ID" class="dhp-input !min-h-[40px] !w-36 !px-2 !py-1 !text-xs" />
                                    <button class="btn-secondary !min-h-[40px] !px-2 !py-1 !text-xs">Link</button>
                                </form>
                            @endif
                        </td>
                        <td class="whitespace-nowrap">
                            <form method="POST" action="{{ route('facility.approvals.approve', $u) }}" class="inline">
                                @csrf
                                <button class="btn-success !min-h-[40px] !px-3 !py-1.5 !text-xs">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('facility.approvals.reject', $u) }}" class="inline">
                                @csrf
                                <input type="hidden" name="reason" value="" />
                                <button class="btn-danger !min-h-[40px] !px-3 !py-1.5 !text-xs" onclick="return confirm('Reject this enrollment?')">Reject</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-slate-500">No pending enrollments.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $pending->links() }}</div>
</x-app-layout>
