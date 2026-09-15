<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">National administration · Staff</p>
            <h2 class="text-2xl font-extrabold leading-tight">User management</h2>
            <p class="text-sm text-dhp-100">Create and manage accounts across all facilities.</p>
        </div>
    </x-slot>

    <div class="mb-4 flex justify-end">
        <a href="{{ route('users.create') }}" class="btn-primary">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Add user
        </a>
    </div>

    @if($users->count())
        <div class="dhp-table-wrap">
            <table class="dhp-table">
                <thead><tr><th>Name</th><th>Email</th><th>Facility</th><th>Role</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td class="font-semibold">{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->facility?->name ?? '—' }}</td>
                            <td>{{ $user->roles->first()?->name ?? 'No role' }}</td>
                            <td><span class="dhp-badge {{ $user->status === 'active' ? 'badge-active' : 'badge-neutral' }}">{{ $user->status ?? 'active' }}</span></td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('users.edit', $user) }}" class="btn-warning !min-h-[40px] !px-3 !py-1.5 !text-xs">Edit</a>
                                <form action="{{ route('users.toggle-status', $user) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="btn-secondary !min-h-[40px] !px-3 !py-1.5 !text-xs">{{ $user->status === 'active' ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                                <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Delete user {{ addslashes($user->name) }}? This cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger !min-h-[40px] !px-3 !py-1.5 !text-xs">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $users->links() }}</div>
    @else
        <div class="dhp-empty">
            <p class="font-bold text-dhp-900">No users found</p>
            <a href="{{ route('users.create') }}" class="btn-primary mt-3">Add the first user</a>
        </div>
    @endif
</x-app-layout>
