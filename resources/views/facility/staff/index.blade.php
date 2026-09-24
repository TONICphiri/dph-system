<x-layouts.app title="Health workers">
    <x-page-header title="Health workers" description="Clerks, nurses, doctors and pharmacists at your facility. Each role sees only the information it needs.">
        <x-slot:actions><a href="{{ route('facility.staff.create') }}" class="btn-primary"><x-icon name="user-plus" class="h-4 w-4" /> Register health worker</a></x-slot:actions>
    </x-page-header>

    <section class="panel">
        <x-search-bar placeholder="Name or email address">
            <select name="role" class="input md:w-48" aria-label="Role">
                <option value="">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
                @endforeach
            </select>
        </x-search-bar>
        @include('partials.account-table', ['users' => $staff, 'showFacility' => false, 'editRoute' => 'facility.staff.edit'])
    </section>
</x-layouts.app>
