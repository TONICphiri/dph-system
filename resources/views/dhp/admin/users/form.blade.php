<x-dhp.layout :title="$user->exists ? 'Edit User' : 'New User'" :nav="[['label' => 'Administration', 'url' => route('dhp.admin.dashboard')], ['label' => 'Users', 'url' => route('dhp.admin.users.index')], ['label' => 'Facilities', 'url' => route('dhp.admin.facilities.index')], ['label' => 'Audit Log', 'url' => route('dhp.admin.audit-logs.index')], ['label' => 'Backups', 'url' => route('dhp.admin.backups.index')]]">
    <div class="panel mx-auto max-w-xl">
        <div class="panel-header"><h1 class="panel-title">{{ $user->exists ? 'Edit User' : 'New User' }}</h1></div>
        <div class="panel-body">
            @if (! $user->exists)
                <p class="mb-4 border border-line bg-paper px-4 py-3 text-[13px] text-muted">Issuer, verifier and administrator accounts need an email address so the holder can set a password through the normal reset process later. Citizen accounts are created only through citizen registration.</p>
            @endif
            <form method="POST" action="{{ $action }}" class="grid gap-4">
                @csrf
                @if ($method !== 'POST') @method($method) @endif
                <div><label class="label" for="name">Name *</label><input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required class="input">@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="email">Email {{ $user->exists ? '' : '*' }}</label><input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" @if(! $user->exists) required @endif class="input">@error('email')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="phone">Phone</label><input id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}" class="input">@error('phone')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="role">Role *</label>
                    <select id="role" name="role" required class="input">
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected(old('role', $user->role?->value) === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                    @error('role')<p class="field-error">{{ $message }}</p>@enderror</div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active ?? true)) class="h-4 w-4 border-line text-brand-700"> Active</label>
                <div><button type="submit" class="btn-primary">{{ $user->exists ? 'Save changes' : 'Create user' }}</button></div>
            </form>
        </div>
    </div>
</x-dhp.layout>
