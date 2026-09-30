<x-dhp.layout title="DHP Users" :nav="[['label' => 'Administration', 'url' => route('dhp.admin.dashboard')], ['label' => 'Users', 'url' => route('dhp.admin.users.index')], ['label' => 'Facilities', 'url' => route('dhp.admin.facilities.index')], ['label' => 'Audit Log', 'url' => route('dhp.admin.audit-logs.index')], ['label' => 'Backups', 'url' => route('dhp.admin.backups.index')]]">
    <div class="panel">
        <div class="panel-header">
            <h1 class="panel-title">Users</h1>
            <a href="{{ route('dhp.admin.users.create') }}" class="btn-primary btn-sm no-print">New user</a>
        </div>
        <div class="panel-body">
            <form method="GET" action="{{ route('dhp.admin.users.index') }}" class="flex flex-wrap gap-2">
                <input name="search" type="text" value="{{ $search }}" placeholder="Name, email or phone" class="input max-w-xs">
                <button type="submit" class="btn-secondary btn-sm">Search</button>
            </form>
            <div class="mt-4 overflow-x-auto">
                <table class="table">
                    <thead><tr><th>Name</th><th>Email / Phone</th><th>Role</th><th>Status</th><th>Created</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email ?? $user->phone ?? '—' }}</td>
                                <td>{{ $user->role?->label() ?? '—' }}</td>
                                <td><x-badge :tone="$user->is_active ? 'success' : 'neutral'">{{ $user->is_active ? 'Active' : 'Inactive' }}</x-badge></td>
                                <td>{{ $user->created_at?->format('j M Y') }}</td>
                                <td class="whitespace-nowrap">
                                    <a href="{{ route('dhp.admin.users.edit', $user) }}" class="link text-[13px]">Edit</a>
                                    <form method="POST" action="{{ route('dhp.admin.users.toggle-active', $user) }}" class="inline js-confirm" data-confirm="Change the active status of this user?">
                                        @csrf
                                        <button type="submit" class="link ml-2 text-[13px]">{{ $user->is_active ? 'Deactivate' : 'Activate' }}</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $users->links() }}</div>
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
