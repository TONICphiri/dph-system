<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">National administration · Security</p>
            <h2 class="text-2xl font-extrabold leading-tight">Audit Logs</h2>
            <p class="text-sm text-dhp-100">Every action, user and timestamp — nationwide.</p>
        </div>
    </x-slot>

    @if($auditLogs->count())
        <div class="dhp-table-wrap">
            <table class="dhp-table">
                <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Subject</th><th>Description</th></tr></thead>
                <tbody>
                    @foreach($auditLogs as $log)
                        <tr>
                            <td class="whitespace-nowrap text-xs text-slate-500">{{ $log->created_at->format('d M Y, H:i') }}</td>
                            <td class="font-semibold">{{ $log->user?->name ?? 'System' }}</td>
                            <td><span class="dhp-badge badge-neutral">{{ $log->action }}</span></td>
                            <td class="dhp-mono">{{ class_basename($log->subject_type) }} #{{ $log->subject_id ?? '—' }}</td>
                            <td class="max-w-md">{{ $log->description ?? 'No description provided.' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($auditLogs->hasPages())
            <div class="mt-4">{{ $auditLogs->links() }}</div>
        @endif
    @else
        <div class="dhp-empty">
            <p class="font-bold text-dhp-900">No audit entries yet</p>
            <p class="text-sm text-slate-500">Clinical activity will appear here automatically.</p>
        </div>
    @endif
</x-app-layout>
