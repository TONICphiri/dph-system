<x-dhp.layout title="Facilities" :nav="[['label' => 'Administration', 'url' => route('dhp.admin.dashboard')], ['label' => 'Users', 'url' => route('dhp.admin.users.index')], ['label' => 'Facilities', 'url' => route('dhp.admin.facilities.index')], ['label' => 'Audit Log', 'url' => route('dhp.admin.audit-logs.index')]]">
    <div class="panel">
        <div class="panel-header">
            <h1 class="panel-title">Facilities</h1>
            <a href="{{ route('dhp.admin.facilities.create') }}" class="btn-primary btn-sm no-print">New facility</a>
        </div>
        <div class="panel-body">
            <form method="GET" action="{{ route('dhp.admin.facilities.index') }}" class="flex flex-wrap gap-2">
                <input name="search" type="text" value="{{ $filters['search'] ?? '' }}" placeholder="Name or district" class="input max-w-xs">
                <select name="type" class="input max-w-[200px]"><option value="">All types</option>@foreach ($types as $type)<option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>@endforeach</select>
                <select name="status" class="input max-w-[160px]"><option value="">Any status</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option><option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option></select>
                <button type="submit" class="btn-secondary btn-sm">Filter</button>
            </form>
            <div class="mt-4 overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Name</th><th>District</th><th>Type</th><th>Status</th><th>Created</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($facilities as $facility)
                            <tr>
                                <td>{{ $facility->name }}</td>
                                <td>{{ $facility->district?->name ?? '—' }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $facility->type)) }}</td>
                                <td><x-badge :tone="$facility->is_active ? 'success' : 'neutral'">{{ $facility->is_active ? 'Active' : 'Inactive' }}</x-badge></td>
                                <td>{{ $facility->created_at?->format('j M Y') }}</td>
                                <td class="whitespace-nowrap">
                                    <a href="{{ route('dhp.admin.facilities.edit', $facility) }}" class="link text-[13px]">Edit</a>
                                    <form method="POST" action="{{ route('dhp.admin.facilities.toggle-active', $facility) }}" class="inline js-confirm" data-confirm="Change the active status of this facility?">
                                        @csrf
                                        <button type="submit" class="link ml-2 text-[13px]">{{ $facility->is_active ? 'Deactivate' : 'Activate' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $facilities->links() }}</div>
        </div>
    </div>
    <script>
        document.querySelectorAll('form.js-confirm').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (!confirm(form.dataset.confirm || 'Are you sure?')) e.preventDefault();
            });
        });
    </script>
</x-dhp.layout>
