<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <p class="text-xs font-bold uppercase tracking-widest text-dhp-200">National administration · Staff</p>
            <h2 class="text-2xl font-extrabold leading-tight">Edit {{ $user->name }}</h2>
            <p class="text-sm text-dhp-100">{{ $user->email }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-3xl">
        <div class="dhp-card dhp-card-pad">
            <form method="POST" action="{{ route('users.update', $user) }}" class="space-y-5" novalidate>
                @csrf
                @method('PUT')
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="dhp-label">Full name <span class="text-rose-600" aria-hidden="true">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" class="dhp-input" />
                        @error('name')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="dhp-label">Email <span class="text-rose-600" aria-hidden="true">*</span></label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" class="dhp-input" />
                        @error('email')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password" class="dhp-label">New password</label>
                        <input type="password" id="password" name="password" autocomplete="new-password" placeholder="Leave blank to keep current" class="dhp-input" />
                        @error('password')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="password_confirmation" class="dhp-label">Confirm new password</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" class="dhp-input" />
                    </div>
                    <div>
                        <label for="facility_id" class="dhp-label">Facility <span class="text-rose-600" aria-hidden="true">*</span></label>
                        <select id="facility_id" name="facility_id" required class="dhp-select">
                            @foreach($facilities as $facility)
                                <option value="{{ $facility->id }}" @selected(old('facility_id', $user->facility_id) == $facility->id)>{{ $facility->name }}</option>
                            @endforeach
                        </select>
                        @error('facility_id')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="role" class="dhp-label">Role <span class="text-rose-600" aria-hidden="true">*</span></label>
                        <select id="role" name="role" required class="dhp-select">
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" @selected(old('role', $user->roles->first()?->name) == $role->name)>{{ $role->name }}</option>
                            @endforeach
                        </select>
                        @error('role')<p class="dhp-field-error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div>
                    <label for="status" class="dhp-label">Status</label>
                    <select id="status" name="status" class="dhp-select">
                        <option value="active" @selected(old('status', $user->status ?? 'active') === 'active')>Active</option>
                        <option value="inactive" @selected(old('status', $user->status ?? 'active') === 'inactive')>Inactive</option>
                    </select>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row">
                    <button type="submit" class="btn-primary flex-1">Update user</button>
                    <a href="{{ route('users.index') }}" class="btn-secondary flex-1">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
