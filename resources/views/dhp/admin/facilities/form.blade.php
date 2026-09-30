<x-dhp.layout :title="$facility->exists ? 'Edit Facility' : 'New Facility'" :nav="[['label' => 'Administration', 'url' => route('dhp.admin.dashboard')], ['label' => 'Users', 'url' => route('dhp.admin.users.index')], ['label' => 'Facilities', 'url' => route('dhp.admin.facilities.index')], ['label' => 'Audit Log', 'url' => route('dhp.admin.audit-logs.index')]]">
    <div class="panel mx-auto max-w-xl">
        <div class="panel-header"><h1 class="panel-title">{{ $facility->exists ? 'Edit Facility' : 'New Facility' }}</h1></div>
        <div class="panel-body">
            <form method="POST" action="{{ $action }}" class="grid gap-4">
                @csrf
                @if ($method !== 'POST') @method($method) @endif
                <div><label class="label" for="name">Name *</label><input id="name" name="name" type="text" value="{{ old('name', $facility->name) }}" required class="input">@error('name')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="code">Code</label><input id="code" name="code" type="text" value="{{ old('code', $facility->code) }}" class="input">@error('code')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="district">District *</label>
                    <select id="district" name="district" required class="input"><option value="">—</option>@foreach ($districts as $d)<option value="{{ $d }}" @selected(old('district', $facility->district?->name) === $d)>{{ $d }}</option>@endforeach</select>
                    @error('district')<p class="field-error">{{ $message }}</p>@enderror</div>
                <div><label class="label" for="type">Type *</label>
                    <select id="type" name="type" required class="input">@foreach ($types as $type)<option value="{{ $type }}" @selected(old('type', $facility->type) === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>@endforeach</select>
                    @error('type')<p class="field-error">{{ $message }}</p>@enderror</div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $facility->is_active ?? true)) class="h-4 w-4 border-line text-brand-700"> Active</label>
                <div><button type="submit" class="btn-primary">{{ $facility->exists ? 'Save changes' : 'Create facility' }}</button></div>
            </form>
        </div>
    </div>
</x-dhp.layout>
