@php use App\Http\Controllers\Dhp\Admin\AuditLogController; @endphp
<x-dhp.layout title="Audit Log" :nav="[['label' => 'Administration', 'url' => route('dhp.admin.dashboard')], ['label' => 'Users', 'url' => route('dhp.admin.users.index')], ['label' => 'Facilities', 'url' => route('dhp.admin.facilities.index')], ['label' => 'Audit Log', 'url' => route('dhp.admin.audit-logs.index')], ['label' => 'Backups', 'url' => route('dhp.admin.backups.index')]]">
    <div class="panel">
        <div class="panel-header"><h1 class="panel-title">Audit Log</h1></div>
        <div class="panel-body">
            <form method="GET" action="{{ route('dhp.admin.audit-logs.index') }}" class="grid gap-2 sm:grid-cols-5">
                <select name="action" class="input"><option value="">All actions</option>@foreach ($actions as $action)<option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $action }}</option>@endforeach</select>
                <select name="user_id" class="input"><option value="">All actors</option><option value="" disabled>—</option>@foreach ($users as $user)<option value="{{ $user->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $user->id)>{{ $user->name }}</option>@endforeach</select>
                <input name="entity_type" type="text" value="{{ $filters['entity_type'] ?? '' }}" placeholder="Entity type" class="input">
                <input name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="input">
                <input name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="input">
                <div class="sm:col-span-5"><button type="submit" class="btn-secondary btn-sm">Filter</button></div>
            </form>
            <div class="mt-4 overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Time</th><th>Action</th><th>Actor</th><th>Entity</th><th>Summary</th><th>IP</th></tr></thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="whitespace-nowrap">{{ $log->created_at?->format('j M Y H:i') }}</td>
                                <td class="mono">{{ $log->action }}</td>
                                <td>{{ $log->user?->name ?? 'System' }}</td>
                                <td class="mono">{{ $log->entity_type ?? '—' }}</td>
                                <td>{{ AuditLogController::summary($log->details) }}@if (AuditLogController::hasHiddenKeys($log->details)) <span class="text-muted">(Additional protected metadata recorded.)</span>@endif</td>
                                <td class="mono">{{ AuditLogController::maskIp($log->ip_address) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($logs->isEmpty())
                <p class="mt-2 text-sm text-muted">No audit activity matched your filters.</p>
            @endif
            <div class="mt-4">{{ $logs->links() }}</div>
        </div>
    </div>
</x-dhp.layout>
